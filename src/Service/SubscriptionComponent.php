<?php
namespace byTorsten\GraphQL\Subscriptions\Service;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Language\Parser;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise;
use byTorsten\GraphQL\Subscriptions\Iterator\AsyncIterator;
use byTorsten\GraphQL\Subscriptions\Iterator\AsyncIteratorInterface;
use byTorsten\GraphQL\Subscriptions\Iterator\EmptyIterable;
use byTorsten\GraphQL\Subscriptions\Iterator\ReturnableAsyncIteratorInterface;
use byTorsten\GraphQL\Subscriptions\Subscription;
use byTorsten\GraphQL\Subscriptions\SubscriptionOptions;
use byTorsten\GraphQL\Subscriptions\Domain\MessageTypes;
use byTorsten\GraphQL\Subscriptions\Domain\Model\ConnectionContext;
use byTorsten\GraphQL\Subscriptions\Domain\Model\ExecutionParameters;
use byTorsten\GraphQL\Subscriptions\Domain\Protocol;
use byTorsten\GraphQL\Subscriptions\Exception;

class SubscriptionComponent implements MessageComponentInterface, WsServerInterface
{

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var ContextCollection
     */
    protected $contextCollection;

    /**
     * @var SubscriptionOptions
     */
    protected $subscriptionOptions;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @param LoopInterface $loop
     * @param Schema $schema
     * @param SubscriptionOptions $subscriptionOptions
     */
    public function __construct(LoopInterface $loop, Schema $schema, SubscriptionOptions $subscriptionOptions)
    {
        $this->loop = $loop;
        $this->schema = $schema;
        $this->contextCollection = new ContextCollection();
        $this->subscriptionOptions = $subscriptionOptions;
        $this->modifySchemaForSubscriptions();
    }


    /**
     * @throws Exception
     */
    public function modifySchemaForSubscriptions()
    {
        /** @var ObjectType $subscriptionType */
        $subscriptionType = $this->schema->getType('Subscription');

        foreach ($subscriptionType->getFields() as $field) {
            $resolveFn = $field->resolveFn;
            if ($resolveFn === null) {
                throw new Exception(sprintf('Subscription.%s needs a resolve function that has to return an instance of byTorsten\GraphQL\Subscriptions\Subscription', $field->name));
            }

            try {
                $subscription = $resolveFn();
                if (!($subscription instanceof Subscription)) {
                    throw new Exception(sprintf('the resolver function of Subscription.%s must return an instance of byTorsten\GraphQL\Subscriptions\Subscription', $field->name));
                }
            } catch (Exception $exception) {
                throw $exception;
            } catch (\Throwable $throwable) {
                throw new Exception(sprintf('the resolver function of Subscription.%s must not have any arguments and has to return an instance of byTorsten\GraphQL\Subscriptions\Subscription', $field->name));
            }

            $field->config['subscribe'] = $subscription->getSubscribe();
            $field->resolveFn = $subscription->getResolve();
        }
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $this->contextCollection->attach($connection);
    }

