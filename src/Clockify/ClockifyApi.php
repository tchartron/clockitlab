<?php

namespace Clockitlab\Clockify;
use GuzzleHttp\Client;

class ClockifyApi {

    private $http_client;

    public function __construct(Client $client) {
        $this->http_client = $client;
    }

    public function getUser() {
        $res = $this->http_client->request('GET', 'user', []);
        return json_decode($res->getBody());
    }

    public function getWorkspace() {
        $res = $this->http_client->request('GET', 'workspaces', []);
        return json_decode($res->getBody());
    }

    public function getTimersBetween($workspace_id, $user_id, $start, $end) {
        $res = $this->http_client->request('GET', sprintf('workspaces/%s/user/%s/time-entries', $workspace_id, $user_id), [
            'query' => [
                'start' => $start,
                'end' => $end
            ]
        ]);
        return json_decode($res->getBody());
    }

}
