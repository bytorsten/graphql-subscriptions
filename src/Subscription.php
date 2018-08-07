<?php
namespace byTorsten\GraphQL\Subscriptions;

use byTorsten\GraphQL\Subscriptions\Iterator\FilteredAsyncIterator;
use byTorsten\GraphQL\Subscriptions\Iterator\MonitoredAsyncIterator;
use byTorsten\GraphQL\Subscriptions\Iterator\ReturnableAsyncIteratorInterface;

class Subscription
{
    /**
     * @var callable
     */
    protected $subscribe;

    /**
     * @var callable|null
     */
    protected $resolve;

    /**
     * @param callable $subscribe
     * @param callable|null $resolve
     */
    public function __construct(callable $subscribe, callable $resolve = null)
    {
        $this->subscribe = $subscribe;
        $this->resolve = $resolve;
    }

    /**
     * @return callable
     */
    public function getSubscribe(): callable
    {
        return $this->subscribe;
    }

    /**
     * @return callable|null
     */
    public function getResolve(): ?callable
    {
        return $this->resolve;
    }

    /**
     * @param callable $resolverFn
     * @param callable $filterFn
     * @return callable
     */
    public static function withFilter(callable $resolverFn, callable $filterFn): callable
    {
        return function ($rootValue, $args, $context, $info) use ($resolverFn, $filterFn) {
            $asyncIterator = $resolverFn($rootValue, $args, $context, $info);

            return new FilteredAsyncIterator(
                $asyncIterator,
                function ($payload) use ($filterFn, $args, $context, $info) {
                    return $filterFn($payload, $args, $context, $info);
                }
            );
        };
    }

    /**
     * @param callable $resolverFn
     * @param callable|null $onSubscribe
     * @param callable|null $onUnsubscribe
     * @return callable
     */
    public static function withEvents(callable $resolverFn, callable $onSubscribe = null, callable $onUnsubscribe = null): callable
    {
        return function ($rootValue, $args, $context, $info) use ($resolverFn, $onSubscribe, $onUnsubscribe) {
            $asyncIterator = $resolverFn($rootValue, $args, $context, $info);

            if ($onSubscribe !== null) {
                $onSubscribe($args, $context, $info);
            }

            if ($onUnsubscribe === null) {
                return $asyncIterator;
            }
            if (!($asyncIterator instanceof ReturnableAsyncIteratorInterface)) {
                throw new \Exception('Cannot monitor unsubscribe on AsyncIterators with out return');
            }

            return new MonitoredAsyncIterator(
                $asyncIterator,
                function () use ($onUnsubscribe, $args, $context, $info) {
                    $onUnsubscribe($args, $context, $info);
                }
            );
        };
    }
}
