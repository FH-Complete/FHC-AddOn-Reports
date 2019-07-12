<?php

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../../../include/filter.class.php');
require_once('rp_chart.class.php');
require_once('rp_problemcheck_helper.class.php');
require_once('dependency_overview.class.php');

class problemcheck extends basis_db
{
	private $problemcheck_helper = null;
	private $dependency_helper = null;

	const REPORTING_SCHEMA = 'reports';
	const FILTER_TYPE_SELECT = 'select';
	const FILTER_TYPE_DATE = 'datepicker';

	private $repobjecttypes = array('view', 'statistik', 'chart');
	private $issuetexts = array();
	private $issues = array();

	/*
	* Konstruktor
	*/
	public function __construct()
	{
		parent::__construct();
		$this->setIssueTexts();
		$this->problemcheck_helper = new problemcheck_helper();
		$this->dependency_helper= new dependency_overview();
	}

	private function setIssueTexts()
	{
		// viewissues
		$this->issuetexts[$this->repobjecttypes[0]] =
			array(
				'notDefined' => "View nicht definiert - kein SQL",
				'noDependencies' => "View in keiner Statistik verwendet",
				'staticTblReference' => "Verweis auf statische Tabelle in View",
				'longExec' => "Ungewöhnlich lange Ausführungszeit"
			);

		// statistikissues
		$this->issuetexts[$this->repobjecttypes[1]] =
			array(
				'notDefined' => "Statistik nicht definiert - kein SQL und keine URL",
				'urlOnly' => "Keine Prüfung möglich - nur URL, kein SQL",
				'noBezeichnung' => "Statistik hat keine Bezeichnung",
				'filterError' => "Filter %s hat Fehler im SQL",
				'filterMissing' => "Filter %s existiert nicht",
				'longExec' => "Ungewöhnlich lange Ausführungszeit"
			);

		// chartissues
		$this->issuetexts[$this->repobjecttypes[2]] =
			array(
				'noStatistik' => "Chart mit keiner Statistik verbunden",
				'noTitle' => "Chart hat keinen title"
			);
	}

