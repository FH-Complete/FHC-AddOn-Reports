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
	public $header;
	public $body;
	public $footer;
	public $docinfo; //xml

	public $gruppe;
	public $publish=false;
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
		$qry = 'SELECT * FROM addon.tbl_rp_report WHERE report_id='.$this->db_add_param($report_id, FHC_INTEGER).';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->report_id	= $row->report_id;
			$this->title 		= $row->title;
			$this->format		= $row->format;
			$this->description	= $row->description;
			$this->header		= $row->header;
			$this->body			= $row->body;
			$this->footer		= $row->footer;
			$this->docinfo		= $row->docinfo;
			$this->gruppe		= $row->gruppe;
			$this->publish		= $this->db_parse_bool($row->publish);
			$this->updateamum    = $row->updateamum;
			$this->updatevon     = $row->updatevon;
			$this->insertamum    = $row->insertamum;
			$this->insertvon     = $row->insertvon;
			$this->berechtigung_kurzbz     = $row->berechtigung_kurzbz;
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
		$qry = 'SELECT * FROM addon.tbl_rp_report ORDER BY title;';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		while($row = $this->db_fetch_object())
		{
			//var_dump($row);
			$obj = new report();

			$obj->report_id		= $row->report_id;
			$obj->title 		= $row->title;
			$obj->description	= $row->description;
			$obj->format		= $row->format;
			$obj->body			= $row->body;
			$obj->gruppe		= $row->gruppe;
			$obj->publish			= $this->db_parse_bool($row->publish);
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
	 * Laedt alle Reports einer Gruppe, Parameter publish zum Filtern.
	 * @return true wenn ok, sonst false
	 */
	public function getGruppe($gruppe,$publish=null)
	{
		$qry = "SELECT tbl_rp_report.* FROM addon.tbl_rp_report WHERE gruppe=".$this->db_add_param($gruppe);
		if ($publish==true)
			$qry.=' AND tbl_rp_report.publish ';
		elseif ($publish==false)
			$qry.=' AND NOT tbl_rp_report.publish ';
		$qry.=' ORDER BY title;';

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new report();

				$obj->report_id		= $row->report_id;
				$obj->title 		= $row->title;
				$obj->description	= $row->description;
				$obj->format		= $row->format;
				$obj->body			= $row->body;
				$obj->gruppe		= $row->gruppe;
				$obj->publish		= $this->db_parse_bool($row->publish);
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
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt alle Statistik Gruppen, Parameter publish zum Filtern.
	 * @return true wenn ok, sonst false
	 */
	public function getAnzahlGruppe($publish = null)
	{
		$qry = 'SELECT gruppe, count(*) AS anzahl FROM addon.tbl_rp_report ';

		if($publish === true)
		{
			$qry .= 'WHERE tbl_rp_report.publish ';
		}
		elseif($publish === false)
		{
			$qry .= 'WHERE NOT tbl_rp_report.publish ';
		}

		$qry .= ' GROUP BY gruppe ORDER BY gruppe;';

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new statistik();

				$obj->gruppe = $row->gruppe;
				$obj->anzahl = $row->anzahl;

				$this->result[] = $obj;
			}

			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
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
			$qry='BEGIN;INSERT INTO addon.tbl_rp_report (title, description, berechtigung_kurzbz, format, header, body, footer, docinfo, gruppe, publish,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->title).', '.
			      $this->db_add_param($this->description).', '.
			      $this->db_add_param($this->berechtigung_kurzbz).', '.
			      $this->db_add_param($this->format).', '.
			      $this->db_add_param($this->header).', '.
			      $this->db_add_param($this->body).', '.
			      $this->db_add_param($this->footer).', '.
			      $this->db_add_param($this->docinfo).', '.
			      $this->db_add_param($this->gruppe).', '.
			      $this->db_add_param($this->publish,FHC_BOOLEAN).', now(), '.
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
				' header='.$this->db_add_param($this->header).', '.
				' body='.$this->db_add_param($this->body).', '.
				' footer='.$this->db_add_param($this->footer).', '.
				' docinfo='.$this->db_add_param($this->docinfo).', '.
				' gruppe='.$this->db_add_param($this->gruppe).', '.
				' publish='.$this->db_add_param($this->publish, FHC_BOOLEAN).', '.
				' berechtigung_kurzbz='.$this->db_add_param($this->berechtigung_kurzbz).', '.
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

	public function printParam($type,$crlf)
	{
		$return='';
		switch ($this->format)
		{
			case 'asciidoc':
				foreach ($_REQUEST AS $key=>$val)
				{
					switch($type)
					{
						case 'param':
							$return.='- '.$key.' = *'.$val.'*'.$crlf;
							break;
						case 'attr':
							$return.=':'.$key.': '.$val.$crlf;
					}
				}
				return $return;

		}
	}

	/**
	 * Loescht einen Eintrag
	 *
	 * @param $report_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($report_id)
	{
		$qry = "DELETE FROM addon.tbl_rp_report WHERE report_id=".$this->db_add_param($report_id).";";

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
	 * Laedt alle Reports
	 * @return true wenn ok, sonst false
	 */
	public function getAll($order = FALSE)
	{
		$qry = 'SELECT * FROM addon.tbl_rp_report';

		if($order)
			$qry .= ' ORDER BY ' . $order;

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new report();

				$obj->report_id = $row->report_id;
				$obj->title = $row->title;
				$obj->format = $row->format;
				$obj->description = $row->description;
				$obj->body = $row->body;
				$obj->publish = $this->db_parse_bool($row->publish);
				$obj->gruppe = $row->gruppe;
				$obj->header = $row->header;
				$obj->footer = $row->footer;
				$obj->docinfo = $row->docinfo;
				$obj->berechtigung_kurzbz = $row->berechtigung_kurzbz;

				$this->result[] = $obj;
			}

			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}
}
