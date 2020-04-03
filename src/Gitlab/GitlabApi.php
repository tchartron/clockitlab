<?php

namespace Clockitlab\Gitlab;
use GuzzleHttp\Client;

class GitlabApi {

    // private $container;
    // private $groups = [];
    // private $issues = [];
    // private $milestone = null;
    private $http_client;

    public function __construct(Client $client) {
        $this->http_client = $client;
    }

    public function getGroups() : array {
        $gitlab_groups = [];
        $groups = (getenv('GITLAB_GROUPS')) ? explode(",", getenv('GITLAB_GROUPS')) : 'vinatis';
        foreach ($groups as $group) {
            $res = $this->http_client->request('GET', 'groups', [
                'query' => [
                    'search' => $group
                ]
            ]);
            $json = json_decode($res->getBody());
            foreach ($json as $json_group) {
                array_push($gitlab_groups, $json_group);
            }
        }
        return $gitlab_groups;
    }

    // public function getMilestone($group_id, $milestone_name$) : array {
    //     $gitlab_milestones = [];
    //     $res = $this->http_client->request('GET', sprintf('groups/%d/milestones', $group_id), [
    //         'query' => [
    //             'search' => $milestone_name,
    //         ]
    //     ]);
    //     $json = json_decode($res->getBody());
    //     foreach ($json as $json_milestone) {
    //         array_push($gitlab_milestone, $json_milestone);
    //     }
    //     return $gitlab_milestone;
    // }

    public function getGroupMilestoneIssues($group_id, $milestone_name) : array {
        $gitlab_issues = [];
        $res = $this->http_client->request('GET', sprintf('groups/%d/issues', $group_id), [
            'query' => [
                'milestone' => $milestone_name,
                'scope' => 'all',
                'per_page' => '100',
            ]
        ]);
        $json = json_decode($res->getBody());
        foreach ($json as $json_issue) {
            array_push($gitlab_issues, $json_issue);
        }
        return $gitlab_issues;
    }

    public function pushTimeSpent($issue_iid, $project_id, $duration) {
        $res = $this->http_client->request('POST', sprintf('projects/%d/issues/%d/add_spent_time', $project_id, $issue_iid), [
            'form_params' => [
                'duration' => $duration,
            ]
        ]);
        return json_decode($res->getBody());
    }

    public function resetTimeSpent($issue_iid, $project_id, $duration) {
        $res = $this->http_client->request('POST', sprintf('projects/%d/issues/%d/reset_spent_time', $project_id, $issue_iid), []);
        return json_decode($res->getBody());
    }

}
