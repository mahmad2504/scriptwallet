@extends('sos.layout')
@section('csslinks')
<link rel="stylesheet" href="{{ asset('sos/css/jsgantt.css') }}" />
@endsection
@section('style')
.progress {height: 10px;}
.deadline-line {
      position: absolute;
      top: 0;
      width: 3px;
      height: 22px;
      background: #ff0000;
    }
@endsection
@section('content')
<div style="width:99%; margin-left: auto; margin-right: auto" class="center">
	<h3>Project Name</h3>
	<div style="margin-top:5px;" class="mainpanel">
	<div style="background-color:#F0F0F0">
		<h3>Gantt Chart</h3>
	</div>
		<div class="gantt" id="GanttChartDIV">Loading ...</div>
	</div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="{{ asset('sos/js/jsgantt.js') }}" ></script>
@endsection
@section('script')

var  vMaxDate = null;
var data = @json($data);
var url='';
var jiraurl='';
console.log(data);
function MakeDate2(string) 
{
	dateObj = new Date(string).toUTCString();
	if(dateObj === 'Invalid Date')
		return '';
	
	return dateObj.slice(4,16);
}
function Round(val)
{
	return  Math.round( val * 10 ) / 10;
}
function Round2(val)
{
	return  Math.round( val * 100 ) / 100;
}		
function drawCustomElements(g) {
  for (const item of g.getList()) {
    if (item.getDataObject().deadline) {
		const x = g.chartRowDateToX(new Date(item.getDataObject().deadline));
		const td = item.getChildRow().querySelector('td');
		td.style.position = 'relative';
		const div = document.createElement('div');
		div.style.left = `${x}px`;
		div.classList.add('deadline-line');
		td.appendChild(div);
    }
  }
}

