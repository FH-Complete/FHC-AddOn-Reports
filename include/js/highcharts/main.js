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

function convertQueryResult() {

	var categories = [],
		series = [];

	for(var i = 0; i < chart.data.raw.length; i++)
	{

		var zeile = chart.data.raw[i],
			count_spalte = 0;

		for(var spalte in zeile)
		{
			count_spalte++;

			if(count_spalte === 1)
			{
				categories.push(zeile[spalte]);
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

	chart.data.series = series;
	chart.data.categories = categories;
}

function hcdrillCreate() {

	var level_one = {},
		level_one_chart_data = [],
		level_two = {},
		level_two_chart_data = [];

	for(var i = 0; i < chart.data.raw.length; i++)
	{
		var zeile = chart.data.raw[i],
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
			renderTo: 'hcdrillChart'
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
		legend: {
			enabled: false
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

function hclineCreate() {

	convertQueryResult();

	hcCreate('line', 'hclineChart');
}

function hccolumnCreate() {

	convertQueryResult();

	hcCreate('column', 'hccolumnChart');
}

function hcCreate(hctype, selector) {
	
	var options = {
		chart: {
			type: hctype,
			renderTo: selector
		},
		title: {
			text: chart.title
		},
		xAxis: {
			categories: chart.data.categories
		},
		yAxis: {
			title: {
				text: ''
			}
		},
		legend: {
			enabled: false
		},
		series: chart.data.series
	};

	if(typeof highchart_colors !== 'undefined' && $.isArray(highchart_colors)) {
		options.colors = highchart_colors;
	}

	if(chart.highchart) {
		chart.highchart.destroy();
	}

	chart.highchart = new Highcharts.Chart(options);
}

function loadChart(url) {

	$.ajax({
		url: url,
		success: function(data) {

			chart.data.raw = data;

			if(chart.type === 'hcdrill')
			{
				hcdrillCreate();
			}
			else if(chart.type === 'hcline')
			{
				hclineCreate();
			}
			else if(chart.type === 'hccolumn')
			{
				hccolumnCreate();
			}
		}
	});
}
