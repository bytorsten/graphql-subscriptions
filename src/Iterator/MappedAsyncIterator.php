<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise\PromiseInterface;

class MappedAsyncIterator implements ReturnableAsyncIteratorInterface
{
    /**
     * @var AsyncIteratorInterface
     */
    protected $baseIterator;

    /**
     * @var callable
     */
    protected $mapper;

    /**
     * @param AsyncIteratorInterface $baseIterator
     * @param callable $mapper
     */
    public function __construct(AsyncIteratorInterface $baseIterator, callable $mapper)
    {
        $this->baseIterator = $baseIterator;
        $this->mapper = $mapper;
    }

    /**
     * @return PromiseInterface
     */
    public function current(): PromiseInterface
    {
        return $this->baseIterator->current()->then($this->mapper);
    }

    /**
     *
     */
    public function next()
    {
        $this->baseIterator->next();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->baseIterator->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->baseIterator->valid();
    }

    /**
     *
     */
    public function rewind()
    {
        $this->baseIterator->rewind();
    }

    /**
     *
     */
    public function return()
    {
        if ($this->baseIterator instanceof ReturnableAsyncIteratorInterface) {
            $this->baseIterator->return();
        }
    }
}
