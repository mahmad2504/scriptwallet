<?php
namespace App\Console\Commands\sos;

class Tj
{
	public function __construct($tree)
	{
		//$header = $this->FlushProjectHeader($tree);
	}
	function FlushProjectHeader(Project $project)
	{
		$today = Date("Y-m-d");
		$start = $project->sdate;
		$end  =  $project->edate;
	
		if($end == null) // No end defined so schedule from start or from today
		{
			if(strtotime($start) < strtotime($today))
				$header =  'project acs "'.$project->name.'" '.$today;
			else
				$header =  'project acs "'.$project->name.'" '.$start;
		}
		else
		{
			if(strtotime($start) > strtotime($today))
			{
				$header =  'project acs "'.$project->name.'" '.$start;
			}
			else
			{
				if(strtotime($end) > strtotime($today))
					$header =  'project acs "'.$project->name.'" '.$today;
				else
					$header =  'project acs "'.$project->name.'" '.$end;
			}
		}
		$header = $header." +48m"."\n";
		$header = $header.'{ '."\n";
		$header = $header.'   timezone "Asia/Karachi"'."\n";
		$header = $header.'   timeformat "%Y-%m-%d"'."\n";
		$header = $header.'   numberformat "-" "" "," "." 1 '."\n";
		$header = $header.'   currencyformat "(" ")" "," "." 0 '."\n";
		//$header = $header.'   now 2019-10-01-01:00'."\n";
		$header = $header.'   currency "USD"'."\n";
		$header = $header.'   scenario plan "Plan" {}'."\n";
		$header = $header.'   extend task { text Jira "Jira"}'."\n";
		$header = $header.'} '."\n";
		return $header;
	}
}