<?php
namespace byTorsten\GraphQL\Subscriptions\Service;

use byTorsten\GraphQL\Subscriptions\Domain\Model\ConnectionContext;
use Ratchet\ConnectionInterface;

class ContextCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $contexts = [];

    /**
     * @param ConnectionInterface $connection
     * @return int
     */
    protected static function getKey(ConnectionInterface $connection)
    {
        return $connection->resourceId;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function attach(ConnectionInterface $connection)
    {
        $this->contexts[static::getKey($connection)] = new ConnectionContext($connection);
    }

    /**
     * @param ConnectionInterface $connection
     * @return ConnectionContext
     */
    public function get(ConnectionInterface $connection): ConnectionContext
    {
        return $this->contexts[static::getKey($connection)];
    }

    /**
     * @param ConnectionInterface|ConnectionContext $connectionOrContext
     */
    public function detach($connectionOrContext)
    {
        if ($connectionOrContext instanceof ConnectionContext) {
            $key = static::getKey($connectionOrContext->getSocket());
        } else {
            $key = static::getKey($connectionOrContext);
        }

        unset($this->contexts[$key]);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->contexts);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->contexts));
    }
}
