<?php
/*
 * Copyright (C) 2015 fhcomplete.org
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
 * 					Andreas Moik <moik@technikum-wien.at>
 */

require_once("../reports.config.inc.php");

if(DASHBOARD): ?>

addon.push(
{
	init: function()
	{
		var tabitem = document.createElement("tab");
		tabitem.setAttribute("id","addon-reports-tab");
		tabitem.setAttribute("label","Dashboard");

		var main_tabs = document.getElementById("main-content-tabs");
		main_tabs.appendChild(tabitem);
		$(tabitem).trigger('click');

		var iframe = document.createElement("iframe");
		iframe.setAttribute("id","addon-reports-tabpannel-iframe");
		iframe.setAttribute('src', '../addons/reports/vilesci/dashboard.php');

		var maintabpanels=document.getElementById("tabpanels-main");
		maintabpanels.appendChild(iframe);
	},
	selectMitarbeiter: function(person_id, mitarbeiter_uid)
	{
	},
	selectStudent: function(person_id, prestudent_id, student_uid)
	{
	},
	selectVerband: function(item)
	{
	},
	selectInstitut: function(institut)
	{
	},
	selectLektor: function(lektor)
	{
	}
});
<?php endif; ?>
