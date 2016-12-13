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
			background: url("../include/images/Publish.svg") no-repeat center center;
			background-size: 10px 10px;
			padding-left: 5px;
			padding-right: 5px;
			margin-left: 2px;
			margin-right: 2px;
		}
		.not_publish {
			background: url("../include/images/NotPublish.svg") no-repeat center center;
			background-size: 10px 10px;
			padding-left: 5px;
			padding-right: 5px;
			margin-left: 2px;
			margin-right: 2px;
		}
		.locked {
			background: url("../include/images/Authorization.svg") no-repeat center center;
			background-size: 10px 10px;
			padding-left: 5px;
			padding-right: 5px;
			margin-left: 2px;
			margin-right: 2px;
		}
	</style>
	<script>
	var folderStates = ["Reports", "Charts", "Statistiken"];
	folderStates["Reports"] = "closed";
	folderStates["Charts"] = "closed";
	folderStates["Statistiken"] = "closed";

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
					alert(res.message + ": " + res.toSource());
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
			mt.tree(
			{
				onDrop: function(node, source, point)
				{
					var nodeData = mt.tree("getData", node);


					//es handelt sich um eine einzelne statistik, einen report oder einen chart!
					if(source.chart_id || source.report_id || source.statistik_kurzbz)
					{
						addEntityToMenue(source, nodeData.reportgruppe_id)

						rebuildAllRCS();
						rebuildMenue();
						return;
					}

					//es handelt sich um eine reportgruppe
					else if(source.reportgruppe_id)
					{
						var sortorder = 500;

						if(point === "append")
						{
							var parent = nodeData;
							if(nodeData.sortorder > 0)
								sortorder = nodeData.sortorder;
							for(var i = 0; i < parent.children.length; i++)
							{
								if(parent.children[i].sortorder >= sortorder)
									sortorder = parseInt(parent.children[i].sortorder)+1;		//wird ganz hinten angehängt
							}
						}
						else
						{
							var parent = mt.tree('getParent',node);
							if(point === "bottom")
							{
								if(nodeData.sortorder > 0)
									sortorder = parseInt(nodeData.sortorder) + 1;
							}
							else
							{
								if(nodeData.sortorder > 0)
									sortorder = parseInt(nodeData.sortorder) - 1;
							}
						}

						if (parent)
						{//an einem parent


							AJAXCall(
							"action=saveReportGruppe" +
							"&bezeichnung=" +	source.text +
							"&reportgruppe_parent_id=" + parent.reportgruppe_id +
							"&sortorder=" + (sortorder) +
							"&reportgruppe_id=" + source.reportgruppe_id,
							function(data)
							{
								rebuildMenue();
							});
							return;
						}
						else
						{//root ebene
							AJAXCall(
							"action=saveReportGruppe" +
							"&bezeichnung=" + source.text +
							"&reportgruppe_parent_id=" + "" +
							"&sortorder=" + (sortorder) +
							"&reportgruppe_id=" + source.reportgruppe_id,
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

			$('#entityTree').tree({
				onExpand: function(node){
					folderStates[node.text] = node.state;
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
			var gruppenzuordnung_id = "";
			if(ent.gruppenzuordnung_id)
				gruppenzuordnung_id = ent.gruppenzuordnung_id;

			if(ent.statistik_kurzbz)
			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&statistik_kurzbz=" +	ent.statistik_kurzbz +
				"&reportgruppe_id=" + reportgruppe_id +
				"&gruppenzuordnung_id="+gruppenzuordnung_id,
				function(data)
				{
					rebuildMenue();
				});
			}
			else if(ent.chart_id)
			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&chart_id=" +	ent.chart_id +
				"&reportgruppe_id=" + reportgruppe_id +
				"&gruppenzuordnung_id="+gruppenzuordnung_id,
				function(data)
				{
					rebuildMenue();
				});
			}
			else if(ent.report_id)
			{
				AJAXCall(
				"action=addEntityToMenue" +
				"&report_id=" +	ent.report_id +
				"&reportgruppe_id=" + reportgruppe_id +
				"&gruppenzuordnung_id="+gruppenzuordnung_id,
				function(data)
				{
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
				//report/statistik/chart-Ordner geöffnet status
				data[0].state = folderStates["Reports"];
				data[1].state = folderStates["Charts"];
				data[2].state = folderStates["Statistiken"];
				$('#entityTree').tree({data: data});
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
				"&sortorder=" + 500 +
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
		<div style="margin-bottom:10px;">
			<input id="menueAdd" placeholder="neuer Menuepunkt"></input>
			<input type="button" onclick="add()" value="Hinzufügen"/>
		</div>
		<div class="easyui-panel">
			<ul id="menueTree" class="easyui-tree" data-options="animate:true,dnd:true"></ul>
		</div>
		<div>
			Reports, Statistiken, Charts und Menüpunkte können per Rechtsklick gelöscht werden, müssen dafür jedoch leer sein.
		</div>
		<div>
			<h3>Legende</h3>
			<div>
				<div><span class="publish"></span> Öffentlich</div>
				<div><span class="not_publish"></span> nicht Öffentlich</div>
				<div><span class="locked"></span> es werden Berechtigungen benötigt</div>
			</div>
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
