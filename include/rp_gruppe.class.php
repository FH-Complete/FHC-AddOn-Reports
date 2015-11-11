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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');


class rp_gruppe extends basis_db
{
	public $bezeichnung;
	public $reportgruppe_id;
	public $reportgruppe_parent_id;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;
	public $new;
	public $errormsg;

	public $result = array();
	public $gruppe = array();
	public $recursive = array();

	public function __construct($reportgruppe_id=null)
	{
		parent::__construct();

		$this->bezeichnung = "";
		$this->reportgruppe_id = "";
		$this->reportgruppe_parent_id = "";

		if(!is_null($reportgruppe_id))
			$this->load($reportgruppe_id);
		else
			$this->new=true;
	}


	public function load($reportgruppe_id)
	{
		//reportgruppe_id auf gueltigkeit pruefen
		if(!is_numeric($reportgruppe_id) || $reportgruppe_id == '')
		{
			$this->errormsg = 'reportgruppe_id must be a number!';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_gruppe WHERE reportgruppe_id='.$reportgruppe_id.';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->reportgruppe_id			    = $row->reportgruppe_id;
			$this->bezeichnung			        = $row->bezeichnung;
			$this->reportgruppe_parent_id		= $row->reportgruppe_parent_id;
			$this->updateamum		= $row->updateamum;
			$this->updatevon		= $row->updatevon;
			$this->insertamum		= $row->insertamum;
			$this->insertvon		= $row->insertvon;
		}
		$this->new=false;
		return true;
	}




	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $reportgruppe_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{

		if($this->new)
		{

			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_gruppe (bezeichnung, reportgruppe_parent_id,
					  insertamum, insertvon) VALUES('.
					  $this->db_add_param($this->bezeichnung).', '.
					  $this->db_add_param($this->reportgruppe_parent_id, FHC_INTEGER).', '.
					  'now(), '.
					  $this->db_add_param($this->insertvon).');';
		}
		else
		{
			//Pruefen ob reportgruppe_id eine gueltige Zahl ist
			if(!is_numeric($this->reportgruppe_id))
			{
				$this->errormsg = 'reportgruppe_id muss eine gueltige Zahl sein';
				return false;
			}
			$qry='UPDATE addon.tbl_rp_gruppe SET'.
				' bezeichnung='.$this->db_add_param($this->bezeichnung).', '.
				' reportgruppe_parent_id='.$this->db_add_param($this->reportgruppe_parent_id, FHC_INTEGER).', '.
				' updateamum= now(), '.
      	' updatevon='.$this->db_add_param($this->updatevon).
      	' WHERE reportgruppe_id='.$this->db_add_param($this->reportgruppe_id, FHC_INTEGER, false).';';
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_rp_gruppe_reportgruppe_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->reportgruppe_id = $row->id;
						$this->db_query('COMMIT');
					}
					else
					{
						$this->db_query('ROLLBACK');
						$this->errormsg = "Fehler beim Auslesen der Sequence";
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}

		}
		else
		{
			$this->errormsg = 'Fehler beim Update des reportgruppe_id-Datensatzes';
			return false;
		}
		return $this->reportgruppe_id;
	}

	/**
	 * Loescht einen Eintrag
	 *
	 * @param $reportgruppe_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($reportgruppe_id)
	{
		$qry = "DELETE FROM addon.tbl_rp_gruppe WHERE reportgruppe_id=".$this->db_add_param($reportgruppe_id).";";

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

	public function loadAll()
	{
		//Lesen der Daten aus der Datenbank
		$qry = "
				SELECT *
				FROM addon.tbl_rp_gruppe AS gr
				ORDER BY gr.reportgruppe_parent_id desc;
			";


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$this->result[] = $row;
		}

		return true;
	}


	public function getGruppenzuordnung($reportgruppe_id)
	{
		$this->gruppe = array();		//eventuelle alte einträge löschen

		$qry = "
			SELECT *
				FROM addon.tbl_rp_gruppenzuordnung
				WHERE reportgruppe_id = " . $this->db_add_param($reportgruppe_id) . ";";


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$this->gruppe[] = $row;
		}
		return true;
	}

	public function loadRecursive()
	{
		$buf = $this->result;

		for($i = 0; $i < count($this->result); $i++)
		{
			if(!is_null($this->result[$i]->reportgruppe_parent_id))
			{
				$found = false;
				foreach($buf as $ent)
				{
					$ent->text = $ent->bezeichnung;
					if($buf[$i]->reportgruppe_parent_id === $ent->reportgruppe_id)
					{
						$found = true;

						if(!isset($ent->children))
							$ent->children = array();

						$ent->children[] = $buf[$i];
					}
				}
				if($found)
				{
					unset($buf[$i]);
				}
			}
			$this->recursive = $buf;
		}
		return true;
	}

	public function getJson($type)
	{
		if($type === "jstree")
			return json_encode($this->toEasyUiArray($this->recursive));
		else
			return json_encode($this->toEasyUiArray($this->recursive));
	}
}


