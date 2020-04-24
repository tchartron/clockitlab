<?php

namespace Clockitlab\Clockify;

class Timer {

    public $gitlab_issue_iid;
    public $gitlab_issue_title;
    public $timer_value;

    public function __construct(int $issue_iid, string $issue_title, string $value) {
        $this->gitlab_issue_iid = $issue_iid;
        $this->gitlab_issue_title = $issue_title;
        $this->timer_value = $value;
    }

    public function getCorrespondingIssue($issues) {
        foreach ($issues as $issue) {
            if($issue->iid === $this->gitlab_issue_iid && $issue->title === $this->gitlab_issue_title) {
                return $issue;
            }
        }
        return false;
    }
}
