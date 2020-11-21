<?php

namespace App\Console\Commands\waqar;

use Illuminate\Console\Command;
use App\Console\Commands\waqar\Email;

class NucCheckPr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waqar:nuccheckpr {--rebuild=0} {--beat=0}';
	
	protected $urls = [
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-docs/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-packaging/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-source/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tests/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tf/pull-requests'
	];
	public $datapath = "data/waqar/nuccheckpr/";
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
		date_default_timezone_set("Asia/Karachi");
		$datetime = new \DateTime();
		$rebuild = $this->option('rebuild');
		if(file_exists($this->datapath."lastemailsent")&&$rebuild==0)
		{
			$dt = file_get_contents($this->datapath."lastemailsent");
			if($dt == $datetime->format('Y-m-d'))
			{
				echo "Todays email already sent\n";	
				return;
			}
		}
		$beat = $this->option('beat');
		foreach($this->urls as $url)
		{
			$data = $this->Get($url);
			$pending_prs = [];
			$repository = null;
			foreach($data->values as $pr)
			{
				//dump($pr->title);
				$openpr =  new \StdClass();
				$openpr->title = $pr->title;
				//$openpr->description = $pr->description;
				$dt = new \DateTime();
				$dt->setTimeStamp($pr->createdDate/1000);
				SetTimeZone($dt);
				$minutes = get_working_seconds($dt,new \DateTime());
				//dump ($dt->format('Y-m-d:h:u'));
				$openpr->createdon = $dt->format('Y-m-d');
				$openpr->openduration = seconds2human($minutes);
				$openpr->state = $pr->state;
				$openpr->link = $pr->links->self[0]->href;
				//dump($pr->fromRef->repository->name);
				//https://stash.alm.mentorg.com/projects/NUC4/repos/nuc4-tf/pull-requests/76/overview
				//dump("Authors");
				$openpr->author = $pr->author->user->displayName;
				$repository = $pr->fromRef->repository->slug;
				//dump("Reviewers");
				$openpr->reviewers = [];
				foreach($pr->reviewers as $reviewer)
				{
					$r =  new \StdClass();
					$r->name = $reviewer->user->displayName;
					$r->approved = $reviewer->approved;
                    $r->email = $reviewer->emailAddress;
					$openpr->reviewers[]= $r;
				}
				$pending_prs[] = $openpr;
			}
			if(count($pending_prs)>0)
			{
				$datetime = new \DateTime();
				$html = $this->HtmlFormat($repository,$pending_prs);
				$email =  new Email($this);
				
				$email->Send('Notification:Open PR:NUC:'.$repository,$html);
				file_put_contents($this->datapath."lastemailsent",$datetime->format('Y-m-d'));
			}
		}
		echo "Done";
    }
	public function HtmlFormat($repository,$prs)
	{
		$msg = '<h2>'.$repository.'</h2>';	
		foreach($prs as $pr)
		{
		   $days = explode("day",$pr->openduration)[0];
		   if($days > 5)
			   $color = 'red';
		   else
			   $color = 'black';
		   $title = $pr->title;
		   if(strlen($pr->title)>60)
		   {
			$title = substr($pr->title, 0,60);
			$title  .= "...";
	       }
		   $msg .= '<div style="float:left;"><a href="'.$pr->link.'">'.$title.'</a></div>';
		   $msg .= '<div style="float:right;"><small>Created by '.$pr->author.' on '.$pr->createdon.'&nbsp&nbsp(<span style="color:'.$color.';">'.$days.' Days old</span>)</small></div><br>';
		   $msg .= '<div>Review Status</div>';
		   $msg .= '<div>';
		   foreach($pr->reviewers as $reviewer)
		   {
			   
			   if($reviewer->approved)
			   {
				   $msg .= '<img  src="cid:checkmark.png" alt="star" width="16" height="16">';
				   $msg .= '<span style="color:green;">'.$reviewer->name."&nbsp&nbsp</span>";
			   }
			   else
			   {
				   $msg .= '<img src="cid:incomplete.jpg" alt="star" width="16" height="16">';
				   $msg .= '<span style="color:orange">'.$reviewer->name."&nbsp&nbsp</span>";
			   }
			   
		   }
		   $msg .= '</div><br>';
		   	   
		}
		$msg .= '<br><br><hr>';
	    $msg .= '<small>This is an automatically generated email, please do not reply. If you think you should not be getting this email then please send email to mumtaz_ahmad@mentor.com </small>';
	
		return $msg;
	}
	public function Get($query)
	{
		$data = null;
		$query = str_replace(" ","%20",$query);
		$resource=$query;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => 'mahmad:NDExMTk0Njk2ODAyOts/IfG8+FgNSlBMKSxk21NIYx/U',
		CURLOPT_URL =>$resource,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($data != null)
		{
			curl_setopt_array($curl, array(
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $data
				));
		}
		$result = curl_exec($curl);
		
		$code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
		if($code == 200)
			return json_decode($result);
		return 0;
	}
}
