<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/
	
	require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");
	
	FS::LoadFSModules();
	echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	$dir = opendir(dirname(__FILE__)."/../datas/mrtg-config/");
	while(($elem = readdir($dir))) {
		$fpath = dirname(__FILE__)."/../datas/mrtg-config/".$elem;
		if(is_file($fpath)) {
			echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] Do: env LANG=C /usr/bin/mrtg ".$fpath."\n";
			if(Config::getOS() == "FreeBSD")
				exec("env LANG=C /usr/local/bin/mrtg ".$fpath);
			else if(Config::getOS() == "Debian")
				exec("env LANG=C /usr/bin/mrtg ".$fpath);
		}
	}
	
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][MRTG-RRD-Refresh] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>