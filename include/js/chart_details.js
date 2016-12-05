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
 */

function checkDashboard() {

	if($('#dashboard').prop('checked'))
	{
		$('tr.dashboard-details input, tr.dashboard-details select').prop('disabled', false);
	}
	else
	{
		$('tr.dashboard-details input, tr.dashboard-details select').prop('disabled', true);
	}
}

$(function()
{

	$('#chart_type').on('change', function() {

		var type = $(this).val(),
			pref = charts.default_preferences[type];

		if(type)
		{
			$('#preferences').val(pref);
		}
	});

	$('#datasource_type').on('change', function() {

		var type = $(this).val();

		if(type === 'intern') {

			$('#statistik_kurzbz').trigger('change');
		}
	});

	$('#statistik_kurzbz').on('change', function() {

		var statistik_kurzbz = $(this).val(),
			type = $('#datasource_type').val();

		if(type === 'intern') {

			$('#datasource').val('../../../vilesci/statistik/statistik_sql.php?statistik_kurzbz=' + statistik_kurzbz + '&outputformat=json');
		}
	});

	$('#dashboard').on('click', function() {
		checkDashboard();
	});

	checkDashboard();
});

function unchanged()
{
		document.chartform.reset();
		document.chartform.action.disabled = true;
		editor.set(chartJson);
		document.chartform.disabled = true;
		document.getElementById("submsg").style.visibility="hidden";
		checkrequired(document.chartform.chart_id);
}

function checkrequired(feld)
{
	if(feld.value == '')
	{
		feld.className = "input_error";
		return false;
	}
	else
	{
		feld.className = "input_ok";
		return true;
	}
}

function submitable()
{
	required1 = checkrequired(document.chartform.chart_id);

	if(!required1)
	{
		document.chartform.action.disabled = true;
	}
	else
	{
		document.chartform.action.disabled = false;
	}
}

function appendChartData()
{
	var prefs = JSON.stringify(editor.get());

	var hiddenField = document.createElement("input");
	hiddenField.setAttribute("type", "hidden");
	hiddenField.setAttribute("name", "preferences");
	hiddenField.setAttribute("value", prefs);
	document.chartform.appendChild(hiddenField);
}
