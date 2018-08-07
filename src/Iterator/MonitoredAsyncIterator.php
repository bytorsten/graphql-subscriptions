<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise\PromiseInterface;

class MonitoredAsyncIterator implements AsyncIteratorInterface, ReturnableAsyncIteratorInterface
{
    /**
     * @var AsyncIteratorInterface
     */
    protected $baseAsyncIterator;

    /**
     * @var callable
     */
    protected $onReturn;

    /**
     * @param AsyncIteratorInterface $baseAsyncIterator
     * @param callable $onReturn
     */
    public function __construct(AsyncIteratorInterface $baseAsyncIterator, callable $onReturn)
    {
        $this->baseAsyncIterator = $baseAsyncIterator;
        $this->onReturn = $onReturn;
    }

    /**
     * @return PromiseInterface
     */
    public function current(): PromiseInterface
    {
        return $this->baseAsyncIterator->current();
    }

    /**
     *
     */
    public function next()
    {
        $this->baseAsyncIterator->next();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->baseAsyncIterator->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->baseAsyncIterator->valid();
    }

    /**
     *
     */
    public function rewind()
    {
        $this->baseAsyncIterator->rewind();
    }

    /**
     * @return void
     */
    public function return()
    {
        if ($this->baseAsyncIterator instanceof ReturnableAsyncIteratorInterface) {
            $this->baseAsyncIterator->return();
        }

        $onReturn = $this->onReturn;
        $onReturn();
    }
}
