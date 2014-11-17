<?php
/* Copyright (C) 2014 FH Technikum-Wien
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
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

/*if(!$rechte->isBerechtigt('addon/datamining'))
{
	die('Sie haben keine Berechtigung fuer dieses AddOn');
}*/
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html style="height:100%">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
		<title>FHC AddOn Data-Mining - Spidergraph</title>
		<script src="../include/js/jquery1.7.1.min.js" type="application/javascript"></script>
		<script src="../include/js/jquery.spidergraph.js" type="application/javascript"></script>
	</head>
	<body style="height:100%">
		<p id="p1"></p>
		<script type="application/javascript">
			var status;
			var fields=new Array();
			var values=new Array();
			var keys=new Array();
			var i=0;
			var j=0;
			$.ajaxSetup({ cache: false });
			$.getJSON('../../../content/statistik/dropout.php?outputformat=json').done(function(data){
				//alert(data);
				var items = [];
				$.each( data,
					function(key,val)
					{
						//alert (key);
						items.push( "<li id='" + key + "'>" + val + "</li>" );
						fields[i]=key;
						values[i]=new Array;
						keys[i]=new Array;
						j=0;
						$.each(val, function (valkey,valval)
						{
							values[i][j]=valval;
							keys[i][j]=valkey;
							j++;
						});
						//alert(values[i]);
						i++;
					}
				);
				//$("<ul/>", {"class": "my-new-list",	html: items.join( "" )}).appendTo( "body" );
				output();
			});
			function output()
			{
				for (i=0; i<fields.length; i++)
				{
					$('#p1').append(fields[i]);
					for (j=0; j<keys.length; j++)
					{
						$('#p1').append(keys[i][j]);
					}
				}
			}
		</script>
	</body>
</html>




