<?php
namespace App;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
class Database
{
	function __construct($dbname)
	{
		$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$mongoClient=new Client($server);
		$this->db = $mongoClient->$dbname;
	}
	public function UpdateDoc($coll,$query,$obj)
	{
		$options=['upsert'=>true];
		$this->db->$coll->updateOne($query,['$set'=>$obj],$options);
	}
	public function IsCollectionExists($coll)
	{
		foreach($this->db->listCollections() as $collection)
		{
			if($collection['name'] == $coll)
				return 1;
		}
		return 0;
	}
	public function DropCollection($collection=null)
	{
		if($collection == null)
			$this->db->drop();
		else
			$this->db->$collection->drop();
	}
	public function IsDbExists($db=null)
	{
		if($db==null)
			$db = self::$db;
		foreach($db->listCollections() as $collection)
		{
			return 1;
		}
		return 0;
	}
	function GetVar($var)
	{
		$query=[];
		$obj = $this->db->settings->findOne($query);
		if($obj  == null)
			return null;
		if(isset($obj->$var))
			return $obj->$var;
		return null;
	}
	function SaveVar($arr)
	{
		$query=[];
		$obj = $this->db->settings->findOne($query);
		if($obj  == null)
		{
			$obj = new \StdClass();
		}
		foreach($arr as $key=>$val)
		{
			$obj->$key=$val;
		}
		$options=['upsert'=>true];
		$this->db->settings->updateOne($query,['$set'=>$obj],$options);
	}
	public function ReadActive()
	{
		$date = new \DateTime('-7 days');
		$query =['$or' => [ ['progress' => ['$ne' =>100]],['due' => ['$gt' => $date->format('Y-m-d')]]]];
		//$query =['dayLastActivity' => ['$gt' => '2020-07-01']];

		return $this->Read($query,['due' => 1],[]);
	}
	public function Read($query,$sort=[],$projection=[],$limit=-1)
	{
		$query = $query;
		$options = ['sort' => $sort,
					'projection' => $projection,
					];
		if($limit != -1)
			$options['limit'] = $limit;
		
		$cursor = $this->db->tickets->find($query,$options);
		return $cursor;
	}
	
}