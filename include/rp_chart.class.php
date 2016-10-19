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
require_once(dirname(__FILE__).'/../include/rp_phantom.class.php');
require_once(dirname(__FILE__).'/../../../include/statistik.class.php');
require_once(dirname(__FILE__).'/../../../vendor/autoload.php');

class chart extends basis_db
{
	public $new;
	public $result = array();
	public $chart = array();  // for DB-Results
	public $vars = '';
	public $statistik;

	//Tabellenspalten
	public $chart_id;
	public $title;
	public $longtitle;
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
	public $inDashboard;

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
			$this->longtitle			= $row->longtitle;
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
			$obj->longtitle				= $row->longtitle;
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
			$obj->longtitle				= $row->longtitle;
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
				$obj->longtitle				= $row->longtitle;
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
			$obj->longtitle				= $row->longtitle;
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
			'hcnorm' => 'Highcharts Normal',
			'hcdrill' => 'Highcharts Drilldown',
			'hcgroupedstacked' => 'Highcharts Grouped Stacked',
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

		$hc_grstacked = <<<EOT
/*
{
 "chart":{
  "zoomType":"none",//Möglichkeiten: "x", "y" ("xy" ist standard)
  "type":"pie"
 }
}
*/
EOT;
		$hc_drill = <<<EOT
/*
{
 "chart":{
  "zoomType":"none",//Möglichkeiten: "x", "y" ("xy" ist standard)
  "type":"pie"
 }
}
*/
EOT;

		$hc_default = <<<EOT
{
 "chart":{"type":"column"}

 /*,"xAxis":{
  "labels":{
   "rotation":90  // Der Winkel der X-Achsenbeschriftung
  }
 },
 "yAxis":[
  {
   "title":{"text":"Test Achse(rechts)"},
   "labels":{
    "format":"{value} Personen",
    "style":{"color":"#FF0000"}
   },
   "opposite":true
  },
  {"title":{"text":"Test Achse(links)"}}
 ],
 "series":{
  "Ausland":{
    "zIndex": -1,
    "type":"column",
    "yAxis":1
  },
  "Ausland 2Stg":{
    "zIndex": -1,
    "type":"column",
    "yAxis":1
  },
  "Inland":{
    "type":"pie","center":["10%","10%"],"size":["20%","20%"]
  }
 }*/
}

EOT;


		return array(
			'hcnorm' => $hc_default,
			'hcdrill' => $hc_drill,
			'hcgroupedstacked' => $hc_grstacked,
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
			$qry='BEGIN;INSERT INTO addon.tbl_rp_chart (title, longtitle, description, publish, dashboard, dashboard_layout, dashboard_pos, statistik_kurzbz, type,sourcetype,preferences,datasource,datasource_type,
			      insertamum, insertvon) VALUES('.
			      $this->db_add_param($this->title).', '.
			      $this->db_add_param($this->longtitle).', '.
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
				' longtitle='.$this->db_add_param($this->longtitle).', '.
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
			case 'hcnorm':
			case 'hcgroupedstacked':
			case 'hcdrill': ?>
				<?php require_once("../include/meta/highcharts.php"); ?>
				<script src="../include/js/highcharts/exporting.js" type="application/javascript"></script>
				<?php break;
		}

