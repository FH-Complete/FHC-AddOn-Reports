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
		<style>
			#spidergraphcontainer {
				width: 600px;
				height: 600px;
				position: absolute;
				top: 50%;
				left: 50%;
				margin-top: -300px;
				margin-left: -300px;
			}
		</style>
		<div id="spidergraphcontainer"></div>
		<script type="application/javascript">
			$.getJSON("/content/statistik/dropout.php?outputformat=json",
				function(data)
				{
					var items = [];
					$.each( data,
						function(key,val)
						{
							items.push( "<li id='" + key + "'>" + val + "</li>" );
						}
					);
					$("<ul/>", {"class": "my-new-list",	html: items.join( "" )}).appendTo( "body" );
				}
			);
				
			$(document).ready( function() {
			$('#spidergraphcontainer').spidergraph({
				'fields': ['a','b','c','d','e'],
				'gridcolor': 'rgba(20,20,20,1)'   
			});
			$('#spidergraphcontainer').spidergraph('addlayer', { 
				'strokecolor': 'rgba(230,104,0,0.8)',
				'fillcolor': 'rgba(230,104,0,0.6)',
				'data': [0, 8, 2, 4, 9]
			});
			$('#spidergraphcontainer').spidergraph('addlayer', { 
				'strokecolor': 'rgba(230,204,0,0.8)',
				'fillcolor': 'rgba(230,204,0,0.6)',
				'data': [5, 4, 9, 9, 4]
			});

			
			$('#spidergraphcontainer').spidergraph('resetdata');
			
			$('#spidergraphcontainer').spidergraph('setactivedata', { 
				'strokecolor': 'rgba(230,104,230,0.8)',
				'fillcolor': 'rgba(230,104,230,0.6)',
				'data': [3, 2, 3, 4, 9]
			});
			$('#spidergraphcontainer').spidergraph('addlayer', { 
				'strokecolor': 'rgba(230,204,0,0.8)',
				'fillcolor': 'rgba(230,204,0,0.6)',
				'data': [5, 4, 9, 8, 1]
			});
			$('#spidergraphcontainer').spidergraph('addlayer', { 
				'strokecolor': 'rgba(230,104,0,0.8)',
				'fillcolor': 'rgba(230,104,0,0.6)',
				'data': [0, 8, 2, 3, 5]
			});

		});
		</script>
	</body>
</html>




