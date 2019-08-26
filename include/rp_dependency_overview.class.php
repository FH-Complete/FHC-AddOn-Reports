<?php

require_once('rp_view.class.php');
require_once(dirname(__FILE__).'/../../../include/statistik.class.php');
require_once('rp_chart.class.php');
require_once('rp_gruppe.class.php');
require_once('rp_gruppenzuordnung.class.php');
require_once('rp_problemcheck_helper.class.php');

/**
 * Class dependency_overview
 * Liefern von Daten für Reporting Abhängigkeitsübersicht
 */
class dependency_overview extends basis_db
{
	const REPORTING_SCHEMA = 'reports';

	/**
	 * Liefert Abhängigkeiten für alle Views in der Datenbank.
	 * @return array
	 */
	public function getAllViewDependencies()
	{
		$dependencies = array();
		$view = new view();

		if ($view->loadAll())
		{
			foreach ($view->result as $vw)
			{
				$dependencies[] = $this->getViewDependencies($vw->view_id);
			}
		}

		return $dependencies;
	}

	/**
	 * Liefert Abhängigkeiten, also abhängende Statistiken or Charts, für eine View.
	 * @param $view_id
	 * @return stdClass Objekt das Viewdaten und Abhängigkeiten beinhaltet
	 */
	public function getViewDependencies($view_id)
	{
		$dependencies = new stdClass();
		$view = new view($view_id);
		$dependencies->view_id = $view_id;
		$dependencies->view_kurzbz = $view->view_kurzbz;
		$dependencies->statistiken = array();

		$statistiken = $this->getStatistikenFromView($view_id);
		foreach ($statistiken as $statistik_kurzbz)
		{
			$statistik = $this->getStatistikWithCharts($statistik_kurzbz);

			$dependencies->statistiken[] = $statistik;
		}

		return $dependencies;
	}

	/**
	 * Liefert alle Abhängigkeiten (Charts, von Statistiken verwendete Views) für bestimmte Statistiken.
	 * @param $statistik_kurzbz_arr
	 * @return array
	 */
	public function getStatistikDependencies($statistik_kurzbz_arr)
	{
		$statistik_dependencies = array();

		foreach ($statistik_kurzbz_arr as $statistik_kurzbz)
		{
			$statistik = $this->getStatistikWithCharts($statistik_kurzbz);

			$views = $this->getViewsFromStatistik($statistik_kurzbz);

			// wenn keine view Abhängigkeiten, nur statistik
			if (empty($views))
			{
				$dependency = new stdClass();
				$dependency->statistiken = array();
				$dependency->statistiken[] = $statistik;
				$statistik_dependencies[] = $dependency;
			}
			else
			{
				foreach ($views as $view)
				{
					$viewfound = false;

					foreach ($statistik_dependencies as $key => $statistik_dependency)
					{
						if (isset($statistik_dependency->view_id) &&
							$statistik_dependency->view_id == $view->view_id)
						{
							$viewfound = true;
							$statistik_dependencies[$key]->statistiken[] = $statistik;
							break;
						}
					}

					if (!$viewfound)
					{
						$dependency = new stdClass();
						$dependency->view_id = $view->view_id;
						$dependency->view_kurzbz = $view->view_kurzbz;
						$dependency->statistiken = array();
						$dependency->statistiken[] = $statistik;
						$statistik_dependencies[] = $dependency;
					}
				}
			}
		}

		return $statistik_dependencies;
	}

	/**
	 * Liefert alle Statistikabhängigkeiten für eine Statistikgruppe.
	 * @param $groupname
	 * @return array
	 */
	public function getStatistikGroupDependencies($groupname)
	{
		$statistik_kurzbz_arr = array();

		if ($groupname === '')
		{
			$nogroupstatistik = $this->getStatistikenNoGroup();

			foreach ($nogroupstatistik as $statistik)
			{
				$statistik_kurzbz_arr[] = $statistik->statistik_kurzbz;
			}
		}
		else
		{
			$statistik = new statistik();
			if ($statistik->getGruppe($groupname))
			{
				foreach ($statistik->result as $singlestatistik)
				{
					$statistik_kurzbz_arr[] = $singlestatistik->statistik_kurzbz;
				}
			}
		}

		return $this->getStatistikDependencies($statistik_kurzbz_arr);
	}

	/**
	 * Liefert alle Statistikabhängigkeiten für alle Menügruppen.
	 * @return array
	 */
	public function getAllMenuGroupDependencies()
	{
		$dependencies = array();

		$reportgruppe = new rp_gruppe();
		$reportgruppe->loadAll();

		$reportgruppen = $reportgruppe->result;

		foreach ($reportgruppen as $gruppe)
		{
			$dependencies = array_merge($dependencies, $this->getMenuGroupDependencies($gruppe->reportgruppe_id));
		}

		return $dependencies;
	}

