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
 *					Andreas Moik <moik@technikum-wien.at>
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../include/rp_attribut_zuweisungen.class.php');
	require_once('../include/rp_attribut.class.php');
	require_once('../include/rp_view.class.php');

	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');

	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);

	if(!$rechte->isBerechtigt('addon/reports_verwaltung'))
		die($rechte->errormsg);

	$reload = false;
	$htmlstr = '';
	$errorstr = ''; //fehler beim insert
	$sel = '';
	$chk = '';
	$explain_output = '';


	$view = new view();
	$view->view_id		= 0;
	$view->view_kurzbz		= 'vw_';
	$view->table_kurzbz 		= 'tbl_';
	$view->sql		= 'SELECT ';
	$view->insertvon		= $user;
	$view->updatevon		= $user;

	$allAttribute = new rp_attribut();
	$allAttribute->loadAll();

	$attribut_zuweisungen = new rp_attribut_zuweisungen();

	if(!$rechte->isBerechtigt('addon/reports_verwaltung', null, 'suid'))
		die($rechte->errormsg);

	if(isset($_REQUEST["view_id"]))
	{
		$view_id = intval($_REQUEST['view_id']);

		if (is_numeric($view_id))
		{
			$view->load($view_id);
			if ($view->errormsg!='')
				die($view->errormsg);
		}

		if(isset($_REQUEST["action"]))
		{
			if ($_REQUEST["action"]=='save')
			{
				$view->view_id = $_POST["view_id"];

				if($view->view_id == 0)
					$view->new = true;

				$view->view_kurzbz = $_POST["view_kurzbz"];
				$view->table_kurzbz = $_POST["table_kurzbz"];
				$view->sql = $_POST["sql"];
				$view->static =isset($_POST["static"]);
				$view->postcreation_sql = $_POST["postcreation_sql"];

				if(!$view->save())
				{
					$errorstr .= $view->errormsg;
				}
				$reload = true;
			}
			else if ($_REQUEST["action"]=='View anlegen/speichern')
			{
				$view->generateView();
				$reload = true;
			}
			else if ($_REQUEST["action"]=='Drop View')
			{
				$view->dropView();
				$reload = true;
			}
			else if($_REQUEST["action"]=='Explain')
			{
				$explain_output = $view->explainView();
				if(!$explain_output)
					$explain_output = "Fehlgeschlagen: " . $view->errormsg;
			}
			else if($_REQUEST["action"]=='saveAttributZuweisung')
			{
				if(isset($_REQUEST["view_id"]) && isset($_REQUEST["attribut_id"]))
				{
					$saveZuweisung = new rp_attribut_zuweisungen();
					$saveZuweisung->view_id = $_REQUEST["view_id"];
					$saveZuweisung->attribut_id = $_REQUEST["attribut_id"];
					$saveZuweisung->save();
				}
			}
			else if($_REQUEST["action"]=='deleteAttributZuweisung')
			{
				if(isset($_REQUEST["attribut_zuweisungen_id"]))
				{
					$delZuweisung = new rp_attribut_zuweisungen();
					$delZuweisung->delete($_REQUEST["attribut_zuweisungen_id"]);
				}
			}
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
	$htmlstr .= " 			<td rowspan='2' colspan='2'>
												<textarea name='sql' cols='70' rows='14' onchange='submitable()'>".$db->convert_html_chars($view->sql)."</textarea>
											</td>\n";
	$htmlstr .= " 		<td valign='top'>Index, etc.:</td>
						<td rowspan='2' colspan='2' valign='top'>
												<textarea name='postcreation_sql' cols='70' rows='7' onchange='submitable()'>".$db->convert_html_chars($view->postcreation_sql)."</textarea>
											</td>\n";
	$htmlstr .= "		</tr>\n";
	$htmlstr .= "	</table>\n";

	$htmlstr .= "<br>\n";
	$htmlstr .= "<div align='right' id='sub'>\n";
	$htmlstr .= "	<span id='submsg' style='color:red; visibility:hidden;'>Datensatz ge&auml;ndert!&nbsp;&nbsp;</span>\n";
	if($view->view_kurzbz == 'vw_')
		$htmlstr .= "	<input type='hidden' name='new' value='new'>";
	$htmlstr .= "	<input type='submit' value='save' name='action'>\n";
	$htmlstr .= "	<input type='submit' value='View anlegen/speichern' name='action'>\n";
	$htmlstr .= "	<input type='submit' value='Drop View' name='action'>\n";
	$htmlstr .= "	<input type='submit' value='Explain' name='action'>\n";
	$htmlstr .= "</div>";
	$htmlstr .= "</form>";

	if($view->view_id != 0)
	{
		$htmlstr .= "<br>\n";
		$htmlstr .= "	<table  class='tablesorter' id='t1' style='margin-left: 5%;width:90%;'>";
		$htmlstr .= "	<thead>\n";
		$htmlstr .= "	<tr>\n";
		$htmlstr .= "	<th>Attribut</th>\n";
		$htmlstr .= "	<th>Titel</th>\n";
		$htmlstr .= "	<th></th>\n";
		$htmlstr .= "	</tr>\n";
		$htmlstr .= "	</thead>\n";
		$htmlstr .= "	<tbody>\n";

		$attribut_zuweisungen->loadAllFromView($view->view_id);
		foreach($attribut_zuweisungen->result as $az)
		{
			$oneAttribut = new rp_attribut($az->attribut_id);

			$htmlstr .= "	<tr>\n";
			$htmlstr .= '	<td>'.$az->attribut_id.'</td>';
			$htmlstr .= "	<td>".$oneAttribut->shorttitle["German"]."</td>\n";
			$htmlstr .= '	<td><a href="view_details.php?action=deleteAttributZuweisung&attribut_zuweisungen_id='.$az->rp_attribut_zuweisungen_id.'&view_id='.$view->view_id.'" onclick="return confdel()">entfernen</a></td>';
			$htmlstr .= "	</tr>\n";
		}
		$htmlstr .= "	</tbody>\n";
		$htmlstr .= "	<tr>\n";



		$htmlstr .= "<form action='view_details.php' method='POST' name='view_attrform'>\n";
		$htmlstr .= "	<input type='hidden' name='view_id' value='".$view->view_id."'>";
		$htmlstr .= "	<td></td>\n";
		$htmlstr .= "	<td>\n";
		$htmlstr .= "	<select name='attribut_id' style='max-width:150px;'>\n";

		foreach($allAttribute->result as $attr)
		{
			$htmlstr .= "	<option value=".$attr->attribut_id.">".$attr->shorttitle["German"]."</option>\n";
		}
		$htmlstr .= "	</select>\n";
		$htmlstr .= "	</td>\n";
		$htmlstr .= "	<input type='hidden' name='action' value='saveAttributZuweisung'>";
		$htmlstr .= "	<td><input type='submit' value='Hinzuf&uuml;gen'></td>\n";
		$htmlstr .= "</form>\n";
		$htmlstr .= "	</tr>\n";
		$htmlstr .= "	</table>\n";
		$htmlstr .= "<br>\n";
	}
	$htmlstr .= "<div class='inserterror'>".$errorstr."</div>\n";
	$htmlstr .= "<div>$explain_output</div>\n";
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>DI-Quelle - Details</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
<script src="../../../include/js/mailcheck.js"></script>
<script src="../../../include/js/datecheck.js"></script>
<?php require_once("../../../include/meta/jquery.php"); ?>
<?php require_once("../../../include/meta/jquery-tablesorter.php"); ?>
<style>
	table.tablesorter tbody td
	{
		margin: 0;
		padding: 0;
		vertical-align: middle;
	}
</style>
<script type="text/javascript">
	$(function() {
		$("#t1").tablesorter(
		{
			sortList: [[0,1]],
			widgets: ["zebra"]
		});
	});


	function confdel()
	{
		return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
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

?>
<?php if($reload): ?>
<script type='text/javascript'>
	parent.frame_view_overview.location.href='view_overview.php';
</script>
<?php endif; ?>

</body>
</html>
