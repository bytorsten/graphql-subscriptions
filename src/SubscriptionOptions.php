<?php
namespace byTorsten\GraphQL\Subscriptions;

use byTorsten\GraphQL\Subscriptions\Domain\Model\ConnectionContext;
use byTorsten\GraphQL\Subscriptions\Domain\Model\ExecutionParameters;
use byTorsten\GraphQL\Subscriptions\GraphQL\Subscription;
use byTorsten\GraphQL\Subscriptions\Rules\SingleFieldSubscriptions;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;

class SubscriptionOptions
{

    /**
     * @var int
     */
    protected $keepAlive = 0;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * @var mixed
     */
    protected $rootValue;

    /**
     *
     */
    public function __construct()
    {
        $this->validationRules = DocumentValidator::defaultRules();
        $this->validationRules[SingleFieldSubscriptions::class] = new SingleFieldSubscriptions();
    }

    /**
     * @return bool
     */
    public function hasKeepAlive(): bool
    {
        return $this->keepAlive > 0;
    }

    /**
     * @return int
     */
    public function getKeepAlive(): int
    {
        return $this->keepAlive;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * @param array $validationRules
     */
    public function setValidationRules(array $validationRules): void
    {
        $this->validationRules = $validationRules;
    }

    /**
     * @param array $message
     * @param ExecutionParameters $parameters
     * @param ConnectionInterface $socket
     * @return ExecutionParameters|PromiseInterface
     */
    public function onOperation(array $message, ExecutionParameters $parameters, ConnectionInterface $socket)
    {
        return $parameters;
    }

    /**
     * @param ConnectionInterface $socket
     * @param string $opId
     */
    public function onOperationComplete(ConnectionInterface $socket, string $opId): void
    {
    }

    /**
     * @param $payload
     * @param ConnectionInterface $socket
     * @param ConnectionContext $connectionContext
     * @return mixed
     */
    public function onConnect($payload, ConnectionInterface $socket, ConnectionContext $connectionContext)
    {
    }

    /**
     * @param ConnectionInterface $socket
     * @param ConnectionContext $connectionContext
     */
    public function onDisconnect(ConnectionInterface $socket, ConnectionContext $connectionContext)
    {
    }

    /**
     * @param Schema $schema
     * @param DocumentNode $documentNode
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array $variableValues
     * @param string $operationName
     * @return \GraphQL\Executor\Promise\Promise
     */
    public function execute(Schema $schema, DocumentNode $documentNode, $rootValue, $contextValue, ?array $variableValues, ?string $operationName)
    {
        return Executor::promiseToExecute(
            new ReactPromiseAdapter(),
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName
        );
    }

    /**
     * @param Schema $schema
     * @param DocumentNode $documentNode
     * @param $rootValue
     * @param $contextValue
     * @param array|null $variableValues
     * @param null|string $operationName
     * @return PromiseInterface
     */
    public function subscribe(Schema $schema, DocumentNode $documentNode, $rootValue, $contextValue, ?array $variableValues, ?string $operationName)
    {
        return Subscription::subscribe(
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName
        );
    }

    /**
     * @return mixed
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    /**
     * @param mixed $rootValue
     */
    public function setRootValue($rootValue): void
    {
        $this->rootValue = $rootValue;
    }
}
