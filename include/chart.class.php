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
 *			Robert Hofer <robert.hofer@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class chart extends basis_db
{
	public $new;
	public $result = array();
	public $vars='';
	
	//Tabellenspalten
	public $chart_id;
	public $title;
	public $description;
	public $type;
	public $sourcetype;
	public $preferences;
	public $datasource;
	public $datasource_type;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;
	public $publish;
	public $statistik_kurzbz;
	
	/**
	 * Konstruktor
	 * @param akadgrad_id ID des zu ladenden Datensatzes
	 */
	public function __construct($chart_id=null)
	{
		parent::__construct();

		if(!is_null($chart_id))
			$this->load($chart_id);
		else
			$this->new=true;
	}
	
	public function load($chart_id)
	{
		//chart_id auf gueltigkeit pruefen
		if(!is_numeric($chart_id) || $chart_id == '')
		{
			$this->errormsg = 'chart_id must be a number!';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT * FROM addon.tbl_rp_chart WHERE chart_id='.$chart_id.';';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->chart_id			= $row->chart_id;
			$this->title			= $row->title;
			$this->description		= $row->description;
			$this->type				= $row->type;
			$this->sourcetype		= $row->sourcetype;
			$this->preferences		= $row->preferences;
			$this->datasource		= $row->datasource;
			$this->datasource_type	= $row->datasource_type;
			$this->updateamum		= $row->updateamum;
			$this->updatevon		= $row->updatevon;
			$this->insertamum		= $row->insertamum;
			$this->insertvon		= $row->insertvon;
			$this->publish			= $row->publish;
			$this->statistik_kurzbz	= $row->statistik_kurzbz;
		}
		/*switch ($chart_id)
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
		$qry = 'SELECT * FROM addon.tbl_rp_chart ORDER BY title;';

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while($row = $this->db_fetch_object())
		{
			$obj = new chart();

			$obj->chart_id			= $row->chart_id;
			$obj->title				= $row->title;
			$obj->description		= $row->description;
			$obj->type				= $row->type;
			$obj->sourcetype		= $row->sourcetype;
			$obj->preferences		= $row->preferences;
			$obj->datasource		= $row->datasource;
			$obj->datasource_type	= $row->datasource_type;
			$obj->updateamum		= $row->updateamum;
			$obj->updatevon			= $row->updatevon;
			$obj->insertamum		= $row->insertamum;
			$obj->insertvon		    = $row->insertvon;
			$obj->publish			= $row->publish;
			$obj->statistik_kurzbz	= $row->statistik_kurzbz;
			//$obj->chart_num_rows= $this->getNumRows('sync.'.$row->chart_tablename);
			$obj->new       = false;

			$this->result[] = $obj;
		}
		return true;
	}
	/**
	 * Laedt alle Statistiken einer Gruppe, Parameter publish zum Filtern.
	 * @return true wenn ok, sonst false
	 */
	public function getGruppe($gruppe,$publish=null)
	{
		$qry = "SELECT tbl_rp_chart.* FROM public.tbl_statistik JOIN addon.tbl_rp_chart USING (statistik_kurzbz) WHERE gruppe='$gruppe'";
		if ($publish==true)
			$qry.=' AND tbl_rp_chart.publish ';
		elseif ($publish==false)
			$qry.=' AND NOT tbl_rp_chart.publish ';
		$qry.=' ORDER BY bezeichnung;';
		
		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new chart();

				$obj->chart_id			= $row->chart_id;
				$obj->title				= $row->title;
				$obj->description		= $row->description;
				$obj->type				= $row->type;
				$obj->sourcetype		= $row->sourcetype;
				$obj->preferences		= $row->preferences;
				$obj->datasource		= $row->datasource;
				$obj->datasource_type	= $row->datasource_type;
				$obj->updateamum		= $row->updateamum;
				$obj->updatevon			= $row->updatevon;
				$obj->insertamum		= $row->insertamum;
				$obj->insertvon			= $row->insertvon;
				$obj->publish			= $row->publish;
				$obj->statistik_kurzbz	= $row->statistik_kurzbz;
				//$obj->chart_num_rows= $this->getNumRows('sync.'.$row->chart_tablename);
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
		$qry = 'SELECT gruppe, count(*) AS anzahl FROM public.tbl_statistik JOIN addon.tbl_rp_chart USING (statistik_kurzbz) ';

		if($publish === true)
		{
			$qry .= 'WHERE tbl_rp_chart.publish ';
		}
		elseif($publish === false)
		{
			$qry .= 'WHERE NOT tbl_rp_chart.publish ';
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
	 * Liefert die möglichen Plugins/Chart-Varianten zurück
	 * @return array
	 */
	public static function getPlugins()
	{

		return array(
			'xchart' => 'XChart',
			'spider' => 'Spider',
			'hcline' => 'Highcharts Line',
			'hccolumn' => 'Highcharts Column',
			'hcpie' => 'Highcharts Pie',
			'hcdrill' => 'Highcharts Drilldown',
		);

	}
	/**
	 * Liefert die unterstützten Datenformate
	 * @return array
	 */
	public static function getDataSourceTypes()
	{
		return array(
			'intern' => 'Interne Statistik (JSON)',
			'extern_json' => 'Extern JSON',
			'extern_csv' => 'Extern CSV',
		);
	}
	/**
	 * Default preferences für Plugins/Chart-Varianten
	 * @return array
	 */
	public static function getDefaultPreferences()
	{

		$hc_drill = <<<EOT
var	level_one_format = '{point.y}',
	level_two_format = '{point.y}';
// Ganze Zahlen: {point.y}
// 1 Nachkommastelle: {point.y:.1f}
// 3 Nachkommastellen: {point.y:.3f}
// Einheiten oder Prozentzeichen: {point.y}% oder {point.y}km/h
EOT;

		$hc_default = <<<EOT
// chart.colors = ['#8d4653', '#91e8e1'];
// HEX-Codes die die Farben der Charts bestimmen:
// 1. Code -> 1. Spalte
// 2. Code -> 2. Spalte usw.
//
// chart.x.rotation = 45;
// Der Winkel der X-Achsenbeschriftung
EOT;

		return array(
			'xchart' => "",
			'spider' => "",
			'hcline' => $hc_default,
			'hccolumn' => $hc_default,
			'hcpie' => $hc_default,
			'hcdrill' => $hc_drill,
		);

	}
	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $chart_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->datasource_type === 'intern') {

			$this->datasource = '../../../vilesci/statistik/statistik_sql.php?statistik_kurzbz=' . $this->statistik_kurzbz . '&outputformat=json';
		}

		if($this->new)
		{
			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_chart (title, description, publish, statistik_kurzbz, type,sourcetype,preferences,datasource,datasource_type,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->title).', '.
			      $this->db_add_param($this->description).', '.
			      $this->db_add_param($this->publish).', '.
			      $this->db_add_param($this->statistik_kurzbz).', '.
			      $this->db_add_param($this->type).', '.
			      $this->db_add_param($this->sourcetype).', '.
			      $this->db_add_param($this->preferences).', '.
			      $this->db_add_param($this->datasource).', '.
			      $this->db_add_param($this->datasource_type).', now(), '.
			      $this->db_add_param($this->insertvon).');';
		}
		else
		{
			//Pruefen ob chart_id eine gueltige Zahl ist
			if(!is_numeric($this->chart_id))
			{
				$this->errormsg = 'chart_id muss eine gueltige Zahl sein';
				return false;
			}
			$qry='UPDATE addon.tbl_rp_chart SET'.
				' title='.$this->db_add_param($this->title).', '.
				' description='.$this->db_add_param($this->description).', '.
				' publish='.$this->db_add_param($this->publish).', '.
				' statistik_kurzbz='.$this->db_add_param($this->statistik_kurzbz).', '.
				' type='.$this->db_add_param($this->type).', '.
				' sourcetype='.$this->db_add_param($this->sourcetype).', '.
				' preferences='.$this->db_add_param($this->preferences).', '.
				' datasource='.$this->db_add_param($this->datasource).', '.
				' datasource_type='.$this->db_add_param($this->datasource_type).', '.
				' updateamum= now(), '.
		      	' updatevon='.$this->db_add_param($this->updatevon).
		      	' WHERE chart_id='.$this->db_add_param($this->chart_id, FHC_INTEGER, false).';';
		}
        //echo $qry;
		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_rp_chart_chart_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->chart_id = $row->id;
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
		return $this->chart_id;
	}
	
	public function getHtmlHead()
	{
		$html='';
		$html.='<script src="../include/js/jquery.min.js" type="application/javascript"></script>';
		switch ($this->type)
		{
			case 'spider':
				$html.='<script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>';
				$html.='<link rel="stylesheet" href="../include/css/spider.css" type="text/css">';
				break;
			case 'xchart':
				$html.="\n\t\t".'<link rel="stylesheet" href="../include/css/xchart.css" type="text/css" />';
				break;
			case 'ngGrid':
				$html.="\n\t\t".'<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />';
				//$html.="\n\t\t".'<script src="../include/js/ngGrid/jquery.min.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/ngGrid/angular.min.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/ngGrid/ng-grid.debug.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/ngGrid/main.js" type="application/javascript"></script>';
				break;
			case 'hcdrill':
				$html.="\n\t\t".'<script src="../include/js/highcharts/highcharts.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/highcharts/drilldown.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/highcharts/main.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/highcharts/exporting.js" type="application/javascript"></script>';
				break;
			case 'hcline':
			case 'hccolumn':
			case 'hcpie':
				$html.="\n\t\t".'<script src="../include/js/highcharts/highcharts.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/highcharts/main.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/highcharts/exporting.js" type="application/javascript"></script>';
				break;
		}

		return $html;
	}

	public static function getAllHtmlHead()
	{
		ob_start(); ?>
			<script src="../include/js/jquery.min.1.11.1.js" type="application/javascript"></script>
			<script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>
			<link rel="stylesheet" href="../include/css/spider.css" type="text/css">
			<link rel="stylesheet" href="../include/css/xchart.css" type="text/css" />
			<link rel="stylesheet" href="../include/css/jquery-ui.1.11.2.min.css" type="text/css" />
			<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />
			<script src="../include/js/ngGrid/angular.min.js" type="application/javascript"></script>
			<script src="../include/js/ngGrid/ng-grid.debug.js" type="application/javascript"></script>
			<script src="../include/js/ngGrid/main.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/highcharts.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/drilldown.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/exporting.js" type="application/javascript"></script>
			<script>
				$(function() {
					$.datepicker.setDefaults({dateFormat: "yy-mm-dd"});
				});
			</script>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	public function getHtmlForm()
	{
		$html="\n\t\t<form>";
		$filter=new filter();
		$filter->loadAll();
		$html.=$filter->getHtmlWidget('Studiengang');
		$html.="\n\t\t\t<input type='submit' />";
		$html.="\n\t\t\t<input type='hidden' name='chart_id' value='".$this->chart_id."' />";
		$html.="\n\t\t\t<input type='hidden' name='htmlbody' value='true' />";
		return $html."\n\t\t</form>";
	}
	
	public function getHtmlDiv()
	{
		$html='';
		switch ($this->type)
		{
			case 'spider':
				$html.= '<div id="spidergraphcontainer"></div>';
				$html.= '<script type="application/javascript">
						var source="'.$this->datasource.$this->vars.'";
						'.$this->preferences.'
						</script>
						';
				$html.= '<script src="../include/js/spidergraph.js" type="application/javascript"></script>';
				break;
			case 'xchart':
				$html.="\n\t\t".'<figure id="xChart"></figure>';
				$html.="\n\t\t".'<script src="../include/js/d3.min.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/xcharts/xcharts.min.js" type="application/javascript"></script>';
				$html.="\n\t\t".'<script src="../include/js/xcharts/main.js" type="application/javascript"></script>';
				$html.= '<script type="application/javascript">
						var source="'.$this->datasource.$this->vars.'";
						'.$this->preferences.'
						</script>';
				break;
			case 'ngGrid':
				$html.="\n\t\t".'<div class="gridStyle" ng-app="myApp">
							<div class="gridStyle" ng-controller="MyCtrl">
								<div class="gridStyle" ng-grid="gridOptions"></div>
							</div>
						</div>';
				break;
			case 'hcdrill':
			case 'hcline':
			case 'hccolumn':
			case 'hcpie':
				$chart_div_id = 'hcChart' . $this->chart_id;
				$html .= "\n\t\t".'<div id="' . $chart_div_id . '"></div>';
				$html .= '<script type="application/javascript">
							var source="'.$this->datasource.$this->vars.'",
								chart = {
									title: "' . $this->title . '",
									type: "' . $this->type . '",
									div_id: "' . $chart_div_id . '",
									raw: {},
									categories: {},
									series: {},
									x: {rotation: 0},
									y: {rotation: 0},
									colors: []
								};
							'.$this->preferences.';

						</script>';
				$html .= '<script src="../include/js/highcharts/init.js" type="application/javascript"></script>';
				break;
		}
		return $html;
	}

	public function getFooter() {

		$html = '<script type="application/javascript">
					initCharts();
				</script>';


		return $html;
	}

	public function printPng()
	{
		switch ($this->type)
		{
			case 'pChartBar':
				/* Create and populate the pData object */
				$MyData = new pData();  
				$MyData->loadPalette("../palettes/blind.color",TRUE);
				$MyData->addPoints(array(150,220,300,250,420,200,300,200,100),"Server A");
				$MyData->addPoints(array(140,0,340,300,320,300,200,100,50),"Server B");
				$MyData->setAxisName(0,"Hits");
				$MyData->addPoints(array("January","February","March","April","May","Juin","July","August","September"),"Months");
				$MyData->setSerieDescription("Months","Month");
				$MyData->setAbscissa("Months");

				/* Create the floating 0 data serie */
				$MyData->addPoints(array(60,80,20,40,0,50,90,30,100),"Floating 0");
				$MyData->setSerieDrawable("Floating 0",FALSE);

				/* Create the pChart object */
				$myPicture = new pImage(700,230,$MyData);
				$myPicture->drawGradientArea(0,0,700,230,DIRECTION_VERTICAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>100));
				$myPicture->drawGradientArea(0,0,700,230,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>20));
				$myPicture->setFontProperties(array("FontName"=>"../include/pChart/fonts/pf_arma_five.ttf","FontSize"=>6));

				/* Draw the scale  */
				$myPicture->setGraphArea(50,30,680,200);
				$myPicture->drawScale(array("CycleBackground"=>TRUE,"DrawSubTicks"=>TRUE,"GridR"=>0,"GridG"=>0,"GridB"=>0,"GridAlpha"=>10));

				/* Turn on shadow computing */ 
				$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

				/* Draw the chart */
				$settings = array("Floating0Serie"=>"Floating 0","Draw0Line"=>TRUE,"Gradient"=>TRUE,"DisplayPos"=>LABEL_POS_INSIDE,"DisplayValues"=>TRUE,"DisplayR"=>255,"DisplayG"=>255,"DisplayB"=>255,"DisplayShadow"=>TRUE,"Surrounding"=>10);
				$myPicture->drawBarChart($settings);

				/* Write the chart legend */
				$myPicture->drawLegend(580,12,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

				/* Render the picture (choose the best way) */
				$myPicture->autoOutput("pictures/example.drawBarChart.floating.png"); 
				break;
		}
	}
}
