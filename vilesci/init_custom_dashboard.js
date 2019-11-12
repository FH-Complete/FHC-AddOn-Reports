/*
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$(document).ready(
	function ()
	{
		FHCAddonReportsFillDashboard();
	}
);

function FHCAddonReportsFillDashboard()
{
	var path = FHC_JS_DATA_STORAGE_OBJECT.app_root;
	var path = path + '/addons/reports/vilesci/getDashboardData.php';
	var page = FHC_JS_DATA_STORAGE_OBJECT.called_path+'/'+FHC_JS_DATA_STORAGE_OBJECT.called_method;

	$.ajax({
		url: path,
		data: {
			page: page
		},
		dataType: "json",
		success: function(data){

			for(i in data)
			{
				$('#dashboard').append(FHCAddonReportsAddChart(data[i].chart_id, data[i].layout));
			}
		}
	});
}

function FHCAddonReportsAddChart(chart_id, layout)
{
	var path = FHC_JS_DATA_STORAGE_OBJECT.app_root;
	path = path + '/addons/reports/vilesci/chart.php?htmlbody=true&chart_id=' + chart_id;
	var width = 100;
	switch(layout)
	{
		case 'full':
			width = '100';
			break;
		case 'half':
			width = '49';
			break;
		case 'third':
		 	width = '32';
			break;
	}
	return '<iframe style="width:'+width+'%; height:600px; border:0px;" src="' + path + '"></iframe>';
}
