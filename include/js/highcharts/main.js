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
			type: 'category'
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

function hclineCreate(chart) {

	convertQueryResult(chart);

	hcCreate(chart, 'line');
}

function hccolumnCreate(chart) {

	convertQueryResult(chart);

	hcCreate(chart, 'column');
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
			title: ''
		},
		yAxis: {
			title: {
				text: ''
			}
		},
		series: chart.series.data
	};

	if(typeof highchart_colors !== 'undefined' && $.isArray(highchart_colors)) {
		options.colors = highchart_colors;
	}

	if(chart.highchart) {
		chart.highchart.destroy();
	}

	chart.highchart = new Highcharts.Chart(options);
}

function loadChart(url, chart) {

	$.ajax({
		url: url,
		success: function(data) {

			chart.raw.data = data;

			if(chart.type === 'hcdrill')
			{
				hcdrillCreate(chart);
			}
			else if(chart.type === 'hcline')
			{
				hclineCreate(chart);
			}
			else if(chart.type === 'hccolumn')
			{
				hccolumnCreate(chart);
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
