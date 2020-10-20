<?php

namespace App\Http\Controllers\sos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Database;
use App;
use App\sos\Project;
use Redirect,Response, Artisan;
use Carbon\Carbon;

class CommandController extends Controller
{
	private $serverurl=[];
	public function Sync($user,$projectid,Request $request)
	{
		//$serverurl['mahmad']='https://script.google.com/macros/s/AKfycbyJjENO0HSdA_8Bcx_zGnJCywzIii-4ArYEcev0BXFS-YbKioWD/exec';
		$serverurl['mahmad']='https://script.google.com/macros/s/AKfycbxQwtkZy4OwbsyQaAFEY-6zw7gjOz9MpCH1ShysBL_EvvFocIg/exec';
		if(!isset($serverurl[$user]))
		{
			dump("Please check url");
			dump("For any help or account information, Please send an email to mumtaz_ahmad@mentor.com");
			return;
		}
		
		$url=$serverurl[$user]."?func=getjob&id=".$projectid;
		$settings = json_decode(file_get_contents($url));
		
		if(!isset($settings->name))
		{
			ConsoleLog("Project Not Found");
			return;
		}
		$settings->user = $user;
		$settings->project_url = $serverurl[$user];
		$project = new Project($settings);
		ConsoleLog("Processing ".$project->name);
		if($request->configure == 1)
		{
			$project->ConfigureJiraFields();
		}
		$project->Sync($request->rebuild);
	}
	private function FormatForGantt($ticket,$tasklist)
    {
		$row['pID'] = $ticket->extid;
		$row['pName'] = $ticket->summary;
		$row['pDepend'] = '';
		
		if(count($ticket->dependson)>0)
		{
			$del = "";
			foreach($ticket->dependson as $key)
			{
			   if(isset($tasklist[$key]))
			   {
					$predecessor = $tasklist[$key];
					if($predecessor->statuscategory == 'resolved')
						continue;
					
					$row['pDepend'] =  $row['pDepend'].$del.$predecessor->extid;
					$del = ",";
			   }
			   else 
					dd($key." dependency not found");
			}
		}
		$row['pParent'] = $ticket->pextid;
		if(!isset($ticket->sched_start)) // Must be a duplicate task
		{
			$dticket = $ticket->duplicates;
			$row['pStart'] = $dticket->sched_start;
			dump($dticket->sched_end);
			$row['pEnd'] = $dticket->sched_start; 
			$row['sEnd'] = $dticket->sched_end;
			$row['dup'] =  1;
			$row['pRes'] = $dticket->sched_assignee=='unassigned'?'':$dticket->sched_assignee;
		}
		else
		{
			$row['pStart'] = $ticket->sched_start;
			$row['pEnd'] = date('Y-m-d', strtotime($ticket->sched_end." + 1 day")); 
			$row['sEnd'] = $ticket->sched_end;
			$row['pRes'] = $ticket->sched_assignee=='unassigned'?'':$ticket->sched_assignee;
		}
		$row['pPlanStart'] =  "";
		$row['pPlanEnd'] =  "";
	
		$row['pIssuesubtype'] = 'DEV';
		$row['key'] = $ticket->key;
		$row['pEstimate'] = $ticket->cestimate/(60*60*8);
		
		$row['pTimeSpent'] = $ticket->spent/(60*60*8);
		if(($ticket->progress > 0)&&($ticket->cstatuscategory == 'open'))
			$ticket->cstatuscategory = 'inprogress';
		
		if( $ticket->isparent )
			$row['pClass'] = "ggroupblack";
		else
		{
			if($ticket->cstatuscategory == 'inprogress')
			{
				$row['pClass'] = 'gtaskgreen';
				if($ticket->cestimate == 0)
					$row['pClass'] = 'gtaskgreenunestimated';
			}
			else if($ticket->cstatuscategory == 'open')
			{
				$row['pClass'] = 'gtaskopen';//'gtaskblue';
				if($ticket->cestimate == 0)
					$row['pClass'] = 'gtaskopenunestimated';
			}
			else
				$row['pClass'] = 'gtaskclosed';//'gtaskblue';
		}
		$row['pLink'] = '';//'/browse/'.$ticket->key;
		$row['pMile'] = 0;
		$row['pComp'] = $ticket->progress;
		$row['pGroup'] = $ticket->isparent;
		$row['pOpen'] = 1;
		if($ticket->cstatuscategory == 'resolved')
			$row['pOpen'] = 0;
		
		$row['pCaption'] = '';
		$row['pNotes'] = 'Some Notes text';
	
		$row['pStatus'] = $ticket->cstatuscategory;
		
		$row['pPrioriy'] = $ticket->schedule_priority;
		$row['pJira'] = $ticket->key;
		if($ticket->cstatuscategory == 'resolved')
			$row['deadline'] = '';
		else
			$row['deadline'] = $ticket->duedate;
		
		$row['pClosedOn'] = $ticket->resolutiondate;
		
		$this->data[] = $row;
		foreach($ticket->children as $cticket)
		{
			$this->FormatForGantt($cticket,$tasklist);
		}
		return $this->data;
    }
	
 	public function Gantt($user,$projectid,Request $request)
    {
		$project = new Project(null,$user,$projectid);
		$data = $this->FormatForGantt($project->tree,$project->ticketlist);
		return View('sos.gantt',compact(['data']));
	}
}
