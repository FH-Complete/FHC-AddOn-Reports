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
 *				Andreas Moik <moik@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class view extends basis_db
{
	public $new;
	public $result = array();
	public $vars='';

	//Tabellenspalten
	public $view_kurzbz;
	private $view_kurzbz_old;
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
	 * @param view_id ID des zu ladenden Datensatzes
	 */
	public function __construct($view_id=null)
	{
		parent::__construct();

		if(!is_null($view_id))
		{
			$this->load($view_id);
		}
		else
			$this->new=true;

	}

	public function load($view_id=null)
	{
		//Pruefen ob view_id eine gueltige Zahl ist
		if(!is_numeric($view_id))
		{
			$this->errormsg = 'view_id muss eine gueltige Zahl sein';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_view WHERE view_id='.$this->db_add_param($view_id, FHC_INTEGER).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->view_id	= $row->view_id;
			$this->view_kurzbz	= $row->view_kurzbz;
			$this->view_kurzbz_old	= $row->view_kurzbz;
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
	 * Laedt alle Views aus DB
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
			$obj = new view();

			$obj->view_id		= $row->view_id;
			$obj->view_kurzbz		= $row->view_kurzbz;
			$obj->table_kurzbz	= $row->table_kurzbz;
			$obj->sql						= $row->sql;
			$obj->static				= $this->db_parse_bool($row->static);
			$obj->lastcopy			= $row->lastcopy;
			$obj->updateamum		= $row->updateamum;
			$obj->updatevon			= $row->updatevon;
			$obj->insertamum		= $row->insertamum;
			$obj->insertvon			= $row->insertvon;
			$obj->new						= false;

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
		if(!$this->validate($this->view_kurzbz))
			die("Ungueltige Zeichen in der view_kurzbz!");



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
			if($this->view_kurzbz !== $this->view_kurzbz_old && $v = $this->getView())
			{
				$qryV='ALTER VIEW reports.'.$this->view_kurzbz_old.' RENAME TO '.
					$this->view_kurzbz.';';

				if(!$this->db_query($qryV))
				{
					$this->errormsg = 'Fehler bei einer Datenbankabfrage';
					return false;
				}
			}

			//Pruefen ob view_id eine gueltige Zahl ist
			if(!is_numeric($this->view_id))
			{
				$this->errormsg = 'view_id muss eine gueltige Zahl sein';
				return false;
			}

			$qry='BEGIN;UPDATE addon.tbl_rp_view SET'.
				' view_kurzbz='.$this->db_add_param($this->view_kurzbz).', '.
				' table_kurzbz='.$this->db_add_param($this->table_kurzbz).', '.
				' sql='.$this->db_add_param($this->sql).', '.
				' static='.$this->db_add_param($this->static, FHC_BOOLEAN).', '.
				' updateamum= now(), '.
				' updatevon='.$this->db_add_param($this->updatevon).
					' WHERE view_id='.$this->db_add_param($this->view_id).';';
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_rp_view_view_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->view_id = $row->id;
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
			else
			{
				if($this->getView())//wenn es schon eine generierte View gibt, wird sie neu generiert, um Inkonsistenzen zu vermeiden
				{
					if(!$this->generateView())
					{
						$this->db_query('ROLLBACK');
						$this->errormsg = 'Fehler beim neu erstellen der View';
						return false;
					}
					else
					{
						$this->db_query('COMMIT');
					}
				}
				else
				{
						$this->db_query('COMMIT');
				}

			}

		}
		else
		{
			$this->db_query('ROLLBACK');
			$this->errormsg = 'Fehler beim Update des View-Datensatzes';
			return false;
		}

		$this->view_kurzbz_old = $this->view_kurzbz;


		return $this->view_id;
	}


	public function setLastCopy($time = null)
	{
		//Pruefen ob view_id eine gueltige Zahl ist
		if(!is_numeric($this->view_id))
		{
			$this->errormsg = 'view_id muss eine gueltige Zahl sein';
			return false;
		}

		$qry='UPDATE addon.tbl_rp_view SET'.
		' lastcopy='.$this->db_add_param($time).
			' WHERE view_id='.$this->db_add_param($this->view_id).';';

		$this->db_query($qry);
		return true;
	}

	/**
	 * Loescht einen Eintrag
	 *
	 * @param $view_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($view_id)
	{
		$qry = "BEGIN;DELETE FROM addon.tbl_rp_view WHERE view_id=".$this->db_add_param($view_id, FHC_INTEGER).";";

		if($this->getView())
		{
			if(!$this->dropView())
			{
				$this->db_query("ROLLBACK");
				return false;
			}
		}


		if($this->db_query($qry))
		{
			$this->db_query("COMMIT");
			return true;
		}
		else
		{
			$this->db_query("ROLLBACK");
			$this->errormsg='Fehler beim Löschen des Eintrages';
			return false;
		}
	}

	/**
	 * Validiert einen string
	 *
	 * @param $str zu validierender string
	 * @return true wenn ok, sonst false
	 */
	private function validate($str)
	{
		return preg_match("/^[a-zA-Z_]+$/", $str);
	}

	/**
	 * Erzeugt eine View
	 *
	 * @return true wenn ok, sonst false
	 */
	public function generateView()
	{
		if($this->getView())
			$this->dropView();

		//Neuen Datensatz einfuegen
		$qry="CREATE OR REPLACE VIEW reports.".
			$this->view_kurzbz." AS ".
			$this->sql;

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim erzeugen der View';
			return false;
		}

		return true;
	}

	/**
	 * Loescht eine View
	 *
	 * @return true wenn ok, sonst false
	 */
	public function dropView()
	{

		//Neuen Datensatz einfuegen
		$qry="DROP VIEW reports.".
			$this->view_kurzbz.";";

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim erzeugen der View';
			return false;
		}
		return true;
	}

	/**
	 * Gibt eine View zurück
	 * @return View, wenn erstellt, false wenn nicht
	 */
	public function getView()
	{
		$qry = ' SELECT table_name FROM INFORMATION_SCHEMA.views WHERE table_name='.
			$this->db_add_param($this->view_kurzbz_old).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		return $this->db_fetch_object();
	}

	public function generateTable()
	{
		//nur statische
		if($this->static)
		{
			//pruefen, ob bereits vorhanden
			$qry = ' SELECT table_name FROM INFORMATION_SCHEMA.tables WHERE table_name='.
				$this->db_add_param($this->table_kurzbz).';';

			if(!$this->db_query($qry))
			{
				die('Fehler bei einer Datenbankabfrage');
			}

			//wenn bereits vorhanden, löschen
			if($this->db_fetch_object())
			{
				$qry = ' DROP TABLE reports.'.
				$this->table_kurzbz;

				if(!$this->db_query($qry))
				{
					die('Fehler bei einer Datenbankabfrage');
				}
				$this->setLastCopy(null);
			}

			if(!$this->getView())
				$this->generateView();

			//neue tabelle erzeugen
			$qry="CREATE TABLE reports.".
				$this->table_kurzbz." AS SELECT * FROM reports.".
				$this->view_kurzbz;

			if(!$this->db_query($qry))
			{
				die('Fehler bei einer Datenbankabfrage');
			}
			$this->setLastCopy('now()');
		}
		return true;
	}
}



function rp_generateAllViews()
{
	$view = new view();
	$view->loadAll();
	$db = new basis_db();
	$errors = false;

	foreach($view->result as $v)
	{
		if(!$v->generateTable())
			$errors = true;
	}

	if(!$errors)
		return true;
	return false;
}
