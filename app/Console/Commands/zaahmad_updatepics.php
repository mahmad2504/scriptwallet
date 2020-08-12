<?php
namespace App\Console\Commands;

use JiraRestApi\Field\Field;
use JiraRestApi\Field\FieldService;
use JiraRestApi\JiraException;
use JiraRestApi\Configuration\ArrayConfiguration;
use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Illuminate\Console\Command;
use Carbon\Carbon;

class zaahmad_updatepics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'zaahmad:updatepics {--fields=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump jirafields to be fetched';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
		$this->tag = 'zaahmad_updatepics';
		$this->server = 'IESD';
        parent::__construct();
    }
	
    /**
     * Execute the console command.
     *
     * @return mixed
     */
	public function DumpFields()
	{
		echo "Dumping field names\n";
		$fields = new Fields($this->tag);
		$fields->Set(['key','status','statuscategory','summary',
		'description','issuelinks',  //transitions
		'timespent','resolution','timeremainingestimate','timeoriginalestimate',
		'resolutiondate','updated','duedate','subtasks','issuetype','subtask',
		'labels','fixVersions','issuetypecategory']);
		$fields->Set(['epic_link'=>'Epic Link','story_points'=>'Story Points','sprint'=>'Sprint']);
		$fields->Dump();
		echo "Done\n";
	}
	public function GenerateMRTree($tasks)
	{
		$query = 'key in (';
		$del = '';
		foreach($tasks as $task)
		{
			foreach($task->subtasks as $key)
			{
				if(!isset($tasks[$key]))
				{
					$query .= $del.$key;
					$del=',';
				}
			}
		}
	}
    public function handle()
    {
		$opt_fields = $this->option('fields');
		if($opt_fields == 'update')
			$this->DumpFields();
		
		//$opt_fixversion = $this->option('fixversion');
		//if(file_exists($opt_fixversion,serialize($tasks));
		//$tasks = Jira::Search('fixVersion='.$opt_fixversion);
		//$tickets = Jira::BuildTree('key in (HMIP-100,HMIP-200,HMIP-1738)');
		Jira::Init($this->server);
		//Jira::Init('IESD');
		$fields = new Fields($this->tag);
		$query="issue in linkedIssues(ANDPR-266, 'releases') and type=Epic  and component in (CVBL) and status !=Released";
		dump($query);
		$tickets =  Jira::FetchTickets($query,$fields);
		foreach($tickets as $ticket)
		{
			$query="'Epic Link'=".$ticket->key;
			dump($query);
			$stasks = Jira::FetchTickets($query,$fields);
			$timeoriginalestimate= 0;
			$timeremainingestimate = 0;
			$timespent = 0;
			foreach($stasks as $task)
			{
				if(count($task->subtasks)>0)
				{
					$query="issueFunction in subtasksOf ('key=".$task->key."')";
					dump($query);
					$ctasks = Jira::FetchTickets($query,$fields);
					
					foreach($ctasks as $ctask)
					{
						$timeoriginalestimate += $ctask->timeoriginalestimate;
						$timeremainingestimate += $ctask->timeremainingestimate;
						$timespent += $ctask->timespent;
					}
				}
				$timeoriginalestimate += $task->timeoriginalestimate;
				$timeremainingestimate += $task->timeremainingestimate;
				$timespent += $task->timespent;
				
			}
			Jira::UpdateTimeTrack($ticket->key,$timeoriginalestimate,$timeremainingestimate,$timespent);
		}
		dump("Done");
    }
}
