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
	require_once('../include/rp_attribut.class.php');
	require_once('../../../include/sprache.class.php');

	if (!$db = new basis_db())
		die('Es konnte keine Verbindung zum Server aufgebaut werden.');

	$user = get_uid();
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);

	if(!$rechte->isBerechtigt('addon/reports'))
		die('Sie haben keine Berechtigung fuer dieses AddOn!');

	$reload = false;
	$htmlstr = '';
	$errorstr = '';

	$sprache = new sprache();
	$sprache->getAll(true);
	$languages = $sprache->getAllIndexesSorted();


	$attribut = new rp_attribut();
	$attribut->attribut_id		= 0;
	foreach($languages as $lang)
	{
		$attribut->shorttitle[$lang]		= '';
		$attribut->middletitle[$lang]		= '';
		$attribut->longtitle[$lang]			= '';
		$attribut->description[$lang]		= '';
	}
	$attribut->insertvon		= $user;
	$attribut->updatevon		= $user;


	if(!$rechte->isBerechtigt('addon/reports', null, 'suid'))
		die('Sie haben keine Berechtigung fuer diese Aktion');

	if(isset($_REQUEST["attribut_id"]))
	{
		$attribut_id = intval($_REQUEST['attribut_id']);

		if (is_numeric($attribut_id))
		{
			$attribut->load($attribut_id);
			if ($attribut->errormsg!='')
				die($attribut->errormsg);
		}
		if(isset($_REQUEST["action"]) && $_REQUEST["action"]=='save')
		{
			$attribut->attribut_id = $_REQUEST["attribut_id"];

			if($attribut->attribut_id == 0)
				$attribut->new = true;

			$attribut->shorttitle = array();

			foreach($languages as $lang)
			{
				$attribut->shorttitle[$lang] = $_REQUEST[$lang]["attribut_shorttitle"];
				$attribut->middletitle[$lang] = $_REQUEST[$lang]["attribut_middletitle"];
				$attribut->longtitle[$lang] = $_REQUEST[$lang]["attribut_longtitle"];
				$attribut->description[$lang] = $_REQUEST[$lang]["attribut_description"];
			}
			$attribut->save();
			$reload = true;
		}
	}

	if($attribut->attribut_id != 0)
		$htmlstr .= "<br><div class='kopf'>Attribut <b>".$attribut->attribut_id."</b></div>\n";
	else
		$htmlstr .="<br><div class='kopf'>Neues Attribut</div>\n";
	$htmlstr .= "<form action='attribut_details.php' method='POST' name='attributform'>\n";



	foreach($languages as $lang)
	{
		$htmlstr .= "	<table class='detail'>\n";
		$htmlstr .= "			<tr>\n";
		$htmlstr .= "				<td>Titel-kurz ".$sprache->getBezeichnung($lang,"German")."</td>\n";
		$htmlstr .= "				<td><input class='detail' type='text' name='".$lang."[attribut_shorttitle]' size='22' maxlength='64' value='".$attribut->shorttitle[$lang]."' onchange='submitable()'></td>\n";
		$htmlstr .= "				<td>Titel-mittel ".$sprache->getBezeichnung($lang,"German")."</td>\n";
		$htmlstr .= "				<td><input class='detail' type='text' name='".$lang."[attribut_middletitle]' size='22' maxlength='256' value='".$attribut->middletitle[$lang]."' onchange='submitable()'>\n";
		$htmlstr .= "				<td>Titel-lang ".$sprache->getBezeichnung($lang,"German")."</td>\n";
		$htmlstr .= "				<td><input class='detail' type='text' name='".$lang."[attribut_longtitle]' size='22' maxlength='512' value='".$attribut->longtitle[$lang]."' onchange='submitable()'>\n";
		$htmlstr .= "				<td style='visibility:hidden;'><input class='detail' type='text' name='attribut_id' size='22' maxlength='32' value='".$attribut->attribut_id."' onchange='submitable()'></td>\n";
		$htmlstr .= "			</tr>\n";
		$htmlstr .= "			<tr>\n";
		$htmlstr .= "				<td rowspan='2' valign='top'>Beschreibung ".$sprache->getBezeichnung($lang,"German")."</td>\n";
		$htmlstr .= " 				<td rowspan='2' colspan='3'>
														<textarea name='".$lang."[attribut_description]' cols='70' rows='14' onchange='submitable()'>".$attribut->description[$lang]."</textarea>
													</td>\n";
		$htmlstr .=	"			</td>\n";
		$htmlstr .= "		</tr>\n";
		$htmlstr .= "	</table>\n";
	}
	$htmlstr .= "<br>\n";


	$htmlstr .= "<div align='right' id='sub'>\n";
	$htmlstr .= "	<span id='submsg' style='color:red; visibility:hidden;'>Datensatz ge&auml;ndert!&nbsp;&nbsp;</span>\n";
	if($attribut->attribut_id == 0)
		$htmlstr .= "	<input type='hidden' name='new' value='new'>";
	$htmlstr .= "	<input type='submit' value='save' name='action'>\n";
	$htmlstr .= "	<input type='button' value='Reset' onclick='unchanged()'>\n";
	$htmlstr .= "</div>";
	$htmlstr .= "</form>";
	$htmlstr .= "<div class='inserterror'>".$errorstr."</div>\n";
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
<style>
	table.tablesorter tbody td
	{
		margin: 0;
		padding: 0;
		vertical-align: middle;
	}
</style>
<script type="text/javascript">


function unchanged()
{
	document.attributform.reset();
	document.attributform.schick.disabled = true;
	document.getElementById("submsg").style.visibility="hidden";
	checkrequired(document.attributform.attribut_id);
}

function confdel()
{
	return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
}


</script>
</head>
<body style="background-color:#eeeeee;">

<?php
	echo $htmlstr;

?>
<?php if($reload): ?>
<script type='text/javascript'>
	parent.frame_attribut_overview.location.href='attribut_overview.php';
</script>
<?php endif; ?>

</body>
</html>
