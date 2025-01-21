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
 * Authors: Andreas Moik <moik@technikum-wien.at>
 */
/**
 * Klasse zur Verwaltung des zugriffs auf den PhantomJS Server
 */
require_once(dirname(__FILE__).'/../reports.config.inc.php');
require_once(dirname(__FILE__).'/../../../include/functions.inc.php');

class phantom
{
	public $errormsg;


	/**
	 * Konstruktor
	 */
	public function __construct()
	{
	}

	/**
	 * Erwartet einen JSON String, welcher die Daten für PhantomJS enthält. Diese werden gerendert(standardmäßig zu png) und zurückgeliefert
	 *
	 * @param $data JSON string
	 * @return string liefert Bilddaten in base64 kodiert zurück
	 */
	public function render($data)
	{
		$json = json_encode($data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, constant('PHANTOM_SERVER'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, numberOfElements($json));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		$ret = curl_exec($ch);
		curl_close($ch);

		return $ret;
	}
}
