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
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class view extends basis_db
{
	public $new;
	public $result = array();
	public $vars='';

	//Tabellenspalten
	public $view_kurzbz;
	public $table_kurzbz;
	public $sql;
	public $static=false;
	public $lastcopy;

	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

	/**
	 * Konstruktor
	 * @param akadgrad_id ID des zu ladenden Datensatzes
	 */
	public function __construct($view_kurzbz=null)
	{
		parent::__construct();

		if(!is_null($view_kurzbz))
			$this->load($view_kurzbz);
		else
			$this->new=true;
	}

	public function load($view_kurzbz='')
	{
		//view_kurzbz auf gueltigkeit pruefen
		if($view_kurzbz == '')
		{
			$this->errormsg = 'view_kurzbz must be set!';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_view WHERE view_kurzbz='.$this->db_add_param($view_kurzbz, FHC_STRING).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->view_kurzbz	= $row->view_kurzbz;
			$this->table_kurzbz	= $row->table_kurzbz;
			$this->sql			= $row->sql;
			$this->static		= $this->db_parse_bool($row->static);
			$this->lastcopy		= $row->lastcopy;
			$this->updateamum    = $row->updateamum;
			$this->updatevon     = $row->updatevon;
			$this->insertamum    = $row->insertamum;
			$this->insertvon     = $row->insertvon;
		}
		$this->new=false;
		return true;
	}
	/**
	 * Laedt alle Charts aus DB
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadAll()
	{

		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_view ORDER BY view_kurzbz;';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		while($row = $this->db_fetch_object())
		{
			//var_dump($row);
			$obj = new view();

			$obj->view_kurzbz	= $row->view_kurzbz;
			$obj->table_kurzbz 	= $row->table_kurzbz;
			$obj->sql			= $row->sql;
			$obj->static		= $this->db_parse_bool($row->static);
			$obj->lastcopy		= $row->lastcopy;
			$obj->updateamum    = $row->updateamum;
			$obj->updatevon     = $row->updatevon;
			$obj->insertamum    = $row->insertamum;
			$obj->insertvon     = $row->insertvon;
			//$obj->report_num_rows= $this->getNumRows('sync.'.$row->report_tablename);
			$obj->new       = false;

			$this->result[] = $obj;
		}
		return true;
	}


	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $view_kurzbz aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_view (view_kurzbz, table_kurzbz, sql, static,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->view_kurzbz).', '.
			      $this->db_add_param($this->table_kurzbz).', '.
			      $this->db_add_param($this->sql).', '.
			      $this->db_add_param($this->static,FHC_BOOLEAN).', now(), '.
			      $this->db_add_param($this->insertvon).');';
		}
		else
		{
			$qry='UPDATE addon.tbl_rp_view SET'.
				' view_kurzbz='.$this->db_add_param($this->view_kurzbz).', '.
				' table_kurzbz='.$this->db_add_param($this->table_kurzbz).', '.
				' sql='.$this->db_add_param($this->sql).', '.
				' static='.$this->db_add_param($this->static, FHC_BOOLEAN).', '.
				' updateamum= now(), '.
		      	' updatevon='.$this->db_add_param($this->updatevon).
		      	' WHERE view_kurzbz='.$this->db_add_param($this->view_kurzbz, FHC_STRING, false).';';
		}
        //echo $qry;
		if($this->db_query($qry))
		{
			$this->db_query('COMMIT');
		}
		else
		{
			$this->db_query('ROLLBACK');
			$this->errormsg = 'Fehler beim Update des Chart-Datensatzes';
			return false;
		}
		return $this->view_kurzbz;
	}


	/**
	 * Loescht einen Eintrag
	 *
	 * @param $view_kurzbz
	 * @return true wenn ok, sonst false
	 */
	public function delete($view_kurzbz)
	{
		$qry = "DELETE FROM addon.tbl_rp_view WHERE view_kurzbz=".$this->db_add_param($view_kurzbz, FHC_STRING).";";

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg='Fehler beim Löschen des Eintrages';
			return false;
		}
	}
}
