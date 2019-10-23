/*
 * Copyright (C) 2014 fhcomplete.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Authors: Robert Hofer <robert.hofer@technikum-wien.at>
 *					Andreas moik <moik@technikum-wien.at>
 */


function die(msg)
{
	document.body.innerHTML = msg;
	throw new Error(msg);
}

function loadChart(chart_id, statistik_kurzbz, putlog)
{
	showFilter(statistik_kurzbz, undefined, chart_id, putlog);
}

function loadReport(report_id, putlog)
{
	showFilter(undefined, report_id, undefined, putlog);
}

function loadStatistik(statistik_kurzbz, putlog, systemfilter_id)
{
	showFilter(statistik_kurzbz, undefined, undefined, putlog, systemfilter_id);
}

function loadData(statistik_kurzbz, report_id, chart_id, get_params)
{
	var url = undefined;

	//charts
	if(chart_id !== undefined)
	{
		get_params.chart_id = chart_id;
		url = 'chart.php';
	}
	//pivots
	else if(statistik_kurzbz !== undefined)
	{
		get_params.statistik_kurzbz = statistik_kurzbz;
		url = 'grid.php';
	}
	//reports
	else if(report_id !== undefined)
	{
		get_params.report_id = report_id;
		url = '../vilesci/report_generate.php';
	}

	if(typeof url !== "undefined")
	{
		$('#spinner').show();
		$('#welcome').hide();

		if(get_params.type === "pdf")
		{
			url = '../vilesci/report_generate.php';

			var getStr= "?report_id=" + report_id;

			for(var k in get_params)
				getStr += "&"+k+"="+get_params[k];

			window.open(encodeURI(url + getStr));
			$('#spinner').hide();
		}
		else
		{
			$.ajax(
			{
				url: url,
				data: get_params,
		    error: function (xhr, ajaxOptions, thrownError)
		    {
					$('#spinner').hide();
		      die("Fehler: " + xhr.status + " \"" + thrownError + "\"");
				},
				success: function(data)
				{
					$('#spinner').hide();

					//.show und resize müssen dürfen nicht nach dem hineinschreiben
					//der daten in das div ausgeführt werden. Bei .show() stimmt die größe nicht
					//und bei resize korrumpiert der resize-Prozess die animation der Charts
					$('#content').show();
					$(window).trigger('resize');

					if(url === "grid.php" || url === "chart.php")
					{
						$('#content').html(data);
						setSysFilterEvents(get_params);
					}
					else
					{
						$('#content').html('<iframe id="contentIframe" width="100%" height="98%" frameborder="0" id="content" style="overflow: visible;"></iframe>');

						var ifrm = document.getElementById('contentIframe');
						ifrm = (ifrm.contentWindow) ? ifrm.contentWindow : (ifrm.contentDocument.document) ? ifrm.contentDocument.document : ifrm.contentDocument;
						ifrm.document.open();
						ifrm.document.write(data);
						ifrm.document.close();
					}
				}
			});
		}
	}
	else
		alert("Es wurden keine korrekten Daten angegeben!")
}

//set events for systemfilters (Ansichten) block
function setSysFilterEvents(get_params)
{
	$("#systemfilter").change(
		function() {
			get_params.systemfilter_id = $("#systemfilter").val();
			initStatistikLoad(get_params);
		}
	);

	if ($("#standardsysfilter").length)
	{
		$("#standardsysfilter").change(
			function() {
				var checked = $(this).prop("checked");
				var systemfilter_id = $("#systemfilter").val();

				setDefaultSysFilter(systemfilter_id, checked, function () {
					alert("Fehler beim Setzten der Standardansicht!");
				});
			}
		);
	}

	$("#addprivatesysfilterbtn").click(
		function() {
			if (validateFilterName())
			{
				saveSysFilter(get_params, function () {
					alert("Fehler beim Speichern der Ansicht!");
				}, false);
			}
			else
			{
				$("#addprvfiltergroup").addClass("has-error");
				alert("Ansichtname muss alphanumerisch, unverwendet und mind. 1 Zeichen sein!");
			}
		}
	);

	$("#updateprivatesysfilterbtn").click(
		function() {
			saveSysFilter(get_params, function () {
				alert("Fehler beim Speichern der Ansicht!");
			}, true);
		}
	);

	$("#deleteprivatesysfilterbtn").click(
		function() {
			get_params.systemfilter_id = $("#systemfilter").val();
			deleteSysFilter(get_params, function(){
				alert("Fehler beim Löschen der Ansicht!")
			});
		}
	);
}

/*--------------------------------------------- Systemfilters AJAX calls ---------------------------------------------*/

