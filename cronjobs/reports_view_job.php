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

require_once(dirname(__FILE__).'/../../../config/vilesci.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/globals.inc.php');
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../include/view.class.php');


$view = new view();
$view->loadAll();
$db = new basis_db();

foreach($view->result as $v)
{
	//nur statische
	if($v->static)
	{

		//pruefen, ob bereits vorhanden
		$qry = ' SELECT table_name FROM INFORMATION_SCHEMA.tables WHERE table_name='.
			$db->db_add_param($v->table_kurzbz).';';

		if(!$db->db_query($qry))
		{
			die('Fehler bei einer Datenbankabfrage');
		}

		//wenn bereits vorhanden, löschen
		if($db->db_fetch_object())
		{
			$qry = ' DROP TABLE reports.'.
			$v->table_kurzbz;

			if(!$db->db_query($qry))
			{
				die('Fehler bei einer Datenbankabfrage');
			}
			$v->setLastCopy(null);
		}

		//neue tabelle erzeugen
		$qry="CREATE TABLE reports.".
			$v->table_kurzbz." AS ".
			$v->sql;

		if(!$db->db_query($qry))
		{
			die('Fehler bei einer Datenbankabfrage');
		}
		$v->setLastCopy('now()');
	}
}


?>
