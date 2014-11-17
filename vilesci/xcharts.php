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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html" charset="utf-8">
		<title>FHC AddOn Data-Mining - Spidergraph</title>
		<script src="../include/js/jquery1.7.1.min.js" type="application/javascript"></script>
		<script src="../include/js/d3.min.js" type="application/javascript"></script>
		<script src="../include/js/xcharts/xcharts.min.js" type="application/javascript"></script>
		<link rel="stylesheet" href="../include/css/xchart.css">
	</head>
	<body>
		<figure id="xChart1"></figure>
		<script type="application/javascript">
			var status;
			var fields=new Array();
			var values=new Array();
			var keys=new Array();
			var i=0;
			var j=0;
			var max=0;
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
						keys[i]=new Array;
						values[i]=new Array;
						j=0;
						$.each(val, function (valkey,valval)
						{
							keys[i][j]=valkey;
							values[i][j]=valval;
							if (valval>max)
								max=valval;
							j++;
						});
						//alert(values[i]);
						i++;
					}
				);
				//$("<ul/>", {"class": "my-new-list",	html: items.join( "" )}).appendTo( "body" );
				output();
			});
			
			var tt = document.createElement('div'),
				leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
					topOffset = -32;
				tt.className = 'ex-tooltip';
				document.body.appendChild(tt);
			function output()
			{
				var v=new Array();
				var vm=new Array();
				var vc=new Array();
					
				for (i=0; i<values[0].length; i++)
				{
					v[i]=new Array();
					for (j=0; j<fields.length; j++)
					{
						v[i][j]={"x": fields[j], "y": Math.round(values[j][i])};
					}
				}
				var data = {
					"main": [
					{
					  "label": "Foo",
					  "data": v[6],
					  "className": ".foo"
					}
					],
					"xScale": "ordinal",
					"yScale": "linear",
					"comp": [
					{
					  "label": "Foo Target",
					  "data": v[2],
					  "className": ".comp.comp_foo",
					  "type": "line-dotted"
					},{
					  "label": "Foo2",
					  "data": v[5],
					  "className": ".comp.foo",
					  "type": "line-dotted"
					}
					]
				};
				var opts = {
					"mouseover": function (d, i) {
						var pos = $(this).offset();
						$(tt).text(d.x + ': ' + d.y)
						  .css({top: topOffset + pos.top, left: pos.left + leftOffset})
						  .show();
					  },
					  "mouseout": function (x) {
						$(tt).hide();
					  }
					};
				chart = new xChart('bar', data, '#xChart1',opts);				
			}	
			
				
		</script>
	</body>
</html>




