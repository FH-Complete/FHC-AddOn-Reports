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

class report extends basis_db
{
	public $new;
	public $result = array();
	public $vars='';
	
	//Tabellenspalten
	public $report_id;
	public $title;
	public $format;
	public $description;
	public $body;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;
	
	/**
	 * Konstruktor
	 * @param akadgrad_id ID des zu ladenden Datensatzes
	 */
	public function __construct($report_id=null)
	{
		parent::__construct();

		if(!is_null($report_id))
			$this->load($report_id);
		else
			$this->new=true;
	}
	
	public function load($report_id)
	{
		//report_id auf gueltigkeit pruefen
		if(!is_numeric($report_id) || $report_id == '')
		{
			$this->errormsg = 'report_id must be a number!';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_report WHERE report_id='.$report_id.';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->report_id	= $row->report_id; 
			$this->title 		= $row->title;
			$this->description	= $row->description;
			$this->format		= $row->format;
			$this->body			= $row->body;
			$this->updateamum    = $row->updateamum;
			$this->updatevon     = $row->updatevon;
			$this->insertamum    = $row->insertamum;
			$this->insertvon     = $row->insertvon;
		}
		/*switch ($report_id)
		{
			case 1:
				$this->title='DropOut - Spidergraph';
				$this->type='spider';
				$this->datasource_type='json';
				$this->datasource='../../../content/statistik/dropout.php?outputformat=json';
				break;
			case 2:
				$this->title='DropOut - xChart';
				$this->type='xchart';
				$this->sourcetype='json';
				$this->datasource='../../../content/statistik/dropout.php?outputformat=json';
		}*/
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
		$qry = 'SELECT * FROM addon.tbl_rp_report ORDER BY title;';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$obj = new report();

			$obj->report_id		= $row->report_id; 
			$obj->title 		= $row->title;
			$obj->description	= $row->description;
			$obj->format		= $row->format;
			$obj->body			= $row->body;
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
	 * andernfalls wird der Datensatz mit der ID in $report_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_report (title, description, format, body,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->title).', '.
			      $this->db_add_param($this->description).', '.
			      $this->db_add_param($this->format).', '.
			      $this->db_add_param($this->body).', now(), '.
			      $this->db_add_param($this->insertvon).');';
		}
		else
		{
			//Pruefen ob report_id eine gueltige Zahl ist
			if(!is_numeric($this->report_id))
			{
				$this->errormsg = 'report_id muss eine gueltige Zahl sein';
				return false;
			}
			$qry='UPDATE addon.tbl_rp_report SET'.
				' title='.$this->db_add_param($this->title).', '.
				' description='.$this->db_add_param($this->description).', '.
				' format='.$this->db_add_param($this->format).', '.
				' body='.$this->db_add_param($this->body).', '.
				' updateamum= now(), '.
		      	' updatevon='.$this->db_add_param($this->updatevon).
		      	' WHERE report_id='.$this->db_add_param($this->report_id, FHC_INTEGER, false).';';
		}
        //echo $qry;
		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_rp_report_report_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->report_id = $row->id;
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
			$this->errormsg = 'Fehler beim Update des Chart-Datensatzes';
			return false;
		}
		return $this->report_id;
	}
	
	public function print_htmlhead()
	{
		echo '<script src="../include/js/jquery1.7.1.min.js" type="application/javascript"></script>';
		switch ($this->type)
		{
			case 'spider':
				echo '<script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>';
				echo '<link rel="stylesheet" href="../include/css/spider.css" type="text/css">';
				break;
			case 'xchart':
				echo '<script src="../include/js/d3.min.js" type="application/javascript"></script>';
				echo '<script src="../include/js/xcharts/xcharts.min.js" type="application/javascript"></script>';
				echo '<link rel="stylesheet" href="../include/css/xchart.css" type="text/css">';
		}
	}
	public function print_htmldiv()
	{
		switch ($this->type)
		{
			case 'spider':
				echo '<div id="spidergraphcontainer"></div>';
				echo '<script type="application/javascript">
						var source="'.$this->datasource.$this->vars.'";
						'.$this->preferences.'
						</script>
						';
				echo '<script src="../include/js/spidergraph.js" type="application/javascript"></script>';
				break;
			case 'xchart':
				echo '<figure id="xChart1"></figure>';
				echo '<script type="application/javascript">
						var source="'.$this->datasource.$this->vars.'";
						</script>
						';
				if ($this->chart_id==2)
					echo '<script src="../include/js/xchart2.js" type="application/javascript"></script>';
				else
					echo '<script src="../include/js/xchart.js" type="application/javascript"></script>';
		}
	}
}
