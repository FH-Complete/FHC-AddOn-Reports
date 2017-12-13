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
 * Authors: Andreas Österreicher < oesi@technikum-wien.at >
 */
 /**
  * Create all existing Views
  */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/rp_view.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
if(!$rechte->isBerechtigt('admin'))
	die($rechte->errormsg);

$views = new view();

$views->loadAll();
foreach($views->result as $entry)
{
	echo "Creating ".$entry->view_kurzbz;
	if($entry->generateView())
		echo 'OK';
	else
		echo 'Failed';
	echo '<br>';
}