	public function getViewIssues($view_id_arr = null)
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
				$this->setError($objecttype, $view->view_id, $issuetexts['notDefined']);
			}
			else
			{
				$viewdependencies = $this->dependency_helper->getStatistikenFromView($view->view_id);
				if (empty($viewdependencies))
					$this->setWarning($objecttype, $view->view_id, $issuetexts['noDependencies']);

				if ($this->problemcheck_helper->checkViewForStaticTables($view->sql))
					$this->setError($objecttype, $view->view_id, $issuetexts['staticTblReference']);

				$explainplan = $this->problemcheck_helper->explainQuery($view->sql);

				if (isset($explainplan))
				{
					$explainplans[$index] = $explainplan;
					$analysiscostsums[$index] = $explainplan['costsum'];
				}
				else
				{
					$lastError = $this->db_last_error();
					$this->setError($objecttype, $index, $lastError);
				}
			}
		}

		$outliers = $this->problemcheck_helper->findOutliers($analysiscostsums);

		$requiredviews = array();

		if (is_array($view_id_arr))
		{
			foreach ($view_id_arr as $view_kurzbz)
			{
				$requiredviews[] = new view($view_kurzbz);
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
				$this->setWarning($objecttype, $view->view_id, $issuetexts['longExec']);

			$responseObj = $this->getResponseObj($objecttype, $view->view_id);
			$response[$idx] = $responseObj;
		}

		return json_encode($response);
	}

	public function getStatistikIssues($statistik_kurzbz_arr = null)
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

		//zuerst ALLE Statistiken durchlaufen (für Mittelwert + Standardabweichung), Fehler speichern
		foreach ($allstatistics as $statistik)
		{
			$index = $statistik->statistik_kurzbz;

			if (!isset($statistik->bezeichnung) || empty($statistik->bezeichnung))
				$this->setError($objecttype, $index, $issuetexts['noBezeichnung']);

			if ($this->setFilterParams($index))
			{
				if (!empty($statistik->sql))
				{
					$sql = $this->problemcheck_helper->replaceFilterVars($statistik->sql);
					$explainplan = $this->problemcheck_helper->explainQuery($sql);
					if (isset($explainplan))
					{
						$explainplans[$index] = $explainplan;
						$analysiscostsums[$index] = $explainplan['costsum'];
					}
					else
					{
						$lastError = $this->db_last_error();
						$this->setError($objecttype, $index,  $lastError);
					}
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

		$outliers = $this->problemcheck_helper->findOutliers($analysiscostsums);

		$requiredstatistics = array();

		//dann wenn nötig, nur verlangte Statistiken zurückgeben
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

			$responseObj = $this->getResponseObj($objecttype, $idx);
			$response[$idx] = $responseObj;
		}

		return json_encode($response);
	}

	public function getChartIssues($chart_id_arr = null)
	{
		$response = array();
		$chart = new chart();
		$objecttype = $this->repobjecttypes[2];
		$statistiktype = $this->repobjecttypes[1];
		$issuetexts = $this->issuetexts[$objecttype];

		$allcharts = array();

		if (is_array($chart_id_arr))
		{
			foreach ($chart_id_arr as $chart_id)
			{
				$allcharts[] = new chart($chart_id);
			}
		}
		else
		{
			if ($chart->loadAll())
			{
				$allcharts = $chart->result;
			}
		}

		$allchart_statistik_kurzbz = array();
		foreach ($allcharts as $chart)
		{
			$allchart_statistik_kurzbz[] = $chart->statistik_kurzbz;
		}
		//Statistik check ausführen - Probleme in Statistik sind auch Probleme im Chart
		$this->getStatistikIssues($allchart_statistik_kurzbz);

		foreach ($allcharts as $chart)
		{
			$index = $chart->title.'_'.$chart->chart_id;
			$singlechart = new chart($chart->chart_id);

			$highchartdata = null;
			$connectedobjects = array();

			if (!isset($chart->title) || empty($chart->title))
				$this->setError($objecttype, $chart->chart_id, $issuetexts['noTitle']);

			if (!isset($chart->statistik_kurzbz) || empty($chart->statistik_kurzbz))
				$this->setError($objecttype, $chart->chart_id, $issuetexts['noStatistik']);
			else
			{
				//Wenn es keine Fehler in Statistik gibt, JSON Daten für Chart holen
				if (empty($this->getIssues($statistiktype, $chart->statistik_kurzbz)))
				{
					if ($this->setFilterParams($chart->statistik_kurzbz))
						$highchartdata = $singlechart->getHighChartDataForCheck();
					$this->unsetFilterParams($chart->statistik_kurzbz);
				}
			$connectedobjects[$statistiktype] = $chart->statistik_kurzbz;
			}

			$responseObj = $this->getResponseObj(
				$objecttype,
				$chart->chart_id,
				$connectedobjects,
				$highchartdata
			);
			$response[$index] = $responseObj;
		}

		return json_encode($response);
	}

	public function getIssues($objecttype, $objectid)
	{
		if (isset($this->issues[$objecttype][$objectid]))
			return $this->issues[$objecttype][$objectid];
		else
			return array();
	}

	private function setFilterParams($statistik_kurzbz)
	{
		$statistik = new statistik($statistik_kurzbz);
		$allfilters = new filter();
		$allfilters->loadAll();
		$errcount = 0;

		$statistikobjtype = $this->repobjecttypes[1];
		$statistikissuetexts = $this->issuetexts[$statistikobjtype];

		//parse sql for param names
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

		//parse sql for param names
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

	private function setIssue($objecttype, $objectid, $issuetype, $issuetext)
	{
		if (!isset($this->issues[$objecttype]))
		{
			$this->issues[$objecttype] = array();
		}
		if (!isset($this->issues[$objecttype][$objectid]))
		{
			$this->issues[$objecttype][$objectid] = array();
		}
		$issue = new stdClass();
		$issue->type = $issuetype;
		$issue->text = $issuetext;

		$this->issues[$objecttype][$objectid][] =  $issue;
	}

	private function setWarning($objecttype, $objectid, $issuetext)
	{
		$this->setIssue($objecttype, $objectid, 'warning', $issuetext);
	}

	private function setError($objecttype, $objectid, $issuetext)
	{
		$this->setIssue($objecttype, $objectid, 'error', $issuetext);
	}

	private function getResponseObj($objecttype, $objectid, $connectedobjects = null, $data = null)
	{
		$issues = $this->getIssues($objecttype, $objectid);
		$lastexecuted = null;

		if (isset($connectedobjects) && is_array($connectedobjects))
		{
			foreach ($connectedobjects as $connobjecttype => $connobjectid)
			{
				$issues = array_merge($issues, $this->getIssues($connobjecttype, $connobjectid));
				$connectedObj = $connobjectid;
			}
		}
		$responseObj = new stdClass();
		$responseObj->objectid = $objectid;
		$responseObj->issues = $issues;
		$responseObj->data = isset($data) ? $data : array();
		$responseObj->lastExecuted = $this->problemcheck_helper->getLastExecutedObj($objecttype, $objectid);

		if (isset($connectedObj))
			$responseObj->connectedObj = $connectedObj;

		return $responseObj;
	}
}
