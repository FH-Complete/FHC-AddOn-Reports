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
/**
 * Klasse zur Verwaltung des zugriffs auf den PhantomJS Server
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');



class rp_gruppenzuordnung extends basis_db
{

	//Tabellenspalten
	public $gruppenzuordnung_id;
	public $reportgruppe_id;
	public $chart_id;
	public $report_id;
	public $statistik_kurzbz;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

	public $result = array();

	/**
	 * Konstruktor
	 * @param $gruppenzuordnung_id ID der Zuordnung, welche geladen werden soll (Default=null)
	 */
	public function __construct($gruppenzuordnung_id=null)
	{
		parent::__construct();

		if(!is_null($gruppenzuordnung_id))
			$this->load($gruppenzuordnung_id);
		else
			$this->new=true;
	}


	public function load($gruppenzuordnung_id)
	{
		$this->errormsg = '';

		if(!is_numeric($gruppenzuordnung_id))
		{
			$this->errormsg = 'gruppenzuordnung_id ist ungueltig';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = "
			SELECT *
			FROM addon.tbl_rp_gruppenzuordnung
			WHERE gruppenzuordnung_id=".$this->db_add_param($gruppenzuordnung_id, FHC_INTEGER);


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->gruppenzuordnung_id= $row->gruppenzuordnung_id;
			$this->reportgruppe_id	  = $row->reportgruppe_id;
			$this->chart_id		        = $row->chart_id;
			$this->report_id		      = $row->report_id;
			$this->statistik_kurzbz		= $row->statistik_kurzbz;
			$this->updateamum		      = $row->updateamum;
			$this->updatevon		      = $row->updatevon;
			$this->insertamum		      = $row->insertamum;
			$this->insertvon		      = $row->insertvon;
		}

		$this->new=false;
		return true;
	}

	/**
	 * Lädt alle Gruppenzuordnungen einer Reportgruppe
	 * @param $reportgruppe_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadByGruppe($reportgruppe_id)
	{
		$this->errormsg = '';

		if(!is_numeric($reportgruppe_id))
		{
			$this->errormsg = 'reportgruppe_id ist ungueltig';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = "
			SELECT *
			FROM addon.tbl_rp_gruppenzuordnung
			WHERE reportgruppe_id=".$this->db_add_param($reportgruppe_id, FHC_INTEGER);


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


	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $gruppenzuordnung_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_gruppenzuordnung (reportgruppe_id, chart_id, report_id, statistik_kurzbz,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->reportgruppe_id, FHC_INTEGER).', '.
			      $this->db_add_param($this->chart_id, FHC_INTEGER).', '.
			      $this->db_add_param($this->report_id, FHC_INTEGER).', '.
			      $this->db_add_param($this->statistik_kurzbz).', '.
			      'now(), '.
			      $this->db_add_param($this->insertvon).');';
		}
		else
		{
			//Pruefen ob reportchart_id eine gueltige Zahl ist
			if(!is_numeric($this->gruppenzuordnung_id))
			{
				$this->errormsg = 'gruppenzuordnung_id muss eine gueltige Zahl sein';
				return false;
			}
			$qry='UPDATE addon.tbl_rp_gruppenzuordnung SET'.
				' gruppenzuordnung_id='.$this->db_add_param($this->gruppenzuordnung_id, FHC_INTEGER).', '.
				' reportgruppe_id='.$this->db_add_param($this->reportgruppe_id, FHC_INTEGER).', '.
				' chart_id='.$this->db_add_param($this->chart_id, FHC_INTEGER).', '.
				' report_id='.$this->db_add_param($this->report_id, FHC_INTEGER).', '.
				' statistik_kurzbz='.$this->db_add_param($this->statistik_kurzbz).', '.
				' updateamum= now(), '.
		      	' updatevon='.$this->db_add_param($this->updatevon).
		      	' WHERE gruppenzuordnung_id='.$this->db_add_param($this->gruppenzuordnung_id, FHC_INTEGER, false).';';
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.seq_rp_gruppenzuordnung_gruppenzuordnung_id') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->gruppenzuordnung_id = $row->id;
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
			$this->errormsg = 'Fehler beim Update des gruppenzuordnung_id-Datensatzes';
			return false;
		}
		return $this->gruppenzuordnung_id;
	}

	/**
	 * Loescht einen Eintrag
	 *
	 * @param $gruppenzuordnung_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($gruppenzuordnung_id)
	{
		$qry = "DELETE FROM addon.tbl_rp_gruppenzuordnung WHERE gruppenzuordnung_id=".$this->db_add_param($gruppenzuordnung_id).";";

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

	/**
	 * Gibt die anzahl der Zuordnungen zurück
	 *
	 * @param $reportgruppe_id
	 * @return anzahl der Zuordnungen
	 */
	public function zuordnungCount($reportgruppe_id)
	{
		//reportgruppe_id auf gueltigkeit pruefen
		if(!is_numeric($reportgruppe_id) || $reportgruppe_id == '')
		{
			$this->errormsg = 'reportgruppe_id must be a number!';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = '
				SELECT count(1)
				FROM addon.tbl_rp_gruppenzuordnung
				WHERE reportgruppe_id='.$reportgruppe_id.';';


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}

		if($ret = $this->db_fetch_object())
		{
			return intval($ret->count);
		}

		$this->errormsg = 'Fehler beim Laden der Daten';
		return false;
	}
}

?>
