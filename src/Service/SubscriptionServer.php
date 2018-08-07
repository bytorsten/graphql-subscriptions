<?php
namespace byTorsten\GraphQL\Subscriptions\Service;

use byTorsten\GraphQL\Subscriptions\SubscriptionOptions;
use GraphQL\Type\Schema;
use React\EventLoop\LoopInterface;
use Ratchet\App;

class SubscriptionServer
{

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var App
     */
    protected $app;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @param LoopInterface $loop
     * @param int $port
     * @param string $httpPost
     * @param string $address
     */
    public function __construct(LoopInterface $loop, int $port = 4000, string $httpPost = 'localhost', string $address = '0.0.0.0')
    {
        $this->loop = $loop;
        $this->app = new App($httpPost, $port, $address, $this->loop);
        $this->uri = sprintf('ws://%s:%s', $httpPost, $port);
    }

    /**
     * @param string $endpoint
     * @param Schema $schema
     * @param SubscriptionOptions|null $options
     */
    public function addEndpoint(string $endpoint, Schema $schema, SubscriptionOptions $options = null)
    {
        if ($options === null) {
            $options = new SubscriptionOptions();
        }

        $this->app->route('/' . $endpoint, new SubscriptionComponent($this->loop, $schema, $options), ['*']);
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }
}