function saveSysFilter(get_params, errorcallback, update)
{
	var data = getSysFilterSaveData(get_params.statistik_kurzbz, update);

	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: data,
			success: function(data) {
				var systemfilter_id = JSON.parse(data);
				console.log(systemfilter_id);
				if ($.isNumeric(systemfilter_id))
				{
					get_params.systemfilter_id = systemfilter_id;
					initStatistikLoad(get_params);
				}
				else
					errorcallback();
			},
			error: errorcallback
		}
	);
}

function setDefaultSysFilter(systemfilter_id, checked, errorcallback)
{
	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: {
				"action": "setDefault",
				"systemfilter_id": systemfilter_id,
				"default_filter": checked
			},
			success: function (data) {
				if (data === 'true')
				{
					if (checked)
					{
						$("#standardsysfilterlabel").css("font-weight", 700);
					}
					else
					{
						$("#standardsysfilterlabel").css("font-weight", "normal");
					}

				} else
					errorcallback();
			},
			error:
				errorcallback
		}
	);
}

function deleteSysFilter(get_params, errorcallback)
{
	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: {
				"action": "deletePrivate",
				"systemfilter_id": get_params.systemfilter_id
			},
			success: function (data) {
				console.log(data);
				if (data === 'true')
				{
					get_params.systemfilter_id = 'undefined';
					initStatistikLoad(get_params);
				} else
					errorcallback();
			},
			error: errorcallback
		}
	);
}

/*--------------------------------------------- Systemfilters AJAX calls Ende---------------------------------------------*/

//Get data for saving a systemfilter (Ansicht)
function getSysFilterSaveData(statistik_kurzbz, update)
{
	if (typeof(Storage) == "undefined")
	{
		alert("No web storage support!");
		return null;
	}

	var filtername = $("#privatesysfiltername").val();
	var preferences = localStorage.FHC_reporting_preferences;

	var filter = JSON.stringify({"name" : filtername, "preferences": JSON.parse(preferences)});

	var data = {
		"action": "savePrivate",
		"filter": filter,
		"statistik_kurzbz": statistik_kurzbz
	};

	var filter_id = $("#systemfilter").val();

	//kein Filtername - überschreiben des gewählten filters.
	if (update === true && $.isNumeric(filter_id))
	{
		data.systemfilter_id = filter_id;
	}

	return data;
}

//filtername should not be empty, only alphanumeric and not already used!
function validateFilterName()
{
	var filtername = $("#privatesysfiltername").val();
	var filtername_priv = filtername + " (p)";
	var filternamefound = false;
	var regex = new RegExp(/^[a-z0-9]+$/i);

	$("#systemfilter").children("option").each(
		function () {
			if ($(this).text() === filtername_priv)
				filternamefound = true;
		}
	);

	return regex.test(filtername) && !filternamefound;
}

//Initialises load of a statistik depending on params. Either statistikfilter is loaded or statistik directly.
function initStatistikLoad(get_params)
{
	if (get_params.systemfilter_id === 'defaultoption')
	{
		$("#standardsysfilterlabel, #deleteprivatesysfilterbtn, #updateprivatesysfilterbtn").hide();
		return;
	}

	//wenn keine filter existieren
	if(!$.trim($('#filter-input').html()) && get_params.report_id === undefined)
	{
		$('#filter').hide();
		//laden wir direkt die daten
		loadStatistik(get_params.statistik_kurzbz, get_params.putlog, get_params.systemfilter_id);
	}
	else
	{
		runFilter('html', get_params.putlog, get_params.systemfilter_id);
	}
}

function showFilter(statistik_kurzbz, report_id, chart_id, putlog, systemfilter_id)
{
	$('#filter').show();
	$(window).trigger('resize');

	$('#spinner').hide();
	$('#welcome').hide();
	$('#content').hide();
	$('#glossar').hide();
	$("#filter-PdfLink").hide();
	$("#filter-debugLink").hide();

	$('#filter-input').load('filter.php?type=data&statistik_kurzbz=' + statistik_kurzbz + '&report_id=' + report_id + "&putlog=" + putlog + "&systemfilter_id=" + systemfilter_id, function()
	{
		if(typeof debug !== "undefined")
			$("#filter-debugLink").show();

		//pdf links gibt es nur bei reports
		if(typeof report_id !== 'undefined')
		{
			$("#filter-PdfLink").show();
		}
		//wenn keine filter existieren
		if(!$.trim($('#filter-input').html()) && report_id === undefined)
		{
			$('#filter').hide();
			//laden wir direkt die daten
			loadData(statistik_kurzbz, report_id, chart_id,{putlog:putlog, systemfilter_id: systemfilter_id});
		}
	});

	//charts
	if(statistik_kurzbz !== undefined && chart_id !== undefined)
	{
		$('.list-group-item').removeClass('itemActive');
		$('#list_item_chart_'+chart_id).addClass('itemActive');
	}
	//pivots
	else if(statistik_kurzbz !== undefined  && chart_id == undefined)
	{
		$('.list-group-item').removeClass('itemActive');
		$('#list_item_statistik_'+statistik_kurzbz).addClass('itemActive');
	}
	//reports
	else if(report_id !== undefined)
	{
		$('.list-group-item').removeClass('itemActive');
		$('#list_item_report_'+report_id).addClass('itemActive');
	}

	$('#filter-input').removeAttr('data-chart_id');
	$('#filter-input').removeAttr('data-statistik_kurzbz');
	$('#filter-input').removeAttr('data-report_id');

	$('#filter-input').attr(
	{
		'data-chart_id': chart_id,
		'data-statistik_kurzbz': statistik_kurzbz,
		'data-report_id': report_id
	});
}

