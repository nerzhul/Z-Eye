<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	require_once(dirname(__FILE__)."/../modules/user_modules/switches/cisco.func.php");
	
	function doSwitchBackup() {
		$query = FS::$pgdbMgr->Select("z_eye_save_device_servers","addr,type,path,login,pwd");
		while($data = pg_fetch_array($query)) {
			if(!FS::$secMgr->isIP($data["addr"]))
				continue;
				
			$query2 = FS::$pgdbMgr->Select("device","ip,name");
			while($data2 = pg_fetch_array($query2)) {
				if($data["type"] == 1)
					$copyId = exportConfigToTFTP($data2["name"],$data["addr"],$data["path"]."conf-".$data2["name"]);
				else if($data["type"] == 2 || $data["type"] == 4 || $data["type"] == 5)
					$copyId = exportConfigToAuthServer($data2["name"],$data["addr"],$data["type"],$data["path"]."conf-".$data2["name"],$data["login"],$data["pwd"]);
				
				sleep(1);
				$copyState = getCopyState($data2["name"],$copyId);
				while($copyState == 2) {
					sleep(1);
					$copyState = getCopyState($data2["name"],$copyId);
				}
				
				if($copyState == 4) {
					$copyErr = getCopyError($data2["name"],$copyId);
					echo "Backup fail for device ".$data2["name"]." (reason: ";
					switch($copyErr) {
						case 2: echo "bad filename/path/rights"; break;
						case 3: echo "timeout"; break;
						case 4: echo "no memory available"; break;
						case 5: echo "config error"; break;
						case 6: echo "unsupported protocol"; break;
						case 7:	echo "config apply fail"; break;
						default: echo "unknown"; break;
					}
					echo ")\n";
				}
				else
					echo "Backup success for device ".$data2["name"]."\n";
			}
		}
	}
	
	FS::LoadFSModules();
	
	echo "[".Config::getWebsiteName()."][Switch-Backup] started at ".date('d-m-Y G:i:s')."\n";
	$start_time = microtime(true);
	
	doSwitchBackup();
	
	$end_time = microtime(true);
	$script_time = $end_time - $start_time;
	echo "[".Config::getWebsiteName()."][Switch-Backup] done at ".date('d-m-Y G:i:s')." (Execution time: ".$script_time."s)\n";

?>
