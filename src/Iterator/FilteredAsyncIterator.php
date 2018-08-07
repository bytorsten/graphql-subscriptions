<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise\PromiseInterface;
use function React\Promise\all;
use function React\Promise\resolve;

class FilteredAsyncIterator implements AsyncIteratorInterface, ReturnableAsyncIteratorInterface
{
    /**
     * @var AsyncIteratorInterface
     */
    protected $baseAsyncIterator;

    /**
     * @var callable
     */
    protected $filterFn;

    /**
     * @param AsyncIteratorInterface $baseAsyncIterator
     * @param callable $filterFn
     */
    public function __construct(AsyncIteratorInterface $baseAsyncIterator, callable $filterFn)
    {
        $this->baseAsyncIterator = $baseAsyncIterator;
        $this->filterFn = $filterFn;
    }

    /**
     * @return PromiseInterface
     */
    protected function getNextPromise(): PromiseInterface
    {
        return $this->baseAsyncIterator->current()
            ->then(function ($payload) {
                $filterFn = $this->filterFn;
                return all([
                    $payload,
                    resolve($filterFn($payload))->otherwise(function () {
                        return false;
                    })
                ]);
            })
            ->then(function (array $payloadAndFilterResult) {
                [$payload, $filterResult] = $payloadAndFilterResult;

                if ($filterResult === true) {
                    return $payload;
                }

                return $this->getNextPromise();
            });
    }

    /**
     * @return PromiseInterface
     */
    public function current(): PromiseInterface
    {
        return $this->getNextPromise();
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
    }
}
