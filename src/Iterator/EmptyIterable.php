<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise\PromiseInterface;
use React\Promise;

class EmptyIterable implements AsyncIteratorInterface
{
    /**
     * @return mixed|null
     */
    public function current(): PromiseInterface
    {
        return Promise\resolve(null);
    }

    /**
     *
     */
    public function next(): void
    {
    }

    /**
     * @return mixed|null
     */
    public function key()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return false;
    }

    /**
     *
     */
    public function rewind()
    {
    }
}
