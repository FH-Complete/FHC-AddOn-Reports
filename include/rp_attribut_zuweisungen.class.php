<?php
/* Copyright (C) 2016 fhcomplete.org
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
 * Klasse zur Verwaltung der attributszuweisungen von views
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');



class rp_attribut_zuweisungen extends basis_db
{
	public $new;
	public $result = array();

	//Tabellenspalten
	public $rp_attribut_zuweisungen_id;
	public $attribut_id;
	public $view_id;

	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

	/**
	 * Konstruktor
	 * @param rp_attribut_zuweisungen_id des zu ladenden Datensatzes
	 */
	public function __construct($rp_attribut_zuweisungen_id=null)
	{
		parent::__construct();

		if(!is_null($rp_attribut_zuweisungen_id))
		{
			$this->load($rp_attribut_zuweisungen_id);
		}
		else
		{
			$this->new=true;
		}
	}

	/**
	 * Laedt ein Attribut aus DB
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($rp_attribut_zuweisungen_id=null)
	{
		//Pruefen ob rp_attribut_zuweisungen_id eine gueltige Zahl ist
		if(!is_numeric($rp_attribut_zuweisungen_id))
		{
			$this->errormsg = 'rp_attribut_zuweisungen_id muss eine gueltige Zahl sein';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_attribut_zuweisungen WHERE rp_attribut_zuweisungen_id='.$this->db_add_param($rp_attribut_zuweisungen_id, FHC_INTEGER).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->rp_attribut_zuweisungen_id	= $row->rp_attribut_zuweisungen_id;
			$this->attribut_id	= $row->attribut_id;
			$this->view_id	= $row->view_id;

			$this->updateamum		= $row->updateamum;
			$this->updatevon		= $row->updatevon;
			$this->insertamum		= $row->insertamum;
			$this->insertvon		= $row->insertvon;
		}
		$this->new=false;
		return true;
	}

	/**
	 * Laedt ein Attribut aus DB
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadAllFromView($view_id)
	{
		//Pruefen ob view_id eine gueltige Zahl ist
		if(!is_numeric($view_id))
		{
			$this->errormsg = 'view_id muss eine gueltige Zahl sein';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_attribut_zuweisungen WHERE view_id='.$this->db_add_param($view_id, FHC_INTEGER).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		$buf = array();

		while($row = $this->db_fetch_object())
		{
			$obj = new rp_attribut_zuweisungen();
			$obj->rp_attribut_zuweisungen_id	= $row->rp_attribut_zuweisungen_id;
			$obj->attribut_id	= $row->attribut_id;
			$obj->view_id	= $row->view_id;

			$obj->updateamum		= $row->updateamum;
			$obj->updatevon		= $row->updatevon;
			$obj->insertamum		= $row->insertamum;
			$obj->insertvon		= $row->insertvon;
			$buf[] = $obj;
		}

		$this->result = $buf;

		return true;
	}




	/**
	 * Loescht einen Eintrag
	 *
	 * @param $rp_attribut_zuweisungen_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($rp_attribut_zuweisungen_id)
	{
		$qry = "BEGIN;DELETE FROM addon.tbl_rp_attribut_zuweisungen WHERE rp_attribut_zuweisungen_id=".$this->db_add_param($rp_attribut_zuweisungen_id, FHC_INTEGER).";";

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
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $rp_attribut_zuweisungen_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$qry='INSERT INTO addon.tbl_rp_attribut_zuweisungen (attribut_id, view_id, insertamum, insertvon)
			VALUES('.
				$this->db_add_param($this->attribut_id, FHC_INTEGER).', '.
				$this->db_add_param($this->view_id, FHC_INTEGER).', '.
				'now(), '.
				$this->db_add_param($this->insertvon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_rp_attribut_zuweisungen SET
						  attribut_id='.$this->db_add_param($this->attribut_id, FHC_INTEGER).', '.
						' view_id='.$this->db_add_param($this->view_id, FHC_INTEGER).', '.
						' updateamum= now(), '.
						' updatevon='.$this->db_add_param($this->updatevon).
							' WHERE rp_attribut_zuweisungen_id='.$this->db_add_param($this->rp_attribut_zuweisungen_id).';';

		}

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Attributszuweisung';
			return false;
		}
	}
}

?>
