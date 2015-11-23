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
 */
/**
 * FH-Complete Addon Report Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
require_once('../../config/system.config.inc.php');
require_once('../../include/basis_db.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/benutzerberechtigung.class.php');

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
	<title>Addon Datenbank Check</title>
</head>
<body>
<h1>Addon Datenbank Check</h1>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon'))
{
    exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<h2>Aktualisierung der Datenbank</h2>';

// Code fuer die Datenbankanpassungen

//Neue Berechtigung für das Addon hinzufügen
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/reports'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
				VALUES('addon/reports','AddOn Reports');";

		if(!$db->db_query($qry))
			echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Berechtigung addon/reports hinzugefuegt!<br>';
	}
}

// Check new Schema reports
$result = $db->db_query("SELECT 1 FROM pg_namespace WHERE nspname = 'reports';");
if ($db->db_num_rows()!=1)
	if(!$db->db_query("CREATE SCHEMA reports; GRANT USAGE ON SCHEMA reports TO vilesci; GRANT USAGE ON SCHEMA reports TO web;"))
		echo '<strong>Reports: '.$db->db_last_error().'</strong><br>';
	else
		echo ' Reports: Schema reports wurde angelegt!<br>';

// Reports (rp) Chart
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_chart"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_chart
			(
				chart_id serial,
				title varchar(32),
				description varchar(512),
				type varchar(32),
				sourcetype varchar(32),
				preferences text,
				datasource varchar(256),
				datasource_type varchar(32),
				statistik_kurzbz varchar(64),
				publish boolean,
				dashboard boolean,
				dashboard_layout varchar(32),
				dashboard_pos smallint,
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32),
				CONSTRAINT pk_rp_chart PRIMARY KEY (chart_id)
			);
			ALTER TABLE addon.tbl_rp_chart ADD CONSTRAINT "fk_rp_chart_statistik" FOREIGN KEY (statistik_kurzbz)
			REFERENCES public.tbl_statistik(statistik_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_chart TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_chart_chart_id_seq TO vilesci;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_chart: Tabelle addon.tbl_rp_chart hinzugefuegt!<br>';

}
// Reports (rp) Report
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_report"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_report
			(
				report_id serial,
				title varchar(64),
				format varchar(32),
				description text,
				body text,
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32),
				CONSTRAINT pk_rp_report PRIMARY KEY (report_id)
			);
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_report TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_report_report_id_seq TO vilesci;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_report: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Tabelle addon.tbl_rp_report hinzugefuegt!<br>';

}

// Reports (rp) Report
if(!$result = @$db->db_query("SELECT statistik_kurzbz FROM addon.tbl_rp_chart"))
{

	$qry = 'ALTER TABLE addon.tbl_rp_chart ADD COLUMN statistik_kurzbz varchar(64);
			ALTER TABLE addon.tbl_rp_chart ADD COLUMN publish boolean;
			ALTER TABLE addon.tbl_rp_chart ADD CONSTRAINT fk_rp_chart_statistik FOREIGN KEY (statistik_kurzbz) REFERENCES public.tbl_statistik(statistik_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_chart: Spalte statistik_kurzbz hinzugefuegt!<br>';

}

// Reports (rp) Report
if(!$result = @$db->db_query("SELECT dashboard FROM addon.tbl_rp_chart"))
{

	$qry = 'ALTER TABLE addon.tbl_rp_chart ADD COLUMN dashboard boolean NOT NULL DEFAULT FALSE;
			ALTER TABLE addon.tbl_rp_chart ADD COLUMN dashboard_layout varchar(32);
			ALTER TABLE addon.tbl_rp_chart ADD COLUMN dashboard_pos smallint;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_chart: Spalte dashboard, dashboard_layout, dashboard_pos hinzugefuegt!<br>';

}
// Reports (rp) Publish
if(!$result = @$db->db_query("SELECT gruppe, publish, header, footer, docinfo FROM addon.tbl_rp_report"))
{

	$qry = 'ALTER TABLE addon.tbl_rp_report ADD COLUMN publish boolean NOT NULL DEFAULT FALSE;
			ALTER TABLE addon.tbl_rp_report ADD COLUMN gruppe varchar(256);
			ALTER TABLE addon.tbl_rp_report ADD COLUMN header text;
			ALTER TABLE addon.tbl_rp_report ADD COLUMN footer text;
			ALTER TABLE addon.tbl_rp_report ADD COLUMN docinfo xml;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Spalte gruppe, header, footer, docinfo und publish hinzugefuegt!<br>';

}

// Reports (rp) to Charts
// Reports (rp) Chart
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_report_chart"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_report_chart
			(
				reportchart_id serial,
				report_id bigint,
				chart_id bigint,
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32),
				CONSTRAINT pk_rp_report_chart PRIMARY KEY (reportchart_id)
			);
			ALTER TABLE addon.tbl_rp_report_chart ADD CONSTRAINT "fk_rp_report_chart_chart" FOREIGN KEY (chart_id)
			REFERENCES addon.tbl_rp_chart(chart_id) ON UPDATE CASCADE ON DELETE RESTRICT;
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_report_chart TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_report_chart_reportchart_id_seq TO vilesci;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_report_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report_chart: Tabelle addon.tbl_rp_report_chart hinzugefuegt!<br>';

}
// Reports (rp) to Statistik
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_report_statistik"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_report_statistik
			(
				reportstatistik_id serial,
				report_id bigint,
				statistik_kurzbz varchar(64),
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32) DEFAULT now(),
				CONSTRAINT pk_rp_report_statistik PRIMARY KEY (reportstatistik_id)
			);
			ALTER TABLE addon.tbl_rp_report_statistik ADD CONSTRAINT "fk_rp_report_statistik_statistik" FOREIGN KEY (statistik_kurzbz)
			REFERENCES public.tbl_statistik(statistik_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_report_statistik TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_report_statistik_reportstatistik_id_seq TO vilesci;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_report_statistik: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report_statistik: Tabelle addon.tbl_rp_report_statistik hinzugefuegt!<br>';

}

// Reports (rp) Report
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_gruppe"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_gruppe
			(
				reportgruppe_id serial,
				bezeichnung varchar(256),
				reportgruppe_parent_id integer,
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32),
				CONSTRAINT pk_rp_gruppe PRIMARY KEY (reportgruppe_id)
			);
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_gruppe TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_gruppe_reportgruppe_id_seq TO vilesci;

			ALTER TABLE addon.tbl_rp_gruppe ADD CONSTRAINT "fk_rp_report_gruppe_parent" FOREIGN KEY (reportgruppe_parent_id) REFERENCES addon.tbl_rp_gruppe(reportgruppe_id) ON UPDATE CASCADE ON DELETE RESTRICT;

			CREATE TABLE addon.tbl_rp_gruppenzuordnung
			(
				gruppenzuordnung_id integer,
				reportgruppe_id integer,
				chart_id integer,
				report_id integer,
				statistik_kurzbz varchar(64),
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32)
			);

			CREATE SEQUENCE addon.seq_rp_gruppenzuordnung_gruppenzuordnung_id
		 INCREMENT BY 1
		 NO MAXVALUE
		 NO MINVALUE
		 CACHE 1;

		ALTER TABLE addon.tbl_rp_gruppenzuordnung ADD CONSTRAINT pk_rp_gruppenzuordnung PRIMARY KEY (gruppenzuordnung_id);
		ALTER TABLE addon.tbl_rp_gruppenzuordnung ALTER COLUMN gruppenzuordnung_id SET DEFAULT nextval(\'addon.seq_rp_gruppenzuordnung_gruppenzuordnung_id\');
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_gruppenzuordnung TO vilesci;
			GRANT SELECT, UPDATE ON addon.seq_rp_gruppenzuordnung_gruppenzuordnung_id TO vilesci;

			ALTER TABLE addon.tbl_rp_gruppenzuordnung ADD CONSTRAINT "fk_rp_gruppenzuordnung_reportgruppe_id" FOREIGN KEY (reportgruppe_id) REFERENCES addon.tbl_rp_gruppe(reportgruppe_id) ON UPDATE CASCADE ON DELETE RESTRICT;
			ALTER TABLE addon.tbl_rp_gruppenzuordnung ADD CONSTRAINT "fk_rp_gruppenzuordnung_chart_id" FOREIGN KEY (chart_id) REFERENCES addon.tbl_rp_chart(chart_id) ON UPDATE CASCADE ON DELETE RESTRICT;
			ALTER TABLE addon.tbl_rp_gruppenzuordnung ADD CONSTRAINT "fk_rp_gruppenzuordnung_report_id" FOREIGN KEY (report_id) REFERENCES addon.tbl_rp_report(report_id) ON UPDATE CASCADE ON DELETE RESTRICT;
			ALTER TABLE addon.tbl_rp_gruppenzuordnung ADD CONSTRAINT "fk_rp_gruppenzuordnung_statistik_kurzbz" FOREIGN KEY (statistik_kurzbz) REFERENCES public.tbl_statistik(statistik_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_gruppe: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_gruppe: Tabelle addon.tbl_rp_gruppe hinzugefuegt!<br>';

}



//Views
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_view"))
{

	$qry = 'CREATE TABLE addon.tbl_rp_view
			(
				view_id serial,
				view_kurzbz varchar(64),
				table_kurzbz varchar(64),
				sql text,
				static boolean DEFAULT false,
				lastcopy TIMESTAMP with time zone,
				insertamum timestamp DEFAULT now(),
				insertvon varchar(32),
				updateamum timestamp DEFAULT now(),
				updatevon varchar(32),
				CONSTRAINT pk_rp_view
				PRIMARY KEY (view_id),
			  UNIQUE (view_kurzbz)
			);
			GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_view TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_rp_view_view_id_seq TO vilesci;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_view: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_view: Tabelle addon.tbl_rp_view hinzugefuegt!<br>';

}


// Reports (rp) Report
if(!$result = @$db->db_query("SELECT berechtigung_kurzbz FROM addon.tbl_rp_report"))
{

	$qry = 'ALTER TABLE addon.tbl_rp_report ADD COLUMN berechtigung_kurzbz varchar(32);
			ALTER TABLE addon.tbl_rp_report ADD CONSTRAINT "fk_rp_report_berechtigung" FOREIGN KEY (berechtigung_kurzbz) REFERENCES system.tbl_berechtigung(berechtigung_kurzbz) ON UPDATE CASCADE ON DELETE RESTRICT;
			';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_report: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Tabelle addon.tbl_rp_report.berechtigung_kurzbz hinzugefuegt!<br>';


}
echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';

// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_rp_chart"  => array("chart_id", "title", "description", "type", "preferences", "datasource", "datasource_type", "insertamum", "insertvon", "updateamum", "updatevon", "statistik_kurzbz")
	,"addon.tbl_rp_view"  => array("view_kurzbz", "table_kurzbz", "sql", "static", "lastcopy", "insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_report" => array("report_id", "title", "format", "description", "header", "footer", "body", "docinfo", "gruppe", "publish", "insertamum", "insertvon", "updateamum", "updatevon", "berechtigung_kurzbz")
	,"addon.tbl_rp_report_chart" => array("reportchart_id","report_id","chart_id","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_report_statistik" => array("reportstatistik_id","report_id","statistik_kurzbz","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_gruppe" => array("reportgruppe_id","bezeichnung","reportgruppe_parent_id","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_gruppenzuordnung" => array("gruppenzuordnung_id","reportgruppe_id","chart_id","report_id","statistik_kurzbz","insertamum","insertvon","updateamum","updatevon")
);


$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr='';
	foreach($attribute AS $attr)
		$sql_attr.=$attr.',';
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query('SELECT '.$sql_attr.' FROM '.$tabs[$i].' LIMIT 1;'))
		echo '<BR><strong>'.$tabs[$i].': '.$db->db_last_error().' </strong><BR>';
	else
		echo '- '.$tabs[$i].': OK<br>';
	flush();
	$i++;
}
?>