    /**
     * @param  ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection)
    {
        $this->onConnectionClose($this->contextCollection->get($connection));
    }


    /**
     * @param  \Ratchet\ConnectionInterface $connection
     * @param  string $message
     */
    public function onMessage(ConnectionInterface $connection, $message)
    {
        $connectionContext = $this->contextCollection->get($connection);

        try {
            $parsedMessage = json_decode($message, true);
        } catch (\Throwable $throwable) {
            $this->sendError($connectionContext, null, ['message' => $throwable->getMessage()], MessageTypes::GQL_CONNECTION_ERROR);
            return;
        }

        $opId = $parsedMessage['id'] ?? null;

        switch ($parsedMessage['type']) {
            case MessageTypes::GQL_CONNECTION_INIT:
                $connectionContext->setInitPromise(new Promise\Promise(function (callable $resolve, callable $reject) use ($parsedMessage, $connectionContext) {
                    try {
                        $resolve($this->subscriptionOptions->onConnect($parsedMessage['payload'], $connectionContext->getSocket(), $connectionContext));
                    } catch (\Throwable $throwable) {
                        $reject($throwable);
                    }
                }));

                $connectionContext->getInitPromise()->then(function ($result) use ($connectionContext) {

                    if ($result === false) {
                        throw new Exception('Prohibited connection!');
                    }

                    $this->sendMessage($connectionContext, null, MessageTypes::GQL_CONNECTION_ACK, null);

                    if ($this->subscriptionOptions->hasKeepAlive()) {
                        $this->sendKeepAlive($connectionContext);

                        $this->loop->addPeriodicTimer($this->subscriptionOptions->getKeepAlive(), function (TimerInterface $timer) use ($connectionContext) {
                            if ($connectionContext->getSocket() !== null) {
                                $this->sendKeepAlive($connectionContext);
                            } else {
                                $this->loop->cancelTimer($timer);
                            }
                        });
                    }
                });

                $connectionContext->getInitPromise()->otherwise(function (\Throwable $error) use ($connectionContext, $opId) {
                    $this->sendError($connectionContext, $opId, ['message' => $error->getMessage()], MessageTypes::GQL_CONNECTION_ERROR);

                    $this->loop->addTimer(10 / 1000, function () use ($connectionContext) {
                        $connectionContext->getSocket()->close(1011);
                        $this->contextCollection->detach($connectionContext);
                    });
                });

                break;

            case MessageTypes::GQL_CONNECTION_TERMINATE:
                $connectionContext->getSocket()->close(1011);
                $this->contextCollection->detach($connectionContext);
                break;

            case MessageTypes::GQL_START:
                $connectionContext->getInitPromise()->then(function ($initResult) use ($connectionContext, $opId, $parsedMessage) {
                    if ($connectionContext->hasOperation($opId)) {
                        $this->unsubscribe($connectionContext, $opId);
                    }

                    $baseParams = new ExecutionParameters();
                    $baseParams->setQuery($parsedMessage['payload']['query']);
                    $baseParams->setVariables($parsedMessage['payload']['variables']);
                    $baseParams->setOperationName($parsedMessage['payload']['operationName']);
                    $baseParams->setContext($initResult ?? []);
                    $baseParams->setSchema($this->schema);

                    $connectionContext->addOperation($opId, new EmptyIterable());

                    $promisedParams = Promise\resolve($this->subscriptionOptions->onOperation($parsedMessage, $baseParams, $connectionContext->getSocket()));
                    $promisedParams
                        ->then(function (ExecutionParameters $params) {
                            $document = is_string($params->getQuery()) === false ? $params->getQuery() : Parser::parse($params->getQuery());
                            $validationErrors = DocumentValidator::validate($params->getSchema(), $document, $this->subscriptionOptions->getValidationRules());

                            if (count($validationErrors) > 0) {
                                $executionPromise = Promise\resolve(['errors' => $validationErrors]);
                            } else {
                                $executor = [$this->subscriptionOptions, 'execute'];
                                if (AST::getOperation($document, $params->getOperationName()) === 'subscription') {
                                    $executor = [$this->subscriptionOptions, 'subscribe'];
                                }

                                $executionPromise = Promise\resolve(
                                    call_user_func_array($executor, [
                                        $params->getSchema(),
                                        $document,
                                        $this->subscriptionOptions->getRootValue(),
                                        $params->getContext(),
                                        $params->getVariables(),
                                        $params->getOperationName()
                                    ])
                                );
                            }

                            return $executionPromise->then(function ($executionResult) use ($params) {
                                return [
                                    'executionIterable' => $executionResult instanceof AsyncIteratorInterface ? $executionResult : new AsyncIterator([$executionResult]),
                                    'params' => $params
                                ];
                            });
                        })
                        ->then(function ($result) use ($connectionContext, $opId) {

                            /** @var ExecutionParameters $params */
                            $params = $result['params'];
                            $executionIterable = $result['executionIterable'];

                            AsyncIterator::forAwaitEach($executionIterable, function ($result) use ($params, $connectionContext, $opId) {
                                $formatResponse = $params->getFormatResponse();

                                if ($formatResponse) {
                                    $result = $formatResponse($result);
                                }

                                $this->sendMessage($connectionContext, $opId, MessageTypes::GQL_DATA, $result);
                            })->done(function () use ($connectionContext, $opId) {
                                $this->sendMessage($connectionContext, $opId, MessageTypes::GQL_COMPLETE, null);
                            }, function (\Throwable $error) use ($connectionContext, $params, $opId) {
                                $formatError = $params->getFormatError();

                                if ($formatError) {
                                    $error = $formatError($error);
                                }

                                if ($error instanceof \Throwable) {
                                    $error = ['message' => $error->getMessage()];
                                }

                                $this->sendError($connectionContext, $opId, $error);
                            });

                            return $executionIterable;
                        })
                        ->then(function ($executionIterable) use ($connectionContext, $opId) {
                            $connectionContext->addOperation($opId, $executionIterable);
                        })
                        ->otherwise(function ($error) use ($connectionContext, $opId) {
                            if (is_array($error) && isset($error['errors'])) {
                                $this->sendMessage($connectionContext, $opId, MessageTypes::GQL_DATA, [ 'errors' => $error['errors']]);
                            } else if ($error instanceof \Throwable) {
                                $this->sendError($connectionContext, $opId, ['message' => $error->getMessage()]);
                            }

                            $this->unsubscribe($connectionContext, $opId);
                        })
                        ->done();

                    return $promisedParams;
                }, function (\Throwable $error) use ($connectionContext, $opId) {
                    $this->sendError($connectionContext, $opId, ['message' => $error->getMessage()]);
                    $this->unsubscribe($connectionContext, $opId);
                });
                break;

            case MessageTypes::GQL_STOP:
                $this->unsubscribe($connectionContext, $opId);
                break;

            default:
                $this->sendError($connectionContext, $opId, ['message' => 'Invalid message type!']);
        }
    }
    /**
     * @param  ConnectionInterface $connection
     * @param  \Exception $error
     */
    public function onError(ConnectionInterface $connection, \Exception $error)
    {
        $this->onConnectionClose($this->contextCollection->get($connection), $error);
    }