		return ob_get_clean();
	}

	public static function getAllHtmlHead()
	{
		ob_start(); ?>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<script type="text/javascript" src="../../../content/phantom.js.php"></script>
		<script src="../include/js/jquery-1.11.2.min.js" type="application/javascript"></script>
		<link rel="stylesheet" href="../include/css/charts.css" type="text/css">
		<?php require_once("../include/meta/highcharts.php"); ?>
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
			case 'hcnorm':
			case 'hcdrill':
			case 'hcgroupedstacked':

			$style = "border: 1px solid transparent;";

			if($this->inDashboard)
			{
				switch($this->dashboard_layout)
				{
					case "half":
						$style .= "width:50%;float:left;";
					break;
					case "third":
						$style .= "width:33%;float:left;";
					break;
					default:
						$style .= "width:100%;float:left;";
					break;
				}
			}

				?>
				<div id="hcChart<?php echo $this->chart_id ?>" class="<?php echo $class ?>" style="<?php echo $style;?>"></div>
				<?php
					$hcData = $this->getHighChartData();
				?>
				<?php if(!$hcData)return false;?>
				<script>
					var hcData = <?php echo $hcData; ?>;
					if(hcData.FHCChartType == "groupedstacked")
					{
						hcData.tooltip = {formatter: function() {return '<b>'+ this.series.options.stack + '</b><br/>'+ this.series.name +': '+ this.y;}};
					}

					$("#hcChart"+<?php echo $this->chart_id ?>).highcharts(hcData);
				</script>
			<?php break;
			default:
				die("No Chart-Type specified");
		}

		if(!$this->inDashboard)
		{
			$parser = new \Netcarver\Textile\Parser();
			$textile = $parser->textileThis($this->description);

			echo '<div>'.$textile.'</div>';
		}

		return ob_get_clean();
	}

	public function getFooter() {

		ob_start(); ?>

		<?php return ob_get_clean();
	}

	public function writePNG($targetDir)
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

			case 'hcnorm':
			case 'hcdrill':
			case 'hcgroupedstacked':
				$tmp_filename=$targetDir.'/chart'.$this->chart_id.date('Y-m-d_H:i:s').'.png';
				$output_filename=$targetDir.'/chart'.$this->chart_id.'.png';
				$output=array();

				/*
				* animationen deaktivieren, da diese Anzeigefehler verursachen können
				* dataLabels funktionieren z.B. mit Animationen nicht
				*/
				$phantomData = $this->getHighChartData(false);


				if(!$phantomData)return false;

				$ph = new phantom();
				$p = $ph->render(array("infile" => $phantomData));

				if(!$p)
				{
					$this->errormsg = "Der PhantomJS Server ist nicht erreichbar";
					return false;
				}

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
					$this->errormsg='<br/>Cannot move File from '.$tmp_filename.' to '.$output_filename.'<br/>';

				return false;
		}
	}

	/**
	* Liefert den Highchart als JSON zurück
	 * @param $animation animationen aktivieren/deaktivieren(default = true)
	* @return JSON wenn ok, sonst false
	*/
	private function getHighChartData($animation = true)
	{
		$this->statistik = new statistik($this->statistik_kurzbz);
		if (!$this->statistik->loadData())
		{
			$this->errormsg = 'Data not loaded!<br/>'.$this->statistik->errormsg;
			return false;
		}

		$series = array();
		$series_data = array();
		$categories = "";
		$hctype=substr($this->type,2);
		$data = $this->statistik->getArray();
		$stacking = "";
		$customCategories = false;
		$prefs = array();

		/* Get the preferences */
		if(isset($this->preferences) && $this->preferences != "" && $this->preferences != null)
		{
			$json = $this->removeCommentsFromJson($this->preferences);

			if($json != '')		//wenn nicht nur kommentare in den preferences standen
			{
				//in einen array umwandeln
				$prefs = json_decode($json, true);
				if(!$prefs)
				{
					$this->errormsg = "Chart".$this->chart_id . ": Preferences sind keine wohlgeformten JSON-Daten:<br>'". $json."'";
					return false;
				}
			}
		}

		if(isset($prefs["xAxis"]) && isset($prefs["xAxis"]["FHCCustomCategories"]))
			$customCategories = $prefs["xAxis"]["FHCCustomCategories"];

		if ($hctype=='drill')
		{
			foreach($data as $zeile)
			{
				$l1_bezeichnung = current($zeile);
				$l1_dd = next($zeile);
				$l1_sum = (int) end($zeile);
				if(!isset($l1_dd))
					$l1_dd = " ";

				//create a drilldown, if not already
				if(!isset($series_data[$l1_bezeichnung]))
				{
					$series_data[$l1_bezeichnung]["name"] = $l1_bezeichnung;
					$series_data[$l1_bezeichnung]["drilldown"] = $l1_bezeichnung;
					$series_data[$l1_bezeichnung]["y"] = $l1_sum;
				}
				//or add the data to an existing
				else
				{
					$series_data[$l1_bezeichnung]["y"] += $l1_sum;
				}

				$drilldown[$l1_bezeichnung]["id"] =	$l1_bezeichnung;
				$drilldown[$l1_bezeichnung]["data"][] =	array($l1_dd, $l1_sum);
			}

			$series[] = array(
				'name' => 'Series 1',
				'colorByPoint' => true,
				'data' => array_values($series_data),
			);
			$xAxis = array
			(
				'type' => 'category',
				'title' => array('text' => '',),
				'labels' => array('rotation' => -45),
			);
		}
		else if($hctype=='groupedstacked')
		{
			$stacking = "normal"; //normal/percent/STD:undefined
			$xAxis = array
			(
				'type' => 'category',
				'title' => array('text' => '',),
				'labels' => array('rotation' => -45),
				'categories' => array(),
			);
			$colors = array('#7cb5ec', '#434348', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1', '#BBBBBB', '#992222', '#226622', '#222266', '#EEAAAA', '#FFFFBB', '#FFBBFF');
			$colorCount = 0;

			$categories = array();
			$groups = array();
			$mainGroups = array();// only used for linked

			foreach($data as $zeile)
			{
				//loop every entry
				$category = current($zeile);if(!isset($category)){$category = " ";}
				$stack = next($zeile);if(!isset($stack)){$stack = " ";}
				$name = next($zeile);if(!isset($name)){$name = " ";}
				$partValue = (int) end($zeile);if(!isset($partValue)){$partValue = 0;}

				//to add all categories
				$categories[$category] = "";

				//and all groups
				if(!isset($groups[$name."_".$stack]))
				{
					$groups[$name."_".$stack] = array("data" => array());
				}
			}

			//set the categories
			$xAxis["categories"] = array_keys($categories);

			//add every category to every group and set the data to 0
			foreach($groups as $k => $v)
			{
				foreach($xAxis["categories"] as $cat)
				{
					$groups[$k]["data"][$cat] = 0;
				}
			}

			//sort the categories(also the index)
			asort($xAxis["categories"]);
			$newCategories = array();
			foreach($xAxis["categories"] as $k => $v)
			{
				$newCategories[] = $v;
			}
			$xAxis["categories"] = $newCategories;



			//loop everything again and add the data to the correct group/category
			foreach($data as $zeile)
			{
				$category = reset($zeile);if(!isset($category)){$category = " ";}
				$stack = next($zeile);if(!isset($stack)){$stack = " ";}
				$name = next($zeile);if(!isset($name)){$name = " ";}
				$partValue = (int) end($zeile);if(!$partValue){$partValue = 0;}

				$groups[$name."_".$stack]["name"] = $name;
				$groups[$name."_".$stack]["stack"] = $stack;
				$groups[$name."_".$stack]["data"][$category] += $partValue;

				if(isset($prefs["FHCGroupingType"]) && $prefs["FHCGroupingType"] == "link")
				{
					if(!isset($mainGroups[$name]))
					{
						if(!isset($colors[$colorCount]))
							$colorCount = 0;

						$mainGroups[$name] = $colors[$colorCount];
						$colorCount ++;
						$groups[$name."_".$stack]["id"] = $name;
						$groups[$name."_".$stack]["color"] = $mainGroups[$name];
					}
					else if(!isset($groups[$name."_".$stack]["id"]))
					{
						$groups[$name."_".$stack]["linkedTo"] = $name;
						$groups[$name."_".$stack]["color"] = $mainGroups[$name];
					}
				}
			}
			$series = $groups;


			//convert all data from associative arrays to normal arrays
			foreach($series as $key => $value)
			{
				ksort($series[$key]["data"]);	// IMPORTANT: keeps the categories and the series in sync -> the categories have been sorted before!
				$series[$key]["data"] = array_values($series[$key]["data"]);
			}
			if(isset($prefs["FHCGroupingType"]) && $prefs["FHCGroupingType"] == "link")
			{
				if(isset($prefs["FHCStackReverse"]) && $prefs["FHCStackReverse"] == "true")
				{
					usort($series, "stackSortRev");
				}
				else
				{
					usort($series, "stackSort");
				}
			}
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
						$dt = floatval($it);
						if($dt === false)
						return false;

						$series[$ik]["name"] = $ik;
						$series[$ik]["data"][] = array($header, $dt);
					}
				}
			}
			$xAxis = array
			(
				'categories' => $categories,
				'title' => array('text' => '',),
				'labels' => array('rotation' => -45),
			);
		}

		// add custom categories
		if($customCategories)
		{
			foreach($xAxis["categories"] as $k => $v)
			{
				$xAxis["categories"][$k] = str_replace("€this", $xAxis["categories"][$k], $customCategories);
			}
		}



		$phantomData = array
		(
			'FHCChartType' => $hctype,
			'FHCBoxplotType' => 0,
			'div_id' => 'hcChart' . $this->chart_id,
			'title' => array
			(
				'text' => $this->title,
			),
			'credits' => array
			(
				'text' => '',
				'href' => 'http://fhcomplete.org'
			),
			'exporting' => array
			(
				'url' => "../cis/chart_export.php",
				'sourceHeight' => 450,
				'sourceWidth' => 800,
				'scale' => 1,			//wird von width überschrieben!
			),
			'chart' => array
			(
				'zoomType' => "xy",
				'type' => "column",
				'animation' => $animation,    //animation für den zoom
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
			'plotOptions' => array
			(
				'series' => array('animation' => $animation),
				'column' => array('stacking' => $stacking),
				'boxplot' => array
				(
					'grouping' => false,
					'stickyTracking' => false,
				)
			),
			'xAxis' => $xAxis,
			'series' => $series
		);

		if(isset($drilldown))
		{
			$phantomData["drilldown"]["series"] = $drilldown;
		}

		if(isset($prefs) && $prefs && is_array($prefs))
		{
			$phantomData = array_replace_recursive($phantomData, $prefs);
		}

		//series und yAxis in normale arrays zurückwandeln, da highcharts keine assoziativen entgegen nimmt!
		$phantomData["series"] = array_values($phantomData["series"]);

		//die info yAxis muss existieren, darum wird hier abgefragt, ob welche in den preferences definiert wurden, wenn nicht wird eine leere angelegt
		if(isset($phantomData["yAxis"]))		//convert assoc arrays to sequential
			$phantomData["yAxis"] = array_values($phantomData["yAxis"]);
		else		// add the default yAxis preferences, if none are set
			$phantomData["yAxis"] = array('title' => array('text' => ' ',));

		//gleiches spiel mit den drilldown infos
		if(isset($drilldown))
		{
			$phantomData["drilldown"]["series"] = array_values($phantomData["drilldown"]["series"]);
		}

		//nur für boxplot charts!
		if($phantomData["chart"]["type"] == "boxplot")
		{
			$bpMaxCount = 0;
			$bpCount = 0;
			$boxplotData = array();
			$bpCategories = array();

			foreach($series as $sk => $se)
			{
				if($phantomData["FHCBoxplotType"] == 0)
				{
					$singleBoxPlot = array();
					foreach($se["data"] as $d)
					{
						if(!isset($bpCategories[$d[0]]))
							$bpCategories[] = $d[0];

						$boxplotData[$d[0]][] = $d[1];
					}
				}
				else
				{
					$bpCategories[] = $se["name"];

					$singleBoxPlot = array();
					foreach($se["data"] as $d)
					{
						$singleBoxPlot[] = $d[1];
					}
					$boxplotData[] = $singleBoxPlot;
				}
			}

			//und in normalen array umwandeln
			$boxplotData = array_values($boxplotData);
			unset($phantomData["drilldown"]);

			$phantomData["series"][0]["data"] = $boxplotData;
			$phantomData["xAxis"]["categories"] = $bpCategories;
		}
		$data = json_encode($phantomData);
		return $data;
	}









	public function removeCommentsFromJson($jsonString)
	{
		$Array = explode("\n", $jsonString);
		$commentCount = 0;

		foreach($Array as $key => $p)
		{
			// \r entfernen
			$Array[$key] = str_replace("\r", "", $Array[$key]);

			// mehrzeilige kommentare
			$posMz = strpos($Array[$key], "/*");
			if($posMz !== false)		//anfang eines Mehrzeiligen kommentars gefunden
			{
				$commentCount ++;
				if($commentCount == 1)	//wenn noch kein kommentar im gange ist
				{
					$Array[$key] = substr($Array[$key], 0, $posMz);
				}
				else
					$posMz = false;
			}
			$posMzE = strpos($Array[$key], "*/");
			if($posMzE !== false)		//ende des Mehrzeiligen kommentars gefunden
			{
				$commentCount --;
				if($commentCount == 0)	//wenn alle kommentare beendet wurden
				{
					$Array[$key] = substr($Array[$key], $posMzE+2, count($Array[$key]));
				}
				else
					$posMzE = false;
			}

			if($posMz === false && $posMzE === false && $commentCount > 0)		//zeile komplett auskommentiert
			{
				unset($Array[$key]);
				continue;		//doppelslashes werden somit umgangen wenn sie /* // */ eingekapselt sind
			}



			// doppelslash-kommentare
			$posEz = strpos ( $p, "//");
			if($posEz !== false)
			{
				$Array[$key] = substr($p, 0, $posEz);
			}
		}
		//und wieder zusammenfügen
		$json = join('', $Array);

		return $json;
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
				$obj->longtitle = $row->longtitle;
				$obj->description = $row->description;
				$obj->type = $row->type;
				$obj->preferences = $row->preferences;
				$obj->publish		= $this->db_parse_bool($row->publish);
				$obj->statistik_kurzbz = $row->statistik_kurzbz;
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



function stackSort($a, $b)
{
	/* 1st level: sort by stack */
	$ret = strcmp($a["stack"], $b["stack"]);

	/* 2nd level: sort by name */
	if($ret == 0)
		return strcmp($a["name"], $b["name"]);
	return $ret;
}

function stackSortrev($a, $b)
{
	/* 1st level: sort by stack */
	$ret = strcmp($b["stack"], $a["stack"]);

	/* 2nd level: sort by name */
	if($ret == 0)
		return strcmp($a["name"], $b["name"]);
	return $ret;
}
