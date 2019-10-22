<?php
/**
 * Systemfiltersclass für system.tbl_filters. Dies sind Filter zum Speichern von Useroptionen (preferences) die nach Anzeige der Statistik angewandt werden.
 * Nicht zu verwechseln mit Statistikfiltern (public.tbl_filter) welche vor der ersten Anzeige der Statistik angewandt werden!
 * Systemfilter für Statistiken sind durch Vorhandensein einer statistikkurzbz sowie bestimmten app und dataset_name gekennzeichnet.
 */

class rp_system_filter extends basis_db
{
	public $new;
	public $result = array();

	const APP = 'reporting';
	const DATASET_NAME = 'statistik';

	//Tabellenspalten
	public $filter_id;
	public $filter_kurzbz;
	public $person_id;
	public $description;
	public $sort;
	public $default_filter;
	public $filter;
	public $oe_kurzbz;
	public $statistik_kurzbz;

	/**
	 * Konstruktor
	 * @param null $statistik_kurzbz
	 * @param null $filter_id
	 */
	public function __construct($statistik_kurzbz = null, $filter_id=null)
	{
		parent::__construct();

		if(!is_null($statistik_kurzbz))
		{
			$this->load($statistik_kurzbz, null, $filter_id);
		}
		else
		{
			$this->new=true;
		}
	}

	/**
	 * Laedt aktiven Systemfilter für eine Statistik aus DB. Wenn allgemeine und private -> zuerst allgemeine, dann private.
	 * Normalerweise max 1 default allgemeiner, max 1 default allgemeiner.
	 * @param $statistik_kurzbz
	 * @param null $person_id wenn person_id vorhanden ist -> privater Filter, ansonsten allgemeiner
	 * @param null $filter_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($statistik_kurzbz, $person_id = null, $filter_id=null)
	{
		// statistik_kurzbz prüfen
		if(!is_string($statistik_kurzbz))
		{
			$this->errormsg = 'statistik_kurzbz muss eine Zeichenkette sein';
			return false;
		}
		//Lesen der Daten aus der Datenbank
		$qry = "SELECT * FROM system.tbl_filters  
                WHERE app=".$this->db_add_param(self::APP)."
                AND dataset_name=".$this->db_add_param(self::DATASET_NAME)."
                AND statistik_kurzbz=".$this->db_add_param($statistik_kurzbz);

		if (is_numeric($filter_id))
			$qry .= ' AND filter_id = '.$this->db_add_param($filter_id, FHC_INTEGER);
		else
		{
			//default filter laden wenn keine filter_id vorhanden
			$qry .= ' AND (person_id = '.$this->db_add_param($person_id, FHC_INTEGER).' OR person_id IS NULL)';
			$qry .= ' AND default_filter = true';
		}

		$qry .= ' ORDER BY person_id NULLS FIRST, filter_id;';

		if (!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}

		while ($row = $this->db_fetch_object())
		{
			$this->filter_id	= $row->filter_id;
			$this->filter_kurzbz	= $row->filter_kurzbz;
			$this->person_id		= $row->person_id;
			$this->description		= $row->description;
			$this->sort	= $row->sort;
			$this->default_filter = $this->db_parse_bool($row->default_filter);
			$this->filter = $row->filter;
			$this->oe_kurzbz = $row->oe_kurzbz;
			$this->statistik_kurzbz		= $row->statistik_kurzbz;
		}
		$this->new=false;
		return true;
	}

	/**
	 * Liefert filter preferences als string zurück
	 * @return false|string
	 */
	public function getPreferencesString()
	{
		$result = '';

		$preferences_obj = json_decode($this->filter);
		if (isset($preferences_obj->preferences))
			$result = json_encode($preferences_obj->preferences);

		return $result;
	}

	/**
	 * Liefert filter namen als string zurück.
	 * @return mixed
	 */
	public function getFilterName()
	{
		$result = '';

		$preferences_obj = json_decode($this->filter);
		if (isset($preferences_obj->name))
			$result =  json_encode($preferences_obj->name);

		return str_replace('"', '', $result);
	}

	/**
	 * Setzt filter preferences als string.
	 * @param $preferences
	 */
	public function setPreferencesString($preferences)
	{
		$prevfilter = json_decode($this->filter);
		$preferences_json = json_decode($preferences);
		$prevfilter->preferences = $preferences_json;
		$this->filter = json_encode($prevfilter);
	}

