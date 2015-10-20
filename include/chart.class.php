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
 *			Andreas Moik <moik@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('../include/phantom.class.php');
require_once('../../../include/statistik.class.php');

class chart extends basis_db
{
	public $new;
	public $result = array();
	public $chart = array();  // for DB-Results
	public $vars = '';
	public $statistik;
	public $addon_root;

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
	public $dashboard;
	public $dashboard_layout;
	public $dashboard_pos;

	/**
	 * Konstruktor
	 * @param akadgrad_id ID des zu ladenden Datensatzes
	 */
	public function __construct($chart_id=null)
	{
		parent::__construct();
		$this->addon_root=dirname(__FILE__).'/../';
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
			$this->publish			= $this->db_parse_bool($row->publish);
			$this->statistik_kurzbz	= $row->statistik_kurzbz;
			$this->dashboard		= $this->db_parse_bool($row->dashboard);
			$this->dashboard_layout	= $row->dashboard_layout;
			$this->dashboard_pos	= $row->dashboard_pos;
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
			$obj->publish			= $this->db_parse_bool($row->publish);
			$obj->statistik_kurzbz	= $row->statistik_kurzbz;
			$obj->dashboard			= $this->db_parse_bool($row->dashboard);
			$obj->dashboard_layout	= $row->dashboard_layout;
			$obj->dashboard_pos		= $row->dashboard_pos;
			//$obj->chart_num_rows= $this->getNumRows('sync.'.$row->chart_tablename);
			$obj->new       = false;

			$this->result[] = $obj;
		}
		return true;
	}

	/**
	 * Laedt alle Charts aus DB zu einem Report
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadCharts($report_id)
	{

		//Lesen der Daten aus der Datenbank
		$qry = 'SELECT tbl_rp_chart.*
				FROM addon.tbl_rp_chart
					JOIN addon.tbl_rp_report_chart USING (chart_id)
				WHERE report_id='.$report_id.' ORDER BY title;';

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
			$obj->publish			= $this->db_parse_bool($row->publish);
			$obj->statistik_kurzbz	= $row->statistik_kurzbz;
			$obj->dashboard			= $this->db_parse_bool($row->dashboard);
			$obj->dashboard_layout	= $row->dashboard_layout;
			$obj->dashboard_pos		= $row->dashboard_pos;
			//$obj->chart_num_rows= $this->getNumRows('sync.'.$row->chart_tablename);
			$obj->new       = false;

			$this->chart[] = $obj;
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
				$obj->publish			= $this->db_parse_bool($row->publish);
				$obj->statistik_kurzbz	= $row->statistik_kurzbz;
				$obj->dashboard			= $this->db_parse_bool($row->dashboard);
				$obj->dashboard_layout	= $row->dashboard_layout;
				$obj->dashboard_pos		= $row->dashboard_pos;
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
	 * Laedt alle Charts die im Dashboard angezeigt werden sollen.
	 */
	public function getDashboard()
	{
		$qry = 'SELECT * '
				. 'FROM addon.tbl_rp_chart '
				. 'WHERE dashboard '
				. 'ORDER BY dashboard_pos';

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
			$obj->publish			= $this->db_parse_bool($row->publish);
			$obj->statistik_kurzbz	= $row->statistik_kurzbz;
			$obj->dashboard			= $this->db_parse_bool($row->dashboard);
			$obj->dashboard_layout	= $row->dashboard_layout;
			$obj->dashboard_pos		= $row->dashboard_pos;
			$obj->new				= false;

			$this->result[] = $obj;
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
		// Convention: Highcharts must start with "hc" followed by the real charttype
		return array(
			'xchart' => 'XChart',
			'spider' => 'Spider',
			'hcline' => 'Highcharts Line',
			'hccolumn' => 'Highcharts Column',
			'hcbar' => 'Highcharts Bar',
			'hcpie' => 'Highcharts Pie',
			'hcdrill' => 'Highcharts Drilldown',
			'hctimezoom' => 'Highcharts Timezoom',
		);
	}
	/**
	 * Liefert die möglichen Layoutvarianten fürs Dashboard (FAS)
	 * @return array
	 */
	public static function getDashboardLayouts()
	{
		return array(
			'full' => '100%',
			'half' => '50%',
			'third' => '33%',
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

		$hc_timezoom = <<<EOT
// chart.raw.x = {
//		spalte: 'datum',
//		label: 'Datum'
// };
// chart.raw.y = [{
//		spalte: 'anzahl',
//		label: 'Anzahl'
// }];
EOT;

		return array(
			'xchart' => "",
			'spider' => "",
			'hcline' => $hc_default,
			'hccolumn' => $hc_default,
			'hcbar' => $hc_default,
			'hcpie' => $hc_default,
			'hcdrill' => $hc_drill,
			'hctimezoom' => $hc_timezoom,
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

		if($this->new)
		{

			//Neuen Datensatz einfuegen
			$qry='BEGIN;INSERT INTO addon.tbl_rp_chart (title, description, publish, dashboard, dashboard_layout, dashboard_pos, statistik_kurzbz, type,sourcetype,preferences,datasource,datasource_type,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->title).', '.
			      $this->db_add_param($this->description).', '.
			      $this->db_add_param($this->publish, FHC_BOOLEAN).', '.
			      $this->db_add_param($this->dashboard, FHC_BOOLEAN).', '.
			      $this->db_add_param($this->dashboard_layout).', '.
			      $this->db_add_param($this->dashboard_pos, FHC_INTEGER).', '.
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
				' publish='.$this->db_add_param($this->publish, FHC_BOOLEAN).', '.
				' dashboard='.$this->db_add_param($this->dashboard, FHC_BOOLEAN).', '.
				' dashboard_layout='.$this->db_add_param($this->dashboard_layout).', '.
				' dashboard_pos='.$this->db_add_param($this->dashboard_pos, FHC_INTEGER).', '.
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
		ob_start(); ?>
		<link rel="stylesheet" href="../include/css/charts.css" type="text/css">

		<?php switch ($this->type)
		{
			case 'spider': ?>
				<script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>
				<link rel="stylesheet" href="../include/css/spider.css" type="text/css">
				<?php break;
			case 'xchart': ?>
				<link rel="stylesheet" href="../include/css/xchart.css" type="text/css" />
				<?php break;
			case 'ngGrid': ?>
				<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />
				<script src="../include/js/ngGrid/angular.min.js" type="application/javascript"></script>
				<script src="../include/js/ngGrid/ng-grid.debug.js" type="application/javascript"></script>
				<script src="../include/js/ngGrid/main.js" type="application/javascript"></script>
				<?php break;
			case 'hcdrill': ?>
				<script src="../include/js/highcharts/highcharts-custom.js" type="application/javascript"></script>
				<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
				<?php break;
			case 'hcline':
			case 'hccolumn':
			case 'hcbar':
			case 'hcpie': ?>
				<script src="../include/js/highcharts/highcharts-custom.js" type="application/javascript"></script>
				<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
				<script src="../include/js/highcharts/exporting.js" type="application/javascript"></script>
				<?php break;
		}

		return ob_get_clean();
	}

	public static function getAllHtmlHead()
	{
		ob_start(); ?>
			<script type="text/javascript" src="../../../content/phantom.js.php"></script>
			<script src="../include/js/jquery-1.11.2.min.js" type="application/javascript"></script>
			<script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>
			<link rel="stylesheet" href="../include/css/charts.css" type="text/css">
			<link rel="stylesheet" href="../include/css/spider.css" type="text/css">
			<link rel="stylesheet" href="../include/css/xchart.css" type="text/css" />
			<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />
			<script src="../include/js/ngGrid/angular.min.js" type="application/javascript"></script>
			<script src="../include/js/ngGrid/ng-grid.debug.js" type="application/javascript"></script>
			<script src="../include/js/ngGrid/main.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/highcharts-custom.js" type="application/javascript"></script>
			<script src="../include/js/highcharts/main.js" type="application/javascript"></script>
			<script>
				$(function() {

					if(typeof $.datepicker !== 'undefined') {

						$.datepicker.setDefaults({dateFormat: "yy-mm-dd"});
					}
				});
			</script>
		<?php

		return ob_get_clean();
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

	public function getHtmlDiv($class = null)
	{
		ob_start();

		$source = $this->datasource.$this->vars;
		switch ($this->type)
		{
			case 'spider': ?>
				<div id="spidergraphcontainer" class="<?php echo $class ?>"></div>
				<script type="application/javascript">
					var source = <?php echo json_encode($source) ?>;
					<?php echo $this->preferences ?>;
				</script>
				<script src="../include/js/spidergraph.js" type="application/javascript"></script>
				<script src="../include/js/highcharts/init.js" type="application/javascript"></script>
				<?php break;
			case 'xchart': ?>
				<figure id="xChart"></figure>
				<script src="../include/js/d3.js" type="application/javascript"></script>
				<script src="../include/js/xcharts/xcharts.min.js" type="application/javascript"></script>
				<script type="application/javascript">
						var source = <?php echo json_encode($this->datasource.$this->vars) ?>;
						<?php echo $this->preferences ?>;
				</script>
				<?php break;
			case 'hctimezoom':
			case 'hcdrill':
			case 'hcline':
			case 'hccolumn':
			case 'hcbar':
			case 'hcpie':
				?>
				<div id="hcChart<?php echo $this->chart_id ?>" class="<?php echo $class ?>" style="border: 1px solid transparent;"></div>
				<?php $json = $this->getHighChartData();?>
				<?php if(!$json)die($this->errormsg);?>
				<script>$("#hcChart"+<?php echo $this->chart_id ?>).highcharts(<?php echo $json; ?>);</script>
			<?php break;
		}

		return ob_get_clean();
	}

	public function getFooter() {

		ob_start(); ?>

		<script type="application/javascript">
			initCharts();
		</script>

		<?php return ob_get_clean();
	}

	public function writePNG()
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
				$MyData->addPoints(array("January","February","March","April","May","June","July","August","September"),"Months");
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
				$myPicture->autoOutput("data/images/example.drawBarChart.floating.png");
				break;

			case 'hcline':
			case 'hccolumn':
			case 'hcbar':
			case 'hcpie':
			case 'hctimezoom':
			case 'hcdrill':
				$tmp_filename=$this->addon_root.'data/images/chart'.$this->chart_id.date('Y-m-d_H:i:s').'.png';
				$output_filename=$this->addon_root.'data/images/chart'.$this->chart_id.'.png';
				$output=array();
				$scale='2.5';
				$width='1920';

				$phantomData = $this->getHighChartData();
				$ph = new phantom();
				$p = $ph->render(array("infile" => $phantomData, "scale" => $scale, "width" => $width));

				if(!$p)
					die("Der PhantomJS Server ist nicht erreichbar");

				$rsc=fopen($tmp_filename,'w');
				$c=fwrite($rsc,base64_decode($p));

				if(!$rsc)
					echo "konnte das Bild nicht öffnen<br>";
				if(!$c)
					echo "konnte das Bild $tmp_filename nicht schreiben<br>";

				fclose($rsc);
				//echo '<img src="data:image/png;base64,'.$p.'" />';


				// move file
				if (rename($tmp_filename,$output_filename))
				{
					//echo 'chart'.$this->chart_id.'.png: ' . $c . ' Bytes written<br/>';
					return $output_filename;
				}
				else
					$this->errormsg='<br/>Cannot remove File from '.$tmp_filename.' to '.$output_filename.'<br/>';

				return false;
		}
	}

	/**
	* Liefert den Highchart als JSON zurück
	* @return JSON wenn ok, sonst false
	*/
	private function getHighChartData()
	{
		$this->statistik = new statistik($this->statistik_kurzbz);
		if (!$this->statistik->loadData())
			die ('Data not loaded!<br/>'.$this->statistik->errormsg);

		$series = array();
		$series_data = array();
		$categories = "";
		$hctype=substr($this->type,2);
		$data = $this->statistik->getArray();

		if ($hctype=='drill')
		{
			$hctype='column';

			foreach($data as $zeile)
			{
				$l1_bezeichnung = current($zeile);

				if(!isset($series_data[$l1_bezeichnung]))
				{
					$series_data[$l1_bezeichnung] = 0 + end($zeile);
				}
				else
				{
					$series_data[$l1_bezeichnung] += end($zeile);
				}
			}

			$series[] = array(
				'data' => array_values($series_data),
				'name' => 'Series 1',
			);
			$categories = array_keys($series_data);
		}
		else
		{
      foreach($data as $key => $item)
      {
			  $first = true;
        foreach($item as $ik => $it)
        {
          if($first)
          {
          	$header = $it;
            $categories[] = $it;
            $first = false;
          }
          else
          {
            $series[$ik]["name"] = $ik;
            $series[$ik]["data"][] = array($header, floatval($it));
          }
        }
      }
		}

		$phantomData = array
		(
			'div_id' => 'hcChart' . $this->chart_id,
			'title' => array
			(
				'text' => $this->title,
			),
			'chart' => array
			(
				'zoomType' => "xy",
				'type' => $hctype,
				/*
				'options3d' => array
				(
					"enabled" => true,
					"alpha" => 45,
					"beta" => 0,
				)
				*/
				//um 3dCharts zu ermöglichen(kann auch über die preferences geschehen)
			),
			'xAxis' => array
			(
				'categories' => $categories,
				'title' => array('text' => '',),
				'labels' => array('rotation' => -45),
			),
			'yAxis' => array
			(
				'title' => array('text' => ' ',),
			),
			'series' => $series
		);

		if(isset($this->preferences) && $this->preferences != "" && $this->preferences != null)
		{
			//einstellungen aufteilen
			$prefs = explode("\n", $this->preferences);

			//kommentarzeilen entfernen
			foreach($prefs as $pk => $p)
			{
				$pos = strpos ( $p, "//");

				if($pos !== false)
				{
					$prefs[$pk] = str_replace("\t", "", substr($p, 0, $pos));
				}
			}
			//und wieder zusammenfügen
			$json = join('', $prefs);

			if(!$json == "")		//nur, wenn nicht nur kommentare in den preferences standen
			{
				//in einen array umwandeln
				$prefs = json_decode($json, true);

				if(!$prefs)
				{
					die("Chart".$this->chart_id . ": Preferences sind keine wohlgeformten JSON-Daten:<br>". $json);
					return false;
				}

				//und über die phantom daten mergen
				$erg = array_replace_recursive($phantomData, $prefs);

				$phantomData = $erg;
			}
		}


		//series wieder in normale arrays zurückwandeln, da highcharts keine assoziativen entgegen nimmt!
		$phantomData["series"] = array_values($phantomData["series"]);

		return json_encode($phantomData);
	}

	/**
	 * Loescht einen Eintrag
	 *
	 * @param $chart_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($chart_id)
	{
		$qry = "DELETE FROM addon.tbl_rp_chart WHERE chart_id=".$this->db_add_param($chart_id).";";

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
	 * Laedt alle Charts
	 * @return true wenn ok, sonst false
	 */
	public function getAll($order = FALSE)
	{
		$qry = 'SELECT * FROM addon.tbl_rp_chart';

		if($order)
			$qry .= ' ORDER BY ' . $order;

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new chart();

				$obj->chart_id = $row->chart_id;
				$obj->title = $row->title;
				$obj->description = $row->description;
				$obj->type = $row->type;
				$obj->preferences = $row->preferences;
				$obj->datasource = $row->datasource;
				$obj->datasource_type = $row->datasource_type;

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
