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

function convertQueryResult(chart) {

	var categories = [],
		series = [];

	for(var i = 0; i < chart.raw.data.length; i++)
	{

		var zeile = chart.raw.data[i],
			count_spalte = 0;

		for(var spalte in zeile)
		{
			count_spalte++;

			if(count_spalte === 1)
			{
				categories.push(zeile[spalte]);
				chart.categories.title = spalte;
			}
			else
			{
				var wert = parseFloat(zeile[spalte]),
					series_obj = $.grep(series, function(value, index) {
					return value.name === spalte;
				});

				if(series_obj.length > 0)
				{
					series_obj[0].data.push(wert);
				}
				else
				{
					series.push({
						name: spalte,
						data: [wert]
					});
				}
			}
		}
	}

	chart.series.data = series;
	chart.categories.data = categories;
}

function hcdrillCreate(chart) {

	var level_one = {},
		level_one_chart_data = [],
		level_two = {},
		level_two_chart_data = [];

	for(var i = 0; i < chart.raw.data.length; i++)
	{
		var zeile = chart.raw.data[i],
			count_spalte = 0,
			l1_bezeichnung,
			l2_bezeichnung;

		for(var spalte in zeile)
		{
			count_spalte++;

			if(count_spalte === 1)
			{
				l1_bezeichnung = zeile[spalte];

				if(typeof level_one[l1_bezeichnung] === 'undefined')
				{
					level_one[l1_bezeichnung] = 0;
				}
			}
			else if(count_spalte === 2)
			{
				l2_bezeichnung = zeile[spalte];
			}
			else if(count_spalte === 3)
			{
				var wert = parseFloat(zeile[spalte]);

				level_one[l1_bezeichnung] += wert;

				if(typeof level_two[l1_bezeichnung] === 'undefined')
				{
					level_two[l1_bezeichnung] = {
						id: l1_bezeichnung,
						name: l1_bezeichnung,
						data: []
					};
				}

				level_two[l1_bezeichnung].data.push([
					l2_bezeichnung,
					wert
				]);
			}
		}
	}

	for(var i in level_one) {
		level_one_chart_data.push({
			name: i,
			drilldown: i,
			y: level_one[i]
		});
	}

	for(var i in level_two)
	{

		level_two_chart_data.push(level_two[i]);
	}

	if (chart.highchart) {
		chart.highchart.destroy();
	}

	chart.highchart = new Highcharts.Chart({
		chart: {
			type: 'column',
			renderTo: chart.div_id
		},
		title: {
			text: chart.title
		},
		xAxis: {
			type: 'category',
			labels: {
				rotation: chart.x.rotation
			}
		},
		yAxis: {
			title: {
				text: ''
			}
		},
		plotOptions: {
			series: {
				borderWidth: 0,
				dataLabels: {
					enabled: true,
					format: level_two_format
				}
			}
	},
		tooltip: {
			headerFormat: '',
			pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>' + level_two_format + '</b><br/>'
		},
		series: [{
			name: '',
			colorByPoint: true,
			data: level_one_chart_data,
			tooltip: {
				headerFormat: '',
				pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>' + level_one_format + '</b><br/>'
			},
			dataLabels: {
				enabled: true,
				format: level_one_format
			}
		}],
		drilldown: {
			series: level_two_chart_data
		}
	});
}

function hcTimezoomCreate(chart) {

	var x, y = [],
		keys = Object.keys(chart.raw.data[0]);

	x = keys[0];
	y = keys.slice(1);

	for(var i = 0; i < y.length; i++)
	{

		chart.series.push({
			type: 'area',
			name: y[i],
			pointInterval: 1000 * 60 * 60 * 24,
			pointStart: Date.parse(chart.raw.data[0][x]),
			data: []
		});
	}

	for(var i = 0; i < chart.raw.data.length; i++)
	{

		var row = chart.raw.data[i];

		for(var j = 0; j < chart.series.length; j++)
		{
			chart.series[j].data.push(parseInt(row[y[j]], 10));
		}
	}

	if (chart.highchart) {
		chart.highchart.destroy();
	}

	chart.highchart = new Highcharts.Chart({
        chart: {
			zoomType: 'x',
			renderTo: chart.div_id
		},
		title: {
			text: chart.title
		},
		xAxis: {
			type: 'datetime',
			minRange: 1000 * 60 * 60 * 24
		},
		yAxis: {
			title: {
				text: '',
				min: 0
			}
		},
		legend: {
			enabled: false
		},
		plotOptions: {
			area: {
				fillColor: {
					linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
					stops: [
						[0, Highcharts.getOptions().colors[0]],
						[1, Highcharts.Color(Highcharts.getOptions().colors[0]).setOpacity(0).get('rgba')]
					]
				},
				marker: {
					radius: 2
				},
				lineWidth: 1,
				states: {
					hover: {
						lineWidth: 1
					}
				}
			}
		},
		series: chart.series
	});
}

function hcCreate(chart, hctype) {

	var options = {
		chart: {
			type: hctype,
			renderTo: chart.div_id
		},
		title: {
			text: chart.title
		},
		xAxis: {
			categories: chart.categories.data,
			title: '',
			labels: {
				rotation: chart.x.rotation
			}
		},
		yAxis: {
			title: {
				text: ''
			}
		},
		series: chart.series.data

	};

	if(chart.colors.length) {
		options.colors = chart.colors;
	}

	if(chart.highchart) {
		chart.highchart.destroy();
	}

	chart.highchart = new Highcharts.Chart(options);
}

function loadHcChartFromJSON(json)
{
			chart.raw.data = json.parse(json);

			if(chart.type === 'hcdrill')
			{
				hcdrillCreate(chart);
			}
			else if(chart.type === 'hcline')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'line');
			}
			else if(chart.type === 'hccolumn')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'column');
			}
			else if(chart.type === 'hcbar')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'bar');
			}
			else if(chart.type === 'hcpie')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'pie');
			}
			else if(chart.type == 'hctimezoom')
			{
				hcTimezoomCreate(chart);
			}
}

function loadHcChart(url, chart)
{
	$('#spinner').show();
	$.ajax({
		url: url,
		error: function(xhr, ajaxOptions, thrownError){
			$('#spinner').hide();
			alert('error'+thrownError);
		},
		success: function(data) {
			$('#spinner').hide();
			if(data.length === 0)
			{
				alert('Keine Daten vorhanden');
				return;
			}

			chart.raw.data = data;

			if(chart.type === 'hcdrill')
			{
				hcdrillCreate(chart);
			}
			else if(chart.type === 'hcline')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'line');
			}
			else if(chart.type === 'hccolumn')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'column');
			}
			else if(chart.type === 'hcbar')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'bar');
			}
			else if(chart.type === 'hcpie')
			{
				convertQueryResult(chart);
				hcCreate(chart, 'pie');
			}
			else if(chart.type == 'hctimezoom')
			{
				hcTimezoomCreate(chart);
			}
		}
	});
}

function initCharts() {

	if(typeof charts !== 'undefined') {

		for(var i = 0; i < charts.length; i++) {

			charts[i].init();
		}
	}
}
