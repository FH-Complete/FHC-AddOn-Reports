<?php
require_once(dirname(__FILE__).'/../../../include/webservicelog.class.php');


/**
 */
class problemcheck_helper extends basis_db
{
	const WEBSERVICETYP = 'reports';
	const REPORTING_SCHEMA = 'reports';
	const TABLE_PREFIX = 'tbl_';
	const OUTLIER_MAGNITUDE = 3;

	private $date_now = null;

	public function __construct()
	{
		parent::__construct();
		$this->date_now = new Datetime();
	}

	public function replaceFilterVars($sql)
	{
		foreach($_REQUEST as $name=>$value)
		{
			$regex = '/\$'.$name.'(?![a-zA-Z0-9])/';
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

	public function getLastExecutedObj($objecttype, $objectid)
	{
		$interval = $this->getLastExecuted($objecttype, $objectid);

		$elapsed = '';
		if (isset($interval))
		{
			$elapsed .= $interval->y.' Jahr'.($interval->y != 1 ? 'e' : '').' ';
			$elapsed .= $interval->m.' Monat'.($interval->m != 1 ? 'e' : '').' ';
			$elapsed .= $interval->d.' Tag'.($interval->d != 1 ? 'e' : '');
			$critical = $interval->y >= 1;
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

	public function getViewDependencies($view_id)
	{
		$dependencies = array();

		$view = new view($view_id);
		$view_kurzbz = $view->view_kurzbz;
		$static_tbl_kurzbz = $view->table_kurzbz;

		$allstatistics = new statistik();
		if ($allstatistics->getAll())
		{
			//words between word boundaries \b
			$regex_vw = '\b'.self::REPORTING_SCHEMA.'.'.$view_kurzbz.'\b';
			$regex_tbl = '\b'.self::REPORTING_SCHEMA.'.'.$static_tbl_kurzbz.'\b';

			foreach ($allstatistics->result as $statistik)
			{
				if (preg_match('/'.$regex_vw.'/', $statistik->sql) === 1 ||
					preg_match('/'.$regex_tbl.'/', $statistik->sql) === 1
				)
					$dependencies[] = $statistik->statistik_kurzbz;
			}
		}

		return $dependencies;
	}

	public function checkViewForStaticTables($sql)
	{
		return strstr($sql, self::REPORTING_SCHEMA.'.'.self::TABLE_PREFIX);
	}

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
				$costssum += $this->getTotalExplainplanCost($explainplan);
			}
			$explainplan['costsum'] = $costssum;
			return $explainplan;
		}
		else
		{
			return null;
		}
	}

	private function sdSquare($x, $mean)
	{
		return pow($x - $mean, 2);
	}

	private function getTotalExplainplanCost($explainplan)
	{
		if (isset($explainplan[0]->Plans) && !empty($explainplan[0]->Plans))
			return $explainplan[0]->{'Total Cost'} + $this->getTotalExplainplanCost($explainplan[0]->Plans);
		else
			return $explainplan[0]->{'Total Cost'};
	}

	private function getLastExecuted($objecttype, $objectid)
	{
		$lastexecutiontime = null;
		$elapsed = null;

		$qry = "SELECT execute_time FROM system.tbl_webservicelog WHERE 
			webservicetyp_kurzbz = ".$this->db_add_param(self::WEBSERVICETYP);

		if ($objecttype == 'view')
		{
			$chart_ids = array();
			$statistik_kurzbz_arr = $this->getViewDependencies($objectid);

			if (empty($statistik_kurzbz_arr))
				return null;
			else
			{
				foreach ($statistik_kurzbz_arr as $statistik_kurzbz)
				{
					$charts = $this->getChartsFromStatistik($statistik_kurzbz);
					$chart_ids = array_merge($chart_ids, $charts);
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
				}

				$qry .= ")";
			}
		}
		elseif ($objecttype == 'statistik')
		{
			$qry .= " AND (
				(beschreibung = ".$this->db_add_param($objecttype).
				" AND request_id = ".$this->db_add_param($objectid).")";

			$chart_ids = $this->getChartsFromStatistik($objectid);

			if (!empty($chart_ids))
			{
				$qry .=
					" OR (beschreibung = 'chart'".
					" AND request_id IN (".$this->implode4SQL($chart_ids)."))";
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

	private function getChartsFromStatistik($statistik_kurzbz)
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
}
