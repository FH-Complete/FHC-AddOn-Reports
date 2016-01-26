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
 * Klasse zur Verwaltung der attribute von views
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');



class rp_attribut extends basis_db
{
	public $new;
	public $result = array();

	//Tabellenspalten
	public $attribut_id;
	public $shorttitle = array();
	public $middletitle = array();
	public $longtitle = array();
	public $description = array();

	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

	/**
	 * Konstruktor
	 * @param attribut_id des zu ladenden Datensatzes
	 */
	public function __construct($attribut_id=null)
	{
		parent::__construct();

		if(!is_null($attribut_id))
		{
			$this->load($attribut_id);
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
	public function load($attribut_id=null)
	{
		//Pruefen ob attribut_id eine gueltige Zahl ist
		if(!is_numeric($attribut_id))
		{
			$this->errormsg = 'attribut_id muss eine gueltige Zahl sein';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_attribut WHERE attribut_id='.$this->db_add_param($attribut_id, FHC_INTEGER).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->attribut_id	= $row->attribut_id;
			$this->shorttitle		= $this->db_parse_lang_array($row->shorttitle);
			$this->middletitle	= $this->db_parse_lang_array($row->middletitle);
			$this->longtitle		= $this->db_parse_lang_array($row->longtitle);
			$this->description	= $this->db_parse_lang_array($row->description);

			$this->updateamum		= $row->updateamum;
			$this->updatevon		= $row->updatevon;
			$this->insertamum		= $row->insertamum;
			$this->insertvon		= $row->insertvon;
		}
		$this->new=false;
		return true;
	}


	/**
	 * Laedt alle Attribute aus DB
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadAll()
	{

		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_attribut ORDER BY attribut_id;';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		while($row = $this->db_fetch_object())
		{
			$obj = new rp_attribut();

			$obj->attribut_id		= $row->attribut_id;
			$obj->shorttitle		= $this->db_parse_lang_array($row->shorttitle);
			$obj->middletitle		= $this->db_parse_lang_array($row->middletitle);
			$obj->longtitle			= $this->db_parse_lang_array($row->longtitle);
			$obj->description		= $this->db_parse_lang_array($row->description);

			$this->updateamum		= $row->updateamum;
			$this->updatevon		= $row->updatevon;
			$this->insertamum		= $row->insertamum;
			$this->insertvon		= $row->insertvon;

			$this->result[] = $obj;
		}
		return true;
	}


	/**
	 * Loescht einen Eintrag
	 *
	 * @param $attribut_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($attribut_id)
	{
		$qry = "BEGIN;DELETE FROM addon.tbl_rp_attribut WHERE attribut_id=".$this->db_add_param($attribut_id, FHC_INTEGER).";";

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
	 * andernfalls wird der Datensatz mit der ID in $attribut_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$qry='INSERT INTO addon.tbl_rp_attribut (shorttitle, middletitle, longtitle, description, insertamum, insertvon)
			VALUES('.
				$this->db_add_param($this->shorttitle, FHC_LANG_ARRAY).', '.
				$this->db_add_param($this->middletitle, FHC_LANG_ARRAY).', '.
				$this->db_add_param($this->longtitle, FHC_LANG_ARRAY).', '.
				$this->db_add_param($this->description, FHC_LANG_ARRAY).', '.
				'now(), '.
				$this->db_add_param($this->insertvon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_rp_attribut SET
						  shorttitle='.$this->db_add_param($this->shorttitle, FHC_LANG_ARRAY).', '.
						' middletitle='.$this->db_add_param($this->middletitle, FHC_LANG_ARRAY).', '.
						' longtitle='.$this->db_add_param($this->longtitle, FHC_LANG_ARRAY).', '.
						' description='.$this->db_add_param($this->description, FHC_LANG_ARRAY).', '.
						' updateamum= now(), '.
						' updatevon='.$this->db_add_param($this->updatevon).
							' WHERE attribut_id='.$this->db_add_param($this->attribut_id).';';

		}

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern des Attributs';
			return false;
		}
	}
}

?>
