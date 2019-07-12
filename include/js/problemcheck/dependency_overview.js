$(function() {
/*	var view_id = $("selectview").val();
	var view_kurzbz = $("selectview").find("option:selected").text();
	DependencyOverview.drawSingleViewGraph(view_id, view_kurzbz);*/

	$("#selectview").change(
		function ()
		{
			$("#selectgroup").val("null");
			var view_id = $(this).val();

			if (view_id === "null")
				return;

			var view_kurzbz = $(this).find("option:selected").text();

			DependencyOverview.initDependencyOverview({"action": "getViewDependencies", "view_id": view_id}, view_kurzbz);
		}
	);

	$("#selectgroup").change(
		function ()
		{
			$("#selectview").val("null");
			var groupname = $(this).val();

			if (groupname === "null")
				return;

			DependencyOverview.initDependencyOverview({"action": "getGroupDependencies", "groupname": groupname}, "Gruppe " + groupname);
		}
	);
});

var DependencyOverview = {
	all_issues: null,
	callDependencyOverview: function(data, callback)
	{
		$.ajax({
			type: "GET",
			url: "dependency_overview_router.php",
			dataType: "json",
			data: data,
			success: callback
		});
	},
	callProblemcheck: function(data, callback)
	{
		return $.ajax({
			type: "POST",
			url: "reports_problemcheck_router.php",
			dataType: "json",
			data: data,
			success: callback
		});
	},
	initDependencyOverview: function(callparams, objectname)
	{
		DependencyOverview.callDependencyOverview(
			callparams,
			function(data)
			{
				var all_issues = [];

				var view_ids = [];
				var statistik_kurzbzarr = [];
				var chart_ids = [];

				for (var i = 0; i < data.length; i++)
				{
					var viewdependencies = data[i];
					//console.log(viewdependencies);
					if ($.isNumeric(viewdependencies.view_id))
						view_ids.push(viewdependencies.view_id);
					for (var j = 0; j < viewdependencies.statistiken.length; j++)
					{
						statistik_kurzbzarr.push(viewdependencies.statistiken[j].statistik_kurzbz);

						for (var k = 0; k < viewdependencies.statistiken[j].charts.length; k++)
						{
							console.log(viewdependencies.statistiken[j].charts);
							chart_ids.push(viewdependencies.statistiken[j].charts[k].chart_id)
						}
					}
				}

				var viewIssuesCall = function()
				{
					if (view_ids.length > 0)
					{
						return DependencyOverview.callProblemcheck(
							{"action": "getViewIssues", "view_ids": view_ids},
							function (viewissues)
							{
								//console.log(viewissues);
								for (var viewname in viewissues)
								{
									all_issues.push({
										objectname: viewname,
										issues:  viewissues[viewname].issues
									});
								}
							}
						);
					}
				};

				var statistikIssuesCall = function(){
					if (statistik_kurzbzarr.length > 0)
					{
						return DependencyOverview.callProblemcheck(
							{"action": "getStatistikIssues", "statistik_ids": statistik_kurzbzarr},
							function (statistikissues)
							{
								//console.log(statistikissues);
								for (var statistikname in statistikissues)
								{
									all_issues.push({
										objectname: statistikname,
										issues: statistikissues[statistikname].issues
									});
								}
							}
						);
					}
				};

				var chartIssuesCall = function(){
					if (chart_ids.length > 0)
					{
						return DependencyOverview.callProblemcheck(
							{"action": "getChartIssues", "chart_ids": chart_ids},
							function (chartissues)
							{
								//console.log(chartissues);
								for (var chartname in chartissues)
								{
									all_issues.push({
										objectname: chartname,
										issues: chartissues[chartname].issues
									});
								}
							}
						);
					}
				};

				var drawGraphCall = function()
				{
					return DependencyOverview.getGraphData(data, objectname);
				};

				$.when(
					viewIssuesCall(),
					statistikIssuesCall(),
					//chartIssuesCall(),
					drawGraphCall()
				).done(
					function(viewCall, statistikCall, /*chartCall, */drawGraphCall)
					{
						console.log("finished now...");
						console.log(all_issues);
						DependencyOverview.all_issues = all_issues;
						DependencyOverview.drawIssues(all_issues, drawGraphCall);
					}
				);
			}
		)
	},
/*	drawSingleViewGraph: function(view_id, view_kurzbz)
	{
		DependencyOverview.callDependencyOverview(
			{"action": "getViewDependencies", "view_id": view_id},
			function(data)
			{
				DependencyOverview.getGraphData(data, view_kurzbz);
			}
		)
	},*/
	getGraphData: function(data, titleobject)
	{
		//console.log(all_issues);
		var nodes = [];
		var renderdata = [];

		for (var i = 0; i < data.length; i ++)
		{
			var viewdependencies = data[i];
			var statistiken = viewdependencies.statistiken;

			if ($.isNumeric(viewdependencies.view_id))
			{

				var viewbez = viewdependencies.view_kurzbz + "_" + viewdependencies.view_id;

				nodes.push(
					{
						id: viewbez,
						marker: {
							symbol: "url(../include/images/View.svg)",
							height: 40,
							width: 30
						},
						events: {
							click: function ()
							{
								var view_id = this.name.substring(this.name.lastIndexOf('_') + 1, this.name.length);
								window.open("../vilesci/view_details.php?view_id=" + view_id);
							}
						}
					}
				);
			}

			for (var j = 0; j < statistiken.length; j++)
			{
				var statistik = statistiken[j];
				var statistik_kurzbz = statistik.statistik_kurzbz;

				if ($.isNumeric(viewdependencies.view_id))
				{
					//statistikdependency.push(viewbez);
					//statistikdependency.push(statistik_kurzbz);
					renderdata.push({from: viewbez, to: statistik_kurzbz});
				}

				nodes.push(
					{
						id: statistik_kurzbz,
						marker: {
							symbol: "url(../include/images/Statistik.svg)",
							height: 27,
							width: 20
						},
						events: {
							click: function ()
							{
								window.open("../cis/vorschau.php?statistik_kurzbz=" + this.name);
								window.open("../../../vilesci/stammdaten/statistik_details.php?statistik_kurzbz=" + this.name);
							}
						}
					}
				);

				for (var k = 0; k < statistik.charts.length; k++)
				{
					var chartbez = statistik.charts[k].title + "_" + statistik.charts[k].chart_id;
					renderdata.push({from: statistik_kurzbz, to: chartbez});

					nodes.push(
						{
							id: chartbez,
							marker: {
								symbol: "url(../include/images/Chart.svg)",
								height: 27,
								width: 20
							},
							events: {
								click: function ()
								{

									var id = this.name.substring(this.name.lastIndexOf('_') + 1, this.name.length);
									window.open("../cis/vorschau.php?chart_id=" + id);
									window.open("../vilesci/chart_details.php?chart_id=" + id);
								}
							}
						}
					);
				}
			}
		}
		return DependencyOverview.drawGraph(nodes, renderdata, titleobject);
	},
	drawIssues: function(all_issues, chart)
	{
		//console.log(all_issues.view_issues);

		if ($.isArray(all_issues))
		{
			for (var v = 0; v < all_issues.length; v++)
			{
				var viewissues = all_issues[v];
				var hasError = false;

				if (chart.series)
				{
					for (var n = 0; n < chart.series[0].nodes.length; n++)
					{
						if (viewissues.objectname === chart.series[0].nodes[n].id)
						{
							var dataLabels = {};
							for (var i = 0; i < viewissues.issues.length; i++)
							{
								var viewissue = viewissues.issues[i];
								if (viewissue.type === "error")
								{
									dataLabels.color = "#a94442";
									dataLabels.formatter =  function () { return "! " + this.key };
									//dataLabels.borderRadius = 2;
									//dataLabels.shape = 'callout';
									hasError = false;
								}
								else if (viewissue.type === "warning")
								{
									if (!hasError)
									{
										dataLabels.color = "#8a6d3b";
										dataLabels.formatter =  function () { return "! " + this.key };
									}
								}
							}
							chart.series[0].nodes[n].options.dataLabels = dataLabels;
						}

					}
				}
			}

			chart.redraw();
/*
			console.log(chart.series[0].nodes);
			chart.series[0].update(
				{
					nodes: chart.series[0].nodes
				}
			);*/
		}



	},
	drawGraph: function(nodes, renderdata, titleobject)
	{
		//console.log(nodes);

		$("#netgraph").addClass("panel panel-default");
		$("#netgraph").css("margin-top", "15px");

		return Highcharts.chart('netgraph', {
			chart: {
				type: 'networkgraph',
				height: '80%',
				spacingRight: 40,
				spacingLeft: 40/*,
				events: {
					render: eventcallback
				}*/
			},
			title: {
				text: 'Abhängigkeiten '+titleobject
			},
			/*subtitle: {
				text: 'A Force-Directed Network Graph in Highcharts'
			},*/
			tooltip: {
				useHTML: true,
				formatter: function() {
					var issuetext = "";

					if ($.isArray(DependencyOverview.all_issues))
					{
						for (var i = 0; i < DependencyOverview.all_issues.length; i++)
						{
/*							console.log(DependencyOverview.all_issues[i].objectname);
							console.log(this.key);*/
							if (DependencyOverview.all_issues[i].objectname === this.key)
							{
								//issuetext = DependencyOverview.all_issues[i].issues.join("<br>");
								var issues = DependencyOverview.all_issues[i].issues;

								for (var j = 0; j < issues.length; j++)
								{
									issuetext += "<br>";

									if (issues[j].type === "error")
										issuetext += "<span class='text-danger'><i class='fa fa-exclamation'></i> ";
									else if (issues[j].type === "warning")
										issuetext += "<span class='text-warning'><i class='fa fa-warning'></i> ";

									issuetext += issues[j].text + "</span>";
								}
								break;
							}
						}
					}

					return this.key + issuetext;
				}
			},
			plotOptions: {
				networkgraph: {
					keys: ['from', 'to'],
					layoutAlgorithm: {
						linkLength: 35,
						enableSimulation: true,
						friction: -0.9
					}
				}
			},
			series: [{
				dataLabels: {
					enabled: true,
					linkFormat: '',
					allowOverlap: true
				},
				nodes: nodes,
				data: renderdata
			}]
		});
	}
};