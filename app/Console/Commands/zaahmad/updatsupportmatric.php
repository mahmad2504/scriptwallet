<?php

namespace App\Console\Commands\zaahmad;

use Illuminate\Console\Command;
use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use App\Database;
class updatsupportmatric extends Command
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
     protected $signature = 'zaahmad:updatesupportmatric {--fields=null} {--rebuild=0} {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates support tickets data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
		parent::__construct();
    }
	public function ConfigureJiraFields()
	{
		dump("Configuring Jira fields");
		Jira::Init($this->server);
		$fields = new Fields($this->key);
		
		$fields->Set(['created','issuetype','resolution','project','status','statuscategory','priority','resolutiondate','components']);
		$fields->Set(['first_contact'=>'Date of First Response','reason_for_closure'=>'Reason For Closure.']);
		$fields->Dump();
	}
    /**
     * Execute the console command.
     *
     * @return int
     */
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
		$db = new Database(env("IESD_SUPPORT_COLLECTION"));
		$opt_fields = $this->option('fields');
		if($opt_fields == 'update')
			$this->ConfigureJiraFields();
		
		$rebuild = $this->option('rebuild');
		if($db->IsCollectionExists('tickets')==0)
			$rebuild = 1;
		$beat = $this->option('beat');
		
		if(($rebuild == 0)&&(($beat % 60)!=0))
			return;
		
		$query='project=VSTARMOD AND issuetype in (Issue, Question) AND component in (CVBL, CVLTP,CVNWM,CVDSL,CVTL,CVTP) AND issueFunction in hasLinkType("AND Support") || project=VOLSUP and issuetype in ("Volcano BL","Volcano IVS") ';
		$query.=' || project=VOLSUP and issuetype in ("Volcano BL","Volcano IVS")';
		
		if($rebuild==1)
		{
			$db->DropCollection('tickets');
			dump('Rebuilding');
		}
		else
		{
			$updated = date('Y-m-d H:i',strtotime(' -2 day'));
			$query = $query.' and updated >= '."'".$updated."'";
		}
		Jira::Init($this->server);
		$fields = new Fields($this->key);
		$tickets =  Jira::FetchTickets($query,$fields);
		foreach($tickets as $key=>$ticket)
		{
			$db->UpdateDoc('tickets',['key'=>$ticket->key],$ticket);
		}
		dump("Done");
    }
}
