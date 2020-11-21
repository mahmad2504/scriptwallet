function Rmo(tabledata,tag,projects)
{
	var self = this;
	this.tabledata=tabledata;
	this.today_color='#8FBC8F';
	this.col = [1,1,1];
	this.projects = projects;
	window.timeoutcounter = 0;
	window.lastutil = 100;
	window.Save=function()
	{
		var projectids = [];
		var out = [];
		var selects = [];
		for(var i=0;i<window.resources.length;i++)
		{
			var resource = window.resources[i];
			var res = {};
			res.id = resource.id;
			res.name =  resource.name;
			
			res.projects = [];
			//console.log(resource.name);
			if(resource.projects !== undefined)
			{
				projectids = [];
				for(var j=0;j<resource.projects.length;j++)
				{
					var prow = resource.projects[j];
					if(prow === undefined)
					   continue;
				
					var select = prow.find("select"); 
					selects.push(select);
					var project_id = select.val()*1;
					var project_name= select.find(":selected").text();
					if(projectids[project_id] != undefined)
					{
						this.alert(resource.name+" has duplicate projects "+project_name);
						return;	
					}
					projectids[project_id]=1;
					if(project_id == -1)
					{
						this.alert(resource.name+" has missing project name");
						return;	
					}
					prj = {'id': project_id, 'name': project_name,'data':{}};
					//console.log(project_id);
					//console.log(project_name);
					var cells = prow.find(".filled_cell"); 
					for(var k=0;k<cells.length;k++)
					{
						//console.log($(cells[k]).data('week'));
						//console.log($(cells[k]).data('util'));
						prj.data[$(cells[k]).data('week')]=$(cells[k]).data('util');
					}
					res.projects.push(prj);
					
				}
			}
			//tds = row.find("td"); 
			if(res.projects.length > 0)
				out.push(res);	
		}
		var data ={};
		data.data=JSON.stringify(out);
		//console.log(data);
		//console.log(window.token);
		data._token = window.token;
	
		for(var i=0;i<selects.length;i++)
		{
			$(selects[i]).attr('disabled', 'disabled');
			$(selects[i]).css('color','black');
		}

		$.ajax({
			type: "POST",
			url: window.saveurl,
			dataType: "json",
			//contentType: false,
			//processData: false,
			success: function (msg) 
			{
				//console.log(CryptoJS.MD5(datatosend).toString());
				 //alert(msg.result);
				 $('#save').css('background-color','DarkSeaGreen');
				 $('#save').css('color','white');
				 
			},
			error:function (msg) 
			{
	   
			},
			data: data
		});
		//console.log(window.resources);
	}
	window.ColorCell = function(cell)
	{
		var util = cell.data('util');
		
		if(util == 0)
		{
			if(cell.hasClass( "project_cell" ))
				cell.css('background-color','#D8EBFC');
			else
				cell.css('background-color','#FFFFFF');
		}
		else if(util > 100)
			cell.css('background-color','#FFCC66');
		else if(util == 100)
			cell.css('background-color','#00FF7F');
		else if(util >= 75)
			cell.css('background-color','#7CFC00');
		else if(util >= 50)
			cell.css('background-color','#ADFF2F');
		else 
			cell.css('background-color','#F5F39D');
	}
	window.UpdateResourceUtil =  function(cell)
	{
		var resource_id = cell.data('resource');
		var cindex = cell.data('cindex');
		var resource = window.getresource(resource_id);
		var util = 0;
		for(var i=0;i<resource.pindex;i++)
		{
			var id = '#cell_'+cindex+'_'+resource_id+'_'+i;
			//console.log($(id));
			if( $(id).length )
			{
				var val = $(id).data('util');
				if(val !== undefined)
				  util += val;
			}
		}
		var id = '#cell_'+cindex+'_'+resource_id;
		$(id).html(util);
		if(util == 0)
			$(id).html('');
		$(id).data('util',util);
		ColorCell($(id))
		
	}
	window.SelectCell = function(cell,util)
	{
		$('#save').css('color','white');
		$('#save').css('background-color','red');

		window.lastutil = util;
		cell.data('util', util);
		cell.html(util);
		cell.attr('title',util);
		cell.addClass('filled_cell');
		
	    window.currentcell = cell;
		
		//console.log(util);

		cell.addClass('highlight');
		window.ColorCell(cell);
		window.UpdateResourceUtil(cell);
		//$(this).html(100);
		//$(this).data('util', 100);
		//var cell = $(this);
		//window.currentcell = cell;
		setTimeout(function()
		{ 
			cell.removeClass('highlight'); 
			var u = cell.data('util');
			if(u == 0)
				cell.html('');
		}, 1000);
	}
	window.UpdateCell = function(inc)
	{
		var cell = window.currentcell;
		if(cell == null)
			return;	
		
		if(cell.data('disabled')==1)
			return;
		var util = cell.data('util');
	
		if(util === undefined)
		   return;

		if(inc)
		{
			util++;
			if(util > 100)
				util = 0;
		}
		else
		{
			util--;
			if(util < 0)
				util = 100;
		}
		window.SelectCell(cell,util);
	}
	window.ClearCell = function()
	{
		var cell = window.currentcell;
		if(cell == null)
			return;	
		
		if(cell.data('disabled')==1)
			return;
		
		window.SelectCell(cell,0);
	}
	window.SelectLeftCell =  function()
	{
		var cell = window.currentcell;
		
		if(cell == null)
			return;

		if(cell.data('disabled')==1)
			return;
		
		var util = 0;//cell.data('util');
		var cindex = cell.data('cindex');
		if(cindex  == 0)
		   return;
		var resourceid = cell.data('resource');
		var pindex=cell.data('pindex');
		cindex = cindex - 1;
		
		var newid = 'cell_'+cindex+'_'+resourceid+'_'+pindex;
		window.SelectCell($('#'+newid),util);
		
	}
	window.SelectRightCell =  function()
	{
		var cell = window.currentcell;
		if(cell == null)
			return;
		if(cell.data('disabled')==1)
			return;
		var util = cell.data('util');
		var cindex = cell.data('cindex');
		
		var resourceid = cell.data('resource');
		var pindex=cell.data('pindex');
		cindex = cindex + 1;
		
		var newid = 'cell_'+cindex+'_'+resourceid+'_'+pindex;
		if($('#'+newid).length )
		{
			window.SelectCell($('#'+newid),util);
		}
	}
	$(document).keydown(function(e){

		if(event.ctrlKey)
		{
			switch (e.which){
			case 37:    //left arrow key
				window.SelectLeftCell();
				break;
			case 38:    //up arrow key
			    window.UpdateCell(1);
				break;
			case 39:    //right arrow key
				window.SelectRightCell();
				break;
			case 40:    //bottom arrow key
			    window.UpdateCell(0);
				break;
			
			}
		}
		else 
		{
			switch (e.which)
			{
				case 46:
					window.ClearCell();
					break;
			}
		}
	});
	window.getresource = function(id)
	{
		for(var i=0;i<window.resources.length;i++)
		{
			if(this.resources[i].id == id)
				return this.resources[i];
		}
		return null;
	}
	this.projectcombo = function(project=null)
	{
		var select=$('<select style=""></select>');
		found=0;
		if(project != null)
		{
			select.append('<option value="'+project.id+'"  selected>'+project.name+'</option>');
			select.attr('disabled', 'disabled');
			select.css('color','black');
		}
		else
		{
			select.append('<option value="-1" selected>Select</option>');
		}
		for (var i=0;i<this.projects.length;i++) 
		{
			select.append('<option value="'+this.projects[i].id+'">'+this.projects[i].name+'</option>');
		}
		return select;
	}
	this.Show = function(tag)
	{
		$('.expand').click(function(){
			var expanded=$(this).data("expanded");
			var resourceid=$(this).data("resource");
			var resource = window.getresource(resourceid);
			//console.log(resource);
			if(expanded == 0)
			{
				$(this).addClass('fa-caret-square-o-up');
				$(this).removeClass('fa-caret-square-o-down');
				$(this).data('expanded', '1');
				$('.project_'+resourceid).show();
				$('#addrow_'+resourceid).show();
			}
			else
			{
				$(this).removeClass('fa-caret-square-o-up');
				$(this).addClass('fa-caret-square-o-down');
				$(this).data('expanded', '0');
				$('.project_'+resourceid).hide();
				$('#addrow_'+resourceid).hide();
			}
			
		});
		$('.addrow').click(function(){
			var resourceid=$(this).data("resource");
			var resource = window.getresource(resourceid);
			var row = self.GenerateProjectRow(resource);
			var parnt  = resource.element;
			for(var i=0;i<resource.projects.length;i++)
			{
				if(resource.projects[i] !== undefined)
					parnt = resource.projects[i];
			}
			parnt.after(row);
			resource.projects.push(row);
		});
		$('#save').click(function(){
			window.Save();
		})
		$('#save').css('color','black');
		$('#save').css('background-color','white');
		this.table.show();
	}
	this.AddRow = function(row)
	{
		this.table.append(row);
	}
	this.CreateTable = function(tag)
	{
		var table = $('<table>');
		table.hide();
		table.addClass("RmoTable");
		$('#'+tag).append(table);
		var row=1;
		yearrow = self.GenerateYearRow(row++);
		table.append(yearrow);
		
		monthrow = self.GenerateMonthRow(row++);
		table.append(monthrow);
		
		sprintrow = self.GenerateSprintRow(row++);
		table.append(sprintrow);
		
		weekrow = self.GenerateWeekRow(row++);
		table.append(weekrow);
		
		
		
		dayrow = self.GenerateDayRow(row++);
		dayrow.attr("height","15px");
		dayrow.attr("width","15px");
		table.append(dayrow);
		this.table = table;
		this.resources = [];
		
	
	}
	
	this.MonthName = function(month)
	{
		if(month == 1)
			return "Jan";
		else if(month == 2)
			return "Feb";
		else if(month == 3)
			return "Mar";
		else if(month == 4)
			return "Apr";
		else if(month == 5)
			return "May";
		else if(month == 6)
			return "Jun";
		else if(month == 7)
			return "Jul";
		else if(month == 8)
			return "Aug";
		else if(month == 9)
			return "Sep";
		else if(month == 10)
			return "Oct";
		else if(month == 11)
			return "Nov";
		else if(month == 12)
			return "Dec";
	
		return month;
	}
	this.GenerateYearRow = function(r)
	{
		var yearArray = this.tabledata.years;
		
		var c=1;
		var row = $('<tr>');
		row.addClass("rowyear");
		
		
		if(this.col[0])
		{
			var col = $('<th>');
			var button='<button id="save">Save</button>';
			col.html('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp'+button+'&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp');
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.html('');
			col.attr('id','r'+r+'c'+c++);
			col.addClass('sticky-col1');
			row.append(col);
		}
		if(this.col[2])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.html('');
			row.append(col);
		}
		var color='#DCDCDC';
		for (var year in yearArray) 
		{
			var today = yearArray[year].includes(1);

			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(yearArray[year]).length;
			if(colspan < 15)
				year = '';
			col = $('<th colspan="'+colspan+'">');
			col.attr('id','r'+r+'c'+c++);
			col.html(year);
			col.css('background-color',color);
			row.append(col);
		}
		return row;
	}
	this.GenerateMonthRow =  function(r)
	{
		monthArray = this.tabledata.months;
		//console.log(monthArray);
		var row = $('<tr>');
		row.addClass("rowmonth");
		var c=1;
		
		if(this.col[0])
		{
			var col = $('<th>');
			row.append(col);
			col.attr('id','r'+r+'c'+c++);
			col.html('Month');
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		var color='#DCDCDC';
		for (var month in monthArray) 
		{
			var today = monthArray[month].includes(1);
			
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(monthArray[month]).length;
			if(colspan <= 15)
				month = '';
			
			col = $('<th colspan="'+colspan+'">');
			col.attr('id','r'+r+'c'+c++);
			col.html(self.MonthName(month.substring(5)));
			col.css('background-color',color);
			row.append(col);
		}
		return row;
	}
	this.GenerateResourceRow =  function(resource)
	{
		console.log(resource);
		weekArray = this.tabledata.weeks;
		var c=1;
		var r=resource.id;
		var row = $('<tr>');
		row.addClass("rowweek");
		row.addClass("resourcerow");
		if(this.col[0])
		{
			var col = $('<th>');
			col.css('padding',2);
			//col.attr('id',resource.id+'_'+ c'+c++);
			var icon = $('<i style="float:left;margin-right:5px;margin-left:5px" class="fa fa-caret-square-o-down" aria-hidden="true"></i>');
			icon.attr('id','expand_'+resource.id);
			icon.addClass('expand');
			icon.data('expanded', '0');
			icon.data('resource', resource.id);
			//col.html('<span style="margin-right:50px;">'+expandicon+resource.name+'</span>');
			if(resource.disabled == 1)
				icon.append('<span style="margin-left:5px">'+resource.name+' [X]</span>');
			else
				icon.append('<span style="margin-left:5px">'+resource.name+'</span>');
			//if(resource.disabled != 1)
			col.append(icon);
			row.append(col);
		}
		if(this.col[1])
		{
			var col = $('<th>');
			col.css('padding',2);
			var icon= $('<i title="Add Row" style="font-size:10px;float:right;margin-left:5px;margin-right:5px;" class="fa fa-plus" aria-hidden="true"></i>');
			icon.attr('id','addrow_'+resource.id);
			icon.addClass('addrow');
			icon.data('resource', resource.id);
			icon.hide();
			if(resource.disabled != 1)
				col.append(icon);
			row.append(col);
		}
		if(this.col[2])
		{
			var col = $('<th>');
			col.css('padding',0);
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		var color='#DCDCDC';
		var i=0;
		for (var week in weekArray) 
		{
			var today = weekArray[week].includes(1);
			
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(weekArray[week]).length;
			col = $('<th colspan="'+colspan+'">');
			col.css('padding',0);
			col.attr('id','cell_'+i+'_'+resource.id);
			col.addClass("resource_cell");//_"+week);
			i++;
			col.click(function()
			{
				//alert($(this));
			});
			//year = week.substring(0,4);
			//weeknum = week.substring(5);
			//col.html(weeknum);
			//col.css('background-color',color);
			col.css('font-size','12px');
			row.append(col);
		}
		this.table.append(row);
		resource.element = row;
		this.resources.push(resource);
		var projects = resource.projects;
		resource.projects =[];
		for(var i=0;i<projects.length;i++)
		{
			var prow = self.GenerateProjectRow(resource,projects[i]);
			var parnt  = resource.element;
			for(var i=0;i<resource.projects.length;i++)
			{
				if(resource.projects[i] !== undefined)
					parnt = resource.projects[i];
			}
			parnt.after(prow);
			prow.hide();
			
			resource.projects.push(prow);

			for(var week in projects[i].data)
			{
				var cell = prow.find("."+week);
				var util = projects[i].data[week];
				if(cell.length)
				{
					window.SelectCell(cell,util);
				}
			}	
		}
		return row;
	}
	this.GenerateProjectRow =  function(resource,project=null)
	{
		if(resource.pindex === undefined)
			resource.pindex = 0;
		
		var pindex = resource.pindex;
		var weekArray = this.tabledata.weeks;
		var c=1;
		var r=123;
		var row = $('<tr>');
		row.addClass("project_"+resource.id);
		row.attr('id',"project_"+resource.id+'_'+pindex);
		row.data('resource', resource.id);
		row.data('pindex',pindex);
		var combo = self.projectcombo(project);
		combo.attr('id','combo_'+resource.id+'_'+pindex);
		combo.data('resource', resource.id);
		combo.data('pindex',pindex);
		
		if(this.col[0])
		{
			var col = $('<th>');
			col.css('padding',0);
			col.append(combo);
			row.append(col);
		}
		if(this.col[1])
		{
			var col = $('<th>');
			col.css('padding',0);
			var icon=$('<i title="Delete row" style="margin-top:3px;font-size:12px; float:right;margin-left:5px;margin-right:5px;" class="fa fa-times-circle" aria-hidden="true"></i>');
			icon.attr('id','delete_'+resource.id+'_'+pindex);
			icon.addClass('delete');
			icon.data('resource', resource.id);
			icon.data('pindex',pindex);
			icon.hide();
			if(project !== null)
			{
				
				if(project.closed==1)
				{
					icon.removeClass('fa-times-circle');
					icon.addClass('fa-codiepie');
					icon.attr('title',"Project is closed");
					//icon.addClass('fa-archive');
					//icon.hide();
					
				}
			}
			icon.click(function(){
				var resourceid=$(this).data("resource");
				var pindex=$(this).data("pindex");
				var resource = window.getresource(resourceid);
				var project = null;
				var i=0;
				for(i=0;i<resource.projects.length;i++)
				{
					project = $(resource.projects[i]);
					var ppindex = project.data("pindex");
					if(pindex == ppindex)
						break;
					project = null;
						
				}
				if(project != null)	
				{
					var select = project.find('select');
					var project_id = select.val()*1;
					var project_name= select.find(":selected").text();
					project.remove();
					
					delete resource.projects[i];
					
					updated=0;
					for(var i=0;i<resource.projects.length;i++)
					{
						if(resource.projects[i] !== undefined)
						{
							var projectrow = resource.projects[i];
							var cells = projectrow.find('.project_cell');
							for(var j=0;j<cells.length;j++)
							{
								updated=1;
								window.UpdateResourceUtil($(cells[j]))
							}
							break;
						}
					}
					if(updated == 0)
					{
						var cells = resource.element.find('.resource_cell');
						for(var j=0;j<cells.length;j++)
						{
							var cell = cells[j];
							$(cell).data('util',0);
							$(cell).html('');
							window.ColorCell($(cell));
						
							//window.UpdateResourceUtil($(cells[j]))
						}
					}
					//resource_cell//MUMTAZ
					//window.ColorCell = function(cell)
					//window.UpdateResourceUtil()
					
				}
				//for(i=0;i<resource.projects.length;i++)
				//{
			//		console.log(resource.projects[i]);
				//}
				//console.log(resource);
				//$("#project_"+resource.id+'_'+pindex).remove();
				//$("#project_"+resource.id+'_'+pindex).removed=1;
				//alert(resourceid+"_"+pindex);
			});
			col.append(icon);
			row.append(col);
		}
		if(this.col[2])
		{
			var col = $('<th>');
			col.css('padding',0);
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		var color='#DCDCDC';
		var i=0;
		for (var week in weekArray) 
		{
			var today = weekArray[week].includes(1);
			
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(weekArray[week]).length;
			col = $('<th colspan="'+colspan+'">');
			col.css('padding',0);
			col.data('week', week);
			col.data('resource', resource.id);
			col.data('pindex', resource.pindex);
			if(project != null)
				if(project.closed == 1)
					col.data('disabled', 1);
			
			col.data('cindex',i);
			col.addClass("project_cell");//_"+week);
			col.addClass(week);//_"+week);
			col.attr('id','cell_'+i+'_'+resource.id+'_'+resource.pindex);
			i++;
			//col.click(function(){
			//	window.lastutil = $(this).data('util');
			//	console.log(window.lastutil);
			//});
			col.click(function(event){
				if($(this).data('disabled')==1)
				{
					alert("This project is closed");
					return;
				}
				var util = $(this).data('util');
				console.log(util);
				if((util === undefined)||(util === 0))
					window.SelectCell($(this),window.lastutil);
				else
					window.SelectCell($(this),util);
			});
			
			
			//year = week.substring(0,4);
			//weeknum = week.substring(5);
			//col.html(weeknum);
			//col.css('background-color',color);
			col.css('font-size','12px');
			row.append(col);
		}
		row.pindex = resource.pindex++;
		if(project != null)
		{
			if(project.closed == 1)
				row.css('background-color','#DCDCDC');
			//else
			//	row.css('background-color','#D8EBFC');
		}
		//else
		//	row.css('background-color','#D8EBFC');
		
		//this.table.append(row);
		//resource.element = row;
		//this.resources.push(resource);
		return row;
	}
	this.GenerateWeekRow =  function(r)
	{
		weekArray = this.tabledata.weeks;
		var c=1;
		var row = $('<tr>');
		row.addClass("rowweek");
		
		if(this.col[0])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.html('Week');
			row.append(col);
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.html('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp');
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		var color='#DCDCDC';
		for (var week in weekArray) 
		{
			var today = weekArray[week].includes(1);
			
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(weekArray[week]).length;
			col = $('<th colspan="'+colspan+'">');
			col.attr('id','r'+r+'c'+c++);
			year = week.substring(0,4);
			weeknum = week.substring(5);
			col.html(weeknum);
			col.css('background-color',color);
			col.css('font-size','12px');
			row.append(col);
		}
		return row;
	}
	this.GenerateSprintRow =  function(r)
	{
		sprintArray = this.tabledata.sprints;
		var row = $('<tr>');
		var c=1;
		
		if(this.col[0])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.html('Sprint');
			row.append(col);
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		
		var color='#DCDCDC';
		for (var sprint in sprintArray) 
		{
			sprintdata = sprintArray[sprint];
			//console.log(sprintdata);
			start = sprintdata[0].date;
			start=new Date(start).toString().slice(4, 10);
			end = sprintdata[sprintdata.length-1].date;
			end=new Date(end).toString().slice(4, 10);
			
			var today = sprintdata.find(function(obj, index) 
			{
				if(obj.today == 1)
					return true;
			});
			
		
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(sprintArray[sprint]).length;
			col = $('<th colspan="'+colspan+'">');
			col.attr('id','r'+r+'c'+c++);
			var html=sprint+"<br><span style='color:green;font-size:8px;'>"+start+"-"+end+"</span>";
			col.attr('title',start+"-"+end);
			if(colspan < 21)
				html = '';
			col.html(html);
			col.css('background-color',color);
			
			row.append(col);
		}
		return row;
	}
	this.GenerateSubSprintRow =  function(cls)
	{
		sprintArray = this.tabledata.sprints;
		var row = $('<tr>');
		row.addClass(cls);
		var c=1;
		
		if(this.col[0])
		{
			var col = $('<th>');
			row.append(col);
			col.html("Epics");
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th>');
			row.append(col);
		}
		
		var color='#DCDCDC';
		for (var sprint in sprintArray) 
		{
			sprintdata = sprintArray[sprint];
			//console.log(sprintdata);
			start = sprintdata[0].date;
			start=new Date(start).toString().slice(4, 10);
			end = sprintdata[sprintdata.length-1].date;
			end=new Date(end).toString().slice(4, 10);
			
			var today = sprintdata.find(function(obj, index) 
			{
				if(obj.today == 1)
					return true;
			});
			
		
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			colspan = Object.keys(sprintArray[sprint]).length;
			col = $('<th colspan="'+colspan+'">');
			var html=sprint+"<br><span style='color:green;font-size:8px;'>"+start+"-"+end+"</span>";
			col.attr('title',start+"-"+end);
			if(colspan < 21)
				html = '';
			col.html(html);
			col.css('background-color',color);
			
			row.append(col);
		}
		return row;
	}
	this.GenerateSprintDataRow =  function(id,classname='noclass')
	{
		sprintArray = this.tabledata.sprints;
		var row = $('<tr>');
		var c=1;
		row.addClass(classname);
		if(this.col[0])
		{
			var col = $('<th style="padding: 0px 5px 0px 15px;">');
			col.attr('id',id+'_cell'+c);
			col.html('<i style="font-size:10px;" data-key="'+classname+'" class="expand fa fa-plus" aria-hidden="true"></i>');
			var span = $('<span style="padding-left:5px;padding-right:5px;font-weight:bold;"></span>');
			span.attr('id',id+'_'+c++);
			col.append(span);
			col.addClass('sticky-col');
			//col.html('Sprint');
			row.append(col);
		}
		
		if(this.col[1])
		{
			var col = $('<th style="text-align:left;padding: 0px 5px 0px 5px;">');
			col.attr('id',id+'_'+c++);
			col.html('<span style="padding-left:15px;padding-right:5px">'+''+'</span>');
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th style="padding:0px;">');
			col.attr('id',id+'_'+c++);
			col.html('<span style="padding-left:5px;padding-right:5px">'+''+'</span>');
			row.append(col);
		}
		
		var color='#DCDCDC';
		for (var sprint in sprintArray) 
		{
			sprintdata = sprintArray[sprint];
			//console.log(sprintdata);
			start = sprintdata[0].date;
			start=new Date(start).toString().slice(4, 10);
			end = sprintdata[sprintdata.length-1].date;
			end=new Date(end).toString().slice(4, 10);
			
			var today = sprintdata.find(function(obj, index) 
			{
				if(obj.today == 1)
					return true;
			});
			
		
			if(today)
			{
				color='#E9FFE2';
			}
			else
			{
				if(color=='#E9FFE2')
					color='#FFFFFF';
			}
			colspan = Object.keys(sprintArray[sprint]).length;
			col = $('<th colspan="'+colspan+'">');
			col.attr('id',id+'_'+sprint);
			col.css('font-size','10px');
			col.css('padding','0px');
			//var html=sprint+"<br><span style='color:green;font-size:8px;'>"+start+"-"+end+"</span>";
			//col.attr('title',start+"-"+end);
			if(colspan < 21)
				html = '';
			//col.html(html);
			col.css('background-color',color);
			//col.css('color','blue');
			row.append(col);
		}
		return row;
	}
	this.GenerateSprintChildDataRow =  function(id,classname='noclass',expand=0)
	{
		sprintArray = this.tabledata.sprints;
		var row = $('<tr>');
		var c=1;
		row.addClass(classname);
		
		if(this.col[0])
		{
			var col = $('<th style="text-align:left;padding: 0px 5px 0px 17px;">');
			//col.attr('id',id+'_'+c++);
			if(expand==1)
			{
				col.html('<i style="text-align:left;font-size:10px" class="expand fa fa-plus" aria-hidden="true"></i>');
				var span = $('<span style="text-align:left;font-size:10px;padding-left:0px;padding-right:5px;font-weight:normal;"></span>');
			}
			else
				var span = $('<span style="text-align:left;font-size:10px;padding-left:20px;padding-right:5px;font-weight:normal;"></span>');
			span.attr('id',id+'_'+c++);
			col.append(span);
			col.addClass('sticky-col');
			//col.html('Sprint');
			row.append(col);
		}
		
		if(this.col[1])
		{
			var col = $('<th style="font-weight: normal;text-align:left;padding: 0px 5px 0px 5px;">');
			col.attr('id',id+'_'+c++);
			//col.html('<span style="padding-left:35px;padding-right:5px">'+'Sprint'+'</span>');
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th style="padding:0px;">');
			col.attr('id',id+'_'+c++);
			col.html('<span style="padding-left:5px;padding-right:5px">'+''+'</span>');
			row.append(col);
		}
		
		var color='#DCDCDC';
		for (var sprint in sprintArray) 
		{
			sprintdata = sprintArray[sprint];
			//console.log(sprintdata);
			start = sprintdata[0].date;
			start=new Date(start).toString().slice(4, 10);
			end = sprintdata[sprintdata.length-1].date;
			end=new Date(end).toString().slice(4, 10);
			
			var today = sprintdata.find(function(obj, index) 
			{
				if(obj.today == 1)
					return true;
			});
			
		
			if(today)
			{
				color='#E9FFE2';
			}
			else
			{
				if(color=='#E9FFE2')
					color='#FFFFFF';
			}
			colspan = Object.keys(sprintArray[sprint]).length;
			col = $('<th colspan="'+colspan+'">');
			col.attr('id',id+'_'+sprint);
			col.css('font-size','10px');
			col.css('padding','0px');
			//var html=sprint+"<br><span style='color:green;font-size:8px;'>"+start+"-"+end+"</span>";
			//col.attr('title',start+"-"+end);
			if(colspan < 21)
				html = '';
			//col.html(html);
			col.css('background-color',color);
			col.css('color','#0066FF');
			row.append(col);
		}
		return row;
	}
	this.GenerateDayRow =  function(r)
	{
		dayArray =  this.tabledata.days;
		var row = $('<tr>');
		row.addClass("rowday");
		var c=1;
		
		if(this.col[0])
		{
			var col = $('<th style="padding:0px;">');
			col.attr('id','r'+r+'c'+c++);
			col.html('<span style="padding:0px;font-weight:bold;">Days</span>');
			row.append(col);
			col.addClass('sticky-col');
		}
		
		if(this.col[1])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			col.addClass('sticky-col1');
			row.append(col);
		}
		
		if(this.col[2])
		{
			var col = $('<th>');
			col.attr('id','r'+r+'c'+c++);
			row.append(col);
		}
		var color='#DCDCDC';
		var todate =  new Date();
		todate = todate.toDateString();
		for (var day in dayArray) 
		{
			var date =  new Date(day);
			
			var today = (todate === date.toDateString());
			
			if(today)
				color=this.today_color;
			else
			{
				if(color==this.today_color)
					color='#FFFFFF';
			}
			col = $('<td>');
			col.attr('id','r'+r+'c'+c++);
			col.css('background-color',color);
			
			col.attr('title',date.toDateString());
			//col.html('<span style="font-size:5px;color:red;">'+day.substring(8,10)+'</span>');
			row.append(col);
		}
		return row;
	}
	self.CreateTable(tag);
}