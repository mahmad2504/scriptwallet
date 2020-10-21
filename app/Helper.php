<?php
use Carbon\Carbon;
use App\Database;

function wfile_get_contents($path,$filename)
{
	if (!App::runningInConsole())
	{
		$path = "../".$path.$filename;
	}
	else
		$path = $path.$filename;
	
	return file_get_contents($path);
}
function ConsoleLog($msg) 
{
	if(App::runningInConsole())
	{
		echo $msg."\n";
		return;
	}
	$msg = str_replace('"', "'", $msg);
	echo '<b>'.$msg.'<br></b>';
	ob_flush();
	flush();
}
function SetTimeZone($datetime)
{
	$datetime->setTimezone(new \DateTimeZone("Asia/Karachi"));
}
function CreateDateTime($timestamp)
{
	$dt = new DateTime();
	$dt->setTimestamp($timestamp);
	SetTimeZone($dt);
	return $dt;
}
function IssueParser($code,$issue,$fieldname)
{
	switch($fieldname)
	{
		case 'story_points':
			if(isset($issue->fields->customFields[$code]))
			{
				return $issue->fields->customFields[$code];
			}
			return 0;
			break;
		case 'sprint':
			if(isset($issue->fields->customFields[$code]))
			{
				return $issue->fields->customFields[$code];
			}
			return '';
			break;
		case 'epic_link':
			if(isset($issue->fields->customFields[$code]))
				return $issue->fields->customFields[$code];
			else
				return '';
		case 'first_contact':
			if(isset($issue->fields->customFields[$code]))
			{
				$first_contact= new Carbon($issue->fields->customFields[$code]);
				SetTimeZone($first_contact);
				return $first_contact->getTimestamp();
			}
			else
				return '';
			break;
		case 'reason_for_closure':
			if(isset($issue->fields->customFields[$code]))
			{
				return strtolower($issue->fields->customFields[$code]->value);
			}
			return '';
		default:
			dd($fieldname.' is not handled in IssueParser');
	}
}
function MapIssueTypeToCategory($issuetype)
{
	if($issuetype=='product change request')
		return 'pcr';
	
	if($issuetype=='risk')
		return 'risk';
	
	if(($issuetype=='cluster')||($issuetype=='feature')||($issuetype == ' customer requirement')||($issuetype=='esd requirement')||($issuetype=='bsp requirement')||($issuetype=='requirement'))
		return 'requirement';

	if(($issuetype=='workpackage')||($issuetype=='project')||($issuetype=='subproject'))
		return 'workpackage';

	if($issuetype=='bug')
		return 'defect';

	if($issuetype=='epic')
		return 'epic';

	if(($issuetype=='devtask')||($issuetype=='qatask')||($issuetype=='documentation')||($issuetype=='action')||($issuetype=='dependency')||($issuetype=='sub-task')||($issuetype=='issue')||($issuetype=='task')||($issuetype=='story')||($issuetype=='Improvement'))
		return 'task';
	return 'task';
}

function seconds2human($ss) 
{
	$hours_day = 8;
	$s = $ss%60;
	$m = floor(($ss%3600)/60);
	$h = floor(($ss)/3600);
	
	$d = floor($h/$hours_day);
	$h = $h%$hours_day;
	
	//return "$d days, $h hours, $m minutes, $s seconds";
	return "$d day,$h hour,$m min";
}
/**
 * Check if the given DateTime object is a business day.
 *
 * @param DateTime $date
 * @return bool
 */
function isBusinessDay(\DateTime $date)
{
	if ($date->format('N') > 5) 
	{
		return false;
	}
	$holidays = [
		"New Years Day"         => new \DateTime(date('Y') . '-01-01'),
		"Memorial Day"          => new \DateTime(date('Y') . '-05-25'),
		"Independence Day"      => new \DateTime(date('Y') . '-07-03'),
		"Labor Day"             => new \DateTime(date('Y') . '-09-07'),
		"Thanksgiving Day"      => new \DateTime(date('Y') . '-11-26'),
		"Thanksgiving Day2"     => new \DateTime(date('Y') . '-11-27'),
		"Floating Holiday1"     => new \DateTime(date('Y') . '-12-24'),
		"Christmas Day"         => new \DateTime(date('Y') . '-12-25'),
		"Floating Holiday2"     => new \DateTime(date('Y') . '-12-31'),
	];
	return true;
	foreach ($holidays as $holiday) 
	{
		if ($holiday->format('Y-m-d') === $date->format('Y-m-d')) {
			return false;
		}
	}
	//December company holidays
	if (new \DateTime(date('Y') . '-12-15') <= $date && $date <= new \DateTime((date('Y') + 1) . '-01-08')) 
	{
		return false;
	}
	// Other checks can go here
	return true;
}

/**
 * Get the available business time between two dates (in seconds).
 *
 * @param $start
 * @param $end
 * @return mixed
 */
function get_working_seconds($start, $end)
{
	$start = $start instanceof \DateTime ? $start : new \DateTime($start);
	$end = $end instanceof \DateTime ? $end : new \DateTime($end);
	$dates = [];

	$date = clone $start;

	while ($date <= $end) 
	{
		$datesEnd = (clone $date)->setTime(23, 59, 59);

		if (isBusinessDay($date)) {
			$dates[] = (object)[
				'start' => clone $date,
				'end'   => clone ($end < $datesEnd ? $end : $datesEnd),
			];
		}

		$date->modify('+1 day')->setTime(0, 0, 0);
	}

	return array_reduce($dates, function ($carry, $item) {

		$businessStart = (clone $item->start)->setTime(9, 000, 0);
		$businessEnd = (clone $item->start)->setTime(17, 00, 0);

		$start = $item->start < $businessStart ? $businessStart : $item->start;
		$end = $item->end > $businessEnd ? $businessEnd : $item->end;

		//Diff in seconds
		return $carry += max(0, $end->getTimestamp() - $start->getTimestamp());
	}, 0);
}
function get_working_minutes($ini_str,$end_str)
{		
	return round(get_working_seconds($ini_str,$end_str)/60);
}
function DbVar($collection,$var)
{
	$db  = new Database('scripwallet');
	if(is_array($var))
		$db->SaveVar($collection,$var);
	else
		return $db->GetVar($collection,$var);
}
