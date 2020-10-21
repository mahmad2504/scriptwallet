<?php

namespace App\Console\Commands\sos;

use Illuminate\Console\Command;
use App;
use App\sos\Accounts;
class Sync extends Command
{
	/**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'sos:sync {--configure=0}  {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates sos projects';

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
     * @return int
     */
	 public function handle()
	 {
		$beat = $this->option('beat');
		if(($beat % 1440)!=0)
			return;
		
		$accounts =  new Accounts();
		foreach($accounts->Get() as $user=>$projects)
		{
			foreach($projects as $settings)
			{
				if( ($settings->auto_update == 'Off')||($settings->auto_update == ''))
					continue;
				$project = $accounts->CreateProject($user,$settings);
				
				if($project == null)
					continue;
				
				if($project->auto_update=="Daily")
				{
					echo "Configured for daily sync\n";
					$project->Sync(1,1);
				}
				else if($project->auto_update=="Weekly")
				{
					if(date('D') == ('Sun')) 
					{ 
						echo "Configured for weekly sync\n";
						$project->Sync(1,1);
					}
				}
			}
		}
	}
	
}
