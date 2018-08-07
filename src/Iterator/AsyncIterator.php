<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

use React\Promise;

class AsyncIterator implements AsyncIteratorInterface
{
    /**
     * @var int
     */
    protected $position;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->position = 0;
        $this->data = $data;
    }

    /**
     * @return Promise\PromiseInterface
     */
    public function current(): Promise\PromiseInterface
    {
        return Promise\resolve($this->data[$this->position]);
    }

    /**
     *
     */
    public function next()
    {
        $this->position += 1;
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    /**
     *
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @param AsyncIteratorInterface $source
     * @param callable $callback
     * @return Promise\Promise
     */
    public static function forAwaitEach(AsyncIteratorInterface $source, callable $callback)
    {
        $asyncIterator = $source;
        return new Promise\Promise(function (callable $resolve, callable $reject) use ($asyncIterator, $callback) {
            $next = function () use ($asyncIterator, $resolve, $reject, $callback, &$next) {
                if ($asyncIterator->valid() === false) {
                    $resolve();
                }

                $asyncIterator
                    ->current()
                    ->then(function ($value) use ($asyncIterator, $callback, $next, $resolve, $reject) {
                        if ($asyncIterator->valid() === false) {
                            return $resolve();
                        }

                        Promise\resolve($callback($value))
                            ->done(function () use ($asyncIterator, $next) {
                                $asyncIterator->next();
                                $next();
                            }, $reject);
                }, $reject);
            };

            $next();
        });
    }
}
