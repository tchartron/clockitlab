<?php
require_once 'vendor/autoload.php';

$container = new League\Container\Container;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); // Dotenv removed constructor for static method "create" container not usable for this ?
$dotenv->load();

// $container->add('climate', (new League\CLImate\CLImate()));
$container->add('climate', League\CLImate\CLImate::class);
$climate = $container->get('climate');

$climate->arguments->add([
    'milestone' => [
        'prefix'      => 'm',
        'longPrefix'  => 'milestone',
        'description' => 'Gitlab mileston name',
        'defaultValue' => 'Sprint 20-12'
    ],
    'verbose' => [
        'prefix'      => 'v',
        'longPrefix'  => 'verbose',
        'description' => 'Verbose output',
        'noValue'     => true,
    ],
    'help' => [
        'prefix'      => 'h',
        'longPrefix'  => 'help',
        'description' => 'Prints a usage statement',
        'noValue'     => true,
    ],
]);

$climate->arguments->parse();

putenv("DEBUG=" . $climate->arguments->defined('verbose'));

if ($climate->arguments->defined('help')) {
    $climate->usage();
    exit;
}
$milestone_name = $climate->arguments->get('milestone');


//Begining
$container->add('guzzle-gitlab', \GuzzleHttp\Client::class)->addArgument([
    'base_uri' => getenv('GITLAB_API_URI'),
    'headers' => [
        'private-token' => getenv('GITLAB_API_TOKEN')
    ],
    'debug' => getenv('DEBUG')
]);
$container->add('guzzle-clockify', \GuzzleHttp\Client::class)->addArgument([
    'base_uri' => getenv('CLOCKIFY_API_URI'),
    'headers' => [
        'content-type' => "application/json",
        'X-Api-Key' => getenv('CLOCKIFY_API_TOKEN')
    ],
    'debug' => getenv('DEBUG')
]);

$gitlab = new Clockitlab\Gitlab\GitlabApi($container->get('guzzle-gitlab'));
$groups = $gitlab->getGroups();

$issues = [];
$milestone_dates = [
    'start' => "",
    'end' => "",
];
$dates = false;

foreach ($groups as $group) {
    // $milestone = $gitlab->getMilestone($group_id);
    $issues_result = $gitlab->getGroupMilestoneIssues($group->id, $milestone_name);
    foreach ($issues_result as $issue) {
        array_push($issues, (new Clockitlab\Gitlab\Issue($issue->id, $issue->iid, $issue->title, $group->id, $group->name, $issue->project_id, $issue->milestone)));
    }
}
//Get first milestone only they all have the same date
$milestone_dates = [
    'start' => $issues[0]->milestone->start_date,
    'end' => $issues[0]->milestone->due_date,
];
$sprint_begin_dt = new DateTime(sprintf("%s 00:00:00", $milestone_dates['start']));
$sprint_end_dt = new DateTime(sprintf("%s 23:59:59", $milestone_dates['end']));

$carbon_sprint_begin = new Carbon\Carbon($sprint_begin_dt);
$carbon_sprint_end = new Carbon\Carbon($sprint_end_dt);

$clockify = new Clockitlab\Clockify\ClockifyApi($container->get('guzzle-clockify'));
$user_id = $clockify->getUser()->id;
$workspace_id = $clockify->getWorkspace()[0]->id; // 1 is user personnal workspace

$sprint_timers = $clockify->getTimersBetween($workspace_id, $user_id, $carbon_sprint_begin->toISOString(), $carbon_sprint_end->toISOString());

if(!is_array($sprint_timers) || empty($sprint_timers)) {
    $climate->red()->out(sprintf("No timers found on clockify for the specified milestone : %s", $milestone));
    exit(1);
}
$timers = [];
foreach ($sprint_timers as $value) {
    if(preg_match("/#(\d+)\s([\w\s \/'\#\@\%\€\£\$\,\.\+\-]+)/", $value->description, $matches)) {
        $issue_iid = $matches[1];
        $issue_title = $matches[2];
        $duration = Carbon\CarbonInterval::make($value->timeInterval->duration)->forHumans(['short' => true]); //duration is an ISO 8601 Period string
        array_push($timers, (new Clockitlab\Clockify\Timer($issue_iid, $issue_title, str_replace(' ', '', $duration))));
    }
}

// VIRER LES SECONDES DANS DURATION
$issue_time_added = [];
foreach ($timers as $timer) {
    $corresponding_issue = $timer->getCorrespondingIssue($issues);
    if($corresponding_issue !== false) {
        $time_spent_stats = $gitlab->pushTimeSpent($corresponding_issue->iid, $corresponding_issue->project_id, $timer->timer_value);
        $climate->green()->out(sprintf("Adding %s to issue (%d) %s", $timer->timer_value, $corresponding_issue->iid, $corresponding_issue->title));
        array_push($issue_time_added, [
            'iid' => $corresponding_issue->iid,
            'title' => $corresponding_issue->title,
            'project_id' => $corresponding_issue->project_id,
            'time-added' => $timer->timer_value,
            'new-time-stats' => $time_spent_stats,
            ]
        );
    }
}
$climate->blue()->out("-------------------- RECAP : ---------------------");
$climate->blue()->out(print_r($issue_time_added));
exit(0);
