<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>IESD-IVS Dashboard</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
		<link rel="stylesheet" href="{{ asset('dygraph/dygraph.css')}}" />
		  <!-- Styles -->
        <style>
		.flex-item {
			text-align: center;
		}
        </style>
    </head>
    <body>
	<label style="margin-left:100px;" for="start">Start:</label>
    <input type="date" id="start" name="Start">
	
	<label style="margin-left:10px;" for="start">End:</label>
    <input type="date" id="end" name="End">
	
	<button id="search" name="search">Search</button>
	<hr>
	<table style="width:100%">
	  <tr>
		<th>
		<label style="float:left;margin-left:70px">VOLSUP</label>
		<select  style="float:left;margin-left:10px" name="select1" id="select1">
		  <option value="volcano ivs,volcano bl">All</option>
		  <option value="volcano ivs">IVS</option>
		  <option value="volcano bl">BL</option>
		</select>
		<br>
		<br>
		<br>
		<div id="graphdiv1"></div>
		</th>
		<th>
		<label style="margin-left:70px;float:left;font-weight:bold;">VSTARMOD</label>
		<select  style="float:left;margin-left:10px" name="select2" id="select2">
		  <option value="cvbl,cvltp,cvnwm,cvdsl,cvtl,cvtp">All</option>
		  <option value="cvbl">CVBL</option>
		  <option value="cvltp">CVLTP</option>
		  <option value="cvnwm">CVNWM</option>
		  <option value="cvdsl">CVDSL</option>
		  <option value="cvtl">CVTL</option>
		  <option value="cvtp">CVTP</option>
		</select>
	    <br>
		<br>
		<br>
		<div id="graphdiv2">
		</th>
	  </tr>
	  <tr>
		<td>
			<br>
			<br>
			<br>
			<div id="graphbl1"></div>
		</td>
		<td>
			<br>
			<br>
			<br>
			<div id="graphbl2"></div>
		</td>
	  </tr>
	  <tr>
		<td>
		     <br>
	         <br>
			 <br>
	         <br>
			<div id="barchart1"></div>
		</td>
		<td>
		     <br>
	         <br>
			 <br>
	         <br>
			<div id="barchart2"></div>
		</td>

	  </tr>
	</table>
	<div class="flex-item">
		<small style="font-size:12px;">Dashboard created by mumtaz.ahmad@siemens.com for IESD IVS<a id="update" href="#"></a></small><br>
		
	</div>
	
	
	<div style="width:700px;height:300px;">
	<canvas id="myChart" ></canvas>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script src="{{ asset('dygraph/dygraph.js')}}"></script>
    <script src="https://www.gstatic.com/charts/loader.js"></script>
	<script>
		var data1 = @json($graphdata1);
		var data2 = @json($graphdata2);
		
	    var graphdata1 = [];
		var graphdata2 = [];
		
		var graphbl1 = [];
		var graphbl2 = [];
		
		var bardata1 = data1.fr;
		var bardata2 = data2.fr;
		
		var start = "{{$start}}";
		var end = "{{$end}}";
		document.getElementById("start").defaultValue = start;
		document.getElementById("end").defaultValue = end;
		google.charts.load('current', {packages: ['corechart']});
		google.charts.setOnLoadCallback(drawChart1);
		google.charts.setOnLoadCallback(drawChart2);
		function drawChart1() 
		{
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Response in days');
			data.addColumn('number', 'Blocker');
			data.addColumn('number', 'Critical');
			data.addColumn('number', 'Major');
			data.addColumn('number', 'Low');
			data.addRows(bardata1);	
			var options = {
				title: 'Response time',
				width: 700,
				height: 500,
				legend: { position: 'top', maxLines: 1 },
				isStacked: true,
				colors: ['#E69138','#F1C232','40E0D0','#6AA84F'],
				bar: {groupWidth: '50%'},
				vAxis: { title: "Ticket Count" ,
						 gridlines: { count: 10 },
				},
				hAxis: { title: "Response time (days)" ,
						 gridlines: { color: '#333',count: 4 }
				},
				chartArea: 
				{
					backgroundColor: {
						stroke: '#cdcdcd',
						strokeWidth: 1
					}
				},
			};
			var chart = new google.visualization.ColumnChart(document.getElementById('barchart1'));
			chart.draw(data, options);
		}
		function drawChart2() 
		{
			var data = new google.visualization.DataTable();
			data.addColumn('string', 'Response in days');
			data.addColumn('number', 'Blocker');
			data.addColumn('number', 'Critical');
			data.addColumn('number', 'Major');
			data.addColumn('number', 'Low');
			data.addRows(bardata2);	
			var options = {
				title: 'Response time',
				width: 700,
				height: 500,
				legend: { position: 'top', maxLines: 3 },
				isStacked: true,
				colors: ['#E69138','#F1C232','40E0D0','#6AA84F'],
				
				bar: {groupWidth: '50%'},
				vAxis: { title: "Ticket Count" ,
						 gridlines: { count: 10 }
				},
				hAxis: { title: "Response time (days)" ,
						 gridlines: { count: 10 }
				},
				chartArea: 
				{
					backgroundColor: {
						stroke: '#cdcdcd',
						strokeWidth: 1
					}
				},
			};
			var chart = new google.visualization.ColumnChart(document.getElementById('barchart2'));
			chart.draw(data, options);
		}
		function Process(data)
		{
			for(var i=0;i<data.length;i++)
			{
				data[i][0]=new Date(data[i][0]);
			}
			return data;
		}
		function DrawBlGraph1()
		{
			var dygraph1 = new Dygraph(
			document.getElementById("graphbl1"),
			graphbl1,
              {
				//labels: [ "Date", "Created this week" ,"Total Created","Resolved this week","Total resolved","Defects resolved this week","Total defects resolved","Defects created this week","Total defects created"],
                labels: [ "Date", "Created","Fixed","Backlog","Defect Backlog"],
				strokeWidth: 2,
				includeZero : true,
				title:"Backlog",
				labelsSeparateLines: false,
                legend: 'always',
				//colors: ['E69997', '#54A653', '#284785','#284785','#284785','#284785','#284785','#284785','#284785'],
				visibility: [true, true, true,false],
				showRangeSelector: false,	
				width: 700,
                height:400,
                series: {
				  'Created': {
					   color: '#ff0000',
					   plotter: barChartPlotter,
				  },
				  'Fixed': {
					   color: '#6AA84F',
					   plotter: barChartPlotter,
				  },
				  'Backlog': {
					   color: '#0000ff',
				  },
				  'Defect Backlog': {
					   color: '#0000ff',
				  },
                }
              }
			);
		}
		function DrawBlGraph2()
		{
			var dygraph1 = new Dygraph(
			document.getElementById("graphbl2"),
			graphbl2,
              {
				//labels: [ "Date", "Created this week" ,"Total Created","Resolved this week","Total resolved","Defects resolved this week","Total defects resolved","Defects created this week","Total defects created"],
                labels: [ "Date", "Created","Fixed","Backlog","Defect Backlog"],
				strokeWidth: 2,
				includeZero : true,
				title:"Backlog",
				labelsSeparateLines: false,
                //legend: 'always',
				//colors: ['E69997', '#54A653', '#284785','#284785','#284785','#284785','#284785','#284785','#284785'],
				visibility: [true, true, true, true],
				showRangeSelector: false,	
				width: 700,
                height:400,
                series: {
				  'Created': {
					   color: '#ff0000',
					   plotter: barChartPlotter,
				  },
				  'Fixed': {
					   color: '#6AA84F',
					   plotter: barChartPlotter,
				  },
				  'Backlog': {
					   color: '#0000ff',
				  },
				  'Defect Backlog': {
					   color: '#00BFFF',
				  },
                }
              }
			);
		}
		function DrawGraph1()
		{
			var dygraph1 = new Dygraph(
			document.getElementById("graphdiv1"),
			graphdata1,
              {
				//labels: [ "Date", "Created this week" ,"Total Created","Resolved this week","Total resolved","Defects resolved this week","Total defects resolved","Defects created this week","Total defects created"],
                labels: [ "Date", "w-created","Created","w-fixed","Fixed","w-dfixed","Defect created","w-dcreated","Defect fixed"],
				strokeWidth: 2,
				includeZero : true,
				title:"Created Vs Fixed",
				labelsSeparateLines: false,
                //legend: 'always',
				//colors: ['E69997', '#54A653', '#284785','#284785','#284785','#284785','#284785','#284785','#284785'],
				visibility: [false, true, false,true,false,false,false,true],
				showRangeSelector: false,	
				width: 700,
                height:400,
                series: {
				  'w-created': {
					   color: '#ff0000'
				  },
				  'Created': {
					   fillGraph:true,
					   color: '#ff0000',
				  },
				  'w-fixed': {
					   color: '#00ff00'
				  },
				  'Fixed': {
					   color: '#00ff00',
					   pointSize: 0,
					   drawPoints: true,
					   fillGraph:true,
				  },
				  'w-dfixed': {
					   color: '#00ff00'
				  },
				  'Defect fixed': {
					   strokeWidth: 0.5,
					   color: '#006400',
					   pointSize: 1,
					   drawPoints: true,
					   fillGraph:true,
					  
				  },
				  'w-dcreated': {
					   color: '#00ff00'
					   
				  },
				  'Defect created': {
					   strokeWidth: 0.5,
					   pointSize: 1,
					   drawPoints: true,
					   color: '#FF8C00',
					   fillGraph:true,
				  }
                }
              }
			);
		}
		function DrawGraph2()
		{
			Dygraph2 = new Dygraph(
			  document.getElementById("graphdiv2"),
			  graphdata2,
			
              {
				//labels: [ "Date", "Created this week" ,"Total Created","Resolved this week","Total resolved","Defects resolved this week","Total defects resolved","Defects created this week","Total defects created"],
                labels: [ "Date", "w-created","Created","w-fixed","Fixed","w-dfixed","Defect created","w-dcreated","Defect fixed"],
				strokeWidth: 2,
				includeZero : true,
				title:"Created Vs Fixed",
				labelsSeparateLines: false,
                //legend: 'always',
				//colors: ['E69997', '#54A653', '#284785','#284785','#284785','#284785','#284785','#284785','#284785'],
				visibility: [false, true, false,true,false,true,false,true],
				showRangeSelector: false,	
				width: 700,
                height:400,
                series: {
				  'w-created': {
					   color: '#ff0000'
				  },
				  'Created': {
					   fillGraph:true,
					   color: '#ff0000'
				  },
				  'w-fixed': {
					   color: '#00ff00'
				  },
				  'Fixed': {
					   color: '#00ff00',
					   fillGraph:true,
				  },
				  'w-dfixed': {
					   color: '#00ff00'
				  },
				  'Defect fixed': {
					   strokeWidth: 0.5,
					   color: '#006400',
					   pointSize: 1,
					   drawPoints: true,
					   fillGraph:true,
					  
				  },
				  'w-dcreated': {
					   color: '#00ff00'
					   
				  },
				  'Defect created': {
					   strokeWidth: 0.5,
					   pointSize: 1,
					   drawPoints: true,
					   color: '#ff0000',
					   fillGraph:true,
				  }
                }
              }
			);
			
		}
		$(document).ready(function()
		{
			$( "#search" ).click(function() {
				var url="/zaahmad/supportmatric/getgraphdata/volsup"+"?start="+$('#start').val()+"&end="+$('#end').val()+"&issuetypes="+$('#select1').val();
				$.ajax({
					url: url,
				}).done(function(data) {
					bardata1 = data.fr;
					drawChart1();
					graphdata1 = Process(data.gd);
					DrawGraph1();
					graphbl1 = Process(data.bl);
					DrawBlGraph1();
				});
				
				var url="/zaahmad/supportmatric/getgraphdata/vstarmod"+"?start="+$('#start').val()+"&end="+$('#end').val()+"&components="+$('#select2').val();
				$.ajax({
					url: url,
				}).done(function(data) {
					bardata2 = data.fr;
					drawChart2();
					graphdata2 = Process(data.gd);
					DrawGraph2();
					graphbl2 = Process(data.bl);
					DrawBlGraph2();
				});
				
			});
			$( "#select1" ).change(function() {
				var url="/zaahmad/supportmatric/getgraphdata/volsup"+"?start="+$('#start').val()+"&end="+$('#end').val()+"&issuetypes="+$('#select1').val();
				$.ajax({
					url: url,
				}).done(function(data) {
					bardata1 = data.fr;
					drawChart1();
					graphdata1 = Process(data.gd);
					DrawGraph1();
					graphbl1 = Process(data.bl);
					DrawBlGraph1();
					
				});
			});
			$( "#select2" ).change(function() {
				var url="/zaahmad/supportmatric/getgraphdata/vstarmod"+"?start="+$('#start').val()+"&end="+$('#end').val()+"&components="+$('#select2').val();
				$.ajax({
					url: url,
				}).done(function(data) {
					bardata2 = data.fr;
					drawChart2();
					graphdata2 = Process(data.gd);
					DrawGraph2();
					graphbl2 = Process(data.bl);
					DrawBlGraph2();
				});
			});
			graphdata1 = Process(data1.gd);
			graphdata2 = Process(data2.gd);
			DrawGraph1();
			DrawGraph2();
			
			graphbl1 = Process(data1.bl);
			graphbl2 = Process(data2.bl);
			DrawBlGraph1();
			DrawBlGraph2();
           
		});
	function darkenColor(colorStr) {
	var color = Dygraph.toRGB_(colorStr);
	color.r = Math.floor((255 + color.r) / 2);
	color.g = Math.floor((255 + color.g) / 2);
	color.b = Math.floor((255 + color.b) / 2);
	return 'rgb(' + color.r + ',' + color.g + ',' + color.b + ')';
	}
	function barChartPlotter(e) {
	var ctx = e.drawingContext;
	var points = e.points;
	var y_bottom = e.dygraph.toDomYCoord(0);

	ctx.fillStyle = darkenColor(e.color);

	// Find the minimum separation between x-values.
	// This determines the bar width.
	var min_sep = Infinity;
	for (var i = 1; i < points.length; i++) {
	  var sep = points[i].canvasx - points[i - 1].canvasx;
	  if (sep < min_sep) min_sep = sep;
	}
	var bar_width = 7;//Math.floor(2.0 / 3 * min_sep);

	// Do the actual plotting.
	for (var i = 0; i < points.length; i++) {
	  var p = points[i];
	  if(e.seriesIndex==1)
		var center_x = p.canvasx-5;
	else
		var center_x = p.canvasx-12;
	  ctx.fillRect(center_x - bar_width / 2, p.canvasy,
		  bar_width, y_bottom - p.canvasy);

	  ctx.strokeRect(center_x - bar_width / 2, p.canvasy,
		  bar_width, y_bottom - p.canvasy);
	}
	}
	
	</script>
    </body>
</html>
