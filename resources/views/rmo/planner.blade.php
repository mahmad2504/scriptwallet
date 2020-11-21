<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Mentor Login</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta HTTP-EQUIV="cache-control" CONTENT="no-cache, no-store, must-revalidate">
<meta HTTP-EQUIV="Expires" CONTENT="Mon, 01 Jan 1970 23:59:59 GMT">
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link href="{{ asset('css/font-awesome.4.7.0.min.css') }}" rel="stylesheet">
<link href="{{ asset('rmo/css/rmo.css') }}" rel="stylesheet">

<style>
	.highlight
	{
		border-top:solid 2px black ;
		border-left:solid 2px black !important;
		border-bottom:solid 2px black !important;
		border-right:solid 2px black;
	}
</style>

</head>

<body>
<div style="overflow-x: scroll;">
	<div id="table"></div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script src="{{ asset('rmo/js/rmo.js') }}" ></script>
<script>
var tabledata = @json($tabledata);
var projects = @json($projects);
var resources = @json($resources);
window.token = '{{csrf_token()}}';
window.saveurl = '{{route("rmo.save")}}';
//console.log(tabledata);
//console.log(projects);
//console.log(resources);

$(document).ready(function()
{
	console.log("Login Page Loaded");
	var rmo = new Rmo(tabledata,"table",projects);	
	
	for(var i=0;i<resources.length;i++)
		rmo.GenerateResourceRow(resources[i]);
	rmo.Show("table");
});

</script>
</body>
</html>