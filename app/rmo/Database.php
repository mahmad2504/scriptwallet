<?php
namespace App\rmo;

use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
class Database
{
	public static $db ;
	function __construct()
	{
		
	}
	public static function Init()
	{
		$dbname = env("MONGO_DB_NAME", "rmo");
		$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$mongoClient=new Client($server);
		self::$db = $mongoClient->$dbname;
		return self::$db;
	}
	public static function IsCollectionExists($collection)
	{
		foreach(self::$db->listCollections() as $c)
		{
			if($c['name'] == $collection)
				return 1;
		}
		return 0;
	}
	public static function Drop($collection)
	{
		self::$db->$collection->Drop();
	}
	public static function InsertMany($collection,$many)
	{
		self::$db->$collection->insertMany($many);
	}
	public static function Update($collection,$query,$obj)
	{
		$options=['upsert'=>true];
		self::$db->$collection->updateOne($query,['$set'=>$obj],$options);
	}
	public static  function GetVar($var)
	{
		$query=[];
		$obj = self::$db->settings->findOne($query);
		if($obj  == null)
			return null;
		if(isset($obj->$var))
			return $obj->$var;
		return null;
	}
	public static function SaveVar($arr)
	{
		$query=[];
		$obj = self::$db->settings->findOne($query);
		if($obj  == null)
		{
			$obj = new \StdClass();
		}
		foreach($arr as $key=>$val)
		{
			$obj->$key=$val;
		}
		$options=['upsert'=>true];
		self::$db->settings->updateOne($query,['$set'=>$obj],$options);
	}
	public static function Read($colection,$query,$sort=[],$projection=[],$limit=-1)
	{
		$query = $query;
		$options = ['sort' => $sort,
					'projection' => $projection,
					];
		if($limit != -1)
			$options['limit'] = $limit;
		
		$cursor = self::$db->$colection->find($query,$options);
		return $cursor;
	}
	
}