<?php

namespace Clockitlab\Gitlab;

class Issue {

    public $id;
    public $iid;
    public $title;
    public $group_id;
    public $project_id;
    public $milestone;

    public function __construct(int $id, int $iid, string $issue_title, int $group_id, string $group_name, int $project_id, object $milestone) {
        $this->id = $id;
        $this->iid = $iid;
        $this->title = $issue_title;
        $this->group_id = $group_id;
        $this->group_name = $group_name;
        $this->project_id = $project_id;
        $this->milestone = $milestone;
    }

}