function resizeContent()
{
	if($("#sidebar").css("display") === "block")
	{
		$('#content').parent().removeClass('col-sm-12').addClass('col-sm-9');
	}
	else
	{
		$('#content').parent().removeClass('col-sm-9').addClass('col-sm-12');
	}

  $('#content').parent().height($(window).height() - 160);
  $('#content').height("100%");
  $('#content').width($('#content').parent().width());

  //$('#pivot').width("1%");
  //$('.pvtUi').width("1%");

 // $('.pvtRendererArea').width("100%");
  $('.pvtRendererArea').css("overflow","auto");

  $('#content').css("overflow-y", "visible");
  $('#content').css("overflow-x", "auto");
}

function showSidebar(num, type, reference_bezeichnung)
{
	resizeContent();
	$('#sidebar').show();
	$('#titel_div').html(reference_bezeichnung);
	$('.reports_sidebar_entry').hide();
	if(type=='all')
	{
		$('.report_'+num+"_charts").show();
		$('.report_'+num+"_data").show();
		$('.report_'+num+"_reports").show();
	}
	else
		$('.report_'+num+"_"+type).show();
	$('.hide-button').show();


	$('#sidebar').attr('data-menu', type);

	$(window).trigger('resize');
}


$(function()
{
	$('#sidebar').hide();

	resizeContent();

	$(window).resize(function() {
	resizeContent();
	}).resize();

	$( "#glossar_link" ).click(function()
	{
		resizeContent();
		$('.reports_sidebar_entry').hide();
		$("#filter-PdfLink").hide();
		$("#filter-debugLink").hide();
		$('#spinner').hide();
		$('#filter').hide();
		$('#welcome').hide();

		$('#sidebar').hide();
		$(window).trigger('resize');
		$('#glossar').show();
	});
});

function hideSidebar()
{
	resizeContent();
	// Sidebar ausblenden
	$('#sidebar').hide();

	$(window).trigger('resize');
}
function minimizeSidebar()
{
	resizeContent();
	// Sidebar ausblenden
	$('#sidebar').hide();
	$('#maximize_sidebar_button').show();

	$(window).trigger('resize');
}
function maximizeSidebar()
{
	resizeContent();
	// Sidebar ausblenden
	$('#sidebar').show();
	$('#maximize_sidebar_button').hide();

	$(window).trigger('resize');
}

/**
 * CSV Export fuer Charts
 */
function exportChartCSV()
{
	var inputs = $('#filter-input > *'),
		chart_id = $('#filter-input').attr('data-chart_id'),
		statistik_kurzbz = $('#filter-input').attr('data-statistik_kurzbz'),
		report_id = $('#filter-input').attr('data-report_id'),
		get_params = {putlog:true},
		url;

	get_params.type = 'csv';

	for(var i = 0; i < inputs.length; i++)
	{
		var input = $(inputs[i]);
		if(input.attr("id") !== undefined)
			get_params[input.attr('id')] = input.val();
	}

	var getStr = 'statistik_kurzbz='+encodeURIComponent(statistik_kurzbz);
	for(var k in get_params)
		getStr += "&"+k+"="+get_params[k];
	window.open("chart_export.php?"+getStr);
}

function runFilter(type, putlog, systemfilter_id)
{
	var inputs = $('#filter-input *'),
		chart_id = $('#filter-input').attr('data-chart_id'),
		statistik_kurzbz = $('#filter-input').attr('data-statistik_kurzbz'),
		report_id = $('#filter-input').attr('data-report_id'),
		get_params = {putlog:putlog, systemfilter_id: systemfilter_id},
		url;

	get_params.type = type;

	for(var i = 0; i < inputs.length; i++)
	{
		var input = $(inputs[i]);
		if(input.attr("id") !== undefined)
			get_params[input.attr('id')] = input.val();
	}

	loadData(statistik_kurzbz, report_id, chart_id, get_params);

	// if available, clean up tmp folder
	if(typeof reportsCleanup == "function")
		reportsCleanup();
}
