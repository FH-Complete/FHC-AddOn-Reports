<?php
/* Copyright (C) 2006 Technikum-Wien
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
 * Authors: Christian Paminger 	< christian.paminger@technikum-wien.at >
 *			Robert Hofer <robert.hofer@technikum-wien.at>
 */
	require_once('../../../config/vilesci.config.inc.php');
	require_once('../../../include/globals.inc.php');
	require_once('../../../include/functions.inc.php');
	require_once('../../../include/benutzerberechtigung.class.php');
	require_once('../../../include/statistik.class.php');
	require_once('../include/chart.class.php');
	$statistik=new statistik();
	if (!$statistik->getAnzahlGruppe(true))
		die();
	$chart=new chart();
	if (!$chart->getAnzahlGruppe(true))
		die();
	//var_dump($statistik);
					
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../../favicon.ico">

    <title>Reports - FH Technikum Wien</title>

    <!-- Bootstrap core CSS -->
    <link href="../include/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="../include/css/offcanvas.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <!--<script src="../include/js/ie10-viewport-bug-workaround.js"></script>-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- ngGrid -->
    <link rel="stylesheet" type="text/css" href="../include/js/ngGrid/ng-grid.css" />
	<link rel="stylesheet" type="text/css" href="../include/js/ngGrid/gridstyle.css" />
	<script type="text/javascript" src="../include/js/jquery.min.js"></script>
	<script type="text/javascript" src="../include/js/ngGrid/angular.min.js"></script>
	<script type="text/javascript" src="../include/js/ngGrid/ng-grid.js"></script>
    <!-- xChart -->    
    <!--<script src="../include/js/jquery.min.js"></script>-->
    <script src="../include/js/d3.min.js" type="application/javascript"></script>
    <script src="../include/js/xcharts/xcharts.min.js" type="application/javascript"></script>
    <link rel="stylesheet" href="../include/css/xchart.css" type="text/css">
    <!-- spiderGraph -->
    <script src="../include/js/spidergraph/jquery.spidergraph.js" type="application/javascript"></script>
	<script type="text/javascript" src="main.js"></script>
    <link rel="stylesheet" href="../include/css/spider.css" type="text/css">
    
  </head>

  <body onload="$('#sidebar div').hide();$('#div_content').hide();$('#iframe_content').hide();$('#div_filter').hide();">
	 <div id="test">
    <div class="navbar navbar-fixed-top navbar-inverse" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#" onclick='$("#welcome").show();'><img src="../data/fhtw_logo_white.png" height="25" />&nbsp;&nbsp;&nbsp;Reporting </a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="dropdown">
			  <a href="#data" class="dropdown-toggle" data-toggle="dropdown">Data <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu" data-name="data">
                <?php foreach ($statistik->result AS $gruppen): ?>
					<li><a href="#" data-gruppe="<?php echo str_replace(' ','',$gruppen->gruppe) ?>">
						<?php echo $gruppen->gruppe ?>
						<span class="badge">
							<?php echo $gruppen->anzahl ?>
						</span>
					</a></li>
				<?php endforeach; ?>
              </ul>
            </li>
            <li class="dropdown">
				<a href="#charts" class="dropdown-toggle" data-toggle="dropdown">Charts <span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu" data-name="charts">
					<?php // var_dump($chart->getAnzahlGruppe(true));
					?>
                <?php foreach ($chart->result AS $gruppen): ?>
					<li><a href="#" data-gruppe="<?php echo str_replace(' ','',$gruppen->gruppe) ?>">
						<?php echo $gruppen->gruppe?>
						<span class="badge">
							<?php echo $gruppen->anzahl ?>
						</span>
					</a></li>
				<?php endforeach; ?>
              </ul>
			</li>
            <li class="dropdown">
			  <a href="#reports" class="dropdown-toggle" data-toggle="dropdown">Reports <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="#" onclick='$("#test").show();'>Studierende <span class="badge">3</span></a></li>
                <li><a href="#" onclick='$("#test2").hide();'>Mitarbeiter <span class="badge">5</span></a></li>
                <li><a href="#" onclick='$("#test2").show();'>Lehre <span class="badge">15</span></a></li>
                <li class="divider"></li>
                <li class="dropdown-header">Extra</li>
                <li><a href="#">Wissensbilanz <span class="badge">3</span></a></li>
                <li><a href="#">Sonstiges <span class="badge">7</span></a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="container">

      <div class="row row-offcanvas row-offcanvas-right">

        <div class="col-xs-12 col-sm-9">
          <p class="pull-right visible-xs">
            <button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas">Toggle nav</button>
          </p>
          <div id="welcome">
			  <div class="jumbotron">
				<h2>FH Technikum Wien</h2>
				<p>Sie befinden sich im Reporting-System der FH Technikum Wien. Das System ist in 3 Bereiche gegliedert.</p>
			  </div>
			  <div class="row">
				<div class="col-6 col-sm-6 col-lg-4">
				  <h2>Data</h2>
				  <p>Auswertungen werden in einer Tabelle mittels Zahlen gezeigt. Klassische Features wie Sortierten und Gruppieren sind hier möglich.</p>
				  <p><a class="btn btn-default" href="#data" role="button" onclick="$('#a_dropdown_data').dropdown();">Jump &raquo;</a></p>
				</div>
				<div class="col-6 col-sm-6 col-lg-4">
				  <h2>Charts</h2>
				  <p>Um Daten schneller zu Überblicken sind Charts sehr behilflich. Diversen Ansichten wie Bar-Diagramme oder Spidergraphs sind hier in Verwendung.</p>
				  <p><a class="btn btn-default" href="#charts" role="button">Jump &raquo;</a></p>
				</div>
				<div class="col-6 col-sm-6 col-lg-4">
				  <h2>Reports</h2>
				  <p>Speziell angefertigte Reports sind ein Kombination aus Daten, Charts und Texten. Teilweise sind diese auch in anderen Formaten wie zB. PDF verfügbar.</p>
				  <p><a class="btn btn-default" href="#reports" role="button">Jump &raquo;</a></p>
				</div>
			  </div>
		  </div>
			
		  <div id="div_filter" class="col-xs-12 col-sm-9">
			  Filter
		  </div>
		  
		  <div id="div_content">
		  </div>
		  
		  <iframe id="iframe_content" name="iframe_content" style="width:100%;min-height:400px;border:0;padding:0;margin:0;overflow: hidden;">
		  </iframe>
		  
        </div>

        <div class="col-xs-6 col-sm-3 sidebar-offcanvas" id="sidebar" role="navigation">
			<?php foreach ($statistik->result AS $gruppen): ?>
				<div id="datagroup_<?php echo str_replace(' ','',$gruppen->gruppe) ?>" class="list-group">
					<ul class="nav">
						<?php
						$data=new statistik();
						$data->getGruppe($gruppen->gruppe, true);
						foreach ($data->result AS $dat): ?>
							<li><a href="#data" data-statistik-kurzbez="<?php echo urlencode($dat->statistik_kurzbz) ?>" class="list-group-item">
								<?php echo $dat->bezeichnung ?>
							</a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
			<?php foreach ($chart->result AS $gruppen): ?>
				<div id="chartsgroup_<?php echo str_replace(' ','',$gruppen->gruppe) ?>" class="list-group">
					<ul class="nav">
						<?php
						$data=new chart();
						$data->getGruppe($gruppen->gruppe, true);
						foreach ($data->result AS $dat): ?>
							<li><a href="#" class="list-group-item" data-chart-id="<?php echo $dat->chart_id ?>">
								<?php echo $dat->title ?>
							</a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
          <div id="test" class="list-group">
			  <ul class="nav">
				<li><a href="#" onclick='$("#welcome").hide();$("#div_content").load("../vilesci/chart.php?chart_id=1");' class="list-group-item">DropOut-Spider</a></li>
				<li><a href="#" onclick='$("#welcome").hide();$("#div_content").load("../vilesci/chart.php?chart_id=2");' class="list-group-item">DropOut-Chart</a></li>
				<li><a href="#" onclick='$("#welcome").hide();$("#div_content").load("../vilesci/chart.php?chart_id=3&Studiengang=227");' class="list-group-item">Abbrecher</a></li>
            </ul>
          </div>
        </div>
      </div>
      
      
      <hr>

      <footer>
        <p>&copy; FH Technikum Wien 2014</p>
      </footer>

    </div>
	
    <script src="../include/js/bootstrap.min.js"></script>
    <script src="../include/js/offcanvas.js"></script>
    </div>
  </body>
</html>
