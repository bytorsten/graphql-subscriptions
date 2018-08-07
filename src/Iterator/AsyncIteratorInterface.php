<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise\PromiseInterface;

interface AsyncIteratorInterface extends \Iterator
{
    /**
     * @return PromiseInterface
     */
    public function current(): PromiseInterface;
}
