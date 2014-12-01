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

function loadPivotTable(url, chart) {

	$.ajax({
		url: url,
		success: function(data) {

			var options = {
				rows: chart.rows,
				cols: chart.cols,
				aggregatorName: 'Integer Sum'
			};

			chart.raw.data = data;

			$('#' + chart.div_id).pivotUI(data, options);
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
