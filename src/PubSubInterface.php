<?php
namespace byTorsten\GraphQL\Subscriptions;

use byTorsten\GraphQL\Subscriptions\Iterator\AsyncIteratorInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

interface PubSubInterface
{
    /**
     * @param LoopInterface $loop
     * @return PromiseInterface
     */
    public function setup(LoopInterface $loop): PromiseInterface;

    /**
     * @param string $triggerName
     * @param $payload
     * @return bool
     */
    public function publish(string $triggerName, $payload): bool;

    /**
     * @param string $triggerName
     * @param callable $onMessage
     * @param array $options
     * @return PromiseInterface
     */
    public function subscribe(string $triggerName, callable $onMessage, array $options = []): PromiseInterface;

    /**
     * @param int $subId
     */
    public function unsubscribe(int $subId): void;

    /**
     * @param array $triggers
     * @return AsyncIteratorInterface
     */
    public function asyncIterator(array $triggers): AsyncIteratorInterface;
}
