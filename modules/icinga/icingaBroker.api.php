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

	class icingaBroker {
		public function readStates($filter = array()) {
			$stateFile = file("/var/spool/icinga/status.dat");
			if(!$stateFile)
				return NULL;

			$stateBuf = array(); 
			$cursor = "";
			$curentry = "";
			$curentrydesc = "";
			$matchBuf = "";
			$filterC = count($filter);

			for($i=0;$i<count($stateFile);$i++) {
				if(preg_match("/^#/",$stateFile[$i]) || $stateFile[$i] == "")
					continue;

				// if header, we pos cursor and create the array for object attrs
				if(preg_match("#^(.*) {#",$stateFile[$i],$matchBuf)) {
					if($matchBuf[1] != "info" && $matchBuf[1] != "programstatus" && $matchBuf[1] != "contactstatus") {
						$cursor = $matchBuf[1];
					}
				}
				else if(preg_match("#^\t}#",$stateFile[$i])) {
					$cursor = "";
					$curentry = "";
					$curentrydesc = "";
				}

				if($cursor) {
					// if we found hostname we set entry cursor and create the array for the object attrs and the host and service arrays
					if(preg_match("#^\thost_name=(.*)\n#",$stateFile[$i],$matchBuf)) {
						$curentry = $matchBuf[1];
						if(!isset($stateBuf[$curentry]))
							$stateBuf[$curentry] = array();
						if(!isset($stateBuf[$curentry][$cursor]))
							$stateBuf[$curentry][$cursor] = array();
					}
					// we parse other attributes
					else if(preg_match("#^\t([\w]+)=(.*)\n#",$stateFile[$i],$matchBuf)) {
						// if we are in servicestatus object
						if($cursor == "servicestatus") {
							// if we found a service_description, we must create the service buffer
							if($matchBuf[1] == "service_description") {
								$curentrydesc = $matchBuf[2];
								$stateBuf[$curentry][$cursor][$curentrydesc] = array();
							}
							else if($curentry && $curentrydesc) {
								// filtering
								if($filterC == 0 || in_array($matchBuf[1],$filter))
									$stateBuf[$curentry][$cursor][$curentrydesc][$matchBuf[1]] = $matchBuf[2];
							}
						}
						else if($cursor == "hoststatus") {
							// filtering
							if($filterC == 0 || in_array($matchBuf[1],$filter))
								$stateBuf[$curentry][$cursor][$matchBuf[1]] = $matchBuf[2];
						}
					}
				}
			}
			ksort($stateBuf);
			return $stateBuf;
		}

		public function writeConfiguration() {
			$path = dirname(__FILE__)."/../../datas/icinga-config/";
				
			/*
			 *  Write commands
			 */
			 
			$file = fopen($path."commands.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd");
			while($data = FS::$dbMgr->Fetch($query))
				fwrite($file,"define command {\n\tcommand_name\t".$data["name"]."\n\tcommand_line\t".$data["cmd"]."\n}\n\n");
			
			fclose($file);
			
			/*
			 *  Write contact & contactgroups
			 */
			 
			$file = fopen($path."contacts.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,srvperiod,srvcmd,hostperiod,hostcmd,hoptd,hoptu,hoptr,hoptf,hopts,soptc,soptw,soptu,soptr,soptf,sopts","template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define contact {\n\tcontact_name\t".$data["name"]."\n\tservice_notification_period\t".$data["srvperiod"]."\n\thost_notification_period\t".$data["hostperiod"]."\n\t");
				fwrite($file,"service_notification_commands\t".$data["srvcmd"]."\n\thost_notification_commands\t".$data["hostcmd"]."\n\temail\t".$data["mail"]."\n\t");
				
				$found = false;
				if($data["hoptd"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"d");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptu"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptr"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptf"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hopts"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"s");
					$found = true;
				}
				
				$found = false;
				if($data["soptc"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"c");
					$found = true;
				}
				
				if($found) fwrite($file,",");
				if($data["soptw"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"w");
					$found = true;
				}
				
				if($found) fwrite($file,",");
				if($data["soptu"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				
				if($data["soptr"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				
				if($data["soptf"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["sopts"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"s");
				}
				fwrite($file,"\n}\n\n");
			}
			
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define contactgroup {\n\tcontactgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]."\n\tmembers\t");
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","member","name = '".$data["name"]."'");
				$found = false;
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($found) fwrite($file,",");
					else $found = true;
					fwrite($file,$data2["member"]);
				}
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 *  Timeperiods
			 */
			 
			$file = fopen($path."timeperiods.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define timeperiod {\n\ttimeperiod_name\t".$data["name"]."\n\talias\t".$data["alias"]);
				if(strtotime($data["mhs"].":".$data["mms"]) < strtotime($data["mhe"].":".$data["mme"]))
					fwrite($file,"\n\tmonday\t".$data["mhs"].":".$data["mms"]."-".$data["mhe"].":".$data["mme"]);
				if(strtotime($data["tuhs"].":".$data["tums"]) < strtotime($data["tuhe"].":".$data["tume"]))
					fwrite($file,"\n\ttuesday\t".$data["tuhs"].":".$data["tums"]."-".$data["tuhe"].":".$data["tume"]);
				if(strtotime($data["whs"].":".$data["wms"]) < strtotime($data["whe"].":".$data["wme"]))
					fwrite($file,"\n\twednesday\t".$data["whs"].":".$data["wms"]."-".$data["whe"].":".$data["wme"]);
				if(strtotime($data["thhs"].":".$data["thms"]) < strtotime($data["thhe"].":".$data["thme"]))
					fwrite($file,"\n\tthursday\t".$data["thhs"].":".$data["thms"]."-".$data["thhe"].":".$data["thme"]);
				if(strtotime($data["fhs"].":".$data["fms"]) < strtotime($data["fhe"].":".$data["fme"]))
					fwrite($file,"\n\tfriday\t".$data["fhs"].":".$data["fms"]."-".$data["fhe"].":".$data["fme"]);
				if(strtotime($data["sahs"].":".$data["sams"]) < strtotime($data["sahe"].":".$data["same"]))
					fwrite($file,"\n\tsaturday\t".$data["sahs"].":".$data["sams"]."-".$data["sahe"].":".$data["same"]);
				if(strtotime($data["suhs"].":".$data["sums"]) < strtotime($data["suhe"].":".$data["sume"]))
					fwrite($file,"\n\tsunday\t".$data["suhs"].":".$data["sums"]."-".$data["suhe"].":".$data["sume"]);
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 *  Write hosts
			 */
			 
			$file = fopen($path."hosts.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,
			perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,iconid","template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define host {\n\thost_name\t".$data["name"]."\n\talias\t".$data["alias"]."\n\tdisplay_name\t".$data["dname"]."\n\taddress\t".$data["addr"]."\n\tcheck_command\t");
				fwrite($file,$data["alivecommand"]."\n\tcheck_period\t".$data["checkperiod"]."\n\tcheck_interval\t".$data["checkinterval"]."\n\tretry_interval\t".$data["retrycheckinterval"]."\n\t");
				fwrite($file,"max_check_attempts\t".$data["maxcheck"]."\n\tevent_handler_enabled\t".($data["eventhdlen"] == "t" ? 1 : 0)."\n\tflap_detection_enabled\t".($data["flapen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tprocess_perf_data\t".($data["perfdata"] == "t" ? 1 : 0)."\n\tretain_status_information\t");
				fwrite($file,($data["retstatus"] == "t" ? 1 : 0)."\n\tretain_nonstatus_information\t".($data["retnonstatus"] == "t" ? 1 : 0)."\n\tnotifications_enabled\t".($data["notifen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tnotification_period\t".$data["notifperiod"]."\n\tnotification_interval\t".$data["notifintval"]."\n\t");
				
				$found = false;
				if($data["hostoptd"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"d");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptu"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptr"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptf"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostopts"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"s");
				}
				
				fwrite($file,"\n\tcontact_groups\t".$data["contactgroup"]);
				
				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tparents\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["parent"]);
				}
				if($data["iconid"] && FS::$secMgr->isNumeric($data["iconid"])) {
					$iconpath = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_icons","path","id = '".$data["iconid"]."'");
					if($iconpath) {
						fwrite($file,"\n\ticon_image\t".$iconpath);
						fwrite($file,"\n\tstatusmap_image\t".$iconpath);
					}
				}
				fwrite($file,"\n}\n\n");
			}
			fclose($file);
			
			/*
			 * Hostgroups config
			 */
			
			$file = fopen($path."hostgroups.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define hostgroup {\n\thostgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]);
				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '1'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tmembers\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["host"]);
					
				}
				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '2'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\thostgroup_members\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["host"]);
				}
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 * Services config
			 */
			 
			$file = fopen($path."services.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
			retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptf,srvopts,notifintval,ctg,srvoptr",
			"template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define service {\n\tservice_description\t".$data["name"]."\n\tcheck_command\t".$data["checkcmd"]."\n\t");
				if($data["hosttype"] == 1)
					fwrite($file,"host_name\t".$data["host"]);
				else
					fwrite($file,"hostgroup_name\t".$data["host"]);
				fwrite($file,"\n\tcheck_period\t".$data["checkperiod"]."\n\tcheck_interval\t".$data["checkintval"]."\n\tretry_interval\t".$data["retcheckintval"]."\n\t");
				fwrite($file,"max_check_attempts\t".$data["maxcheck"]."\n\tevent_handler_enabled\t".($data["eventhdlen"] == "t" ? 1 : 0)."\n\tflap_detection_enabled\t".($data["flapen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tprocess_perf_data\t".($data["perfdata"] == "t" ? 1 : 0)."\n\tretain_status_information\t");
				fwrite($file,($data["retstatus"] == "t" ? 1 : 0)."\n\tretain_nonstatus_information\t".($data["retnonstatus"] == "t" ? 1 : 0)."\n\tnotifications_enabled\t".($data["notifen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tnotification_period\t".$data["notifperiod"]."\n\tnotification_interval\t".$data["notifintval"]."\n\tactive_checks_enabled\t".($data["actcheck"] == "t" ? 1 : 0));
				fwrite($file,"\n\tpassive_checks_enabled\t".($data["pascheck"] == "t" ? 1 : 0)."\n\tobsess_over_service\t".($data["obsess"] == "t" ? 1 : 0)."\n\tcheck_freshness\t".($data["freshness"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tparallelize_check\t".($data["parcheck"] == "t" ? 1 : 0)."\n\tcontact_groups\t".$data["ctg"]."\n\t");
				
				$found = false;
				if($data["srvoptc"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"c");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptw"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"w");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptu"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptr"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptf"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvopts"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"s");
				}
				fwrite($file,"\n}\n\n");
			}
			fclose($file);
			
			/*
			 * Restarter
			 */
			 
			$file = fopen("/tmp/icinga_restart","w+");
			if(!$file)
				return false;
			fwrite($file,"1");
			fclose($file);
			
			return true;
		}
	}
?>
