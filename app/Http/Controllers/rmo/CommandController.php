<?php

namespace App\Http\Controllers\rmo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\rmo\Database;
use App\rmo\Projects;
use App\rmo\Resources;
use App\rmo\Calendar;
use App;
use Redirect,Response, Artisan;
use Carbon\Carbon;
use App\Ldap;
class CommandController extends Controller
{
	public function Login(Request $request)
	{
		return view('rmo.login');
	}
	public function Logout(Request $request)
	{
		$request->session()->forget('data');
		echo "Your are logged out of system";
		return redirect('rmo/login');
		//return view('login');
	}
	public function Authenticate(Request $request)
	{
		if(!isset($request->data['USER'])||!isset($request->data['PASSWORD']))
			return Response::json(['error' => 'Invalid Credentials'], 404); 
		
		$ldap =  new Ldap();
		$data = $ldap->Login($request->data['USER'],$request->data['PASSWORD']);
		
		if($data== null)
		{
			$request->session()->forget('data');
			return Response::json(['error' => 'Invalid Credentials'], 404); 
		}
		else
			$request->session()->put('data', $data);
		
		return Response::json(['result'=>'success']);
		//return $data->user_displayname;
	}
	public function Planner(Request $request)
	{
	    $data = $request->session()->get('data');
		if($data == null)
			return view('rmo\login');
		if(!isset($data->user_name))
			return view('rmo\login');
		
		$displayname=$data->user_displayname;
		$p = new Projects();
		$projects = $p->Get($data->user_name,1);
		
		$resources = new Resources();
		$resources = $resources->Get($data->user_name);
		
				$start = Carbon::now();
		$start->subDays(90);
		$end = Carbon::now();
		$end=  $end->addDays(365);
		
		//ob_start('ob_gzhandler');

		$calendar =  new Calendar($start,$end);
		$tabledata = $calendar->GetGridData();
		$fweek = (array_key_first($tabledata->weeks));
	
		$wk = explode("_",$fweek);
		if(strlen($wk[1])==1)
			$wk[1] = "0".$wk[1];
		$fweek = $wk[0].$wk[1];

		Database::Init();
		foreach($resources as $resource)
		{
			$resource->projects = [];
			$resourcdata = Database::Read('rmo',['id'=>$resource->id])->toArray();
			if(count($resourcdata)>0)
			{
				foreach($resourcdata[0]->projects as $project)
				{
					$pd = $p->GetById($project->id);
					$project->closed = 0;
					if($pd === null)
						$project->closed = 1;
					else 
						$project->closed = $pd->closed;

					
					//////////////////////////////////////////// Remove unnecessary data from projects //////////////////////////////
					$dels = [ ];
					
					foreach($project->data as $week=>$util)
					{
						$wk = explode("_",$week);
						if(strlen($wk[1])==1)
							$wk[1] = "0".$wk[1];
						$tweek = $wk[0].$wk[1];
						
						if($tweek < $fweek)
						{
							$dels[$week] = $week;

						}
						if($util==0)
							$dels[$week] = $week;
					}	
					foreach($dels as $week)
					{
						unset($project->data[$week]);
					}
				
					if(count($project->data)>0)
						$resource->projects[] = $project;
				}
								
			}
		}
		return view('rmo.planner',compact('tabledata','projects','resources'));
	}
	public function MergeData($nproject,$oprojects)
	{
		$data = [];
		foreach($oprojects as $oproject)
		{
			if($oproject->id == $nproject->id)
			{
				foreach($nproject->data as $week=>$util)
				{
					$oproject->data[$week] = $util;

				}
				
				return $oproject->data;
			}
		}
		return $nproject->data;
	}
	public function RemoveZeroData($indata)
	{
		$data = [];
		foreach($indata as $week=>$util)
		{
			if($util != 0)
				$data[$week] = $util;
		}
		return $data;
	}
	public function SaveRMO(Request $request)
	{
		Database::Init();
		$data = json_decode($request->data);
		foreach($data as $resource)
		{
			$rdata = Database::Read('rmo',['id'=>$resource->id])->toArray();
			if(count($rdata) > 0)
			{
				$rdata = $rdata[0];
				$dels = [];
				foreach ($resource->projects as $index=>$project)
				{
					$project->data = $this->RemoveZeroData($this->MergeData($project,$rdata->projects));
					if(count($project->data) == 0)
					{
					   $dels[] = $index;
					}
				}
				foreach($dels as $del)
					unset($resource->projects[$del]);
			}
			
			Database::Update('rmo',['id'=>$resource->id],$resource);
		}
		return Response::json(['status' => 'OK']); 
	}
	public function Projects(Request $request)
	{
		$projects = new Projects();
		return $projects->Get();
	}
	public function Resources(Request $request)
	{
		$data = $request->session()->get('data');
		$resources = new Resources();
		if($data == null)
			return $resources->Get();
		else
			return $resources->Get($data->user_name);
	}
}