	/**
	 * Liefert alle Statistikabhängigkeiten für eine reportgruppe_id, also für eine Menügruppe.
	 * @param $reportgruppe_id
	 * @return array
	 */
	public function getMenuGroupDependencies($reportgruppe_id)
	{
		$statistiken_kurzbz_arr = array();

		$reportgruppe = new rp_gruppe();
		$reportgruppe->loadAll();

		$gruppezuordnung = new rp_gruppenzuordnung();

		if ($reportgruppe->loadGroupChildren($reportgruppe_id))
		{
			$reportgruppen = $reportgruppe->result;

			foreach ($reportgruppen as $gruppe)
			{
				if ($gruppezuordnung->loadByGruppe($gruppe->reportgruppe_id))
				{
					$gruppezuordnungen = $gruppezuordnung->result;

					foreach ($gruppezuordnungen as $zuordnung)
					{
						if (isset($zuordnung->chart_id))
						{
							$chart = new chart($zuordnung->chart_id);
							if (!in_array($chart->statistik_kurzbz, $statistiken_kurzbz_arr))
								$statistiken_kurzbz_arr[] = $chart->statistik_kurzbz;
						}
						elseif (isset($zuordnung->statistik_kurzbz))
						{
							if (!in_array($zuordnung->statistik_kurzbz, $statistiken_kurzbz_arr))
								$statistiken_kurzbz_arr[] = $zuordnung->statistik_kurzbz;
						}
						elseif (isset($zuordnung->report_id))
						{
							$reportstatistiken = $this->getStatistikenFromReport($zuordnung->report_id);
							$statistiken_kurzbz_arr = array_unique(array_merge($statistiken_kurzbz_arr, $reportstatistiken));
						}
					}
				}
			}

		}
		return $this->getStatistikDependencies($statistiken_kurzbz_arr);
	}

	/**
	 * Liefert alle Statistikabhänigkeiten die länger nicht ausgeführt wurden (wie durch Problemcheck definiert).
	 * @return array
	 */
	public function getLongerNotUsedDependencies()
	{
		$statistiken_kurzbz_arr = array();

		$statistik = new statistik();
		$problemcheck_helper = new problemcheck_helper();

		if ($statistik->getAll())
		{
			foreach ($statistik->result as $stat)
			{
				$lastexec = $problemcheck_helper->getLastExecutedObj('statistik', $stat->statistik_kurzbz);

				if ($lastexec->critical)
					$statistiken_kurzbz_arr[] = $stat->statistik_kurzbz;
			}
		}

		return $this->getStatistikDependencies($statistiken_kurzbz_arr);
	}

	/**
	 * Liefert alle Statistiken, die eine View verwenden.
	 * @param $view_id
	 * @return array
	 */
	public function getStatistikenFromView($view_id)
	{
		$statistiken = array();

		$view = new view($view_id);

		$allstatistiken = new statistik();
		if ($allstatistiken->getAll())
		{
			foreach ($allstatistiken->result as $statistik)
			{
				if ($this->checkViewUsageInStatistik($statistik->sql, $view))
					$statistiken[] = $statistik->statistik_kurzbz;
			}
		}

		return $statistiken;
	}

	/**
	 * Liefert alle in einem Report verwendete Statistiken (direkt oder über einen Chart).
	 * @param $report_id
	 * @return array
	 */
	public function getStatistikenFromReport($report_id)
	{
		$qry = "SELECT tbl_rp_report_statistik.statistik_kurzbz
				FROM addon.tbl_rp_report_statistik
				WHERE report_id=".$this->db_add_param($report_id)."
				UNION
				SELECT tbl_rp_chart.statistik_kurzbz
				FROM addon.tbl_rp_chart
				JOIN addon.tbl_rp_report_chart USING (chart_id)
				WHERE report_id=".$this->db_add_param($report_id)."
				";

		$statistik_kurzbz_arr = array();

		if ($this->db_query($qry))
		{
			while ($row = $this->db_fetch_object())
			{
				$statistik_kurzbz_arr[] = $row->statistik_kurzbz;
			}
		}

		return $statistik_kurzbz_arr;
	}

	/**
	 * Liefert in einer Statistik verwendeten Views.
	 * @param $statistik_kurzbz
	 * @return array
	 */
	public function getViewsFromStatistik($statistik_kurzbz)
	{
		$statistik = new statistik($statistik_kurzbz);

		$allviews = new view();
		$allviews->loadAll();

		$usedviews = array();

		foreach ($allviews->result as $view)
		{
			if ($this->checkViewUsageInStatistik($statistik->sql, $view))
				$usedviews[] = $view;
		}

		return $usedviews;
	}

	/**
	 * Liefert alle auf einer Statistik basierenden Charts.
	 * @param $statistik_kurzbz
	 * @return array
	 */
	public function getChartsFromStatistik($statistik_kurzbz)
	{
		$qry = "SELECT chart_id FROM addon.tbl_rp_chart
				WHERE statistik_kurzbz=".$this->db_add_param($statistik_kurzbz);

		$chart_ids = array();

		if ($this->db_query($qry))
		{
			while ($row = $this->db_fetch_object())
			{
				$chart_ids[] = $row->chart_id;
			}
		}

		return $chart_ids;
	}

