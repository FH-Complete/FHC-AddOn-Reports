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
	public $result = array();
	public $gruppe = array();
	
	
	/**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->load();
	}
	
	public function load()
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
}
