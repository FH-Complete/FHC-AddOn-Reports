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
			$('#statistik_kurzbz').closest('tr').show();

		} else {

			$('#statistik_kurzbz').closest('tr').hide();
		}
	});

	var type = $('#datasource_type').val();

	if(type !== 'intern') {

		$('#statistik_kurzbz').closest('tr').hide();
	}

	$('#statistik_kurzbz').on('change', function() {

		var statistik_kurzbz = $(this).val();

		$('#datasource').val('../../../vilesci/statistik/statistik_sql.php?statistik_kurzbz=' + statistik_kurzbz + '&outputformat=json');
	});
});
