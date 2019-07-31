$(function() {

	DependencyOverview.showreports = false;
	DependencyOverview.showanimations = true;
	$("#showreports").prop("checked", DependencyOverview.showreports);
	$("#showanimations").prop("checked", DependencyOverview.showanimations);

	$("#selectmenugroup, #selectstatistikgroup, #selectview").val("null");

	$("#selectmenugroup, #selectstatistikgroup, #selectview").change(
		function ()
		{
			var selectedDropdownData = DependencyOverview.dropdowns[$(this).prop("id")];

			DependencyOverview.selectedDropdown.object = $(this);
			DependencyOverview.selectedDropdown.action = selectedDropdownData.action;
			DependencyOverview.selectedDropdown.titleprefix = selectedDropdownData.titleprefix;

			DependencyOverview.initDependencyOverview();
		}
	);

	$("#showreports").change(
		function ()
		{
			DependencyOverview.showreports = $(this).prop("checked");
			DependencyOverview.initDependencyOverview();
		}
	);

	$("#showanimations").change(
		function ()
		{
			DependencyOverview.showanimations = $(this).prop("checked");
			DependencyOverview.initDependencyOverview();
		}
	);
});

var DependencyOverview = {
	all_issues: null,
	settings:
	{
		showreports: false,
		showanimations: true
	},
	dropdowns:
	{
		"selectmenugroup":
		{
			"action": "getMenuGroupDependencies",
			"titleprefix": "Menügruppe"
		},
		"selectstatistikgroup":
		{
			"action": "getStatistikGroupDependencies",
			"titleprefix": "Statistikgruppe"
		},
		"selectview":
		{
			"action": "getViewDependencies",
			"titleprefix": "View"
		}
	},
	selectedDropdown:
	{
		"object": null,
		"action": "",
		"titleprefix": ""
	},
	currentObjectTitle: "",
	callDependencyOverview: function(data, callback)
	{
		$.ajax({
			type: "GET",
			url: "reports_dependency_overview_router.php",
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
	initDependencyOverview: function()
	{
		DependencyOverview.lockInput();

		var selectedDropdown = DependencyOverview.selectedDropdown;
		var selectedDropdownObject = selectedDropdown.object;
		DependencyOverview.toggleDropDownSelection(selectedDropdownObject.prop("id"));

		var object_id = selectedDropdownObject.val();

		if (object_id === "null")
			return;

		var object_name = selectedDropdownObject.find("option:selected").text();

		DependencyOverview.currentObjectTitle = selectedDropdown.titleprefix + " " + object_name.trim();

		DependencyOverview.initDependencyOverviewCall(
			{"action": selectedDropdown.action, "object_id": object_id}
		);
	},
	initDependencyOverviewCall: function(callparams)
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
					return DependencyOverview.getGraphData(data);
				};

				$.when(
					viewIssuesCall(),
					statistikIssuesCall(),
					//chartIssuesCall(),
					drawGraphCall()
				).done(
					function(viewCall, statistikCall, /*chartCall, */drawGraphCall)
					{
						DependencyOverview.all_issues = all_issues;
						DependencyOverview.drawIssues(all_issues, drawGraphCall);
					}
				);
			}
		)
	},
	getGraphData: function(data)
	{
		var nodes = [];
		var renderdata = [];

		for (var i = 0; i < data.length; i ++)
		{
			var viewdependencies = data[i];
			var statistiken = viewdependencies.statistiken;

			if ($.isNumeric(viewdependencies.view_id) && viewdependencies.view_kurzbz !== null)
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

				if (statistik_kurzbz == null)
					continue;

				if ($.isNumeric(viewdependencies.view_id))
				{
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
					if (DependencyOverview.showreports)
					{
						var reports = statistik.charts[k].reports;

						for (var r = 0; r < reports.length; r++)
						{
							var reportbez = reports[r].title + "_" + reports[r].report_id;
							renderdata.push({from: reportbez, to: chartbez});

							var node = DependencyOverview.getReportNode(reportbez);

							nodes.push(node);
						}
					}
				}

				if (DependencyOverview.showreports)
				{
					var statistikresports = statistik.reports;
					for (var rst = 0; rst < statistikresports.length; rst++)
					{
						var reportstbez = statistikresports[rst].title + "_" + statistikresports[rst].report_id;
						renderdata.push({from: reportstbez, to: statistik_kurzbz});

						var noderpst = DependencyOverview.getReportNode(reportstbez);

						nodes.push(noderpst);
					}
				}
			}
		}

		DependencyOverview.unlockInput();

		return DependencyOverview.drawGraph(nodes, renderdata);
	},
	getReportNode: function(reportbez)
	{
		return {
			id: reportbez,
			marker: {
				symbol: "url(../include/images/Report.svg)",
				height: 40,
				width: 30
			},
			events: {
				click: function ()
				{
					var id = this.name.substring(this.name.lastIndexOf('_') + 1, this.name.length);
					window.open("../cis/vorschau.php?report_id=" + id);
					window.open("../vilesci/report_details.php?report_id=" + id);
				}
			}
		}
	},
	drawIssues: function(all_issues, chart)
	{
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
		}
	},
	drawGraph: function(nodes, renderdata)
	{
		$("#netgraph").addClass("panel panel-default");
		$("#netgraph").css("margin-top", "15px");

		return Highcharts.chart('netgraph', {
			chart: {
				type: 'networkgraph',
				height: '80%',
				spacingRight: 40,
				spacingLeft: 40
			},
			title: {
				text: 'Abhängigkeiten ' + DependencyOverview.currentObjectTitle
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
							if (DependencyOverview.all_issues[i].objectname === this.key)
							{
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
						enableSimulation: DependencyOverview.showanimations,
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
	},
	toggleDropDownSelection: function(selectedDropdownId)
	{
		var selects = $("select.form-control");

		selects.each(
			function()
			{
				if ($(this).prop("id") !== selectedDropdownId)
					$(this).val("null");
			}
		);
	},
	lockInput: function()
	{
		$("select, input").prop("disabled", true);
	},
	unlockInput: function()
	{
		$("select, input").prop("disabled", false);
	}
};