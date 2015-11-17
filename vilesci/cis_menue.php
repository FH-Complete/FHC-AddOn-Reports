<?php
/* Copyright (C) 2015 FH Technikum-Wien
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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
require_once('../../../config/vilesci.config.inc.php');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reporting Men&uuml;-Builder</title>

  <?php require_once("../../../include/meta/easyui.php"); ?>

	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<style>
		.publish {
  		padding-left: 6px;
  		padding-right: 6px;
  		background-color: #44FF44;
  		border-radius: 6px;
  		margin-left: 3px;
  		margin-right: 3px;
		}
		.not_publish {
  		padding-left: 6px;
  		padding-right: 6px;
  		background-color: #FF4444;
  		border-radius: 6px;
  		margin-left: 3px;
  		margin-right: 3px;
		}
		.locked {
			background: url("../../../skin/images/lock.png") no-repeat center center;
  		background-size: 10px 10px;
  		padding-left: 5px;
  		padding-right: 5px;
		}
	</style>
  <script>


	  function die(msg)
	  {
		  document.body.innerHTML = msg;
		  throw new Error(msg);
	  }

    function AJAXCall(info, successfunction)
	  {
		  var errMsg = "<p style='color:#ff0000;font-weight:bold;font-size:17pt;text-align:center;margin:5%;'>Es ist ein schwerwiegender Fehler aufgetreten:</p>";

		  $.ajax(
		  {
			  url: "cis_menue.json.php",
			  type: "POST",
			  dataType: "html",
			  data: info,
			  timeout: 5000

		  }).done(function(result)
		  {
			  try
			  {
				  var res = JSON.parse(result)
			  }
			  catch (e)
			  {
				  die(errMsg + result);
			  }
			  if(res.erfolg)
			  {
				  successfunction(res.info);
			  }
			  else
			  {
				  alert(res.message);
					rebuildMenue();
					rebuildAllRCS();
			  }

		  }).fail(function(jqXHR, status)
		  {
			  die(errMsg + result);
		  });
	  }

		function confdel()
		{
			return confirm("Wollen Sie diesen Eintrag wirklich löschen?");
		}

 		$(function()
 		{
			rebuildMenue();
			rebuildAllRCS();


			var mt = $('#menueTree');
	 		mt.tree({
				onDrop: function(node, ent){

					var target = mt.tree("getData", node);


					/* TODO
					//um noch einen parent höher zu gehen!(tiefere menüstruktur)
					if (p1)
					{
						var p2 = mt.tree("getParent", p1.target);
						if (p2 && p2.reportgruppe_parent_id === null)
						{
							alert("Verschieben!");
							return;
						}
					}
					*/


					//es handelt sich um eine einzelne statistik, einen report oder einen chart!
					if(ent.chart_id || ent.report_id || ent.statistik_kurzbz)
					{
						var p1 = mt.tree("getParent", node);
						if (p1 && p1.reportgruppe_parent_id === null)
						{
								addEntityToMenue(ent, target.reportgruppe_id)

								rebuildAllRCS();
								rebuildMenue();
								return;
						}
					}

					//es handelt sich um eine reportgruppe
					else if(ent.reportgruppe_id)
					{
						var p1 = mt.tree("getParent", ent.target);

						if (p1)
						{
							AJAXCall(
							"action=saveReportGruppe" +
							"&bezeichnung=" +	ent.text +
							"&reportgruppe_parent_id=" +	p1.reportgruppe_id +
							"&reportgruppe_id=" +	ent.reportgruppe_id,
							 function(data)
							 {
									rebuildMenue();
							});
							return;
						}
					}

					//wenn nichts der erlaubten drops zutrifft
					alert("Nicht erlaubt!");
					rebuildMenue();
					rebuildAllRCS();
				}
			});

	 		$('#entityTree').tree({
				onDrop: function(node){
					alert("Nicht erlaubt!");
					rebuildAllRCS();
					rebuildMenue();
				}
			});

	 		$('#menueTree').tree({
				onContextMenu: function(e, node)
				{
					e.preventDefault();
					var ent = mt.tree("getData", node.target);

					if(ent.gruppenzuordnung_id)
					{
						if(confdel())
						{
							AJAXCall(
							"action=removeGruppenzuordung" +
							"&gruppenzuordnung_id=" +	ent.gruppenzuordnung_id,
							 function(data)
							 {
									rebuildMenue();
							});
						}
					}
					else if(ent.reportgruppe_id && ent.gruppenzuordnung_id === undefined)
					{
						if(confdel())
						{
							AJAXCall(
							"action=removeReportgruppe" +
							"&reportgruppe_id=" +	ent.reportgruppe_id,
							 function(data)
							 {
									rebuildMenue();
							});
						}
					}
				}
			});
 		});

 		function addEntityToMenue(ent, reportgruppe_id)
 		{
 			if(ent.statistik_kurzbz)
 			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&statistik_kurzbz=" +	ent.statistik_kurzbz +
				"&reportgruppe_id=" + reportgruppe_id,
				 function(data){
				 	rebuildMenue();
				 });
 			}
 			else if(ent.chart_id)
 			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&chart_id=" +	ent.chart_id +
				"&reportgruppe_id=" + reportgruppe_id,
				 function(data){
				 	rebuildMenue();
				 });
 			}
 			else if(ent.report_id)
 			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&report_id=" +	ent.report_id +
				"&reportgruppe_id=" + reportgruppe_id,
				 function(data){
				 	rebuildMenue();
				 });
 			}
 		}

 		function rebuildMenue()
 		{
			AJAXCall("action=menueBaum", function(data)
			{
	 			$('#menueTree').tree({data: data});
			});
 		}

 		function rebuildAllRCS()		//alle Reports Charts und Statistiken neu holen und anzeigen
 		{
			AJAXCall("action=alleDaten", function(data)
			{
				//report/statistik/chart-Ordner schließen
				data[0].state = "closed"
				data[1].state = "closed"
				data[2].state = "closed"
				$('#entityTree').tree({data: data});
			});
 		}

 		function saveNewEntry(entry)
 		{
			alert("spinner show ");

			AJAXCall("action=alleDaten", function(data){
				alert("hackerl show ");
			});
 		}



 		function add()
 		{
 			var txt = $("#menueAdd").val();
 			if(txt === "")
 			{
 				alert("Es wurde kein Name angegeben");
 				return;
 			}

 			$("#menueAdd").val("");

			AJAXCall(
				"action=saveReportGruppe" +
				"&bezeichnung=" +	txt +
				"&reportgruppe_parent_id=" +	"",
				 function(data)
				 {
						rebuildMenue();
				});
 		}
  </script>
</head>
<body>
  <h2>Reports Men&uuml;-Builder</h2>

	<div id="builder" style="float:left;width:47%;">
		<input id="menueAdd" placeholder="neuer Menuepunkt"></input>
		<button onclick="add()">Hinzufügen</button>
		<div class="easyui-panel" style="padding:5px;margin-top:10px;margin-bottom:10px;">
			<ul id="menueTree" class="easyui-tree" data-options="animate:true,dnd:true"></ul>
		</div>
		<div>
			Reports, Statistiken, Charts und Menüpunkte können per Rechtsklick gelöscht werden, müssen dafür jedoch leer sein.
		</div>
	</div>
	<div id="zuordnung" style="float:right;width:47%;">
		<div>
			Neue Reports, Statistiken oder Charts können von hier aus per Drag&Drop zum Menü hinzugefügt werden.
		</div>
		<div class="easyui-panel" style="padding:5px;margin-top:10px;margin-bottom:10px;">
			<ul id="entityTree" class="easyui-tree" data-options="animate:true,dnd:true"></ul>
		</div>
	</div>
</body>
</html>