	/**
	 * Liefert alle Reports, die angegebene Statistik beinhalten.
	 * @param $statistik_kurzbz
	 * @return array
	 */
	public function getReportsFromStatistik($statistik_kurzbz)
	{
		$qry = "SELECT tbl_rp_report.report_id, tbl_rp_report.title
				FROM addon.tbl_rp_report_statistik
				JOIN addon.tbl_rp_report USING (report_id)
				WHERE statistik_kurzbz =".$this->db_add_param($statistik_kurzbz);

		$reports = array();

		if ($this->db_query($qry))
		{
			while ($row = $this->db_fetch_object())
			{
				$report = new stdClass();
				$report->report_id = $row->report_id;
				$report->title = $row->title;
				$reports[] = $report;
			}
		}

		return $reports;
	}

	/**
	 * Liefert alle Reports, die angegebenen Chart beinhalten.
	 * @param $chart_id
	 * @return array
	 */
	public function getReportsFromChart($chart_id)
	{
		$qry = "SELECT tbl_rp_report.report_id, tbl_rp_report.title
				FROM addon.tbl_rp_report_chart
				JOIN addon.tbl_rp_report USING (report_id)
				WHERE chart_id=".$this->db_add_param($chart_id);

		$reports = array();

		if ($this->db_query($qry))
		{
			while ($row = $this->db_fetch_object())
			{
				$report = new stdClass();
				$report->report_id = $row->report_id;
				$report->title = $row->title;
				$reports[] = $report;
			}
		}

		return $reports;
	}

	/**
	 * Laedt alle Statistiken ohne Gruppe, Parameter publish zum Filtern.
	 * @param $publish
	 * @return array|null
	 */
	public function getStatistikenNoGroup($publish=null)
	{
		$statistiken = array();

		$qry = "SELECT * FROM public.tbl_statistik WHERE gruppe IS NULL OR gruppe = ''";
		if ($publish===true)
			$qry.=' AND publish ';
		elseif ($publish===false)
			$qry.=' AND NOT publish ';
		$qry.=' ORDER BY bezeichnung;';

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new statistik();

				$obj->statistik_kurzbz = $row->statistik_kurzbz;
				$obj->content_id = $row->content_id;
				$obj->bezeichnung = $row->bezeichnung;
				$obj->url = $row->url;
				$obj->sql = $row->sql;
				$obj->gruppe = $row->gruppe;
				$obj->publish = $this->db_parse_bool($row->publish);
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->udpatevon = $row->updatevon;
				$obj->berechtigung_kurzbz = $row->berechtigung_kurzbz;
				$obj->preferences = $row->preferences;

				$statistiken[] = $obj;
			}

			return $statistiken;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return null;
		}
	}

	/**
	 * Liefert Statistik und alle auf ihr basierende Charts.
	 * @param $statistik_kurzbz
	 * @return stdClass
	 */
	private function getStatistikWithCharts($statistik_kurzbz)
	{
		$statistik = new stdClass();
		$statistik->statistik_kurzbz = $statistik_kurzbz;
		$statistik->charts = array();
		$statistik->reports = $this->getReportsFromStatistik($statistik_kurzbz);

		$chart_ids = $this->getChartsFromStatistik($statistik_kurzbz);

		foreach ($chart_ids as $chart_id)
		{
			$chartdata = new chart($chart_id);
			$chart = new stdClass();
			$chart->chart_id = $chart_id;
			$chart->title = $chartdata->title;

			$chart->reports = $this->getReportsFromChart($chart_id);
			$statistik->charts[] = $chart;
		}

		return $statistik;
	}

	/**
	 * Prüft, ob View in einer Statistik verwendet wird,
	 * also ob der Name der View oder die aus der View generierte statische Tabelle im Statistik-SQL vorkommen.
	 * @param $statistik_sql
	 * @param $view
	 * @return bool
	 */
	private function checkViewUsageInStatistik($statistik_sql, $view)
	{
		$view_kurzbz = $view->view_kurzbz;
		$static_tbl_kurzbz = $view->table_kurzbz;

		//Wörter zwischen word boundaries \b. Word characters sind Zahlen, Buchstaben oder Unterstrich, alle anderen Zeichen sind word boundaries.
		$regex_vw = '\b'.self::REPORTING_SCHEMA.'.'.$view_kurzbz.'\b';
		$regex_tbl = '\b'.self::REPORTING_SCHEMA.'.'.$static_tbl_kurzbz.'\b';

		return (preg_match('/'.$regex_vw.'/', $statistik_sql) === 1 ||
			preg_match('/'.$regex_tbl.'/', $statistik_sql) === 1);
	}
}
