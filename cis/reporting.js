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

	$('#sidebar div').hide();
	$('#div_content').hide();
	$('#iframe_content').hide();
	$('#div_filter').hide();

	$('.navbar-brand').on('click', function() {
		$('#sidebar div').hide();
		$('#div_content').hide();
		$('#iframe_content').hide();
		$('#div_filter').hide();
		$("#welcome").show();
	});

	$('.nav ul li a').on('click', function() {

		var gruppe = $(this).attr('data-gruppe'),
			menu = $(this).closest('ul').attr('data-name');

		$('#sidebar div').hide();
		$('#' + menu + 'group_' + gruppe).show();

	});

	$('#sidebar a').on('click', function() {

		var statistik_kurzbz = $(this).attr('data-statistik-kurzbez'),
			chart_id = $(this).attr('data-chart-id');

		$('#welcome').hide();

		if(statistik_kurzbz) {

			$('#iframe_content').show();
			$('#div_filter').show();
			$('#div_filter').load('filter.php?type=data&statistik_kurzbz=' + statistik_kurzbz);

		} else {

			$('#iframe_content').hide();
			$('#div_filter').hide();

			charts = [];

			$.ajax({
				url: 'chart.php?' + $.param({chart_id: chart_id}),
				success: function(data) {

					$('#div_content').html(data).show();
					initCharts();
				}
			});
		}
	});

	$('#welcome button').on('click', function() {

		var link = $('ul.nav li.dropdown [href="#' + $(this).attr('data-dropdown') + '"]');

		$('.navbar-collapse').collapse('show');

		link.dropdown('toggle');
		return false;
	});
});