	/**
	 * Sets einen privaten Filter als default für einen User.
	 * @param $filter_id
	 * @param $person_id
	 * @param $default_filter
	 * @return bool
	 */
	public function setDefault($filter_id, $person_id, $default_filter)
	{
		if (!is_numeric($filter_id) || !is_numeric($person_id) || !is_bool($default_filter))
			return false;

		$stastik_kurzbz = '';
		$statistikqry = "SELECT statistik_kurzbz FROM system.tbl_filters
						WHERE app='".self::APP."'
                		AND dataset_name='".self::DATASET_NAME."'
                		AND person_id = ".$this->db_add_param($person_id, FHC_INTEGER)."
                		AND filter_id = ".$this->db_add_param($filter_id, FHC_INTEGER).";";

		if ($this->db_query($statistikqry))
		{
			if ($row = $this->db_fetch_object())
			{
				$stastik_kurzbz = $row->statistik_kurzbz;
			}
		}

		if (!is_string($stastik_kurzbz) || strlen($stastik_kurzbz) <= 0)
			return false;

		$clear = true;
		if ($default_filter)
		{
			$clearqry = "UPDATE system.tbl_filters SET default_filter = false
				WHERE default_filter = true
				AND person_id = ".$this->db_add_param($person_id, FHC_INTEGER)."
				AND statistik_kurzbz = ".$this->db_add_param($stastik_kurzbz).";";

			$clear = $this->db_query($clearqry);
		}

		if ($clear)
		{
			$qry = "UPDATE system.tbl_filters SET default_filter = ".$this->db_add_param($default_filter, FHC_BOOLEAN)."
					WHERE filter_id = ".$this->db_add_param($filter_id, FHC_INTEGER).";";

			if($this->db_query($qry))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
			return false;
	}

	/**
	 * Laedt alle Systemfilter einer Statistik aus DB.
	 * @param $statistik_kurzbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadAll($statistik_kurzbz)
	{
		//Lesen der Daten aus der Datenbank
		$qry = "SELECT * FROM system.tbl_filters
				WHERE statistik_kurzbz=".$this->db_add_param($statistik_kurzbz)."
				ORDER BY person_id NULLS FIRST, filter->'name', filter_id DESC;";

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler bei einer Datenbankabfrage';
			return false;
		}
		while($row = $this->db_fetch_object())
		{
			$obj = new rp_system_filter();

			$obj->filter_id	= $row->filter_id;
			$obj->filter_kurzbz	= $row->filter_kurzbz;
			$obj->person_id		= $row->person_id;
			$obj->description		= $row->description;
			$obj->sort	= $row->sort;
			$obj->default_filter = $row->default_filter;
			$obj->filter = $row->filter;
			$obj->oe_kurzbz = $row->oe_kurzbz;
			$obj->statistik_kurzbz		= $row->statistik_kurzbz;

			$this->result[] = $obj;
		}
		return true;
	}


	/**
	 * Loescht einen privaten Filter eines Users.
	 *
	 * @param $filter_id
	 * @param $person_id
	 * @return true wenn ok, sonst false
	 */
	public function delete($filter_id, $person_id)
	{
		if (!is_numeric($filter_id) || !is_numeric($person_id))
			return false;

		$qry = "BEGIN;DELETE FROM system.tbl_filters
				WHERE app='".self::APP."'
                AND dataset_name='".self::DATASET_NAME."'
				AND filter_id=".$this->db_add_param($filter_id, FHC_INTEGER).
				" AND person_id=".$this->db_add_param($person_id, FHC_INTEGER).";";

		if($this->db_query($qry))
		{
			$this->db_query("COMMIT");
			return true;
		}
		else
		{
			$this->db_query("ROLLBACK");
			$this->errormsg='Fehler beim Löschen des Eintrages';
			return false;
		}
	}

	/**
	 * Speichert den aktuellen Datensatz in die Datenbank
	 * Wenn $new auf true gesetzt ist wird ein neuer Datensatz angelegt
	 * andernfalls wird der Datensatz mit der ID in $attribut_id aktualisiert
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$description = str_replace('%desc%', $this->getFilterName(), '{"%desc%", "%desc%", "%desc%", "%desc%"}');

			$qry='INSERT INTO system.tbl_filters (app, dataset_name, filter_kurzbz, person_id, description, sort, default_filter, filter, oe_kurzbz, statistik_kurzbz)
			VALUES('.
				$this->db_add_param(self::APP).', '.
				$this->db_add_param(self::DATASET_NAME).', '.
				$this->db_add_param(uniqid($this->person_id, true)).', '.
				$this->db_add_param($this->person_id, FHC_INTEGER).', '.
				$this->db_add_param($description).', '.
				'NULL,'.
				'FALSE,'.
				$this->db_add_param($this->filter).','.
				'NULL,'.
				$this->db_add_param($this->statistik_kurzbz).
			');';
		}
		else
		{
			$qry = 'UPDATE system.tbl_filters SET
					filter='.$this->db_add_param($this->filter).
					' WHERE app='.$this->db_add_param(self::APP).'
                	AND dataset_name='.$this->db_add_param(self::DATASET_NAME).'
                	AND person_id IS NOT NULL
					AND filter_id='.$this->db_add_param($this->filter_id).';';
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('system.tbl_filters_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->filter_id = $row->id;
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
			$this->errormsg = 'Fehler beim Speichern des Filters';
			return false;
		}
		return $this->filter_id;
	}
}
