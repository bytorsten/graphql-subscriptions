<?php
namespace byTorsten\GraphQL\Subscriptions\PubSub;

use React\EventLoop\LoopInterface;
use React\Promise;
use Evenement\EventEmitter;
use byTorsten\GraphQL\Subscriptions\PubSubInterface;
use byTorsten\GraphQL\Subscriptions\Iterator\AsyncIteratorInterface;

class SyncPubSub implements PubSubInterface
{
    /**
     * @var EventEmitter
     */
    protected $eventEmitter;

    /**
     * @var array
     */
    protected $subscriptions = [];

    /**
     * @var int
     */
    protected $subIdCounter = 0;

    /**
     * @param LoopInterface $loop
     * @return Promise\PromiseInterface
     */
    public function setup(LoopInterface $loop): Promise\PromiseInterface
    {
        $this->eventEmitter = new EventEmitter();
        return Promise\resolve();
    }

    /**
     * @param string $triggerName
     * @param $payload
     * @return bool
     */
    public function publish(string $triggerName, $payload): bool
    {
        $this->eventEmitter->emit($triggerName, [$payload]);
        return true;
    }

    /**
     * @param string $triggerName
     * @param callable $onMessage
     * @param array $options
     * @return Promise\PromiseInterface
     */
    public function subscribe(string $triggerName, callable $onMessage, array $options = []): Promise\PromiseInterface
    {
        $this->eventEmitter->on($triggerName, $onMessage);
        $this->subIdCounter += 1;
        $this->subscriptions[$this->subIdCounter] = [$triggerName, $onMessage];

        return Promise\resolve($this->subIdCounter);
    }

    /**
     * @param int $subId
     */
    public function unsubscribe(int $subId): void
    {
        if (isset($this->subscriptions[$subId])) {
            [$triggerName, $onMessage] = $this->subscriptions[$subId];
            unset ($this->subscriptions[$subId]);
            $this->eventEmitter->removeListener($triggerName, $onMessage);
        }
    }

    /**
     * @param array $triggers
     * @return AsyncIteratorInterface
     */
    public function asyncIterator(array $triggers): AsyncIteratorInterface
    {
        return new PubSubAsyncIterator($this, $triggers);
    }
}
