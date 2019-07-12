<?php

require_once('rp_view.class.php');
require_once(dirname(__FILE__).'/../../../include/statistik.class.php');
require_once('rp_chart.class.php');

/**
 */
class dependency_overview extends basis_db
{
	const REPORTING_SCHEMA = 'reports';

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

	public function getStatistikDependencies($statistik_kurzbz_arr)
	{
		$statistik_dependencies = array();

		foreach ($statistik_kurzbz_arr as $statistik_kurzbz)
		{
			$statistik = $this->getStatistikWithCharts($statistik_kurzbz);

			$views = $this->getViewsFromStatistik($statistik_kurzbz);

			//var_dump($views);

			if (empty($views))
			{
				$dependency = new stdClass();
				$dependency->statistiken = array();
				$dependency->statistiken[] = $statistik;
				$statistik_dependencies[] = $dependency;
			}
			else
			{
				//var_dump($views);
				foreach ($views as $view)
				{
					$viewfound = false;

					foreach ($statistik_dependencies as $key => $statistik_dependency)
					{
						/*if (!isset($statistik_dependency->view_id))
							var_dump($statistik_dependency);*/

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

		//
		//var_dump($statistik_dependencies);

		return $statistik_dependencies;
	}

	public function getGroupDependencies($groupname)
	{
		$statistik_kurzbz_arr = array();
		$statistik = new statistik();
		$statistik->getGruppe($groupname);

		foreach ($statistik->result as $singlestatistik)
		{
			$statistik_kurzbz_arr[] = $singlestatistik->statistik_kurzbz;
		}

		return $this->getStatistikDependencies($statistik_kurzbz_arr);
	}

	public function getStatistikenFromView($view_id)
	{
		$statistiken = array();

		$view = new view($view_id);

		$allstatistiken = new statistik();
		if ($allstatistiken->getAll())
		{
			foreach ($allstatistiken->result as $statistik)
			{
				if ($this->checkViewUsageInStatistik($statistik->sql, $view)
				)
					$statistiken[] = $statistik->statistik_kurzbz;
			}
		}

		return $statistiken;
	}

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

		//var_dump($usedviews);

		return $usedviews;
	}

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

	private function checkViewUsageInStatistik($statistik_sql, $view)
	{
		$view_kurzbz = $view->view_kurzbz;
		$static_tbl_kurzbz = $view->table_kurzbz;

		//words between word boundaries \b. word characters are numbers, letters or underscore
		$regex_vw = '\b'.self::REPORTING_SCHEMA.'.'.$view_kurzbz.'\b';
		$regex_tbl = '\b'.self::REPORTING_SCHEMA.'.'.$static_tbl_kurzbz.'\b';

		return (preg_match('/'.$regex_vw.'/', $statistik_sql) === 1 ||
			preg_match('/'.$regex_tbl.'/', $statistik_sql) === 1);
	}

	private function getStatistikWithCharts($statistik_kurzbz)
	{
		$statistik = new stdClass();
		$statistik->statistik_kurzbz = $statistik_kurzbz;
		$statistik->charts = array();

		$chart_ids = $this->getChartsFromStatistik($statistik_kurzbz);

		foreach ($chart_ids as $chart_id)
		{
			$chartdata = new chart($chart_id);
			$chart = new stdClass();
			$chart->chart_id = $chart_id;
			$chart->title = $chartdata->title;
			$statistik->charts[] = $chart;
		}

		return $statistik;
	}
}
