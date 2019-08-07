<?php
require_once('rp_dependency_overview.class.php');
require_once(dirname(__FILE__).'/../../../include/webservicelog.class.php');

/**
 * Class problemcheck_helper
 * Bereitstellung von Funktionalität zum Check von Problemen, also issues, im Reporting.
 */
class problemcheck_helper extends basis_db
{
	const WEBSERVICETYP = 'reports';
	const REPORTING_SCHEMA = 'reports';
	const TABLE_PREFIX = 'tbl_';
	const OUTLIER_MAGNITUDE = 3;
	const LAST_EXECUTED_CRITICAL_THRESHOLD = 365; // in days

	private $date_now = null;
	private $dependencyhelper = null;

	public function __construct()
	{
		parent::__construct();
		$this->date_now = new Datetime();
		$this->dependencyhelper = new dependency_overview();
	}

	/**
	 * Ersetzt Filter Parameter (beginnend mit $) in Statistik SQL mit Requestparameterwerten.
	 * @param $sql Statistik SQL
	 * @return string
	 */
	public function replaceFilterVars($sql)
	{
		foreach($_REQUEST as $name=>$value)
		{
			//$regex = '/\$'.$name.'(?![a-zA-Z0-9_-])/';
			$regex = '/\$'.$name.'\b/';
			if (is_array($value))
			{
				$in = $this->db_implode4SQL($value);
				$sql = preg_replace($regex,$in,$sql);
			}
			else
				$sql = preg_replace($regex,$this->db_add_param($value),$sql);
		}
		return $sql;
	}

