<?php
namespace App\Console\Commands\zaahmad;

use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateEpics extends Command
{
	private $ping_url = null;
	//"https://script.google.com/macros/s/AKfycbwCNrLh0BxlYtR3I9iW2Z-4RQK88Hryd4DEC03lIYLoLCce80A/exec?func=alive&device=iesd_support"; 
    private $ping = 10; // minutes
	private $self_update = 60; // minutes
	private $server = 'IESD';
	
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'zaahmad:updateepics {--fields=null} {--beat=0}';
	// 'zaahmad:updatepics --fields=update --beat=0';
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
        parent::__construct();
    }
	
    /**
     * Execute the console command.
     *
     * @return mixed
     */
	public function ConfigureJiraFields()
	{
		dump("Configuring Jira fields");
		$fields = new Fields($this->key);
		$fields->Set(['key','status','statuscategory','summary',
		'description','issuelinks',  //transitions
		'timespent','resolution','timeremainingestimate','timeoriginalestimate','timetracking',
		'resolutiondate','updated','duedate','subtasks','issuetype','subtask',
		'labels','fixVersions','issuetypecategory']);
		$fields->Set(['epic_link'=>'Epic Link','story_points'=>'Story Points','sprint'=>'Sprint']);
		$fields->Dump();
	}
	public function handle()
	{
		date_default_timezone_set("Asia/Karachi");
		
		$classname = __CLASS__;
		$namespace = __NAMESPACE__;
		$this->key = (substr($namespace, strrpos($namespace, '\\') + 1))."/".(substr($classname, strrpos($classname, '\\') + 1));
		$beat = $this->option('beat');
		if($beat == 0)
		{
			$this->ProcessCommand();
			return;
		}
		
		if(($beat % $this->ping) ==0)
		{
			if($this->ping_url != null)
				file_get_contents($this->ping_url);
		}
		if(($beat % $this->self_update) ==0)
		{
			$this->ProcessCommand();
		}
	}
	public function ProcessCommand()
    {
		$opt_fields = $this->option('fields');
		Jira::Init($this->server);
		if($opt_fields == 'update')
			$this->ConfigureJiraFields();
		
		$beat = $this->option('beat');
		if(($beat % 60)!=0)
			return;
		
		$fields = new Fields($this->key);
                $query="issue in linkedIssues(ANDPR-266, 'releases') and type=Epic  and component in (CVBL) and status !=Released ";
		$query.="or issue in linkedIssues(ANDPR-286, 'releases') and type=Epic  and component in (CVBL) and status !=Released";
		dump($query);
		
		//$query='key=VSTARMOD-23421';
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
