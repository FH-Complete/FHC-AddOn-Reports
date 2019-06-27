<?php

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once('rp_view.class.php');
require_once(dirname(__FILE__).'/../../../include/statistik.class.php');
require_once(dirname(__FILE__).'/../../../include/filter.class.php');
require_once('rp_chart.class.php');

class problemcheck extends basis_db
{
	const REPORTING_SCHEMA = 'reports';
	//const VIEW_PREFIX = 'vw_';
	const TABLE_PREFIX = 'tbl_';
	const OUTLIER_MAGNITUDE = 3;
	const FILTER_TYPE_SELECT = 'select';
	const FILTER_TYPE_DATE = 'datepicker';

	private $repobjecttypes = array('view', 'statistik');
	private $issuetexts = array();
	private $issues = array();

	/*
	* Konstruktor
	*/
	public function __construct()
	{
		parent::__construct();
		$this->setIssueTexts();
	}

	private function setIssueTexts()
	{
		// view
		$this->issuetexts[$this->repobjecttypes[0]] =
			array(
				'notDefined' => "View nicht definiert - kein SQL",
				'noDependencies' => "View in keiner Statistik verwendet",
				'staticTblReference' => "Verweis auf statische Tabelle in View",
				'longExec' => "Ungewöhnlich lange Ausführungszeit"
			);

		// statistikerrors
		$this->issuetexts[$this->repobjecttypes[1]] =
			array(
				'notDefined' => "Statistik nicht definiert - kein SQL und keine URL",
				'urlOnly' => "Keine Prüfung möglich - nur URL, kein SQL",
				'filterError' => "Filter %s hat Fehler im SQL",
				'filterMissing' => "Filter %s existiert nicht",
				'longExec' => "Ungewöhnlich lange Ausführungszeit"
			);
	}

	public function getViewData($view_id_arr = null)
	{
		$response = array();
		$objecttype = $this->repobjecttypes[0];
		$issuetexts = $this->issuetexts[$objecttype];

		$explainplans = array();
		$analysiscostsums = array();

		$allviews = new view();
		if ($allviews->loadAll())
		{
			$allviews = $allviews->result;
		}

		foreach ($allviews as $view)
		{
			$index = $view->view_kurzbz.'_'.$view->view_id;

			if (empty($view->sql))
			{
				$this->setError($objecttype, $index, $issuetexts['notDefined']);
			}
			else
			{
				$viewdependencies = $this->getViewDependencies($view->view_id);
				if (empty($viewdependencies))
					$this->setWarning($objecttype, $index, $issuetexts['noDependencies']);

				if (strstr($view->sql, self::REPORTING_SCHEMA.'.'.self::TABLE_PREFIX))
					$this->setError($objecttype, $index, $issuetexts['staticTblReference']);

				$explainplan = $this->explainQuery($objecttype, $index, $view->sql);

				$explainplans[$index] = $explainplan;
				$analysiscostsums[$index] = $explainplan['costsum'];
			}
		}

		$outliers = $this->findOutliers($analysiscostsums);

		$requiredviews = array();

		if (is_array($view_id_arr))
		{
			foreach ($view_id_arr as $view_kurzbz)
			{
				$requiredviews[] = new statistik($view_kurzbz);
			}
		}
		else
		{
			$requiredviews = $allviews;
		}

		foreach ($requiredviews as $view)
		{
			$idx = $view->view_kurzbz.'_'.$view->view_id;

			if (isset($outliers[$idx]))
				$this->setWarning($objecttype, $idx, $issuetexts['longExec']);

			$responseObj = $this->getResponseObj($objecttype, $view->view_id, $idx);
			$response[$idx] = $responseObj;
		}

		return json_encode($response);
	}

