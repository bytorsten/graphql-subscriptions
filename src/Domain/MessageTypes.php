<?php
namespace byTorsten\GraphQL\Subscriptions\Domain;

class MessageTypes
{
    const GQL_CONNECTION_INIT = 'connection_init';
    const GQL_CONNECTION_ACK = 'connection_ack';
    const GQL_CONNECTION_ERROR = 'connection_error';
    const GQL_CONNECTION_KEEP_ALIVE = 'ka';
    const GQL_CONNECTION_TERMINATE = 'connection_terminate';
    const GQL_START = 'start';
    const GQL_DATA = 'data';
    const GQL_ERROR = 'error';
    const GQL_COMPLETE = 'complete';
    const GQL_STOP = 'stop';
}
