$(function() {

	var action = $("#objecttype").val();
	Problemcheck.initProblemcheck(action);

	$("#objecttype").change(
		function ()
		{
			var action = $(this).val();
			Problemcheck.initProblemcheck(action);
		}
	);
	$("#showerrors,#showpassed,#showwarnings").change(
		function()
		{
			Problemcheck.checkTblRowDisplay();
		}
	);
});

var Problemcheck =
{
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
	problemData : null,
	initProblemcheck: function (action)
	{
		var callback = {};

		$("#checktable").empty();
		if (action == "null")
			return;

		Problemcheck.showVeil();

		switch(action)
		{
			case 'checkViews':
				callback = function(data)
				{
					Problemcheck.afterObjectsCheck(data, Problemcheck.viewparams);
				};
				break;
			case 'checkStatistics':
				callback = function(data)
				{
					Problemcheck.afterObjectsCheck(data, Problemcheck.statistikparams);
				};
				break;
			case 'checkCharts':
				callback = function(data)
				{
					callback = Problemcheck.afterChartsCheck(data, Problemcheck.chartparams);
				};
				break;
		}

		Problemcheck.callProblemcheck(action, callback);
	},
	callProblemcheck: function(actionparam, callback)
	{
		$.ajax({
			type: "GET",
			url: "reports_problemcheck_data.php",
			dataType: "json",
			data: {action: actionparam},
			success: callback
		});
	},
	afterChartsCheck: function (data, params)
	{
		Problemcheck.problemData = data;
		for (var index in data)
		{
			var item = data[index];
			var issues = item.issues;

			var row = "<tr id='"+index+"'>" + Problemcheck.createFirstCell(item.objectid, index, params, item.connectedObj);

			row += "<td>";

			if ($.isArray(issues) && issues.length > 0)
			{
				var first = true;
				for (var i = 0; i < issues.length; i++)
				{
					if (!first)
						row += " ";
					row += Problemcheck.createIssueText(issues[i]);
					first = false;
				}
			}
			else
			{
				try
				{
					var dataitem = JSON.parse(item.data);
					var res = $("#charttest").highcharts(dataitem);

					if (res)
					{
						row += Problemcheck.createSuccessText();
					}
				}
				catch (err)
				{
					var issue = {
						type: "error",
						text: ""
					};

					issue.text = (typeof err == 'string' && err.length > 0) ? err : "JS Fehler beim Erstellen des Charts";
					Problemcheck.problemData[index].issues.push(issue);
					row += Problemcheck.createIssueText(issue);
				}

			}
			row += "</td>";
			row += "<td>"+Problemcheck.createLastExecuted(item.lastExecuted)+"</td>";
			row += "</tr>";
			$("#checktable").append(
				row
			);
		}
		Problemcheck.checkTblRowDisplay();
		Problemcheck.hideVeil();
	},
	afterObjectsCheck: function (data, params)
	{
		Problemcheck.problemData = data;
		for (var index in data)
		{
			var item = data[index];

			var row = "<tr id='"+index+"'>" + Problemcheck.createFirstCell(item.objectid, index, params);
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
		Problemcheck.checkTblRowDisplay();
		Problemcheck.hideVeil();
	},
	checkTblRowDisplay: function()
	{
		var backgroundcolor = "";
		$("#checktable tr").each(function ()
			{
				if ($(this).css('background-color') !== 'rgba(0, 0, 0, 0)')
				{
					backgroundcolor = $(this).css('background-color')
				}
			}
		);

		var showerrors = $("#showerrors").prop("checked");
		var showwarnings = $("#showwarnings").prop("checked");
		var showpassed = $("#showpassed").prop("checked");

		for (var index in Problemcheck.problemData)
		{
			var item = Problemcheck.problemData[index];
			var selector = "tr[id='"+index+"']";

			if ($.isArray(item.issues) && item.issues.length > 0)
			{
				var hasErrors, hasWarnings;
				hasErrors = hasWarnings= false;

				for (var i = 0; i < item.issues.length; i++)
				{
					var issue = item.issues[i];

					if (issue.type === 'error')
					{
						hasErrors = true;
					}
					else if (issue.type === 'warning')
					{
						hasWarnings = true;
					}
				}

				if ((showerrors && hasErrors) ||
					(showwarnings && hasWarnings))
				{
					$(selector).show();
				}
				else
				{
					$(selector).hide();
				}
			}
			else
			{
				if (showpassed)
				{
					$(selector).show();
				}
				else
				{
					$(selector).hide();
				}
			}
		}

		$("#checktable tr:visible").each(function (index)
			{
				$(this).css("background-color", "inherit");
				if (index % 2 == 0)
					$(this).css("background-color", backgroundcolor);
			}
		);
	},
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