	public function getStatistikData($statistik_kurzbz_arr = null)
	{
		$response = array();
		$objecttype = $this->repobjecttypes[1];
		$issuetexts = $this->issuetexts[$objecttype];

		$explainplans = array();
		$analysiscostsums = array();
		$allstatistics = new statistik();
		if ($allstatistics->getAll('statistik_kurzbz'))
		{
			$allstatistics = $allstatistics->result;
		}

		foreach ($allstatistics as $statistik)
		{
			$index = $statistik->statistik_kurzbz;

			if ($this->setFilterParams($index))
			{
				if (!empty($statistik->sql))
				{
					$sql = $this->replaceFilterVars($statistik->sql);
					$explainplan = $this->explainQuery($objecttype, $index, $sql);
					$explainplans[$index] = $explainplan;
					$analysiscostsums[$index] = $explainplan['costsum'];
				}
				else
				{
					if (empty($statistik->url))
						$this->setError($objecttype, $index, $issuetexts['notDefined']);
					else
						$this->setWarning($objecttype, $index, $issuetexts['urlOnly']);
				}
			}
			//Filterparameter aus REQUEST variable entfernen zur Vermeidung von Konflikten
			$this->unsetFilterParams($index);
		}

		$outliers = $this->findOutliers($analysiscostsums);

		$requiredstatistics = array();

		if (is_array($statistik_kurzbz_arr))
		{
			foreach ($statistik_kurzbz_arr as $statistik_kurzbz)
			{
				$requiredstatistics[] = new statistik($statistik_kurzbz);
			}
		}
		else
		{
			$requiredstatistics = $allstatistics;
		}

		foreach ($requiredstatistics as $statistik)
		{
			$idx = $statistik->statistik_kurzbz;

			if (isset($outliers[$idx]))
				$this->setWarning($objecttype, $idx, $issuetexts['longExec']);

			$responseObj = $this->getResponseObj($objecttype, $idx, $idx);
			$response[$idx] = $responseObj;
		}

		return json_encode($response);
	}

	public function getChartData()
	{
		$response = array();
		$chart = new chart();
		$objecttype = $this->repobjecttypes[1];

		if ($chart->loadAll())
		{
			$allcharts = $chart->result;

			$allchart_kurzbz_array = array();
			foreach ($allcharts as $chart)
			{
				$allchart_kurzbz_array = $chart->statistik_kurzbz;
			}
			$this->getStatistikData($allchart_kurzbz_array);

			foreach ($allcharts as $chart)
			{
				$index = $chart->title.'_'.$chart->chart_id;
				$singlechart = new chart($chart->chart_id);

				$highchartdata = null;

				//Wenn es keine Fehler in Statistik gibt, JSON Daten für Chart holen
				if (empty($this->getIssues($objecttype, $chart->statistik_kurzbz)))
				{
					$highchartdata = $singlechart->getHighChartDataForCheck();
				}
				$responseObj = $this->getResponseObj($objecttype, $chart->chart_id, $chart->statistik_kurzbz, $highchartdata, $chart->statistik_kurzbz);
				$response[$index] = $responseObj;
			}
		}
		return json_encode($response);
	}

	public function getViewDependencies($view_id)
	{
		$dependencies = array();

		$view = new view($view_id);
		$view_kurzbz = $view->view_kurzbz;
		$static_tbl_kurzbz = $view->table_kurzbz;

		$allstatistics = new statistik();
		$allstatistics->getAll();

		foreach ($allstatistics->result as $statistik)
		{
			if (strstr($statistik->sql, self::REPORTING_SCHEMA.'.'.$view_kurzbz)
				|| strstr($statistik->sql, self::REPORTING_SCHEMA.'.'.$static_tbl_kurzbz))
				$dependencies[] = $statistik->statistik_kurzbz;
		}

		return $dependencies;
	}

	public function getIssues($objecttype, $objectname)
	{
		if (isset($this->issues[$objecttype][$objectname]))
			return $this->issues[$objecttype][$objectname];
		else
			return array();
	}

	private function replaceFilterVars($sql)
	{
		foreach($_REQUEST as $name=>$value)
		{
			if (is_array($value))
			{
				$in = $this->db_implode4SQL($value);
				$sql = str_replace('$'.$name,$in,$sql);
			}
			else
				$sql = str_replace('$'.$name,$this->db_add_param($value),$sql);
		}
		return $sql;
	}

