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
require_once('include/rp_chart.class.php');

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
if(!$result = @$db->db_query("SELECT publish FROM addon.tbl_rp_chart"))
{

	$qry = 'ALTER TABLE addon.tbl_rp_chart ADD COLUMN publish boolean;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_chart: Spalte publish hinzugefuegt!<br>';

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

// Reports (rp) footer
if(!$result = @$db->db_query("SELECT footer FROM addon.tbl_rp_report"))
{
	$qry = 'ALTER TABLE addon.tbl_rp_report ADD COLUMN footer text;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Spalte footer hinzugefuegt!<br>';
}

// Reports (rp) docinfo
if(!$result = @$db->db_query("SELECT docinfo FROM addon.tbl_rp_report"))
{
	$qry = 'ALTER TABLE addon.tbl_rp_report ADD COLUMN docinfo xml;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Spalte docinfo hinzugefuegt!<br>';
}

// Reports (rp) header
if(!$result = @$db->db_query("SELECT header FROM addon.tbl_rp_report"))
{
	$qry = 'ALTER TABLE addon.tbl_rp_report ADD COLUMN header text;';

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_report: Spalte header hinzugefuegt!<br>';
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






/************************************  SELECT RECHTE FÜR USER "web"  ************************************/


// Reports (rp) Chart
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_chart' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_chart TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_chart: User "web" SELECT Rechte gewaehrt!<br>';
	}
}




// Reports (rp) Gruppe
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_gruppe' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_gruppe TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_gruppe: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_gruppe: User "web" SELECT Rechte gewaehrt!<br>';
	}
}



// (rp) gruppenzuordnung
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_gruppenzuordnung' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_gruppenzuordnung TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_gruppenzuordnung: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_gruppenzuordnung: User "web" SELECT Rechte gewaehrt!<br>';
	}
}



// Reports (rp) Report
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_report' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_report TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_report: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_report: User "web" SELECT Rechte gewaehrt!<br>';
	}
}



// Reports (rp) Report_chart
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_report_chart' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_report_chart TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_report_chart: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_report_chart: User "web" SELECT Rechte gewaehrt!<br>';
	}
}



// Reports (rp) Report_statistik
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_report_statistik' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_report_statistik TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_report_statistik: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_report_statistik: User "web" SELECT Rechte gewaehrt!<br>';
	}
}


// Reports (rp) Views
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_rp_view' AND table_schema='addon' AND privilege_type='SELECT' AND grantee='web' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON addon.tbl_rp_view TO web;';

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_view: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_view: User "web" SELECT Rechte gewaehrt!<br>';
	}
}




// (public) filter
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_name='tbl_filter' AND table_schema='public' AND privilege_type='SELECT' AND grantee='web' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON public.tbl_filter TO web;';

		if(!$db->db_query($qry))
			echo '<strong>public.tbl_filter: '.$db->db_last_error().'</strong><br>';
		else
			echo ' public.tbl_filter: User "web" SELECT Rechte gewaehrt!<br>';
	}
}


// Reports (rp) Schema Reports
if($result = @$db->db_query("SELECT * FROM information_schema.role_table_grants WHERE table_schema='reports' AND privilege_type='SELECT' AND grantee='web'"))
{
	if(!$db->db_fetch_object($result))
	{
		$qry = 'GRANT SELECT ON ALL TABLES IN SCHEMA reports TO web;';

		if(!$db->db_query($qry))
			echo '<strong>reports.*: '.$db->db_last_error().'</strong><br>';
		else
			echo ' reports.*: User "web" SELECT Rechte gewaehrt!<br>';
	}
}