    /**
     * @param ConnectionContext $connectionContext
     */
    protected function sendKeepAlive(ConnectionContext $connectionContext): void
    {
        $this->sendMessage($connectionContext, null, MessageTypes::GQL_CONNECTION_KEEP_ALIVE, null);
    }

    /**
     * @param ConnectionContext $connectionContext
     * @param string $opId
     */
    protected function unsubscribe(ConnectionContext $connectionContext, string $opId)
    {
        if ($connectionContext->hasOperation($opId)) {
            $operation = $connectionContext->getOperation($opId);

            if ($operation instanceof ReturnableAsyncIteratorInterface) {
                $operation->return();
            }

            $connectionContext->removeOperation($opId);
            $this->subscriptionOptions->onOperationComplete($connectionContext->getSocket(), $opId);
        }
    }

    /**
     * @param ConnectionContext $connectionContext
     * @param \Exception|null $error
     */
    protected function onConnectionClose(ConnectionContext $connectionContext, \Exception $error = null)
    {
        if ($error !== null) {
            $this->sendError($connectionContext, null, ['message' => $error->getMessage()], MessageTypes::GQL_CONNECTION_ERROR);

            $this->loop->addTimer(10 / 1000, function () use ($connectionContext) {
                $connectionContext->getSocket()->close(1011);
            });
        }

        foreach ($connectionContext->getOperations() as $opId => $_) {
            $this->unsubscribe($connectionContext, $opId);
        }

        $this->contextCollection->detach($connectionContext);
        $this->subscriptionOptions->onDisconnect($connectionContext->getSocket(), $connectionContext);
        $connectionContext->clearSocket();
    }

    /**
     * @param ConnectionContext $connectionContext
     * @param null|string $opId
     * @param string $type
     * @param $payload
     */
    protected function sendMessage(ConnectionContext $connectionContext, ?string $opId, string $type, $payload): void
    {
        $message = [
            'type' => $type,
            'id' => $opId,
            'payload' => $payload
        ];

        $connectionContext->getSocket()->send(json_encode($message));
    }

    /**
     * @param ConnectionContext $connectionContext
     * @param null|string $opId
     * @param $errorPayload
     * @param null|string $overrideDefaultErrorType
     * @throws Exception
     */
    protected function sendError(ConnectionContext $connectionContext, ?string $opId, $errorPayload, string $overrideDefaultErrorType = null): void
    {
        $sanitizedOverrideDefaultErrorType = $overrideDefaultErrorType || MessageTypes::GQL_ERROR;
        if (!in_array($sanitizedOverrideDefaultErrorType, [MessageTypes::GQL_CONNECTION_ERROR, MessageTypes::GQL_ERROR])) {
            throw new Exception('overrideDefaultErrorType should be one of the allowed error messages GQL_CONNECTION_ERROR or GQL_ERROR');
        }

        $this->sendMessage($connectionContext, $opId, $sanitizedOverrideDefaultErrorType, $errorPayload);
    }

    /**
     * @return array
     */
    public function getSubProtocols()
    {
        return [Protocol::GRAPHQL_WS];
    }
}