	private function setFilterParams($statistik_kurzbz)
	{
		$statistik = new statistik($statistik_kurzbz);
		$allfilters = new filter();
		$allfilters->loadAll();
		$errcount = 0;

		$statistikobjtype = $this->repobjecttypes[1];
		$statistikissuetexts = $this->issuetexts[$statistikobjtype];

		// parse sql for param names
		$vars = $statistik->parseVars($statistik->sql);

		foreach ($vars as $var)
		{
			$found = false;
			foreach ($allfilters->result as $filter)
			{
				if ($filter->kurzbz == $var)
				{
					if ($filter->type == self::FILTER_TYPE_SELECT)
					{
						if (@$this->db_query($filter->sql))
						{
							while ($row = $this->db_fetch_assoc())
							{
								$_REQUEST[$filter->kurzbz] = $row['value'];
								break;
							}
						}
						else
						{
							$this->setError($this->repobjecttypes[1], $statistik_kurzbz, sprintf($statistikissuetexts['filterError'], $var));
							$errcount++;
						}
					}
					elseif ($filter->type == self::FILTER_TYPE_DATE)
					{
						$_REQUEST[$filter->kurzbz] = date('Y-m-d');
					}
					$found = true;
					break;
				}
			}
			if (!$found)
			{
				$this->setWarning($this->repobjecttypes[1], $statistik_kurzbz, sprintf($statistikissuetexts['filterMissing'], $var));
				$errcount++;
			}
		}

		if ($errcount > 0)
			return false;
		return true;
	}

	private function unsetFilterParams($statistik_kurzbz)
	{
		$statistik = new statistik($statistik_kurzbz);
		$allfilters = new filter();
		$allfilters->loadAll();

		// parse sql for param names
		$vars = $statistik->parseVars($statistik->sql);

		foreach ($vars as $var)
		{
			foreach ($allfilters->result as $filter)
			{
				if ($filter->kurzbz == $var)
				{
					unset($_REQUEST[$filter->kurzbz]);
					break;
				}
			}
		}
	}

	private function explainQuery($objecttype, $objectname, $query)
	{
		$statistikresult = @$this->db_query('EXPLAIN (FORMAT JSON) '.$query);
		$explainplan = array();
		$costssum = 0;

		if ($statistikresult)
		{
			while ($row = $this->db_fetch_assoc())
			{
				$jsondata = json_decode($row['QUERY PLAN']);
				$explainplan[] = $jsondata[0]->Plan;
				$costssum += $this->getTotalExplainplanCost($explainplan);
			}
			$explainplan['costsum'] = $costssum;
			return $explainplan;
		}
		else
		{
			$lastError = $this->db_last_error();
			$this->setError($objecttype, $objectname,  $lastError);
			return null;
		}
	}

	private function getTotalExplainplanCost($explainplan)
	{
		if (isset($explainplan[0]->Plans) && !empty($explainplan[0]->Plans))
			return $explainplan[0]->{'Total Cost'} + $this->getTotalExplainplanCost($explainplan[0]->Plans);
		else
			return $explainplan[0]->{'Total Cost'};
	}

	private function setIssue($objecttype, $objectname, $issuetype, $issuetext)
	{
		if (!isset($this->issues[$objecttype]))
		{
			$this->issues[$objecttype] = array();
		}
		if (!isset($this->issues[$objecttype][$objectname]))
		{
			$this->issues[$objecttype][$objectname] = array();
		}
		$issue = new stdClass();
		$issue->type = $issuetype;
		$issue->text = $issuetext;

		$this->issues[$objecttype][$objectname][] =  $issue;
	}

	private function setWarning($objecttype, $objectname, $issuetext)
	{
		$this->setIssue($objecttype, $objectname, 'warning', $issuetext);
	}

	private function setError($objecttype, $objectname, $issuetext)
	{
		$this->setIssue($objecttype, $objectname, 'error', $issuetext);
	}

	private function getResponseObj($objecttype, $objectid, $objectname, $data = null, $conncectedObj = null)
	{
		$responseObj = new stdClass();
		$responseObj->objectid = $objectid;
		$responseObj->issues = $this->getIssues($objecttype, $objectname);
		$responseObj->data = isset($data) ? $data : array();
		if (isset($conncectedObj))
			$responseObj->connectedObj = $conncectedObj;

		return $responseObj;
	}

	private function findOutliers($dataset)
	{
		$count = count($dataset);
		$mean = array_sum($dataset) / $count; // Calculate the mean
		$deviation = sqrt(array_sum(array_map(array($this, "sdSquare"), $dataset, array_fill(0, $count, $mean))) / $count) * self::OUTLIER_MAGNITUDE; // Calculate standard deviation and times by magnitude

		return array_filter(
			$dataset,
			function ($x) use ($mean, $deviation)
			{
				return ($x >= $mean + $deviation);
			}
		); // Return filtered array of values that lie within $mean +- $deviation.
	}

	private function sdSquare($x, $mean) {
		return pow($x - $mean, 2);
	}
}
