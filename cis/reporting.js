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

$(function() {

	$('.navbar-brand').on('click', function() {
		$('#sidebar div').hide();
		$('#content').hide();
		$('#iframe_content').hide();
		$('#filter').hide();
		$("#welcome").show();
	});

	$('.nav ul li a').on('click', function() {

		var gruppe = $(this).attr('data-gruppe'),
			menu = $(this).closest('ul').attr('data-name');

		$('#sidebar div').hide();
		$('#sidebar').attr('data-menu', menu);
		$('#' + menu + 'group_' + gruppe).show();
	});

	$('#sidebar a').on('click', function() {

		var statistik_kurzbz = $(this).attr('data-statistik-kurzbez'),
			chart_id = $(this).attr('data-chart-id');

		$('#welcome,#content').hide();
		$('#filter').show();

		$('#filter-input').load('filter.php?type=data&statistik_kurzbz=' + statistik_kurzbz, function() {

			if(!$.trim($('#filter-input').html())) {

				$('#run-filter').trigger('click');
			}
		});

		$('#filter-input').attr({
			'data-chart-id': chart_id,
			'data-statistik-kurzbz': statistik_kurzbz
		});
	});

	$('#welcome button').on('click', function() {

		var link = $('ul.nav li.dropdown [href="#' + $(this).attr('data-dropdown') + '"]');

		$('.navbar-collapse').collapse('show');

		link.dropdown('toggle');
		return false;
	});

	$('#run-filter').on('click', function() {

		$('#filter').hide();

		var inputs = $('#filter-input > *'),
			chart_id = $('#filter-input').attr('data-chart-id'),
			data_statistik_kurzbz = $('#filter-input').attr('data-statistik-kurzbz'),
			get_params = {},
			url;

		for(var i = 0; i < inputs.length; i++) {

			var input = $(inputs[i]);

			get_params[input.attr('id')] = input.val();
		}

		if($('#sidebar').attr('data-menu') === 'charts') {

			url = 'chart.php';
			get_params.chart_id = chart_id;

		} else {

			url = 'grid.php';
			get_params.statistik_kurzbz = data_statistik_kurzbz;
		}

		$.ajax({
			url: url,
			data: get_params,
			success: function(data) {
				charts = [];
				$('#content').html(data).show();
				initCharts();
			}
		});
	});
});
