<?php
namespace byTorsten\GraphQL\Subscriptions;

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
}
