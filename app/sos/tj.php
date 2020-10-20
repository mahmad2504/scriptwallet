<?php
namespace App\sos;
use App;
class Tj
{
	public function __construct($project,$ticketlist)
	{
		$this->project=$project;
		$this->ticketlist = $ticketlist;
		$header = $this->FlushProjectHeader($project);
		$header .= $this->FlushResourceHeader($project->resourcetable);
		$header .= $this->FlushTask($project);
		$header .= $this->FlushReportHeader();
		file_put_contents($project->datapath."/plan.tjp",$header);
		//dump($header);
		//dd($header);
	}
	function FlushProjectHeader($project)
	{		
		$project = $this->project;
		$today = strtotime(Date("Y-m-d"));
		$start = strtotime($project->start);
		$end = strtotime($project->end);
		if($start > $today)
		{
			$header =  'project acs "'.$project->name.'" '.$project->start;
		}
		else
		{
			if($end > $today)
				$header =  'project acs "'.$project->name.'" '.Date("Y-m-d");
			else
				$header =  'project acs "'.$project->name.'" '.$project->end;
		}
		$header = $header." +48m"."\n";
		$header = $header.'{ '."\n";
		$header = $header.'   timezone "Asia/Karachi"'."\n";
		$header = $header.'   timeformat "%Y-%m-%d"'."\n";
		$header = $header.'   numberformat "-" "" "," "." 1 '."\n";
		$header = $header.'   currencyformat "(" ")" "," "." 0 '."\n";
		//$header = $header.'   now 2019-10-01-01:00'."\n";
		$header = $header.'   currency "USD"'."\n";
		$header = $header.'   scenario plan "Plan" {}'."\n";
		$header = $header.'   extend task { text Jira "Jira"}'."\n";
		$header = $header.'} '."\n";
		return $header;
	}
	function FlushResourceHeader($resourcetable)
	{

		$header =  "macro allocate_developers ["."\n";	
		foreach($resourcetable as $resource)
		{
			if($resource->active)
			{
				$header = $header."   allocate ".$resource->user."\n";
			}
		}
		$header = $header."]"."\n";
		$header = $header.'resource all "Developers" {'."\n";
		foreach($resourcetable as $resource)
		{
			if($resource->active)
			{
				$name = $resource->user;
				$header = $header.'    resource '.$name.' "'.$name.'" {'."\n";
				$weekhours = $resource->eff/100*40;
				$header = $header.'        limits { weeklymax  '.$weekhours.'h}'."\n";
				$header = $header.'    }'."\n";
			}
		}
		$header = $header.'}'."\n";
		return $header;
	}
	function FlushTask($task)
	{	
		$tname = trim($task->extid)." ".substr($task->summary,0,10);
		$pos  = strpos($task->summary,'$');// Task name with $ sign causes schedular error
		if($pos !== false)
			$taskname = str_replace("$","-",$task->summary);
		else
			$taskname = $task->summary;
		$first = substr($task->summary,0);
	
		$pos  = strpos($taskname,';');// Task name with ; sign causes schedular error
		if($pos !== false)
			$taskname = str_replace(";","-",$taskname);
	
		$pos  = strpos($taskname,'(');// Task name with ( sign causes schedular error
		if($pos !== false)
			$taskname = str_replace("(","-",$taskname);
		
		$pos  = strpos($taskname,'\\');// Task name with \ sign causes schedular error
		if($pos !== false)
			$taskname = str_replace("\\","-",$taskname);
		
		$taskname = str_replace('"',"'",$taskname);
		$taskname = trim($task->extid)." ".substr($taskname,0,15);
		$header = "";
		$spaces = "";
		for($i=0;$i<$task->level-1;$i++)
			$spaces = $spaces."     ";
		$tag = str_replace(".", "a", $task->extid);
		$header = $header.$spaces.'task t'.$tag.' "'.$taskname.'" {'."\n";
		
		if($task->isparent == 0)
			$header = $header.$spaces."   complete ".round($task->progress,0)."\n";
		
		$dheader = $this->DependsHeader($task);
		
		if($dheader != null)
			$header = $header.$spaces."   depends ".$dheader."\n";
		
		
		$remffort =$task->remaining/(60*60*8);
		if(isset($task->duplicates))
			$remffort=0;
		//if(isset($task->isexcluded)||($task->duplicate==1))
		//{
		//	$remffort = 0;
		//}
		//if(($task->_startconstraint!=null)&&($remffort > 0))
		//{
		if(isset($task->start))
		{
			if(strtotime($task->start) > strtotime(Date("Y-m-d")))
				$header = $header.$spaces."   start ".$task->start."\n";
		}
		//}
		if($task->isparent == 0)
		{
			$header = $header.$spaces.'   Jira "'.$task->key.'"'."\n";
			$header = $header.$spaces.'   priority '.$task->schedule_priority."\n";
			if($task->cestimate > 0)
			{
				
				if( ($remffort > 0)&&($task->statuscategory != 'resolved'))
				{
					if( $remffort < .125)
						$remffort  = .125;
					
					$header = $header.$spaces."   effort ".$remffort."d"."\n";
					$header = $header.$spaces."   allocate ".$task->assignee['displayName']."\n";
				}
			}
		}
		foreach($task->children as $stask)
			$header = $header.$this->FlushTask($stask);
		
		$header = $header.$spaces.'}'."\n";
		return $header;
		//dd($header);
		//dd($taskname);
	}
	function DependsHeader($task)
	{
		$header = "";
		$keys = Project::GetDependsOn($task);
		$task->dependson = $keys;
		if(count($keys) > 0)
		{
			$del = "";
			$count = count(explode(".",$task->extid));
			$pre = "";;
			while($count--)
				$pre = $pre."!";
			
			foreach($keys as $skey)
			{
				//depends !!!t1.t1a1.t1a1a1,!!!t1.t1a2.t1a2a1 
				//echo $stask->ExtId." ";
				if(!array_key_exists($skey,$this->ticketlist))
				{
					ConsoleLog("Dependency ".$skey." for ticket ".$task->key." is not in plan");
					continue;
				}
				if($this->ticketlist[$skey]->statuscategory == 'resolved')
					continue;
				
				$stask = $this->ticketlist[$skey];
				$post = "";
				$codes = explode(".",$stask->extid);
				$lastcode = "";
				for($i=0;$i<count($codes);$i++)
				{
					if($i == 0)
					{
						$lastcode = "t".$codes[$i];
						$post = $lastcode;
					}
					else
					{
						$lastcode = $lastcode."a".$codes[$i];
						$post  =  $post.".".$lastcode;
					}
				}
				$header = $header.$del.$pre.$post;
				$del=",";
				//echo $stask->ExtId." ";
				//echo "[".$pre.$post."]";
				//echo EOL;
			}
			return $header;
		}
		else
			return null;
	}
	function FlushReportHeader()
	{
		
		$header =
		# Now the project has been specified completely. Stopping here would
		# result in a valid TaskJuggler file that could be processed and
		# scheduled. But no reports would be generated to visualize the
		# results.
		
		
		
		# A traditional Gantt chart with a project overview.
		
		"
		
		#taskreport monthreporthtml \"monthreporthtml\" {
		#	formats html
		#	columns bsi, name, start, end, effort,resources, complete,Jira, monthly
			# For this report we like to have the abbreviated weekday in front
			# of the date. %a is the tag for this.
		#	timeformat \"%a %Y-%m-%d\"
		#	loadunit hours
		#   hideresource @all
		#}
		
		#taskreport monthreport \"monthreport\" {
		#	formats csv
		#	columns bsi { title \"ExtId\" },name, start { title \"Start\" }, end { title \"End\" }, resources { title \"Resource\" }, monthly
			# For this report we like to have the abbreviated weekday in front
			# of the date. %a is the tag for this.
		#	timeformat \"%Y-%m-%d\"
		#	loadunit hours
		#	hideresource @all
		#}
		
		#taskreport weekreporthtml \"weekreporthtml\" {
		#	formats html
		#	columns bsi, name, start, end, effort,resources, complete,Jira, weekly
			# For this report we like to have the abbreviated weekday in front
			# of the date. %a is the tag for this.
		#	timeformat \"%Y-%m-%d\"
		#	loadunit hours
		#	hideresource @all
		#}
		
		taskreport weekreport \"weekreport\" {
			formats csv
			columns bsi { title \"ExtId\" },name, start { title \"Start\" }, end { title \"End\" }, resources { title \"Resource\" }, weekly
			# For this report we like to have the abbreviated weekday in front
			# of the date. %a is the tag for this.
			timeformat \"%Y-%m-%d\"
			loadunit hours
			hideresource @all
		}
		
		#taskreport dayreporthtml \"dayreporthtml\" {
		#	formats html
		#	columns bsi, name, start, end, effort,resources, complete,Jira, daily
			# For this report we like to have the abbreviated weekday in front
			# of the date. %a is the tag for this.
		#	timeformat \"%Y-%m-%d\"
		#	loadunit hours
		#	hideresource @all
		#}
	
		
		#resourcereport resourcegraphhtm \"resourcehtml\" {
		#   formats html
		#   headline \"Resource Allocation Graph\"
		#   columns no, name, effort, weekly 
		   #loadunit shortauto
	       # We only like to show leaf tasks for leaf resources.
		   # hidetask ~(isleaf() & isleaf_())
		#   hidetask 1
		#   sorttasks plan.start.up
		#}
		
		resourcereport resourcegraph \"resource\" {
		   formats csv
		   headline \"Resource Allocation Graph\"
		   columns name, effort, weekly 
		   #loadunit shortauto
	       # We only like to show leaf tasks for leaf resources.
		   hidetask 1
		   #hidetask ~(isleaf() & isleaf_())
		   sorttasks plan.start.up
		}
		";
		return $header;
	}
	function ReadResourceCsv()
	{
		$data = new \stdClass();
		$header = array();
		$colcount = 0;
		$handle = FALSE;
		
		$file = $this->project->datapath."/resource.csv";
		$handle = fopen($file, "r");
		$type = 'month';
		$data->headers = new \stdClass();
		$data->resources = array();
		if($handle !== FALSE) 		
		{
			$i=0;
			while (($indata = fgetcsv($handle, 1000, ";")) !== FALSE) 
			{
				$num = count($indata);
				if($i==0)
				{
					$colcount = count($indata);
					for ($c=0; $c < $num; $c++) 
					{
						$header[] = $indata[$c];
					}
					//var_dump($header);
					$data->headers->$type = array_slice($header,5);
					$i++;
					continue;
				}
				if($colcount != $num)
				{
					ConsoleLog('TJ',"col count not same");
					exit();
					//echo "col count not same";
				}
				$obj= new \stdClass();
				$dates = array();

				for ($j=0; $j < $num; $j++) 
				{
					$value = $indata[$j];
					$hf = $header[$j];
					if($header[$j] == 'Name')
					{
						if($value == 'Developers')
						{
							$obj = null;
							break;
						}
					}
					if(trim($value) != '')
						$obj->$hf=trim($value);
				}
				if($obj != null)
					$data->resources[] = $obj;
			}
		}
		foreach($data->resources as $resource)
		{		
			$found = 0;
			foreach($this->project->resourcetable as $resourcet)
			{
				if($resource->Name ==  $resourcet->user)
				{
					$resourcet->future = $resource;
					$found = 1; 
				}
			}
			if($found == 0)
			{
				ConsoleLog("FATAL ERROR:".$resource->Name." not found in resourcetable");
				exit();
			}
		}
	}
	function ReadOutputCsv()
	{
		$data = new \stdClass();
		$header = array();
		$colcount = 0;
		$handle = FALSE;
		
		$file = $this->project->datapath."/weekreport.csv";
		$handle = fopen($file, "r");
		$type = 'month';
		$data->headers = new \stdClass();
		$data->tasks = array();
		if($handle !== FALSE) 		
		{
			$i=0;
			while (($indata = fgetcsv($handle, 1000, ";")) !== FALSE) 
			{
				$num = count($indata);
				if($i==0)
				{
					$colcount = count($indata);
					for ($c=0; $c < $num; $c++) 
					{
						$header[] = $indata[$c];
					}
					//var_dump($header);
					$data->headers->$type = array_slice($header,5);
					$i++;
					continue;
				}
				if($colcount != $num)
				{
					ConsoleLog('TJ',"col count not same");
					exit();
					//echo "col count not same";
				}
				$obj= new \stdClass();
				$dates = array();

				for ($j=0; $j < $num; $j++) 
				{
					$value = $indata[$j];
					$hf = $header[$j];
					if($header[$j] == 'Resource')
					{
						$resource = explode("(",$value);
						if( count($resource) > 1)
						{
							$res = explode(")",$resource[1]);
							$value = $res[0];
						}
						else
							$value = $resource[0];
						$obj->$hf=$value;
					}
					else if($header[$j] == 'Start')
						$obj->$hf=$value;
					else if($header[$j] == 'End')
						$obj->$hf=$value;
					else if($header[$j] == 'ExtId')
					{
						$obj->$hf=$value;
						
					}
					else if($header[$j] == 'Name')
					{
						//echo $value."<br>";
						$value = explode(' ',trim($value))[0];
						//$obj->$header[$j]=$value;
						//echo $value."<br>";
						if($obj->ExtId!==$value)
						{
							//echo "----------->".$obj->ExtId," ".$value."<br>";
							$obj->ExtId = $value;
						}
						$data->tasks[$obj->ExtId] = $obj;
					}
					else
					{
						$dates[] = $value;
					}
				}
				$obj->$type = [];
				for($i=0; $i< count($data->headers->$type); $i++)
					$obj->$type[$data->headers->$type[$i]] = $dates[$i];
				//$obj->$type = $dates;
				$i++;
			}
			fclose($handle);
		}
		return $data->tasks;
		
	}
	function Execute()
	{
		//." 2>&1"
		$project = $this->project;
		ConsoleLog('Wait::Generating Schedule ...');

		$cmd = "tj3 -o '".$project->datapath."'  '".$project->datapath.'/plan.tjp'."' 2>&1";
		if(App::runningInConsole())
			$cmd = "public\\tj3 -o ".$project->datapath."  ".$project->datapath.'/plan.tjp'." 2>&1";
		
		//echo $cmd;
		exec($cmd,$result);
		$pos1 = strpos($result[0], 'Error');
		if ($pos1 != false)
		{
			ConsoleLog(time(),'Error::'.$result[0]);
			exit();
		}
		ConsoleLog('Schedule Created Successfully');
		$resource_data =  $this->ReadResourceCsv();
		$scheduled_data =  $this->ReadOutputCsv();
		foreach($this->ticketlist as $ticket)
		{
			$extid = $ticket->extid;
			if(!array_key_exists($extid,$scheduled_data))
			{
				ConsoleLog($ticket->key.' have sceduling issues');
				$ticket->sched_start = '';
				$ticket->sched_end = '';
				$ticket->sched_assignee = '';
				$ticket->schedule = '';	
			}
			else
			{
				$ticket->sched_start = $scheduled_data[$extid]->Start;
				$ticket->sched_end = $scheduled_data[$extid]->End;
				$ticket->sched_assignee = $scheduled_data[$extid]->Resource;
				$ticket->schedule = $scheduled_data[$extid]->month;
			}
		}
		return;
	}
}