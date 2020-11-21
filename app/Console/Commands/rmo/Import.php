<?php

namespace App\Console\Commands\rmo;

use Illuminate\Console\Command;
use App\rmo\Database;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmo:import {--rebuild=0} {--beat=0}';
    protected $CONFIG_URL= 'http://script.google.com/macros/s/AKfycbwDZwi7VFTYKey7LfVMY9MbNlhVyyueMke0POzfzLmws24fcT4/exec';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$rebuild = $this->option('rebuild');
		$beat = $this->option('beat');
		
		$url = $this->CONFIG_URL; 
		$db = Database::Init();
		echo "Importing Projects\r\n";
		if(Database::IsCollectionExists('projects')==0)
			$rebuild=1;
		
		if($rebuild > 0)
			$data = file_get_contents($url."?func=getprojects&force=1");
		else
			$data = file_get_contents($url."?func=getprojects");
		
		$projects = json_decode($data);
		
		if( isset($projects->status)&& Database::IsCollectionExists('projects'))
			echo "Nothing to update\r\n";
		else
		{
			foreach($projects as $project)
			{
				
				//if(count($project->manager)==0)
				if($project->closed == "1")
				{
					$project->closed = 1;
				}
				else
					$project->closed = 0;
			}
			Database::Drop('projects');
			Database::InsertMany('projects',$projects);
		}
		echo "Importing Resources\r\n";
		if($rebuild > 0)
			$data = file_get_contents($url."?func=getresources&force=1");
		else
			$data = file_get_contents($url."?func=getresources");

		$resources = json_decode($data);
		if(isset($resources->status)&&Database::IsCollectionExists('resources'))
			echo "Nothing to update\r\n";
		else
		{
			Database::Drop('resources');
			Database::InsertMany('resources',$resources);
		}
		//$data = file_get_contents($url."?func=notify&message=Database Updated'");
		echo "Done";
    }
}
