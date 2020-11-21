<?php

namespace App\rmo;
use App\rmo\Database ;
class Projects
{
	private $projects=null;
	function __construct()
	{
		$this->db = Database::Init();
		$this->collection = $this->db->projects;
	}
	function GetById($id)
	{
		if($id === null)
		  return null;
		$project = $this->collection->findOne(['closed'=>0,'id'=>$id*1],['projection'=>['_id'=>0]]);
		return $project;
	}
	function Get($user=null,$ignoreclosed=0)
	{
		if($user==null)
		{
			$query=[];
			if($ignoreclosed)
				$query=['closed'=>0];

			$cursor = $this->collection->find($query,['projection'=>['_id'=>0]]);
			return $cursor->toArray();
		}
		else
		{
			$query=['manager'=>['$in'=>[$user,'all']]];
			if($ignoreclosed)
				$query['closed'] = 0;

			$cursor = $this->collection->find($query,['projection'=>['_id'=>0]]);
			$projects = $cursor->toArray();
			if( count($projects) ==0) 
			{
				{
					dump("202 Unauthroized Access");
					dd("Please send email to mumtaz_ahmad@mentor.com for an account");
				}
			}
			return $projects;
		}
	}
}
