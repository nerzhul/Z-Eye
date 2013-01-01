<?php
	/*
	* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/modules/Network.FS.class.php");
	
	class iDNSManager extends genModule{
		function iDNSManager() { parent::genModule(); $this->loc = new lDNSManager(); }
		public function Load() {
			$output = "";
			$output .= $this->showStats();
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$dnsoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$output .= "<h1>".$this->loc->s("title-dns")."</h1>";
				$formoutput .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
				$formoutput .= FS::$iMgr->select("f");
				
				$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
				if($shA == NULL) $shA = 1;
				
				$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
				if($shAAAA == NULL) $shAAAA = 1;
				
				$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
				if($shNS == NULL) $shNS = 1;
				
				$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
				if($shCNAME == NULL) $shCNAME = 1;
				
				$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
				if($shSRV == NULL) $shSRV = 1;
				
				$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
				if($shPTR == NULL) $shPTR = 1;
				
				$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
				if($shTXT == NULL) $shTXT = 1;

				$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
				if($shother == NULL) $shother = 1;

				$found = false;
				$query = FS::$pgdbMgr->Select("z_eye_dns_zone_cache","zonename","","zonename");
				while($data = pg_fetch_array($query)) {
					if(!$found) $found = true;
					$formoutput .= FS::$iMgr->selElmt($data["zonename"],$data["zonename"],($filter == $data["zonename"] ? true : false));
				}
				if($found) {
					$output .= $formoutput;
					$output .= "</select><br />";
					$output .= FS::$iMgr->check("sa",array("check" => $shA))."A ";
					$output .= FS::$iMgr->check("saaaa",array("check" => $shAAAA))."AAAA ";
					$output .= FS::$iMgr->check("scname",array("check" => $shCNAME))."CNAME ";
					$output .= FS::$iMgr->check("sns",array("check" => $shNS))."NS ";
					$output .= FS::$iMgr->check("ssrv",array("check" => $shSRV))."SRV ";
					$output .= FS::$iMgr->check("stxt",array("check" => $shTXT))."TXT ";
					$output .= FS::$iMgr->check("sptr",array("check" => $shPTR))."PTR ";
					$output .= FS::$iMgr->check("sother",array("check" => $shother)).$this->loc->s("Others")." ";
					$output .= "<br />";
					$output .= FS::$iMgr->submit("",$this->loc->s("Filter"));
					$output .= "</form>";
					if($filter) {
						$output .= "<div id=\"contenttabs\"><ul>";
						$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother."\">".$this->loc->s("Stats")."</a>";
						$output .= "<li><a href=\"index.php?mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother."&sh=2\">".$this->loc->s("expert-tools")."</a>";
						$output .= "</ul></div>";
						$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
						$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
					}
				}
				else
					$output .= FS::$iMgr->printError($this->loc->s("no-data-found"));
			} else {
				if(!$showmodule || $showmodule == 1) {
					$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
					if($shA == NULL) $shA = true;
					else if($shA > 0) $shA = true;
					else $shA = false;
					
					$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
					if($shAAAA == NULL) $shAAAA = true;
					else if($shAAAA > 0) $shAAAA = true;
					else $shAAAA = false;
					
					$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
					if($shNS == NULL) $shNS = true;
					else if($shNS > 0) $shNS = true;
					else $shNS = false;
					
					$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
					if($shCNAME == NULL) $shCNAME = true;
					else if($shCNAME > 0) $shCNAME = true;
					else $shCNAME = false;
					
					$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
					if($shSRV == NULL) $shSRV = true;
					else if($shSRV > 0) $shSRV = true;
					else $shSRV = false;
					
					$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
					if($shPTR == NULL) $shPTR = true;
					else if($shPTR > 0) $shPTR = true;
					else $shPTR = false;
					
					$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
					if($shTXT == NULL) $shTXT = true;
					else if($shTXT > 0) $shTXT = true;
					else $shTXT = false;
					
					$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
					if($shother == NULL) $shother = true;
					else if($shother > 0) $shother = true;
					else $shother = false;
					
					if(!$filter) {
						$output .= FS::$iMgr->printError($this->loc->s("err-no-zone"));
						return $output;
					}
					
					$rectypef = "";
					if(!$shA || !$shAAAA || !$shNS || !$shCNAME || !$shPTR || !$shSRV || !$shTXT || !$shother) {
						$rectypef .= " AND rectype IN (";
						$found = false;
						if($shA) {
							$rectypef .= "'A'";
							$found = true;
						}
						if($shAAAA) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'AAAA'";
						}
						if($shNS) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'NS'";
						}
						if($shCNAME) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'CNAME'";
						}
						if($shPTR) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'PTR'";
						}
						if($shSRV) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'SRV'";
						}
						if($shTXT) {
							if($found) $rectypef .= ",";
							else $found = true;
							$rectypef .= "'TXT'";
						}
						
						$rectypef .= ")";
						if($shother) $rectypef .= " OR rectype NOT IN ('A','AAAA','CNAME','NS','PTR','SRV','TXT')";
					}
					
					$query = FS::$pgdbMgr->Select("z_eye_dns_zone_record_cache","zonename,record,rectype,recval,server",($filter != NULL ? "zonename = '".$filter."'" : "").$rectypef,"zonename,record",2);
					$curzone = "";
					$dnsrecords = array();
					while($data = pg_fetch_array($query)) {
						if($curzone != $data["zonename"]) {
							$curzone = $data["zonename"];
							if($curzone != "") $dnsoutput .= "</table>";
							$dnsoutput .= "<h3>Zone: ".$filter."</h3><table><th>".$this->loc->s("Record")."</th><th>Type</th><th>".$this->loc->s("Value")."</th><th>".$this->loc->s("Servers")."</th></tr>";
						}
						if(!isset($dnsrecords[$data["record"]])) $dnsrecords[$data["record"]] = array();
						if(!isset($dnsrecords[$data["record"]][$data["rectype"]])) $dnsrecords[$data["record"]][$data["rectype"]] = array();
						if(!isset($dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]])) $dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]] = array();
						array_push($dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]],$data["server"]);
					}
					foreach($dnsrecords as $recordname => $records) {
						foreach($records as $recordtype => $records2) {
							foreach($records2 as $recordval => $servers) {
								switch($recordtype) {
									case "A": case "AAAA":
										$style = "background-color: #FFFF80;"; break;
									case "CNAME":
										$style = "background-color: #BFFFBF;"; break;
									case "SRV":
										$style = "background-color: #B3FFFF;"; break;
									case "NS":
										$style = "background-color: #FF8888;"; break;
									default: $style = ""; break;
								}
								$dnsoutput .= "<tr style=\"$style\"><td style=\"padding: 2px\">".$recordname."</td><td>".$recordtype."</td><td>";
								if($recordtype == "A")
									$dnsoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&node=".$recordval."\">".$recordval."</a>";
								else
									$dnsoutput .= $recordval;
								$dnsoutput .= "</td><td>";
								$count = count($servers);
								for($i=0;$i<$count;$i++) {
									$dnsoutput .= $servers[$i];
									if($i != count($servers)) $dnsoutput .= "<br />";
								}
								$dnsoutput .= "</td></tr>";
							}
						}
					}

					if(strlen($dnsoutput) > 0)
						$output .= $dnsoutput."</table>";
				}
				else if($showmodule == 2) {
					$output .= "<h3>".$this->loc->s("title-old-records")."</h3>";
					$output .= "<script type=\"text/javascript\">function searchobsolete() {";
					$output .= "$('#obsres').html('".FS::$iMgr->img('styles/images/loader.gif')."');";
					$output .= "$.post('index.php?at=3&mod=".$this->mid."&act=2', { ival: document.getElementsByName('ival')[0].value, obsdata: document.getElementsByName('obsdata')[0].value}, function(data) {";
					$output .= "$('#obsres').html(data);";
					$output .= "});return false;}</script>";
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->hidden("obsdata",$filter);
					$output .= "Intervalle (jours) ".FS::$iMgr->numInput("ival")."<br />";
					$output .= FS::$iMgr->JSSubmit("search",$this->loc->s("Search"),"return searchobsolete();");
					$output .= "</form><div id=\"obsres\"></div>";
				}
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$filtr = FS::$secMgr->checkAndSecurisePostData("f");
					$shA = FS::$secMgr->checkAndSecurisePostData("sa");
					$shAAAA = FS::$secMgr->checkAndSecurisePostData("saaaa");
					$shNS = FS::$secMgr->checkAndSecurisePostData("sns");
					$shCNAME = FS::$secMgr->checkAndSecurisePostData("scname");
					$shSRV = FS::$secMgr->checkAndSecurisePostData("ssrv");
					$shPTR = FS::$secMgr->checkAndSecurisePostData("sptr");
					$shTXT = FS::$secMgr->checkAndSecurisePostData("stxt");
					$shother = FS::$secMgr->checkAndSecurisePostData("sother");
					
					if($filtr == NULL && $shA == NULL && $shAAAA == NULL && $shNS == NULL && $shCNAME == NULL && $shSRV == NULL && $shPTR == NULL && $shTXT == NULL && $shother == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",2,"Some filtering values are wrong");
						header("Location: index.php?mod".$this->mid."");
					}
					else {
						if($shA == "on") $shA = 1;
						else $shA = 0;
			
						if($shAAAA == "on") $shAAAA = 1;
						else $shAAAA = 0;
						
			
						if($shNS == "on") $shNS = 1;
						else $shNS = 0;
						
			
						if($shCNAME == "on") $shCNAME = 1;
						else $shCNAME = 0;

						if($shSRV == "on") $shSRV = 1;
						else $shSRV = 0;

						if($shPTR == "on") $shPTR = 1;
						else $shPTR = 0;

						if($shTXT == "on") $shTXT = 1;
						else $shTXT = 0;

						if($shother == "on") $shother = 1;
						else $shother = 0;
						header("Location: index.php?mod=".$this->mid.($filtr != NULL ? "&f=".$filtr : "")."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother);
					}
					return;
				case 2:
					$filter = FS::$secMgr->checkAndSecurisePostData("obsdata");
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$filter || !$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						echo FS::$iMgr->printError($this->loc->s("err-invalid-req"));
						FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",2,"Invalid data when searching obsolete datas");
						return;
					}

					$found = false;
					$output = "";

					$obsoletes = array();
					// Search deprecated records
					$query = FS::$pgdbMgr->Select("z_eye_dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'A'");
					while($data = pg_fetch_array($query)) {
						$query2 = FS::$pgdbMgr->Select("node_ip","mac,time_last","ip = '".$data["recval"]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'","time_last",1);
						while($data2 = pg_fetch_array($query2)) {
							$foundrecent = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'","time_last",1);
							if(!$foundrecent) {
								if(!$found) $found = true;
								$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$data["recval"]."<br />";
							}
						}
					}

					$query = FS::$pgdbMgr->Select("z_eye_dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'CNAME'");
					while($data = pg_fetch_array($query)) {
						$toquery = "";
						if($data["recval"][strlen($data["recval"])-1] == ".") {
							$toquery = $data["recval"];
							$toquery[strlen($toquery)-1] = '';
						}
						else
							$toquery = $data["record"].".".$filter;
						$out = array();
						exec("dig -t A ".$toquery." +short|grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}$'",$out);
						if(count($out) == 0) {
							$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$this->loc->s("Alone")."<br />";
						}
						else {
							$count = count($out);
							for($i=0;$i<$count;$i++) {
								$query2 = FS::$pgdbMgr->Select("node_ip","mac,time_last","ip = '".$out[$i]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'","time_last",1);
								while($data2 = pg_fetch_array($query2)) {
									$foundrecent = FS::$pgdbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'","time_last",1);
									if(!$foundrecent) {
										if(!$found) $found = true;
										$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$out[$i]."<br />";
									}
								}
							}
						}
					}
					if($found) {
						echo "<h3>".$this->loc->s("found-records")."</h3>".$output;
						foreach($obsoletes as $key => $value)
							echo $value;
					}
					else echo FS::$iMgr->printDebug($this->loc->s("no-found-records"));
					FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",3,"User read ".$filter." DNS zone");
					return;
			}
		}
	};
?>
