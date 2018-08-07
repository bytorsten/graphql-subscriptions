<?php
namespace byTorsten\GraphQL\Subscriptions\Domain\Model;

use Ratchet\ConnectionInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise;

class ConnectionContext
{
    /**
     * @var ConnectionInterface
     */
    protected $socket;

    /**
     * @var
     */
    protected $initPromise;

    /**
     * @var array
     */
    protected $operations = [];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->socket = $connection;
        $this->initPromise = Promise\resolve(true);
    }

    /**
     * @return ConnectionInterface
     */
    public function getSocket(): ?ConnectionInterface
    {
        return $this->socket;
    }

    /**
     *
     */
    public function clearSocket(): void
    {
        $this->socket = null;
    }

    /**
     * @return ExtendedPromiseInterface
     */
    public function getInitPromise(): ExtendedPromiseInterface
    {
        return $this->initPromise;
    }

    /**
     * @param ExtendedPromiseInterface $initPromise
     */
    public function setInitPromise(ExtendedPromiseInterface $initPromise): void
    {
        $this->initPromise = $initPromise;
    }

    /**
     * @param string $opId
     * @param $operation
     */
    public function addOperation(string $opId, $operation)
    {
        $this->operations[$opId] = $operation;
    }

    /**
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param string $opId
     * @return bool
     */
    public function hasOperation(string $opId): bool
    {
        return isset($this->operations[$opId]);
    }

    /**
     * @param string $opId
     * @return mixed
     */
    public function getOperation(string $opId)
    {
        return $this->operations[$opId];
    }

    /**
     * @param string $opId
     */
    public function removeOperation(string $opId): void
    {
        unset($this->operations[$opId]);
    }
}
