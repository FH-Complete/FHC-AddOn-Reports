<?php
/* Copyright (C) 2006 Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Christian Paminger 	< christian.paminger@technikum-wien.at >
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../include/view.class.php');

	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');

	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);

	if(!$rechte->isBerechtigt('addon/reports'))
		die('Sie haben keine Berechtigung fuer dieses AddOn!');

	$reloadstr = '';  // neuladen der liste im oberen frame
	$htmlstr = '';
	$errorstr = ''; //fehler beim insert
	$sel = '';
	$chk = '';


	$view = new view();
	$view->view_id		= 0;
	$view->view_kurzbz		= 'vw_';
	$view->table_kurzbz 		= 'tbl_';
	$view->sql		= 'SELECT ';
	$view->insertvon		= $user;
	$view->updatevon		= $user;

	if(!$rechte->isBerechtigt('addon/reports', null, 'suid'))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	if(isset($_REQUEST["view_id"]))
	{
		$view_id = intval($_REQUEST['view_id']);

		if (is_numeric($view_id))
		{
			$view->load($view_id);
			if ($view->errormsg!='')
				die($view->errormsg);
		}

		if (isset($_REQUEST["action"]) && $_REQUEST["action"]=='save')
		{
			$view->view_id = $_POST["view_id"];

			if($view->view_id == 0)
				$view->new = true;

			$view->view_kurzbz = $_POST["view_kurzbz"];
			$view->table_kurzbz = $_POST["table_kurzbz"];
			$view->sql = $_POST["sql"];
			$view->static =isset($_POST["static"]);

			if(!$view->save())
			{
				$errorstr .= $view->errormsg;
			}

			$reloadstr .= "<script type='text/javascript'>\n";
			$reloadstr .= "	parent.frame_view_overview.location.href='view_overview.php';";
			$reloadstr .= "</script>\n";
		}
		else if (isset($_REQUEST["action"]) && $_REQUEST["action"]=='generate')
		{
			$view->generate();
		}
	}


    if($view->view_kurzbz != 'vw_')
        $htmlstr .= "<br><div class='kopf'>View <b>".$view->view_kurzbz."</b></div>\n";
    else
        $htmlstr .="<br><div class='kopf'>Neue View</div>\n";
	$htmlstr .= "<form action='view_details.php' method='POST' name='viewform'>\n";
	$htmlstr .= "	<table class='detail'>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td>View</td>\n";
	$htmlstr .= "				<td><input class='detail' type='text' name='view_kurzbz' size='22' maxlength='32' value='".$db->convert_html_chars($view->view_kurzbz)."' onchange='submitable()'></td>\n";
	$htmlstr .= "				<td style='visibility:hidden;'><input class='detail' type='text' name='view_id' size='22' maxlength='32' value='".$view->view_id."' onchange='submitable()'></td>\n";
	$htmlstr .= "				<td>Table</td>\n";
	$htmlstr .= "				<td><input class='detail' type='text' name='table_kurzbz' size='22' maxlength='32' value='".$db->convert_html_chars($view->table_kurzbz)."' onchange='submitable()'>\n";
	$htmlstr .= "				Static: <input class='detail' type='checkbox' name='static' ".($view->static?'checked="checked"':'')." onchange='submitable()'></td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "			<tr>\n";
	$htmlstr .= "				<td rowspan='2' valign='top'>SQL</td>\n";
	$htmlstr .= " 				<td rowspan='2' colspan='3'>
									<textarea name='sql' cols='70' rows='14' onchange='submitable()'>".$db->convert_html_chars($view->sql)."</textarea>
								</td>\n";
	$htmlstr .= "			</tr>\n";
	$htmlstr .= "	</table>\n";


	$htmlstr .= "<br>\n";
	$htmlstr .= "<div align='right' id='sub'>\n";
	$htmlstr .= "	<span id='submsg' style='color:red; visibility:hidden;'>Datensatz ge&auml;ndert!&nbsp;&nbsp;</span>\n";
	if($view->view_kurzbz == 'vw_')
		$htmlstr .= "	<input type='hidden' name='new' value='new'>";
	$htmlstr .= "	<input type='submit' value='save' name='action'>\n";
	$htmlstr .= "	<input type='button' value='Reset' onclick='unchanged()'>\n";
	$htmlstr .= "	<input type='submit' value='generate' name='action'>\n";
	$htmlstr .= "</div>";
	$htmlstr .= "</form>";
	$htmlstr .= "<div class='inserterror'>".$errorstr."</div>"
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>DI-Quelle - Details</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
<script src="../../../include/js/mailcheck.js"></script>
<script src="../../../include/js/datecheck.js"></script>
		<script type="text/javascript" src="../../../include/js/jquery.min.1.11.1.js"></script>
<script type="text/javascript" src="../../../submodules/tablesorter/jquery.tablesorter.min.js"></script>
<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
<style>
	table.tablesorter tbody td
	{
		margin: 0;
		padding: 0;
		vertical-align: middle;
	}
</style>
<script type="text/javascript">


function confdel()
{
	return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
}

$(function() {
	$("#t1").tablesorter(
	{
		sortList: [[1,0]],
		widgets: ["zebra"]
	});

	$("#t2").tablesorter(
	{
		sortList: [[1,0]],
		widgets: ["zebra"]
	});
});

function unchanged()
{
		document.viewform.reset();
		document.viewform.schick.disabled = true;
		document.getElementById("submsg").style.visibility="hidden";
		checkrequired(document.viewform.view_kurzbz);
}

function checkrequired(feld)
{
	if(feld.value == '')
	{
		feld.className = "input_error";
		return false;
	}
	else
	{
		feld.className = "input_ok";
		return true;
	}
}

function submitable()
{
	required1 = checkrequired(document.viewform.view_kurzbz);

	if(!required1)
	{
		document.viewform.schick.disabled = true;
		document.getElementById("submsg").style.visibility="hidden";
	}
	else
	{
		document.viewform.schick.disabled = false;
		document.getElementById("submsg").style.visibility="visible";
	}
}
</script>
</head>
<body style="background-color:#eeeeee;">

<?php
	echo $htmlstr;
	echo $reloadstr;
?>

</body>
</html>
