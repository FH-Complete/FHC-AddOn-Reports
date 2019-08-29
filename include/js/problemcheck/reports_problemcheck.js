$(function() {
	Problemcheck.initProblemcheck();

	$("#objecttype").change(
		function ()
		{
			Problemcheck.initProblemcheck();
		}
	);

	var options = $("#showerrors, #showpassed, #showwarnings");

	options.prop("checked", "checked");
	options.change(
		function()
		{
			Problemcheck.drawProblemcheck(Problemcheck.allProblemData, $("#objecttype").val())
		}
	);
});

var Problemcheck =
{
	actions: {
		"view": "getViewIssues",
		"statistik": "getStatistikIssues",
		"chart": "getChartIssues"
	},
	// Parameter pro Objekttyp für Anzeige der issues
	viewparams: {
		"objectidname": "view_id",
		"detailslink": "../vilesci/view_details.php"
	},
	statistikparams: {
		"objectidname": "statistik_kurzbz",
		"detailslink": "../../../vilesci/stammdaten/statistik_details.php",
		"objecticon": "Statistik.svg"
	},
	chartparams: {
		"objectidname": "chart_id",
		"detailslink": "../vilesci/chart_details.php",
		"objecticon": "Chart.svg"
	},
	allProblemData : null,
	filteredProblemData : null,//filtered by checkboxes, e.g. only errors
	settings: {
		"showerrors": true,
		"showpassed": true,
		"showwarnings": true
	},
	// initialisiert Ajax call zum Holen der Problemcheckdaten
	initProblemcheck: function ()
	{
		var action = $("#objecttype").val();

		if (action == "null")
			return;

		Problemcheck.showVeil();
		Tablesort.addTablesorter("checktableparent", [[0, 0], [1,0], [2, 0]], ["zebra", "filter"]);

		Problemcheck.callProblemcheck(action, function (data)
			{
				// wenn Charts - zusätzlich issues die bei Ausführen der Grafik in JavaScript auftreten hinzufügen
				if (action == Problemcheck.actions.chart)
					data = Problemcheck.checkChartsExecution(data);
				Problemcheck.drawProblemcheck(data, action);
			}
		);
	},
	callProblemcheck: function(actionparam, callback)
	{
		$.ajax({
			type: "GET",
			url: "reports_problemcheck_router.php",
			dataType: "json",
			data: {action: actionparam},
			success: callback
		});
	},
	checkChartsExecution: function(data)
	{
		for (var index in data)
		{
			var item = data[index];

			// Ausführen des Charts, Fehler mit try/catch finden
			try
			{
				var dataitem = JSON.parse(item.data);
				$("#charttest").highcharts(dataitem);
			}
			catch (err)
			{
				var issue = {
					type: "error",
					text: ""
				};

				if (typeof err == 'string' && err.length > 0)
					issue.text = "JS Fehler: " + err;
				else if (typeof err.message == 'string' && err.message.length > 0)
					issue.text = "JS Fehler: " + err.message;
				else
					issue.text = "JS Fehler beim Erstellen des Charts";

				data[index].issues.push(issue);
			}
			return data;
		}
	},
	drawProblemcheck: function(data, action)
	{
		Problemcheck.allProblemData = data;
		Problemcheck.setFilteredProblemdata();
		$("#checktable").empty();
		var params = null;

		switch(action)
		{
			// je nach ausgewähltem Typ (View, Statistik, ...) Probleme anzeigen
			case Problemcheck.actions.view:
				params = Problemcheck.viewparams;
				break;
			case Problemcheck.actions.statistik:
				params = Problemcheck.statistikparams;
				break;
			case Problemcheck.actions.chart:
				params = Problemcheck.chartparams;
				break;
		}

		Problemcheck.drawObjectProblems(params);
		Problemcheck.finishDrawProblemcheck();
	},
	// bei Auswahl eines Filters (z.B. errors, warnings, passed anzeigen/verstecken) Daten filtern
	setFilteredProblemdata: function()
	{
		Problemcheck.settings.showerrors = $("#showerrors").prop("checked");
		Problemcheck.settings.showwarnings = $("#showwarnings").prop("checked");
		Problemcheck.settings.showpassed = $("#showpassed").prop("checked");

		var filteredProblemData = [];
		var settings = Problemcheck.settings;
		for (var index in Problemcheck.allProblemData)
		{
			var item = Problemcheck.allProblemData[index];
			var issues = item.issues;

			if ($.isArray(issues) && issues.length > 0)
			{
				var hasErrors, hasWarnings;
				hasErrors = hasWarnings = false;
				for (var i = 0; i < issues.length; i++)
				{
					var issue = issues[i];
					if (issue.type === 'error')
					{
						hasErrors = true;
					}
					else if (issue.type === 'warning')
					{
						hasWarnings = true;
					}
				}
				if ((hasErrors && settings.showerrors) || (hasWarnings && settings.showwarnings))
					filteredProblemData[index] = item;
			}
			else if (settings.showpassed)
				filteredProblemData[index] = item;
		}

		Problemcheck.filteredProblemData = filteredProblemData;
	},
	drawObjectProblems: function (params)
	{
		for (var index in Problemcheck.filteredProblemData)
		{
			var item = Problemcheck.filteredProblemData[index];

			var row = "<tr id='"+index+"'>" + Problemcheck.createFirstCell(item.objectid, index, params, item.connectedObj);
			row += "<td>";
			if ($.isArray(item.issues) && item.issues.length > 0)
			{
				for (var i = 0; i < item.issues.length; i++)
				{
					row += Problemcheck.createIssueText(item.issues[i]);
					row += "&nbsp;&nbsp;";
				}
			}
			else
			{
				row += Problemcheck.createSuccessText();
			}
			row += "</td>";
			row += "<td>"+Problemcheck.createLastExecuted(item.lastExecuted)+"</td>";
			row +=	"</tr>";

			$("#checktable").append(
				row
			);
		}
	},
	// Aktionen nach Zeichnen der Tabelle
	finishDrawProblemcheck: function()
	{
		// tablesorter update
		$("#checktableparent").trigger("update").trigger("applyWidgets");
		Problemcheck.hideVeil();
	},
	// HTML der Zelle der ersten Spalte mit Symbol und Verlinkungen auf Vorschau- und Detailseiten generieren
	createFirstCell: function(objectid, index, params, connectedObj)
	{
		var cell = "<td>";
		cell += Problemcheck.createVorschauLink(objectid, params);
		cell += Problemcheck.createDetailsLink(objectid, index, params);

		if (typeof connectedObj === 'string' && connectedObj.length > 0)
		{
			cell += " (";
			cell += Problemcheck.createDetailsLink(connectedObj, connectedObj, Problemcheck.statistikparams);
			cell += ")";
		}
		
		cell += "</td>";

		return cell;
	},
	createVorschauLink: function(objectid, params)
	{
		if (typeof params.objecticon == "string" && params.objecticon.length > 0)
		{
			return "<a href='../cis/vorschau.php?" + params.objectidname + "=" + objectid + "&debug=true' target='_blank'>"
				+ "<img title='Vorschau' src='../include/images/" + params.objecticon + "' class='mini-icon'>"
				+ "</a> ";
		}
		else
			return "";
	},
	createDetailsLink: function(objectid, index, params)
	{
		return "<a href='../vilesci/"+params.detailslink+"?" + params.objectidname + "="+objectid+"' target='_blank'>" + index + "</a>";
	},
	createIssueText: function(issue)
	{
		var colorclass, iconclass, issuetext;
		colorclass = iconclass = issuetext = "";

		if (issue == null || typeof issue == "undefined" )
		{
			return Problemcheck.createSuccessText();
		}
		else
		{
			issuetext = issue.text;
			if (issue.type === "warning")
			{
				colorclass = "text-warning";
				iconclass = "fa fa-warning";
			}
			else if (issue.type === "error")
			{
				colorclass = "text-danger";
				iconclass = "fa fa-exclamation";
			}
		}

		return "<span class='"+colorclass+"'><i class='"+iconclass+"'></i> "+issuetext+"</span>";
	},
	createSuccessText: function()
	{
		return "<span class='text-success'><i class='fa fa-check'></i> OK</span>";
	},
	createLastExecuted: function(lastExecutedObj)
	{
		if (lastExecutedObj.critical)
		{
			return "<span class='text-warning'> "+lastExecutedObj.elapsed+"</span>"
		}
		else
			return "<span class='text-success'> "+lastExecutedObj.elapsed+"</span>";
	},
	showVeil: function()
	{
		$("<div class=\"fhc-ajaxclient-veil\"></div>").appendTo("body");
	},
	hideVeil: function()
	{
		$(".fhc-ajaxclient-veil").remove();
	}
};
