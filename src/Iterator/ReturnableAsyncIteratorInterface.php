<?php
namespace byTorsten\GraphQL\Subscriptions\Iterator;

interface ReturnableAsyncIteratorInterface extends AsyncIteratorInterface
{
    /**
     * @return void
     */
    public function return();
}
