$(function() {

	// Initialwerte
	DependencyOverview.showreports = false;
	DependencyOverview.showanimations = true;
	$("#showreports").prop("checked", DependencyOverview.showreports);
	$("#showanimations").prop("checked", DependencyOverview.showanimations);

	var allDropdowns = $("#selectmenugroup, #selectstatistikgroup, #selectview, #selectansicht");

	allDropdowns.val("null");

	// Dropdown Events hinzufügen
	allDropdowns.change(
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
	all_issues: null,//Zwischenspeichern der Issues
	settings:
	{
		showreports: false,//Reports anzeigen?
		showanimations: true//Bewegung der Reporting Objekte aktivieren? - wenn false, wird gleich das Endresultat angezeigt,
							//aber es wird keine Aktivität angezeigt während längerer Ladazeiten.
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
		},
		"selectansicht":
		{
			"action": "getAnsichtDependencies",
			"titleprefix": ""
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
	//initiiert das Laden der Abhängigkeiten
	initDependencyOverview: function()
	{
		var selectedDropdown = DependencyOverview.selectedDropdown;

		if (selectedDropdown.object == null || selectedDropdown.object.val() === "null")
			return;

		DependencyOverview.lockInput();

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
					if ($.isNumeric(viewdependencies.view_id) && ($.inArray(viewdependencies.view_id, view_ids) < 0))
						view_ids.push(viewdependencies.view_id);

					for (var j = 0; j < viewdependencies.statistiken.length; j++)
					{
						var statistik_kurzbz = viewdependencies.statistiken[j].statistik_kurzbz;
						if ($.inArray(statistik_kurzbz, statistik_kurzbzarr) < 0)
							statistik_kurzbzarr.push(statistik_kurzbz);

						for (var k = 0; k < viewdependencies.statistiken[j].charts.length; k++)
						{
							var chart_id = viewdependencies.statistiken[j].charts[k].chart_id;
							if ($.inArray(chart_id, chart_ids) < 0)
								chart_ids.push(chart_id)
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

				//nicht verwendet aufgrund zu langer Ladezeit
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

				// Holen der Issues parallel gleichzeitig mit Zeichnen des Graphen.
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
		var nodes = [];//angezeigte Knoten
		var renderdata = [];//Knotenverbindungen

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
	drawGraph: function(nodes, renderdata)
	{
		$("#netgraph").addClass("panel panel-default");

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
			tooltip: {
				//farbliche Anzeige der Warnings/Errors
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
						friction: -0.9,
						integration: 'euler',
						approximation: 'barnes-hut'
					}
				}
			},
			series: [{
				dataLabels: {
					enabled: true,
					linkFormat: '',
					allowOverlap: true//Knotenlabels auch bei Überschneidungen angezeigt
				},
				nodes: nodes,
				data: renderdata
			}]
		});
	},
	drawIssues: function(all_issues, chart)
	{
		if ($.isArray(all_issues))
		{
			for (var a = 0; a < all_issues.length; a++)
			{
				var issues = all_issues[a];
				var hasError = false;

				if (chart.series)
				{
					for (var n = 0; n < chart.series[0].nodes.length; n++)
					{
						if (issues.objectname === chart.series[0].nodes[n].id)
						{
							var dataLabels = {};
							for (var i = 0; i < issues.issues.length; i++)
							{
								var viewissue = issues.issues[i];
								//farbliche Anzeige der Warnings/Errors
								if (viewissue.type === "error")
								{
									dataLabels.color = "#a94442";
									dataLabels.formatter =  function () { return "! " + this.key };
									hasError = true;
								}
								else if (viewissue.type === "warning" && !hasError)
								{
									dataLabels.color = "#8a6d3b";
									dataLabels.formatter =  function () { return "! " + this.key };
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