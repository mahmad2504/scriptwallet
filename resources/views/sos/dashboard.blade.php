<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SOS Dashboard</title>
  <meta name="description" content="SOS Dashboard">
  <meta name="mumtaz" content="">
  <link rel="stylesheet" href="{{ asset('sos/css/dashboard.css') }}" />
</head>

<body>
	<table width="100%" border="0" align="left" cellpadding="4" cellspacing="0">
		<tr style="background-color:red">
			<td>
			Dashboard
			</td>
			<td style="float:right">
			
			</td>
		</tr>
	</table>
	<table width="15%" border="0" align="left" cellpadding="4" cellspacing="0">
	  	<tr>
			<td>
				<div id="mainmenu">
					<a class="num1 selected" href="{$level}index.php">Projects</a>
					<a class="num2" href="{$level}library/search.php">- Hardware</a>
					<a class="num2" href="{$level}library/search.php"> - Search for Hardware</a>
					<a class="num2" href="{$level}library/search_expanded.php"> - Expanded Search</a>
				</div>
			</td>
			<td>
				<h1>Hello world</h1>
			</td>
		</tr>
		
	<table>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
  <script>
	var projects=@json($projects);
	$(document).ready(function()
	{
		for(var i=0;i<projects.length;i++)
		{
			
		}
		
	});
  </script>
</body>
</html>