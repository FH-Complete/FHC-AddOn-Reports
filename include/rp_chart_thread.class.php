<?php
/* Copyright (C) 2016 fhcomplete.org
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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */

require_once("rp_chart.class.php");

class ChartThread extends Thread
{
	private $chart_id;
	private $chart;
	private $targetDir;
	public $outputfilename;
	public $errormsg;

	public function __construct($workerId, $chart, $targetDir)
	{
		$this->chart_id = $chart->chart_id;
		$this->chart = $chart;
		$this->targetDir = $targetDir;
		$this->outputfilename = false;
		$this->errormsg = "";
	}

	public function run()
	{
		$this->outputfilename=$chart->writePNG($reportsTmpDir);
	}

	public function getOutputfilename()
	{
		return $this->outputfilename;
	}
}

?>
