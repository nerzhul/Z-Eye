<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	
	echo "[".Config::getWebsiteName()."][MRTG-Discover] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	$file = fopen(dirname(__FILE__)."/../datas/tmp/snort");
	if($file) {
		$filedata = fread($file,filesize(dirname(__FILE__)."/../datas/tmp/snort"));
		if($filedata == "1")
			exec("service snort restart");
		else
			echo "[WARN] Invalid data into ".dirname(__FILE__)."/../datas/tmp/snort file !";
		fclose($file);
	}	
	
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][MRTG-Discover] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";
?>