	/**
	 * Findet Ausreißer in einer Datenmenge. Mittelwert und Standardabweichung werden herangezogen.
	 * Wenn Wert > MW + x * STDABW, dann gilt der Wert als Außreißer
	 * @param $dataset
	 * @return array
	 */
	public function findOutliers($dataset)
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
		); // Return filtered array of values that lie over $mean + $deviation.
	}

	/**
	 * Holt Zeit, die seit letzter Ausführung eines Reportingobjekts vergangen ist.
	 * Checkt ob es zu lange her war (critical).
	 * @param $objecttype z.B. statistik, chart
	 * @param $objectid z.B. statistik_kurzbz, chart_id
	 * @return stdClass
	 */
	public function getLastExecutedObj($objecttype, $objectid)
	{
		$interval = $this->getLastExecuted($objecttype, $objectid);

		$elapsed = '';
		if (isset($interval))
		{
			$elapsed .= $interval->y.' Jahr'.($interval->y != 1 ? 'e' : '').' ';
			$elapsed .= $interval->m.' Monat'.($interval->m != 1 ? 'e' : '').' ';
			$elapsed .= $interval->d.' Tag'.($interval->d != 1 ? 'e' : '');
			$critical = $interval->days >= self::LAST_EXECUTED_CRITICAL_THRESHOLD;
		}
		else
		{
			$elapsed .= 'Nie';
			$critical = true;
		}

		$lastexecuted = new stdClass();
		$lastexecuted->elapsed = $elapsed;
		$lastexecuted->critical = $critical;

		return $lastexecuted;
	}

	/**
	 * Prüft, ob View eine statische Tabelle enthält.
	 * @param $sql SQL der view
	 * @return string Name der statischen Tabelle oder false
	 */
	public function checkViewForStaticTables($sql)
	{
		return strstr($sql, self::REPORTING_SCHEMA.'.'.self::TABLE_PREFIX);
	}

	/**
	 * Führt explain einer SQL-Query durch. Liefert Ausführungsplan (samt benötigten Zeiten) im JSON-Format zurück.
	 * @param $query
	 * @return array|null
	 */
	public function explainQuery($query)
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
				$this->getTotalExplainplanCost($jsondata[0]->Plan, $costssum);
			}
			$explainplan['costsum'] = $costssum;
			return $explainplan;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Berechnet Quadrat der Mittelwertabweichung eines Wertes für Außreissercheck.
	 * @param $x
	 * @param $mean
	 * @return float|int
	 */
	private function sdSquare($x, $mean)
	{
		return pow($x - $mean, 2);
	}

	/**
	 * Summiert Ausführungszeit eines als Result einer EXPLAIN-Query erhaltenen Ausführungsplans.
	 * @param $explainplan
	 * @return mixed
	 */
	private function getTotalExplainplanCost($explainplan, &$sum)
	{
		$sum += $explainplan->{'Total Cost'};
		if (!isset($explainplan->Plans) || empty($explainplan->Plans))
		{
			return $sum;
		}
		foreach ($explainplan->Plans as $plan)
		{
			$this->getTotalExplainplanCost($plan, $sum);
		}
	}

	/**
	 * Liefert Zeitinterval seit letzter Ausführung eines Reportingobjekts zurück.
	 * @param $objecttype z.B. statistik, chart
	 * @param $objectid z.B. statistik_kurzbz, chart_id
	 * @return DateInterval|null
	 */
	private function getLastExecuted($objecttype, $objectid)
	{
		$lastexecutiontime = null;
		$elapsed = null;

		$qry = "SELECT execute_time FROM system.tbl_webservicelog WHERE 
			webservicetyp_kurzbz = ".$this->db_add_param(self::WEBSERVICETYP);

		if ($objecttype == 'view')
		{
			$chart_ids = array();
			$report_ids = array();
			$statistik_kurzbz_arr = $this->dependencyhelper->getStatistikenFromView($objectid);

			if (empty($statistik_kurzbz_arr))
				return null;
			else
			{
				foreach ($statistik_kurzbz_arr as $statistik_kurzbz)
				{
					$charts = $this->dependencyhelper->getChartsFromStatistik($statistik_kurzbz);
					$chart_ids = array_merge($chart_ids, $charts);
					$reports = $this->dependencyhelper->getReportsFromStatistik($objectid);
				}

				$qry .=
					" AND (
					(beschreibung = 'statistik'".
					" AND request_id IN (".$this->implode4SQL($statistik_kurzbz_arr)."))";

				if (!empty($chart_ids))
				{
					$qry .=
						" OR (beschreibung = 'chart'".
						" AND request_id IN (".$this->implode4SQL($chart_ids)."))";

					foreach ($chart_ids as $chart_id)
					{
						$reports = array_merge($reports, $this->dependencyhelper->getReportsFromChart($chart_id));
					}
				}

				foreach ($reports as $report)
				{
					$report_ids[] = $report->report_id;
				}

				if (!empty($report_ids))
				{
					$qry .=
						" OR (beschreibung = 'report'".
						" AND request_id IN (".$this->implode4SQL($report_ids)."))";
				}

				$qry .= ")";
			}
		}
		elseif ($objecttype == 'statistik')
		{
			$qry .= " AND (
				(beschreibung = ".$this->db_add_param($objecttype).
				" AND request_id = ".$this->db_add_param($objectid).")";

			$chart_ids = $this->dependencyhelper->getChartsFromStatistik($objectid);
			$reports = $this->dependencyhelper->getReportsFromStatistik($objectid);
			$report_ids = array();

			if (!empty($chart_ids))
			{
				$qry .=
					" OR (beschreibung = 'chart'".
					" AND request_id IN (".$this->implode4SQL($chart_ids)."))";

				foreach ($chart_ids as $chart_id)
				{
					$reports = array_merge($reports, $this->dependencyhelper->getReportsFromChart($chart_id));
				}
			}

			foreach ($reports as $report)
			{
				$report_ids[] = $report->report_id;
			}

			if (!empty($report_ids))
			{
				$qry .=
					" OR (beschreibung = 'report'".
					" AND request_id IN (".$this->implode4SQL($report_ids)."))";
			}

			$qry .= ")";
		}
		else
		{
			$qry .= " AND beschreibung = ".$this->db_add_param($objecttype).
				" AND request_id = ".$this->db_add_param($objectid);
		}
		$qry .= " ORDER BY execute_time DESC
				LIMIT 1;";

		if ($this->db_query($qry))
		{
			while ($row = $this->db_fetch_object())
			{
				$lastexecutiontime = $row->execute_time;
			}
		}

		if (isset($lastexecutiontime))
		{
			$lastexecutiontime = new DateTime($lastexecutiontime);
			$elapsed = $this->date_now->diff($lastexecutiontime);
		}
		return $elapsed;
	}
}
