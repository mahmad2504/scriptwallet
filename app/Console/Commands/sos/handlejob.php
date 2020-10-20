<?php

namespace App\Console\Commands\sos;

use Illuminate\Console\Command;
use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use App\Database;
use Aws\S3\S3Client;  
use Aws\Exception\AwsException;
use App\Console\Commands\sos\Tj;
class HandleJob extends Command
{
	//private $ping_url = null;
	private $ping_url = "https://script.google.com/macros/s/AKfycbyJjENO0HSdA_8Bcx_zGnJCywzIii-4ArYEcev0BXFS-YbKioWD";
	private $ping = 10; // minutes
	private $self_update = 60; // minutes
	private $server = 'EPS';
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'sos:handlejob {--fields=null}  {--beat=0} {--server=EPS} {--projectid=null}';

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
		
		//$fields->Set(['created','issuetype','resolution','project','status','statuscategory','priority','resolutiondate','components']);
		$fields->Set(['sprint'=>'Sprint']);
		$fields->Set(['duedate','timespent','timeremainingestimate','timeoriginalestimate','id','assignee','created','issuetype','resolution','project','status','statuscategory','timetracking']);
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
		if( ($this->option('server') != 'EPS')&&
		    ($this->option('server') != 'IESD')&&
			($this->option('server') != 'ATLASSIAN'))
		{
			echo "Wrong server";
			return;
		}
		
		$classname = __CLASS__;
		$namespace = __NAMESPACE__;
		$this->key = (substr($namespace, strrpos($namespace, '\\') + 1))."/".(substr($classname, strrpos($classname, '\\') + 1));
		$this->key = $this->key."_".$this->option('server');
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
	public function ProcessTree($ticket,$level=0)
	{
		$str = '';
		for($i=0;$i<$level;$i++)
			$str .= "---";
		if($level == 0)
		{
			$ticket->extid = '1';
			$ticket->key = 'head';
			
			//echo $str.$ticket->extid."  ".$ticket->key."  "."\n";
			
		}
		else
		{
			$ticket->worklogs= Jira::WorkLogs($ticket->key);
			//echo $str.$ticket->extid."  ".$ticket->key." ".$ticket->timetracking->originalEstimate."\n";
			
		}
		
		$level++;
		$i=0;
		$ticket->isparent=0;
		$ticket->estimate=0;
		$ticket->remaining=0;
		$ticket->spent = 0;
		if(count($ticket->children)==0)
		{
			$ticket->estimate = $ticket->timeoriginalestimate;
			$ticket->remaining =$ticket->timeremainingestimate;
			$ticket->spent =$ticket->timespent;
			$acc = 0;
			foreach($ticket->worklogs as $worklog)
			{
				$acc = $acc + $worklog->seconds;
			}
			if($acc != $ticket->spent)
			{
				dd($ticket);
			}
			return ;
		}
		foreach($ticket->children as $sticket)
		{
			$sticket->extid = $ticket->extid.".".$i++;
			$ticket->isparent=1;
			$this->ProcessTree($sticket,$level);
			$ticket->estimate += $sticket->estimate;
			$ticket->remaining += $sticket->remaining;
			$ticket->spent += $sticket->spent;
		}
	}
	public function ShowTree($ticket,$level=0)
	{
		$str = '';
		for($i=0;$i<$level;$i++)
			$str .= "---";
		
		$d = floor($ticket->estimate/28800);
		$h = (($ticket->estimate/28800)-floor($ticket->estimate/28800))*8;
		
		if($d >= 5)
		{
			$w = floor($d/5);
			$d = $d - $w*5;
			echo $str.$ticket->extid."  ".$ticket->key." ".$ticket->estimate." ".$ticket->remaining." ".$ticket->spent."\n";		
		}
		else
		{
			echo $str.$ticket->extid."  ".$ticket->key." ".$ticket->estimate." ".$ticket->remaining." ".$ticket->spent."\n";		
		
		}
		$level++;
		$i=0;
		
		foreach($ticket->children as $sticket)
		{
			$sticket->extid = $ticket->extid.".".$i++;
			$this->ShowTree($sticket,$level);
		}
	}
    public function ProcessCommand()
    {
		//$projectid = $this->option('projectid');
		//echo $projectid;
		echo "Executing";
	   $s3Client = new S3Client([
			'profile' => 'default',
			'region' => 'us-west-2',
			'version' => '2006-03-01'
		]);

	
		//$buckets = $s3Client->listBuckets();
		//	foreach ($buckets['Buckets'] as $bucket) {
		//	dump($bucket);
		//}
		// List objects from a bucket
		//$result = $s3Client->getObject(array(
        //'Bucket' => 'eps.mentorcloudservices.com',
        //'Key'    => 'sos/test.json'
    //));


//$tree= json_decode((string)$result['Body']);
//$tj =  new Tj($tree);

//$this->ShowTree($tree);
//return;
//foreach ($objects as $wo)
//{
    // Prints junk
//    echo $wo['Key'] . ' - ' . $wo['Size'] . PHP_EOL;
//}

	//	return;
		//$db = new Database(env("IESD_SUPPORT_COLLECTION"));
		$opt_fields = $this->option('fields');
		if($opt_fields == 'update')
			$this->ConfigureJiraFields();
		
		$query='key in (PSP-9330)';
		Jira::Init($this->server);
		$objects = Jira::GetStructureObjects(847);
		if($objects == null)
		{
			echo "Structure is empty";
			return;
		}
		$fields = new Fields($this->key);
		
		$query = Jira::GetStructureQuery($objects);
		$tickets =  Jira::FetchTickets($query,$fields);
		$tree = Jira::StructureTree($objects,$tickets);
		

	    $this->ProcessTree($tree);
		$this->ShowTree($tree);
		//foreach($tickets as $key=>$ticket)
		//{
		//	dump($ticket->key);
		//	dump($ticket->assignee);
		//}
		//$jsontree = json_encode($tree);
		//$result = $s3Client->putObject([
        //'Bucket' => 'eps.mentorcloudservices.com',
        //'Key'    => 'sos/test.json',
        //'Body'   => $jsontree,
        //'ACL'    => 'public-read'
		//]);
		//$tj =  new Tj();
		$dt = new \DateTime();
		
		file_put_contents("job.txt",$dt->format('Y-m-d h:i:s'));
		dump("Done");
    }
}
