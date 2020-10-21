<?php
namespace App\sos;
use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Aws\S3\S3Client;  
use App;
use App\sos\Project;
class Accounts
{
	public function __construct()
	{
		$this->serverurl['mahmad']='https://script.google.com/macros/s/AKfycbxQwtkZy4OwbsyQaAFEY-6zw7gjOz9MpCH1ShysBL_EvvFocIg/exec';		
	}
	public function CreateProject($user,$settings)
	{
		if(!isset($settings->name))
		{
			dump("Project Not Found");
			return null;
		}
		$settings->user = $user;
		$settings->project_url = $this->serverurl[$user];
		$project = new Project($settings);
		ConsoleLog("Processing ".$project->name);
		return $project;
	}
	public function LoadProject($user,$projectid)
	{
		if(!isset($this->serverurl[$user]))
		{
			dump("Account not found");
			dump("For any help or account information, Please send an email to mumtaz_ahmad@mentor.com");
			return null;
		}	
		$url=$this->serverurl[$user]."?func=getjob&id=".$projectid;
		$settings = json_decode(file_get_contents($url));
		$project = $this->CreateProject($user,$settings);
		return $project;
	}
	public function Get()
	{
		$data = [];
		foreach($this->serverurl as $user=>$url)
		{
			$url=$this->serverurl[$user]."?func=getall";
			$data[$user] = json_decode(file_get_contents($url));
		}
		return $data;
	}
}