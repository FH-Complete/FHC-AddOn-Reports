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
 * Authors: Andreas Moik           < moik@technikum-wien.at >.
 */

$error = "";



foreach(getAllReportFolders() as $report)
{
	if(!recurseRmdir(sys_get_temp_dir() .  "/" . $report))
		$error = "remove Failed";
}




if($error == "")
{
	echo "true";
}
else
{
	echo $error;
}






function getAllReportFolders()
{
	$ffs = scandir(sys_get_temp_dir());
	$list = array();
	foreach ( $ffs as $ff )
	{
		$timestampPath = sys_get_temp_dir() . "/" . $ff . "/timestamp";

		if($ff != '.'
		&& $ff != '..'
		&& strlen($ff) == 21	// report folders are always createt with "reports_[UNIQID]"(=21 chars)
		&& substr($ff, 0, 8) == "reports_"
		)
		{
			/* if there is no timestamp file, or it is empty (old versions) */
			if(!file_exists($timestampPath) || filesize($timestampPath) < 1)
			{
				$list[] = $ff;
			}
			else
			{
				$now = new DateTime();
				$timestampFile = fopen($timestampPath, "r");
				$timestamp = fread($timestampFile, filesize($timestampPath));
				fclose($timestampFile);
				if($now->getTimestamp() - $timestamp > 60 * 60 * 2) /* older than 2 hours */
					$list[] = $ff;// is to delete
			}
		}
	}
	return $list;
}

function recurseRmdir($dir)
{
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file)
	{
		(is_dir("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
	}
	return rmdir($dir);
}

?>