/************************************  11.15 tbl_rp_chart änderungen  ************************************/
//longtitle einfuegen
if(!$result = @$db->db_query("SELECT longtitle FROM addon.tbl_rp_chart LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_rp_chart ADD COLUMN longtitle varchar(128);";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_chart: Spalte "longtitle" eingefuegt!<br>';
}

//aenderung title von varchar(32) auf varchar(64)
if($result = @$db->db_query("SELECT * FROM information_schema.columns WHERE table_schema='addon' AND table_name='tbl_rp_chart' AND column_name='title'"))
{
	if($db->db_fetch_object($result)->character_maximum_length == 32)
	{
		$qry = "ALTER TABLE addon.tbl_rp_chart ALTER COLUMN title TYPE varchar(64)";

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_chart: Spalte "title" von varchar(32) auf varchar(64) geaendert!<br>';
	}
}

//aenderung description von varchar(512) auf text
if($result = @$db->db_query("SELECT * FROM information_schema.columns WHERE table_schema='addon' AND table_name='tbl_rp_chart' AND column_name='description'"))
{
	if($db->db_fetch_object($result)->data_type == "character varying")
	{
		$qry = "ALTER TABLE addon.tbl_rp_chart ALTER COLUMN description TYPE text";

		if(!$db->db_query($qry))
			echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
		else
			echo ' addon.tbl_rp_chart: Spalte "description" von varchar(512) auf text geaendert!<br>';
	}
}

/************************************  12.15 tbl_rp_gruppe sortierung  ************************************/
//sortorder einfuegen
if(!$result = @$db->db_query("SELECT sortorder FROM addon.tbl_rp_gruppe LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_rp_gruppe ADD COLUMN sortorder integer;";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_gruppe: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_gruppe: Spalte "sortorder" eingefuegt!<br>';
}
//CREATE für vilesci
if(!$db->db_fetch_object(@$db->db_query("SELECT nspname,
       coalesce(nullif(role.name,''), 'PUBLIC') AS name,
       substring(
          CASE WHEN position('U' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0 THEN ', USAGE' ELSE '' END
          || CASE WHEN position('C' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0 THEN ', CREATE' ELSE '' END
       , 3,10000) AS privileges
FROM pg_namespace pn, (SELECT pg_roles.rolname AS name
   FROM pg_roles UNION ALL SELECT '' AS name) AS role
 WHERE (','||array_to_string(nspacl,',')) LIKE '%,'||role.name||'=%'
AND name='vilesci'
AND position('C' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0
AND nspname='reports';")))
{
// Create für vilesci
	if(!$db->db_query("GRANT USAGE, CREATE ON SCHEMA reports TO vilesci;"))
		echo '<strong>Reports: '.$db->db_last_error().'</strong><br>';
	else
		echo ' reports.*: User "vilesci" CREATE Rechte gewaehrt!<br>';
}


//CREATE für web
if(!$db->db_fetch_object(@$db->db_query("SELECT nspname,
       coalesce(nullif(role.name,''), 'PUBLIC') AS name,
       substring(
          CASE WHEN position('U' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0 THEN ', USAGE' ELSE '' END
          || CASE WHEN position('C' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0 THEN ', CREATE' ELSE '' END
       , 3,10000) AS privileges
FROM pg_namespace pn, (SELECT pg_roles.rolname AS name
   FROM pg_roles UNION ALL SELECT '' AS name) AS role
 WHERE (','||array_to_string(nspacl,',')) LIKE '%,'||role.name||'=%'
AND name='web'
AND position('C' in split_part(split_part((','||array_to_string(nspacl,',')), ','||role.name||'=',2 ) ,'/',1)) > 0
AND nspname='reports';")))
{
	// Create für web
	if(!$db->db_query("GRANT USAGE, CREATE ON SCHEMA reports TO web;"))
		echo '<strong>Reports: '.$db->db_last_error().'</strong><br>';
	else
		echo ' reports.*: User "web" CREATE Rechte gewaehrt!<br>';
}


/************************************  01.16 tbl_rp_attribut  ************************************/
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_attribut"))
{
	$qry = "

	CREATE SEQUENCE addon.tbl_rp_attribut_attribut_id_seq
		INCREMENT BY 1
		NO MAXVALUE
		NO MINVALUE
		CACHE 1;

	CREATE TABLE addon.tbl_rp_attribut
	(
		attribut_id integer NOT NULL DEFAULT nextval('addon.tbl_rp_attribut_attribut_id_seq'),
		shorttitle varchar(64)[],
		middletitle varchar(256)[],
		longtitle varchar(512)[],
		description TEXT[],
		insertamum timestamp DEFAULT now(),
		insertvon varchar(32),
		updateamum timestamp DEFAULT now(),
		updatevon varchar(32),
		CONSTRAINT pk_rp_attribut PRIMARY KEY (attribut_id)
	);


	GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_attribut TO vilesci;
	GRANT SELECT, UPDATE ON addon.tbl_rp_attribut_attribut_id_seq TO vilesci;
	";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_attribut: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_attribut: Tabelle addon.tbl_rp_attribut hinzugefuegt!<br>';
}



/************************************  01.16 tbl_rp_attribut_zuweisungen  ************************************/
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_rp_attribut_zuweisungen"))
{
	$qry = "

	CREATE SEQUENCE addon.tbl_rp_attribut_zuweisungen_rp_attribut_zuweisungen_id_seq
		INCREMENT BY 1
		NO MAXVALUE
		NO MINVALUE
		CACHE 1;

	CREATE TABLE addon.tbl_rp_attribut_zuweisungen
	(
		rp_attribut_zuweisungen_id integer NOT NULL DEFAULT nextval('addon.tbl_rp_attribut_zuweisungen_rp_attribut_zuweisungen_id_seq'),
		attribut_id integer NOT NULL,
		view_id integer NOT NULL,
		insertamum timestamp DEFAULT now(),
		insertvon varchar(32),
		updateamum timestamp DEFAULT now(),
		updatevon varchar(32),
		CONSTRAINT pk_rp_attribut_zuweisungen PRIMARY KEY (rp_attribut_zuweisungen_id)
	);


	ALTER TABLE addon.tbl_rp_attribut_zuweisungen ADD CONSTRAINT \"fk_rp_attribut_zuweisungen_attribut\" FOREIGN KEY (attribut_id)
	REFERENCES addon.tbl_rp_attribut(attribut_id) ON UPDATE CASCADE ON DELETE RESTRICT;

	ALTER TABLE addon.tbl_rp_attribut_zuweisungen ADD CONSTRAINT \"fk_rp_attribut_zuweisungen_view\" FOREIGN KEY (view_id)
	REFERENCES addon.tbl_rp_view(view_id) ON UPDATE CASCADE ON DELETE RESTRICT;

	GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_rp_attribut_zuweisungen TO vilesci;
	GRANT SELECT, UPDATE ON addon.tbl_rp_attribut_zuweisungen_rp_attribut_zuweisungen_id_seq TO vilesci;
	";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_rp_attribut_zuweisungen: '.$db->db_last_error().'</strong><br>';
	else
		echo ' addon.tbl_rp_attribut_zuweisungen: Tabelle addon.tbl_rp_attribut_zuweisungen hinzugefuegt!<br>';
}

/************************************  02.16 highcharts typen ************************************/
if($result = @$db->db_query("SELECT * FROM addon.tbl_rp_chart WHERE type!='hcnorm' AND type!='hcdrill'"))
{
	if($db->db_num_rows($result)!=0)
	{
		if($result = @$db->db_query("SELECT * FROM addon.tbl_rp_chart WHERE type!='hcnorm' OR type is NULL"))
		{
			while($row = $db->db_fetch_object($result))
			{
				$c = new chart();
				$prefBuf = $c->removeCommentsFromJson($row->preferences);
				$prefsArray = json_decode($prefBuf);

				if($prefsArray)
				{
					if(!isset($prefsArray->chart))
						$prefsArray->chart = new stdClass();
					if(!isset($prefsArray->chart->type))
						$prefsArray->chart->type = "line";		//if no type is set

				}
				else
				{
					$prefsArray = new stdClass();
					if(!isset($prefsArray->chart))
						$prefsArray->chart = new stdClass();

					switch($row->type)
					{//we use the type from the db
						case "hcline":
							$prefsArray->chart->type = "line";
							break;
						case "hccolumn":
							$prefsArray->chart->type = "column";
							break;
						case "hcbar":
							$prefsArray->chart->type = "bar";
							break;
						case "hcpie":
							$prefsArray->chart->type = "pie";
							break;
						case "hcdrill":
							$prefsArray->chart->type = "column";
							break;
						default:
							ob_start();
							var_dump($row->type);
							$output = ob_get_clean();
							echo "<span style='float:left;'>unknown type: </span><span style='float:left;'>" . $output . "</span><div style='clear:both'></div>";
							$prefsArray->chart->type = "line";
					}
				}


				$newPrefs = json_encode($prefsArray);
				if($row->preferences != "")
					$newPrefs .= "\n/*\n//OLD PREFERENCES:\n".$row->preferences."\n*/";

				if($row->type != "hcnorm" && $row->type != "hcdrill")
				{
					$row->type = "hcnorm";
				}


				$updQry = 'UPDATE addon.tbl_rp_chart SET'.
						' preferences='.$db->db_add_param($newPrefs).', '.
						' type='.$db->db_add_param($row->type).', '.
						' updateamum= now(), '.
						' updatevon='.$db->db_add_param("DBCHECK").
							' WHERE chart_id='.$db->db_add_param($row->chart_id, FHC_INTEGER, false).';';

				if (!@$db->db_query($updQry))
					echo '<strong>addon.tbl_rp_chart: '.$db->db_last_error().'</strong><br>';
				else
					echo ' addon.tbl_rp_chart: Chart '.$row->chart_id.' angepasst<br>';

			}
		}
	}
}


/************************************  02.16 highcharts pdf-export Vorlage ************************************/
if($result = $db->db_query("SELECT * FROM public.tbl_vorlage WHERE vorlage_kurzbz='HCPDFExport'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry_oe = "SELECT oe_kurzbz FROM public.tbl_organisationseinheit WHERE oe_parent_kurzbz is null";
		if($result = $db->db_query($qry_oe))
		{
			$qry = "INSERT INTO public.tbl_vorlage(vorlage_kurzbz, bezeichnung, anmerkung,mimetype)
			VALUES('HCPDFExport','HighCharts Pdf Export', 'wird für den PDF-Export der Charts verwendet', 'application/vnd.oasis.opendocument.text');";

			$text = file_get_contents('system/xsl/HCPDFExport.xml');

			while($row = $db->db_fetch_object($result))
			{
				$qry.="INSERT INTO public.tbl_vorlagestudiengang(vorlage_kurzbz, studiengang_kz, version, text,
				oe_kurzbz, style, berechtigung, anmerkung_vorlagestudiengang, aktiv) VALUES(
				'HCPDFExport',0,0,".$db->db_add_param($text).",".$db->db_add_param($row->oe_kurzbz).",null,null,'',true);";
			}
		}

		if(!$db->db_query($qry))
			echo '<strong>HCPDFExport Dokumentenvorlage: '.$db->db_last_error().'</strong><br>';
		else
			echo 'HCPDFExport Dokumentenvorlage hinzugefuegt<br>';
	}
}


echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';

// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_rp_chart"  => array("chart_id", "title", "longtitle", "description", "type", "preferences", "datasource", "datasource_type", "insertamum", "insertvon", "updateamum", "updatevon", "statistik_kurzbz")
	,"addon.tbl_rp_view"  => array("view_kurzbz", "table_kurzbz", "sql", "static", "lastcopy", "insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_report" => array("report_id", "title", "format", "description", "header", "footer", "body", "docinfo", "gruppe", "publish", "insertamum", "insertvon", "updateamum", "updatevon", "berechtigung_kurzbz")
	,"addon.tbl_rp_report_chart" => array("reportchart_id","report_id","chart_id","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_report_statistik" => array("reportstatistik_id","report_id","statistik_kurzbz","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_gruppe" => array("reportgruppe_id","bezeichnung","reportgruppe_parent_id","sortorder","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_gruppenzuordnung" => array("gruppenzuordnung_id","reportgruppe_id","chart_id","report_id","statistik_kurzbz","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_attribut" => array("attribut_id","shorttitle","middletitle","longtitle","description","insertamum","insertvon","updateamum","updatevon")
	,"addon.tbl_rp_attribut_zuweisungen" => array("rp_attribut_zuweisungen_id","attribut_id","view_id","insertamum","insertvon","updateamum","updatevon")
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
