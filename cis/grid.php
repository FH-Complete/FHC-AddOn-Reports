<?php
/* Copyright (C) 2014 fhcomplete.org
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
 * Authors: Christian Paminger, 
 */

// pChart library inclusions 
require_once("../include/pChart/class/pData.class.php");
require_once("../include/pChart/class/pDraw.class.php");
require_once("../include/pChart/class/pImage.class.php");

require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/filter.class.php');
require_once('../../../include/statistik.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);
/* if(!$rechte->isBerechtigt('addon/reports'))
  {
  die('Sie haben keine Berechtigung fuer dieses AddOn');
  } */

$statistik = new statistik();

$statistik_kurzbz = filter_input(INPUT_GET, 'statistik_kurzbz');
$htmlbody = filter_input(INPUT_GET, 'htmlbody', FILTER_VALIDATE_BOOLEAN);

if(isset($statistik_kurzbz))
{
	$statistik->load($statistik_kurzbz);
}
else
{
	die('"statistik_kurzbz" is not set!');
}

$i = 0;
while(isset($_GET['varname' . $i]))
{
	$statistik->vars.='&' . $_GET['varname' . $i] . '=';
	if(isset($_GET['var' . $i]))
	{
		$statistik->vars.=$_GET['var' . $i];
	}
	else
	{
		die('"var"' . $i . ' is not set!');
	}
	$i++;
}

$statistik->loadData(); ?>

<?php if($htmlbody): ?>
<html>
	<head>
		<link rel="stylesheet" href="../../../submodules/pivottable/dist/pivot.min.css" />
		<script type="text/javascript" src="../include/js/jquery-1.11.2.min.js"></script>
		<script type="text/javascript" src="../include/js/jquery-ui.1.11.2.min.js"></script>
		<script type="text/javascript" src="../../../submodules/pivottable/dist/pivot.min.js"></script>
	</head>
	<body>
<?php endif; ?>

		<div id="pivot">
		</div>
		<?php if($statistik->data): ?>
		<script type="text/javascript">
			var options = <?php echo $statistik->preferences ? : '{}' ?>;
			var dateFormat =       $.pivotUtilities.derivers.dateFormat;
            var sortAs =           $.pivotUtilities.sortAs;
            var tpl =              $.pivotUtilities.aggregatorTemplates;
			$('#pivot').pivotUI(<?php echo $statistik->db_getResultJSON($statistik->data) ?>, options);
		</script>
		<?php endif; ?>

<?php if($htmlbody): ?>
	</body>
</html>
<?php endif;
