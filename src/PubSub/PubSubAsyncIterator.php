<?php
namespace byTorsten\GraphQL\Subscriptions\PubSub;

use byTorsten\GraphQL\Subscriptions\Iterator\ReturnableAsyncIteratorInterface;
use byTorsten\GraphQL\Subscriptions\PubSubInterface;
use React\Promise;

class PubSubAsyncIterator implements ReturnableAsyncIteratorInterface
{
    /**
     * @var array
     */
    protected $pullQueue = [];

    /**
     * @var array
     */
    protected $pushQueue = [];

    /**
     * @var bool
     */
    protected $listening = true;

    /**
     * @var PubSubInterface
     */
    protected $pubSub;

    /**
     * @var array
     */
    protected $eventNames;

    /**
     * @var \Closure
     */
    protected $pushValue;

    /**
     * @var \Closure
     */
    protected $pullValue;

    /**
     * @var array
     */
    protected $subscriptionIds = [];

    /**
     * @param PubSubInterface $pubSub
     * @param array $eventNames
     */
    public function __construct(PubSubInterface $pubSub, array $eventNames)
    {
        $this->pubSub = $pubSub;
        $this->eventNames = $eventNames;

        $this->pushValue = function ($event) {
            if (count($this->pullQueue) !== 0) {
                array_shift($this->pullQueue)($event);
            } else {
                $this->pushQueue[] = $event;
            }
        };

        $this->pullValue = function () {
            return new Promise\Promise(function (callable $resolve) {
                if (count($this->pushQueue) !== 0) {
                    $resolve(array_shift($this->pushQueue));
                } else {
                    $this->pullQueue[] = $resolve;
                }
            });
        };

        $this->addEventListeners();
    }

    /**
     *
     */
    protected function emptyQueue(): void
    {
        if ($this->listening === true) {
            $this->listening = false;
            $this->removeEventListeners();
            foreach ($this->pullQueue as $resolver) {
                $resolver(null);
            }
            $this->pullQueue = [];
            $this->pushQueue = [];
        }
    }

    /**
     *
     */
    protected function addEventListeners()
    {
        foreach ($this->eventNames as $eventName) {
            $this->pubSub->subscribe($eventName, $this->pushValue)->then(function (int $subscriptionId) {
                $this->subscriptionIds[] = $subscriptionId;
            });
        }
    }

    /**
     *
     */
    protected function removeEventListeners()
    {
        foreach ($this->subscriptionIds as $subscriptionId) {
            $this->pubSub->unsubscribe($subscriptionId);
        }
    }

    /**
     * @return Promise\PromiseInterface
     */
    public function current(): Promise\PromiseInterface
    {
        if ($this->listening) {
            $pullValue = $this->pullValue;
            return $pullValue();
        }

        $this->emptyQueue();
        return Promise\resolve(null);
    }

    /**
     *
     */
    public function return()
    {
        $this->emptyQueue();
    }

    /**
     *
     */
    public function next()
    {
        return null;
    }

    /**
     * @return mixed|void
     */
    public function key()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->listening;
    }

    /**
     *
     */
    public function rewind()
    {
    }
}
