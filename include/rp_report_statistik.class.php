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

class rp_report_statistik extends basis_db
{
	public $result = array();

	//Tabellenspalten
	public $reportstatistik_id;
	public $report_id;
	public $statistik_kurzbz;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

	/**
	 * Konstruktor
	 * @param $reportstatistik_id ID der report_statistik, welche geladen werden soll (Default=null)
	 */
	public function __construct($reportstatistik_id=null)
	{
		parent::__construct();

		if(!is_null($reportstatistik_id))
			$this->load($reportstatistik_id);
		else
			$this->new=true;
	}

	public function load($reportstatistik_id)
	{
		$this->errormsg = '';

		if(!is_numeric($reportstatistik_id))
		{
			$this->errormsg = 'reportstatistik_id ist ungueltig';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = "
			SELECT *
			FROM addon.tbl_rp_report_statistik
			WHERE reportstatistik_id=".$this->db_add_param($reportstatistik_id, FHC_INTEGER);


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->reportstatistik_id	= $row->reportstatistik_id;
			$this->report_id			    = $row->report_id;
			$this->statistik_kurzbz		= $row->statistik_kurzbz;
			$this->updateamum		      = $row->updateamum;
			$this->updatevon		      = $row->updatevon;
			$this->insertamum		      = $row->insertamum;
			$this->insertvon		      = $row->insertvon;
		}

		$this->new=false;
		return true;
	}

	public function getReportStatistiken($report_id)
	{
		$this->errormsg = '';

		if(!is_numeric($report_id))
		{
			$this->errormsg = 'report_id ist ungueltig';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = "
			SELECT *
			FROM addon.tbl_rp_report_statistik
			WHERE report_id=".$this->db_add_param($report_id, FHC_INTEGER);


		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
		else
		{
			while($row = $this->db_fetch_object())
			{
				$this->result[] = $row;
			}
		}
		$this->new=false;
		return true;
	}


	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $reportstatistik_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{

		if($this->new)
		{

			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_report_statistik (report_id, statistik_kurzbz,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->report_id, FHC_INTEGER).', '.
			      $this->db_add_param($this->statistik_kurzbz).', '.
			      'now(), '.
			      $this->db_add_param($this->insertvon).');';
		}
		else
		{
			//Pruefen ob reportstatistik_id eine gueltige Zahl ist
			if(!is_numeric($this->reportstatistik_id))
			{
				$this->errormsg = 'reportstatistik_id muss eine gueltige Zahl sein';
				return false;
			}
			$qry='UPDATE addon.tbl_rp_report_statistik SET'.
				' report_id='.$this->db_add_param($this->report_id, FHC_INTEGER).', '.
				' statistik_kurzbz='.$this->db_add_param($this->statistik_kurzbz).', '.
				' updateamum= now(), '.
		      	' updatevon='.$this->db_add_param($this->updatevon).
		      	' WHERE reportstatistik_id='.$this->db_add_param($this->reportstatistik_id, FHC_INTEGER, false).';';
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_rp_report_statistik_reportstatistik_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->reportstatistik_id = $row->id;
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
			$this->errormsg = 'Fehler beim Update des report_statistik-Datensatzes';
			return false;
		}
		return $this->reportstatistik_id;
	}



	/**
	 * Loescht einen Eintrag
	 *
	 * @param $reportstatistik_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($reportstatistik_id)
	{
		$qry = "DELETE FROM addon.tbl_rp_report_statistik WHERE reportstatistik_id=".$this->db_add_param($reportstatistik_id).";";

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
