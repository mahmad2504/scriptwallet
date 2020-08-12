<?php
use Carbon\Carbon;
function SetTimeZone($datetime)
{
	$datetime->setTimezone(new \DateTimeZone("Asia/Karachi"));
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
		default:
			dd($fieldname.' is not handled in IssueParser');
	}
}
function MapIssueTypeToCategory($issuetype)
{
	if($issuetype=='product change request')
		return 'PCR';
	
	if($issuetype=='risk')
		return 'RISK';
	
	if(($issuetype=='cluster')||($issuetype=='feature')||($issuetype == ' customer requirement')||($issuetype=='esd requirement')||($issuetype=='bsp requirement')||($issuetype=='requirement'))
		return 'REQUIREMENT';

	if(($issuetype=='workpackage')||($issuetype=='project')||($issuetype=='subproject'))
		return 'WORKPACKAGE';

	if($issuetype=='bug')
		return 'DEFECT';

	if($issuetype=='epic')
		return 'EPIC';

	if(($issuetype=='devtask')||($issuetype=='qatask')||($issuetype=='documentation')||($issuetype=='action')||($issuetype=='dependency')||($issuetype=='sub-task')||($issuetype=='issue')||($issuetype=='task')||($issuetype=='story')||($issuetype=='Improvement'))
		return 'TASK';
	return 'TASK';
}