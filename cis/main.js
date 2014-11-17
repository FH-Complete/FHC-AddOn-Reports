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

	$('.nav ul li a').on('click', function() {

		var gruppe = $(this).attr('data-gruppe'),
			menu = $(this).closest('ul').attr('data-name');

		$('#sidebar div').hide();
		$('#' + menu + 'group_' + gruppe).show();

	});

	$('#sidebar a').on('click', function() {

		var statistik_kurzbz = $(this).attr('data-statistik-kurzbez'),
			chart_id = $(this).attr('data-chart-id');

		if(statistik_kurzbz) {

			$('#welcome').hide();
			$('#div_content').empty().hide();
			$('#div_filter').show();
			$('#iframe_content').show();
			$('#div_filter').load('filter.php?type=data&statistik_kurzbz=' + statistik_kurzbz);

		} else {

			$('#welcome').hide();
			$('#div_content').show();
			$('#div_filter').hide();
			$('#iframe_content').hide();

			$.ajax({
				url: 'chart.php',
				data: {chart_id: chart_id},
				success: function(data) {
					$('#div_content').empty();
					$('#div_content').html(data);
					loadChart(source);
				}
			});
		}

	});

});
