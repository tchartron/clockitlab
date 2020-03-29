<?php

namespace Timer;
use League\Container\Container;

class Timer {
    public function __construct(Container $container) {
        parent::__construct($container);

        $this->container->add('guzzle', \GuzzleHttp\Client::class)->addArgument([
            'base_uri' => getenv('TRELLO_API_URI'),
            'query' => [
                'key' => getenv('TRELLO_API_KEY'),
                'token' => getenv('TRELLO_API_TOKEN'),
            ],
            'debug' => getenv('DEBUG')
        ]);
    }

}
