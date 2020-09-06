<?php

namespace App\Http\Controllers\zaahmad;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Database;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class CommandController extends Controller
{
 	public function UpdatEpics()
    {
		Artisan::queue('zaahmad:updateepics', []);
	}
	public function UpdatSupportMatric(Request $request)
    {
		if(isset($request->rebuild))
			Artisan::queue('zaahmad:updatsupportmatric', ['--rebuild'=>$request->rebuild]);
		else
			Artisan::queue('zaahmad:updatsupportmatric', []);
	}
	public function ShowGraphdata(Request $request)
	{
		$end  = new Carbon('now');
		$start  = new Carbon('now');
		$start = $start->addDays(-90);
		
		if($request->start != null)
			$start = new Carbon($request->start);
		$start->hour = 0;
		$start->minute = 0;
		if($request->end != null)
			$end =  new Carbon($request->end);
		$end->hour = 23;
		$end->minute = 59;
		
		$issuetypes = [];
		$components = [];
		
		if($request->components != null)
			$components = explode(",",$request->components);
		
		if($request->issuetypes != null)
			$issuetypes = explode(",",$request->issuetypes);
		
		$graphdata1 = $this->GetData('volsup',[],[],$start->getTimeStamp(),$end->getTimeStamp());
		$graphdata2 = $this->GetData('vstarmod',[],[],$start->getTimeStamp(),$end->getTimeStamp());
		$start = $start->format('Y-m-d');
		$end = $end->format('Y-m-d');
		return view('zaahmad.supportmatric.index',compact('graphdata1','graphdata2','start','end'));
	}
	public function GetGraphData(Request $request,$project)
	{
		$end  = new Carbon('now');
		$start  = new Carbon('now');
		if($request->start != null)
			$start = new Carbon($request->start);
		$start->hour = 0;
		$start->minute = 0;
		if($request->end != null)
			$end =  new Carbon($request->end);
		$end->hour = 23;
		$end->minute = 59;
		
		$issuetypes = [];
		$components = [];
		
		if($request->components != null)
			$components = explode(",",$request->components);
		
		if($request->issuetypes != null)
			$issuetypes = explode(",",$request->issuetypes);
		
		$graphdata = $this->GetData($project,$components,$issuetypes,$start->getTimeStamp(),$end->getTimeStamp());
		return $graphdata;
	}
	public function GetData($project,$components,$issuetypes,$start,$end)
	{
		$c =[ 'ctw'=>1,'tc'=>2,'rtw'=>3,'tr'=>4,'drtw'=>5,'trd'=>8,
		'dctw'=>7,'tdc'=>6];
		
		$db = new Database(env("IESD_SUPPORT_COLLECTION"));
		
		$query = [
			'project'=>$project,
			'$or'=>[
					['created'=>['$gte'=> $start,'$lte'=>$end]],
					['resolutiondate'=>['$gte'=> $start,'$lte'=>$end]],
		       ]
		    ];
		if(count($components)>0)
		{
			$query['components']= ['$in'=>$components];
		}
		
		if(count($issuetypes)>0)
		{
			$query['issuetype']= ['$in'=>$issuetypes];
		}
		//dump($query);
		//$query = 
		//	['$or'=>[ ['created'=>$startts->getTimeStamp()],['resolutiondate'=> $startts->getTimeStamp()]]];
		//dump($query);
		$records=$db->Read($query)->toArray();
		$first_response = [['<1',0,0,0,0],['1-5',0,0,0,0],['5-10',0,0,0,0],['>10',0,0,0,0]];
		$today = new Carbon('now');	
		$graphdata = [];		
		foreach($records as $record)
		{
			$record = $record->jsonSerialize();
			$record->components = $record->components->jsonSerialize();
			unset($record->timetracking);
			unset($record->outwardIssue);
			unset($record->inwardIssue);
			$carbon = new Carbon();
			$created = $carbon->setTimeStamp($record->created);
			
			$carbon = new Carbon();
			$first_contact = $carbon->setTimeStamp($record->first_contact);
			$record->firsresponse = get_working_minutes($created,$first_contact);
			if(($record->priority >= 4)||($record->priority==null))
				$record->priority = 4;
			
			if($record->firsresponse <= 8*60)
				$first_response[0][$record->priority]++;
			else if(($record->firsresponse > 8*60) && ($record->firsresponse <= 8*5*60))
			{
				$first_response[1][$record->priority]++;
			}
			else if(($record->firsresponse > 8*5*60) && ($record->firsresponse <= 8*10*60))
			{
				$first_response[2][$record->priority]++;
			}
			else
			{
				$first_response[3][$record->priority]++;
			}
			/////////////////////////////////////////////////////////////////////////////////
			
			if(($record->created >= $start)&&($record->created  <= $end))
			{
				$week = $created->next(Carbon::SUNDAY)->format('Y-m-d');
				if($created->next(Carbon::SUNDAY)->getTimeStamp() > $today->getTimeStamp())
				{
					$week = $today->format('Y-m-d');
				}
				$week = $week." 00:00";	
				if(isset($graphdata[$week]))
					$graphdata[$week];
				else
					$graphdata[$week] = [$week,0,0,0,0,0,0,0,0];
				
				$graphdata[$week][$c['ctw']]++;
				if($record->issuetype=='issue')
				{
					if(($record->status == 'new')||($record->status == 'in analysis')
						||($record->status == 'rejected')||($record->status == 'duplicate'))
					{}
					else
						$graphdata[$week][$c['dctw']]++;

				}
			}
			//////////////////////////////////////////////////////////////////////////////////
			if($record->resolutiondate != null)
			{
				if(($record->resolutiondate >= $start)&&($record->resolutiondate <= $end))
				{	
					$resolutiondate = $carbon->setTimeStamp($record->resolutiondate);
					$week = $resolutiondate->next(Carbon::SUNDAY)->format('Y-m-d');
					if($resolutiondate->next(Carbon::SUNDAY)->getTimeStamp() > $today->getTimeStamp())
					{
						$week = $today->format('Y-m-d');
					}
					$week = $week." 00:00";	
					if(isset($graphdata[$week]))
						$graphdata[$week];
					else
						$graphdata[$week] = [$week,0,0,0,0,0,0,0,0];
					$graphdata[$week][$c['rtw']]++;
					if($record->issuetype=='issue')
					{
						if(($record->status == 'new')||($record->status == 'in analysis')
						||($record->status == 'rejected')||($record->status == 'duplicate'))
						{}
						else
							$graphdata[$week][$c['drtw']]++;
					}
					if($record->reason_for_closure == "defect/bug")
						$graphdata[$week][$c['drtw']]++;
				}
			}
		}
		ksort($graphdata);
		$created = 0;
		$resolved = 0;
		$defects_resolved = 0;
		$defects_created = 0;
		$backlogdata = [];
		$bl = 0;
		$dbl = 0;
		foreach($graphdata as $week=>&$record)
		{
			$resolved = $resolved + $record[$c['rtw']];
			$created = $created + $record[$c['ctw']];
			$defects_resolved = $defects_resolved + $record[$c['drtw']];
			$defects_created = $defects_created + $record[$c['dctw']];
			
			$record[$c['tc']] = $created;
			$record[$c['tr']] = $resolved;
			$record[$c['trd']] = $defects_resolved;
			$record[$c['tdc']] = $defects_created;
			$bl = $record[$c['ctw']]-$record[$c['rtw']]+$bl;
			$dbl = $record[$c['dctw']]-$record[$c['drtw']]+$dbl;
			
			$backlogdata[$week] = [$week,$record[$c['ctw']],$record[$c['rtw']],$bl,$dbl];
			
		}
		$out['gd'] = (array_values($graphdata));
		$out['fr'] = $first_response;
		$out['bl'] = (array_values($backlogdata));;
		return $out;
		//$db =  new Database();
		//$startts = $start->getTimeStamp();
		//$endts = $end->getTimeStamp();
	}
}
