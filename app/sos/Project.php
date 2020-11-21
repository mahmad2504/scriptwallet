<?php
namespace App\sos;
use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Aws\S3\S3Client;  
use App;
class Project
{
	public $ticketlist=[];
	private $worklogs = [];
	private $savedtickets = [];
	private $resources = [];
	private $schedule_priority=1000;
	function SetDefaults($ticket)
	{
		if(!isset($ticket->children))
			$ticket->children =[];
		if(!isset($ticket->resources))
			$ticket->resources = [];
		if(!isset($ticket->worklogs))
			$ticket->worklogs = [];
		if(isset($ticket->assignee))
			$ticket->assignee['displayName'] = $this->FixResourceName($ticket->assignee['displayName']);
		
		
		if(isset($ticket->duedate))
		{
			if($ticket->duedate != '')
			{
				$duedate =  new \DateTime();
				$duedate->setTimestamp($ticket->duedate);
				$duedate = $duedate->format('Y-m-d');
				$ticket->duedate = $duedate;
			}
		}
		else
			$ticket->duedate = '';
		
		if(isset($ticket->resolutiondate))
		{
			if($ticket->resolutiondate != '')
			{
				$resolutiondate =  new \DateTime();
				$resolutiondate->setTimestamp($ticket->resolutiondate);
				$resolutiondate = $resolutiondate->format('Y-m-d');
				$ticket->resolutiondate = $resolutiondate;
			}
		}
		else
			$ticket->resolutiondate = '';
		
		if( isset($ticket->end)&& $ticket->duedate == '')
		{
			$ticket->duedate=$ticket->end;
		}
			
		if(!isset($ticket->start))
			$ticket->start ='';
		if(!isset($ticket->end))
			$ticket->end ='';
		if($ticket->duedate > $this->project->end)
			ConsoleLog($ticket->key." has due date beyond project end date");
		
		if(!isset($ticket->key))
		{
			$ticket->timespent = 0;
			$ticket->timeremainingestimate = 0;
			$ticket->timeoriginalestimate=0;
			$ticket->estimation = 'none';
			$ticket->statuscategory = 'resolved';
			$ticket->isparent=0;
			$ticket->schedule_priority=0;
		}
	}
	function  BuildTicketList($ticket)
	{
		if(!isset($ticket->duplicates))
			$this->ticketlist[$ticket->key]=$ticket;
		
		foreach($ticket->children as $sticket)
		{
			$this->BuildTicketList($sticket);
		}
		return $this->ticketlist;
	}
	function __construct($settings=null,$user=null,$id=null)
	{
		if($settings == null)
		{
			if (App::runningInConsole())
			{
				$path = 'data/sos/'.$user."/".$id."/tree.json";
			}
			else
			{
				$path = '../data/sos/'.$user."/".$id."/tree.json";
			}
			$this->tree = json_decode(file_get_contents($path));
			$this->ticketlist = $this->BuildTicketList($this->tree);
			
			return ;
		}
		foreach($settings->queries as $query)
		{
			if( strlen(trim($query->jql))==0)
			{
				dd("Jira Query is empty");
			}
			$query->links = [];
			foreach(explode("\n",$query->params) as $keyvalue )
			{
				$param = explode("=",$keyvalue);
				if(strtolower($param[0]) == 'link')
					$query->links[] = $param[1];
				else if(strtolower($param[0]) == 'alternate')
					$query->alternate=$param[1];
				
			}
			unset($query->params);
		}
		
		$this->project = $settings;
		$start = new \DateTime($settings->start);
		$end = new \DateTime($settings->end);
		
		// Set Defaults
		//$this->SetDfaults($this->project);
		$this->project->key = 'project';
		
		if($start->getTimeStamp() > $end->getTimeStamp())
		{
			ConsoleLog("Project end is earlier than start");
			exit();
		}
		$namespace = (substr(__NAMESPACE__, strrpos(__NAMESPACE__, '\\') + 1));
		if (App::runningInConsole())
		{
			$this->folder = 'data/'.$namespace."/".$settings->user."/".$settings->id;
		}
		else
		{
			$this->folder = '../data/'.$namespace."/".$settings->user."/".$settings->id;
		}
		$this->project->datapath = $this->folder;
		$this->fieldkey = $namespace."/fields_".$settings->server;
	}
	public function __get($value)
	{
		if(isset($this->project->$value))
			return $this->project->$value;
	}
	public function ConfigureJiraFields()
	{
		ConsoleLog("Configuring Jira fields");
		Jira::Init($this->server);
		$fields = new Fields($this->fieldkey);
		$fields->Set(['story_points'=>'Story Points']);
		$fields->Set(['summary','updated','duedate','timespent','timeremainingestimate','timeoriginalestimate','id','assignee','created','issuetype','resolution','resolutiondate','project','status','statuscategory','timetracking']);
		$fields->Dump();
		dump($fields);
	}
	private function FixResourceName($name)
	{
		$name =str_replace(" ","_",$name);
		$name =str_replace(",","_",$name);
		$name =str_replace("__","_",$name);
		$name = str_replace('_[X]','',$name);
		$name =explode('@',$name)[0];
		return $name;
	}
	public static function GetNextLink($task,$type)
	{
		$output = [];
		if(isset($task->outwardIssue))
		{
			$outwardIssues = $task->outwardIssue;
			foreach($outwardIssues as $link=>$keys)
			{
				if($link == $type)
					$output = array_merge($output,$keys);
				
			}
		}
		if(isset($task->inwardIssue))
		{
			$inwardIssues = $task->inwardIssue;
			foreach($inwardIssues as $link=>$keys)
			{
				if($link == $type)
					$output = array_merge($output,$keys);
				
			}
		}
		return $output;
	}
	public static function GetTestedBy($task)
	{
		if(!isset($task->inwardIssue))
			return [];
		$inwardIssues = $task->inwardIssue;
		foreach($inwardIssues as $inwardIssue)
		{
			foreach($inwardIssues as $link=>$keys)
			{
				if($link == 'is tested by')
					return $keys;
				
			}
		}
		return [];
	}
	public static function GetDependsOn($task)
	{
		if(!isset($task->outwardIssue))
			return [];
		$outwardIssues = $task->outwardIssue;
		
		foreach($outwardIssues as $outwardIssue)
		{
			foreach($outwardIssues as $link=>$keys)
			{
				if($link == 'depends on')
					return $keys;
			}
		}
		return [];
	}
	private function Process($ticket,$level=0,$firtcall=1)
	{
		$str = '';
		$ticket->level = $level;
		for($i=0;$i<$level;$i++)
			$str .= "---";
		
		$this->SetDefaults($ticket);

		
		if($level == 0)
		{
			$ticket->key = 'project';
			$ticket->extid = '1';
			$ticket->summary = $ticket->name;
			$ticket->pextid = 0;
			$this->ticketlist[$ticket->key]=$ticket;
		}
		else
		{
			if(isset($ticket->assignee))// means it is Jira task
			{
				//if($ticket->key == 'HMIP-1642')
				//	dd($ticket);
				$ticket->resources[$ticket->assignee['displayName']]=$ticket->assignee['displayName'];
				if(!isset($this->resources[$ticket->assignee['displayName']]))
					$this->resources[$ticket->assignee['displayName']]=[];
				if(isset($this->savedtickets[$ticket->key]))
				{
					$savedticket = $this->savedtickets[$ticket->key];
					if($savedticket->updated != $ticket->updated)
					{
						if($ticket->timespent > 0)
						{
							ConsoleLog("Processing ".$str.$ticket->key);
							$ticket->worklogs= Jira::WorkLogs($ticket->key);
						}
					}
					else
						$ticket->worklogs = $savedticket->worklogs;
				}
				else
				{
					if($ticket->timespent > 0)
					{
						ConsoleLog("Processingn ".$str.$ticket->key);
						$ticket->worklogs= Jira::WorkLogs($ticket->key);
					}
				}
				foreach($ticket->worklogs as $worklog)
				{
					$worklog->author = $this->FixResourceName($worklog->author);
					
					$this->worklogs[$worklog->id] = $worklog;
					$worklog->ticket = $ticket->key;
					$this->resources[$worklog->author][$worklog->id]=$worklog;
					$ticket->resources[$worklog->author]=$worklog->author;
				}
			}
			else
			{
				//if(isset($ticket->jql))
				//	ConsoleLog("Query    ".$str.$ticket->jql);
			}
		}
		$level++;
		$i=0;
		$ticket->isparent=0;
		$ticket->estimate=0;
		$ticket->remaining=0;
		$ticket->spent = 0;
		$ticket->cestimate=0;
		$ticket->schedule_priority = 0;
		if(count($ticket->children)==0)
		{
			if(($ticket->statuscategory == 'inprogress')&&($ticket->isparent == 0))
			{
				$ticket->schedule_priority = $this->schedule_priority;
				$this->schedule_priority--;
			}
			$ticket->estimation = 'time';
			if($ticket->timeoriginalestimate > 0)
			{
				$ticket->estimate = $ticket->timeoriginalestimate;
			}
			else
			{
				$ticket->estimation = 'story_points';
				$ticket->estimate = $ticket->story_points*8*60*60;
			}
			if($ticket->timeremainingestimate > 0)
				$ticket->remaining =$ticket->timeremainingestimate;
			if($ticket->timespent > 0)
				$ticket->spent=$ticket->timespent;
			
			if($ticket->statuscategory == 'resolved')
				$ticket->remaining = 0;
			
			if($ticket->estimation == 'story_points')
			{
				if($ticket->statuscategory == 'resolved')
					$ticket->spent = $ticket->estimate;
				else
					$ticket->remaining = $ticket->estimate ;
			
			}
			$ticket->cestimate = $ticket->spent+$ticket->remaining;
			$ticket->cstatuscategory = $ticket->statuscategory;
			$ticket->progress = 0;
			if($ticket->cestimate >0)
				$ticket->progress = $ticket->spent/$ticket->cestimate*100;
			if($ticket->statuscategory == 'resolved')
				$ticket->progress =100;
			$acc = 0;
			foreach($ticket->worklogs as $worklog)
			{
				$acc = $acc + $worklog->seconds;
			}
			if($acc != $ticket->spent)
			{
				if($ticket->estimation == 'time')
					ConsoleLog($ticket->key."::Time spend != accumulated time spent");
			}
			return;
		}
		$ticket->cstatuscategory = 'resolved';
		$status_count['resolved']=0;
		$status_count['open']=0;
		$status_count['inprogress']=0;
		foreach($ticket->children as $sticket)
		{
			$sticket->extid = $ticket->extid.".".$i++;
			$sticket->pextid = $ticket->extid;
			if(isset($this->ticketlist[$sticket->key]))
			{
				$sticket->duplicates=$this->ticketlist[$sticket->key];
			}
			else
				$this->ticketlist[$sticket->key]=$sticket;
			
			$ticket->isparent=1;
			$this->Process($sticket,$level,0);
			
			if(!isset($sticket->duplicates))
			{
				$ticket->estimate += $sticket->estimate;
				$ticket->remaining += $sticket->remaining;
				$ticket->spent += $sticket->spent;
				$ticket->cestimate += $sticket->cestimate;
			}
			$status_count[$sticket->cstatuscategory]++;
		}
		if($status_count['inprogress'] > 0)
			$ticket->cstatuscategory = 'inprogress';
		else if($status_count['open'] > 0)
			$ticket->cstatuscategory = 'open';
		$ticket->progress=0;
		if($ticket->cestimate > 0)
			$ticket->progress = $ticket->spent/$ticket->cestimate*100;
		
		if($ticket->cstatuscategory == 'resolved')
			$ticket->progress = 100;
		if($firtcall)
		{
			$temp = [];
			foreach($ticket->resourcetable as $obj)
			{
				$obj->active = 0;
				$obj->past = 0;
				$obj->future = new \StdClass();
				$obj->future->Effort = 0;
				$obj->baselinework = 0;
				$temp[$obj->user] = $obj;
			}
			$total = 0;
			foreach($this->resources as $user=>$worklogs)
			{
				$acc = 0;
				foreach($worklogs as $worklogid=>$worklog)
				{
					$acc  += $worklog->seconds;
				}
				if(isset($temp[$user]))
				{
					$temp[$user]->active=1;
				}
				else
				{
					$u =  new \StdClass();
					$u->user=$user;
					$u->rate=10;
					$u->eff=100;
					$u->active=1;
					$u->past = 0;
					$u->future = new \StdClass();
					$u->future->Effort = 0;
					$u->baselinework = 0;
				
					$temp[$user] = $u;
				}
				$temp[$user]->past = Round($acc/(60*60*8),1);
				$total += $acc;
			}
			$ticket->resourcetable=$temp;
			$this->ComputeFinancials($this->project);
			$ticket->resourcetable=array_values($temp);
			//dd($ticket->resourcetable);
		}
	}
	function FetchTickets($ticket)
	{
		if(isset($ticket->key))
		{
			$this->savedtickets[$ticket->key]=$ticket;
		}
		foreach($ticket->children as $sticket)
			$this->FetchTickets($sticket);
		return $this->savedtickets;
	}
	function ConvertSeconds($seconds)
	{
		$days = floor($seconds/(60*60*8));
		$hr = $seconds/(60*60)-$days*8;
		$str = '';
		if($days  > 0)
			$str = $str.$days."d ";
		if($hr > 0)
			$str = $str.$hr."h ";
		return $str;
	}
	function Show($ticket)
	{
		$estimate = $this->ConvertSeconds($ticket->estimate);
		$remaining = $this->ConvertSeconds($ticket->remaining);
		$spent =  $this->ConvertSeconds($ticket->spent);
		$cestimate = $this->ConvertSeconds($ticket->cestimate);
		$cstatuscategory = $ticket->cstatuscategory;
		$resources = implode(",",$ticket->resources);
		$progress = round($ticket->progress,1);
		$schedule_priority = $ticket->schedule_priority;
		$start=$ticket->start;
		$end=$ticket->end;
		$extid=$ticket->extid;
		$pextid=$ticket->pextid;
		$duedate = $ticket->duedate;
		if(!isset($ticket->sched_start)) // Must be a duplicate task
		{
			$dticket = $ticket->duplicates;
			$sched_end = $dticket->sched_end;
		}
		else
			$sched_end = $ticket->sched_end;
		
		echo '<tr>';
		echo '<td><b style="margin-left:'.($ticket->level*10).'px;">'.$extid.'</b></td>';
		echo '<td>'.$pextid.'</td>';
		echo '<td><b style="margin-left:'.($ticket->level*10).'px;">'.$ticket->key.'</b></td>';
		echo '<td><div style="font-size:12px;">'."&nbsp&nbsp&nbsp&nbsp".$start.":".$end.'</div></td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$estimate.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp(".$remaining.')</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$spent.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$cestimate.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$cstatuscategory.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$resources.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$progress.'</td>';
		echo '<td>'."&nbsp&nbsp&nbsp&nbsp".$schedule_priority.'</td>';
		echo '<td><div style="font-size:12px;">'.$duedate.'</div></td>';
		echo '<td>'.$sched_end.'</td>';
		
		
		echo '</tr>';
		foreach($ticket->children as $sticket)
		{
			$this->Show($sticket);
		}
	}
	function FetchLinks($task,$fields,$links)
	{
		
		if($task->issuetype == 'epic')
		{
			$query = "'Epic Link'=".$task->key;
		}
		else 
		{
			$keys = [];
			foreach($links as $link)
			{
				$k = Project::GetNextLink($task,$link);
				$keys = array_merge($keys,$k);
			}
			$query  = implode(",",$keys);
			if(strlen($query)>0)
			{
				$query  = 'key in ('.$query .')';
			}
		}
		if(strlen($query)>0)
		{
			dump($query);
			$tickets =  @Jira::FetchTickets($query,$fields);
			$task->children = array_values($tickets);
		}
		if($task->issuetype != 'epic')
		{
			if(isset($task->children))
			{
				foreach($task->children as $stask)
					$this->FetchLinks($stask,$fields,$links);
			}
		}
	}
	function Sync($rebuild=0,$auto_sync=0)
	{
		set_time_limit(2000);
		
			
		$this->project->auto_sync = $auto_sync;
		$this->project->history = new \StdClass();
		if(file_exists($this->folder."/tree.json"))
		{
			$savedtree = json_decode(file_get_contents($this->folder."/tree.json"));
			if($rebuild!=1)
				$this->savedtickets = $this->FetchTickets($savedtree);
			if(isset($savedtree->history))
				$this->project->history = $savedtree->history;
			else
				$this->project->history = new \StdClass();
			
		}
		
		Jira::Init($this->server);
		$this->children = [];
		$fields = new Fields($this->fieldkey);
		$i=0;
		foreach($this->queries as $query)
		{
			$i++;
			if(substr(trim($query->jql),0,9)=='structure')
			{
				ConsoleLog("Query ".$query->jql);
				$query->summary = $query->jql;
				if(isset($query->alternate))
					$query->summary=$query->alternate;
				$query->key='query'.$i;
				
				$objects = Jira::GetStructureObjects(explode("=",$query->jql)[1]);
				if($objects == null)
				{
					ConsoleLog("Invalid Query [".$query->jql."]");
					return -1;
				}
				$q = Jira::GetStructureQuery($objects);
				$tickets =  Jira::FetchTickets($q,$fields);
				
				$query->children = Jira::StructureTree($objects,$tickets);
				
				$this->project->children[] = $query;
			}
			else
			{
				if(Jira::ValidateQuery($query->jql))
				{
					ConsoleLog("Query ".$query->jql);	
					$query->summary = $query->jql;
					$query->issuetype = 'query';
					if(isset($query->alternate))
						$query->summary=$query->alternate;
					$query->key='query'.$i;
					$tickets =  @Jira::FetchTickets($query->jql,$fields);
					$query->children = array_values($tickets);
					$this->project->children[] = $query;
					$this->FetchLinks($query,$fields,$query->links);
				}
				else
				{
					ConsoleLog("Invalid Query [".$query->jql."]");
					return -1;
				}
			}
		}
		$this->Process($this->project);
		//dd($this->project->resourcetable);
		//$this->GetResources($this->project);
		@mkdir($this->folder,0777,true);
		$this->Schedule();
		//ConsoleLog('<table>');
		//$this->Show($this->project);
		//dd($this->project->remaining/(60*60*8));
		//ConsoleLog('</table>');
		//$this->AddHistory();
		$this->Save();		
	}
	public function ComputeFinancials($ticket)
	{
		if(isset($ticket->assignee)&&($ticket->isparent==0)&&(!isset($ticket->duplicates)))
		{
			//ConsoleLog($ticket->assignee['displayName']." ".$ticket->estimate/(60*60*8));
		    $resource = $this->resourcetable[$ticket->assignee['displayName']];
			if(!isset($resource->baselinework))
				dd($this->resourcetable);
			$resource->baselinework += $ticket->estimate/(60*60*8);
			$hours = $ticket->estimate/(60*60);
		}
		foreach($ticket->children as $sticket)
			$this->ComputeFinancials($sticket);
	}
	public function Schedule()
	{
		$tj =  new Tj($this->project,$this->ticketlist);
		$tj->Execute();
	}
	/*public function AddHistory()
	{
		dump($this->project->history);
		$history =  new \StdClass();
		$dte =  new \DateTime();
		$dte = $dte->format('Y-m-d');
		foreach($this->project as $key => $value) 
		{
			$history->project->$key = $value;
		}
		$this->project->history->$dte=$history;
	}*/
	public function Save()
	{	
		$this->project->sync_url = $url = route('sos.sync',[$this->user,$this->id]);
		$this->project->gantt_url = $url = route('sos.gantt',[$this->user,$this->id]);
		$this->project->last_sync = date("Y-m-d"); 
		$this->project->tree_url = 'https://'.env('AWS_URL').'.s3.us-west-2.amazonaws.com/sos/'.$this->user."/".$this->id."/tree.json";
		$json_data=json_encode($this->project);
		file_put_contents($this->folder."/tree.json",$json_data);
		$s3Client = new S3Client([
			//'profile' => 'default',
			'region' => 'us-west-2',
			'version'     => 'latest',
			'credentials' => [
				'key'    => env('AWS_KEY'),
				'secret' => env('AWS_SECRET'),
			]
		]);
		
		 // https://eps.mentorcloudservices.com.s3.us-west-2.amazonaws.com/staging/images/logo.png
		//https://eps.mentorcloudservices.com.s3.us-west-2.amazonaws.com/sos/mahmad/1
		//>aws s3 rm s3://eps.mentorcloudservices.com/sos --recursive
		$result = $s3Client->putObject([
        'Bucket' => env('AWS_URL'),
        'Key'    => 'sos/'.$this->user."/".$this->id."/tree.json",
        'Body'   => $json_data,
        'ACL'    => 'public-read'
		]);
		$url=$this->project_url."?func=statusupdate&id=".$this->id."&sheetname=".$this->sheetname."&tree_url=".$this->tree_url;
		file_get_contents($url);
		dump("Done");
		//dump(file_get_contents($url));
		//dd($this->project);
	}
}