function ShowGantt()
{
	var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'day');
	for(var i=0;i<data.length;i++)
	{
		//if(data[i].pStatus == 'resolved')
		var href = "#";
		if(data[i].pJira != data[i].pID)
			href=jiraurl+"/browse/"+data[i].pJira;
		else
			data[i].pJira = '';

		style = '';
		
		//data[i].pEstimate = data[i].pEstimate*8;
		data[i].pEstimate = Round2(data[i].pEstimate);
		data[i].pComp = Round2(data[i].pComp);
		if(data[i].pIssuesubtype == 'DEV')
		{
			style = 'color:black;';
		}
			
		if(data[i].pStatus == 'resolved')
		{
			style = 'color:lightgrey;';
			data[i].pComp = "<span style='font-size:9px;"+style+"'>Done</span>";
			data[i].pEndString = "<span style='font-size:9px;"+style+"'>"+data[i].pClosedOn+"</span>";
			data[i].pEstimate = "<span style='font-size:9px;"+style+"'>"+data[i].pEstimate+"</span>";
		}
		else if(data[i].pStatus == 'inprogress')
		{
			if(data[i].dup !== undefined)
				style='color:red;';
			data[i].pEndString = "<span style='font-size:9px;"+style+"'>"+MakeDate2(data[i].sEnd)+"</span>";
			data[i].pComp = data[i].pComp+"%";
			data[i].pComp = "<span style='font-size:9px;"+style+"'>"+data[i].pComp+"</span>";
			data[i].pEstimate = "<span style='font-size:9px;"+style+"'>"+data[i].pEstimate+"</span>";
		}
		else
		{
			if(data[i].dup !== undefined)
				style='color:red;';
			data[i].pEndString = "<span style='font-size:9px;"+style+"'>"+MakeDate2(data[i].sEnd)+"</span>";
			data[i].pEstimate = "<span style='font-size:9px;'>"+data[i].pEstimate+"</span>";
			data[i].pComp = data[i].pComp+"%";
		}
		
		if(data[i].dup !== undefined)
			data[i].pName = "<a style='"+style+"' class='taskname' href='"+href+"'>Duplicate of "+data[i].key+"</a>";
		else
			data[i].pName = "<a style='"+style+"' class='taskname' href='"+href+"'>"+data[i].pName+"</a>";
		if(data[i].pStatus == 'resolved')
			data[i].pCaption = "<a href='"+href+"'>"+data[i].pJira+"</a>";
		else
			data[i].pCaption = "&nbsp<span style='color:orange;'>"+data[i].pRes+"</span>&nbsp<a href='"+href+"'>"+data[i].pJira+"</a>";
		if(data[i].deadline.length > 0)
		{
			if(vMaxDate == null)
			{
				vMaxDate = data[i].deadline;
			}
			else
			{
				var d1 = dates.convert(vMaxDate);
				var d2 = dates.convert(data[i].deadline);
				if(dates.compare(d1,d2)<0)
					vMaxDate = data[i].deadline;
			}
		}
		g.AddTaskItemObject(data[i]);
	}
	g.setOptions({
	  vCaptionType: 'Caption',  // Set to Show Caption : None,Caption,Resource,Duration,Complete,     
	  vQuarterColWidth: 12,
	  vDateTaskDisplayFormat: 'day dd month yyyy', // Shown in tool tip box
	  vDayMajorDateDisplayFormat: 'mon yyyy - Week ww',// Set format to dates in the "Major" header of the "Day" view
	  vWeekMinorDateDisplayFormat: 'dd mon', // Set format to display dates in the "Minor" header of the "Week" view
	  vLang: 'en',
	  vShowTaskInfoLink: 0, // Show link in tool tip (0/1)
	  vShowEndWeekDate: 0,  // Show/Hide the date for the last day of the week in header for daily
	  vAdditionalHeaders: { 
			pEstimate: {
				title: 'Estimate'
			},
			pComp: {
				title: 'Progress'
			},
			pEndString: {
				title: 'End'
		    }
		},
	
	  vMaxDate : vMaxDate,
	  vUseSingleCell: 100000, // Set the threshold cell per table row (Helps performance for large data.
	  vFormatArr: ['Day', 'Week', 'Month'], // Even with setUseSingleCell using Hour format on such a large chart can cause issues in some browsers,  
	  vEvents: {
        beforeDraw: () => console.log('before draw listener'),
        afterDraw: () => {
          console.log('after draw listener');
		  drawCustomElements(g);
        }
      },	
	
	});
	//console.log(vMaxDate);
	
	g.setShowDur(0);
	g.setShowRes(0);
	g.setShowStartDate(0);
	g.setShowEndDate(0);
	g.setShowComp(0);
	
	g.setDateInputFormat('yyyy-mm-dd'); 
	//g.setScrollTo('2018-07-02');
	
	
		
	//JSGantt.parseJSONString(data, g);
	
	g.Draw();
	
	
	$(".gtaskclosed").hover(function() {
        $(this).css('cursor','pointer').attr('title', 'Closed Task');
    }, function() {
        $(this).css('cursor','auto');
    });
	
	$(".gtaskopen").hover(function() {
        $(this).css('cursor','pointer').attr('title', 'Open Task');
    }, function() {
        $(this).css('cursor','auto');
    });
	$(".gtaskopenunestimated").hover(function() {
        $(this).css('cursor','pointer').attr('title', 'Unestimated Open Task');
    }, function() {
        $(this).css('cursor','auto');
    });
	
	$(".gtaskgreenunestimated").hover(function() {
        $(this).css('cursor','pointer').attr('title', 'Unestimated Task In Progress');
    }, function() {
        $(this).css('cursor','auto');
    });

	$(".gtaskgreen").hover(function() {
        $(this).css('cursor','pointer').attr('title', 'Task In Progress');
    }, function() {
        $(this).css('cursor','auto');
    });
	
	
	$(".taskname").hover(function() {
        $(this).css('cursor','pointer').attr('title', $(this).text());
    }, function() {
        $(this).css('cursor','auto');
    });
	
	$(".deadline-line").hover(function() {
        $(this).css('cursor','pointer').attr('title','Deadline');
    }, function() {
        $(this).css('cursor','auto');
    });
}	

$(document).ready(function()
{
	ShowGantt();
})
@endsection