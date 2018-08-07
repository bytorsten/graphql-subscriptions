<?php
namespace byTorsten\GraphQL\Subscriptions\GraphQL;

use byTorsten\GraphQL\Subscriptions\Iterator\AsyncIteratorInterface;
use byTorsten\GraphQL\Subscriptions\Iterator\MappedAsyncIterator;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Schema;
use React\Promise;

class Subscription
{
    /**
     * @param Schema $schema
     * @param DocumentNode $document
     * @param null $rootValue
     * @param null $contextValue
     * @param null $variableValues
     * @param null $operationName
     * @param callable|null $fieldResolver
     * @param callable|null $subscribeFieldResolver
     * @return Promise\PromiseInterface
     */
    public static function subscribe(
        Schema $schema,
        DocumentNode $document,
        $rootValue = null,
        $contextValue = null,
        $variableValues = null,
        $operationName = null,
        callable $fieldResolver = null,
        callable $subscribeFieldResolver = null
    ) {
        $sourcePromise = static::createSourceEventStream(
            $schema,
            $document,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $subscribeFieldResolver
        );

        $mapSourceToResponse = function ($payload) use ($schema, $document, $contextValue, $variableValues, $operationName, $fieldResolver) {

            /** @var Promise\PromiseInterface $promise */
            $promise = Executor::promiseToExecute(
                new ReactPromiseAdapter(),
                $schema,
                $document,
                $payload,
                $contextValue,
                $variableValues,
                $operationName,
                $fieldResolver
            )->adoptedPromise;

            return $promise->then(function (ExecutionResult $result) {
                return $result->toArray(true);
            });
        };

        return $sourcePromise->then(function ($resultOrStream) use ($mapSourceToResponse) {
            if ($resultOrStream instanceof AsyncIteratorInterface) {
                return new MappedAsyncIterator($resultOrStream, $mapSourceToResponse);
            }

            return $resultOrStream;
        });
    }

    protected static function createSourceEventStream(
        Schema $schema,
        DocumentNode $document,
        $rootValue = null,
        $contextValue = null,
        $variableValues = null,
        $operationName = null,
        callable $fieldResolver = null
    ): Promise\PromiseInterface {

        try {
            $exeContext = Utils::buildExecutionContext($schema, $document, $rootValue, $contextValue, $variableValues, $operationName, $fieldResolver);

            if (is_array($exeContext)) {
                return Promise\reject([ 'errors' => $exeContext ]);
            }

            $type = Utils::getOperationRootType($schema, $exeContext->operation);

            $fields = Utils::collectFields($exeContext, $type, $exeContext->operation->selectionSet);

            $responseNames = array_keys($fields);
            $responseName = $responseNames[0];
            /** @var \ArrayObject $fieldNodes */
            $fieldNodes = $fields[$responseName];

            /** @var FieldNode $fieldNode */
            $fieldNode = $fieldNodes[0];
            $fieldName = $fieldNode->name->value;
            $fieldDef = Utils::getFieldDef($schema, $type, $fieldName);

            if (!$fieldDef) {
                throw new Error(sprintf('The subscription field "%s" is not defined.', $fieldName), $fieldNodes);
            }

            $resolveFn = $fieldDef->config['subscribe'] ?? $exeContext->fieldResolver;


            $path = Utils::addPath(null, $responseName);
            $info = Utils::buildResolveInfo($exeContext, $fieldDef, $fieldNodes, $type, $path);

            $result = Utils::resolveFieldValueOrError($exeContext, $fieldDef, $fieldNode, $resolveFn, $rootValue, $info);

            return Promise\resolve($result)
                ->then(function ($eventStream) use ($fieldNodes, $path) {
                    if ($eventStream instanceof Error) {
                        throw Error::createLocatedError($eventStream, $fieldNodes, $path);
                    }

                    if ($eventStream instanceof AsyncIteratorInterface) {
                        return $eventStream;
                    }

                    throw new Error('Subscription field must return an async iterable, received: ' . gettype($eventStream));
                });
        } catch (\Throwable $error) {
            return Promise\reject($error);
        }
    }
}
