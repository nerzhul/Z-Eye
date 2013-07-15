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

	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");

	final class iDNSManager extends FSModule{
		function __construct($locales) { parent::__construct($locales); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-dns"));
			return $this->showMain();
		}

		private function showMain() {
			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
	
			$output = "";
			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-dns");

				if($addr && FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
					$output .= $this->CreateOrEditServer(false);
				}
				else {
					 if(FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
						$output .= $this->showCreateEditErr();

						$output .= FS::$iMgr->opendiv(1,$this->loc->s("add-server"),array("line" => true));

						$tmpoutput = "";
						$found = false;
						if($exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","addr","dns = '1'"))
							$output .= FS::$iMgr->opendiv(2,$this->loc->s("modify-servers"));
					}
				}
			}
			if(!$addr) $output .= $this->showStats();
			return $output;
		}

		private function showStats() {
			$output = "";
			$formoutput = "";
			$dnsoutput = "";

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!$showmodule) $showmodule = 1;
			if(!FS::isAjaxCall()) {
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
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_cache","zonename","",array("order" => "zonename"));
				while($data = FS::$dbMgr->Fetch($query)) {
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
						$panElmts = array(
						array(1,"mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother,$this->loc->s("Stats")),
						array(2,"mod=".$this->mid."&at=2&f=".$filter."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother,$this->loc->s("expert-tools")));
						$output .= FS::$iMgr->tabPan($panElmts,$showmodule);
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
					
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","zonename,record,rectype,recval,server",($filter != NULL ? "zonename = '".$filter."'" : "").$rectypef,
						array("order" => "zonename,record","ordersens" => 2));
					$curzone = "";
					$dnsrecords = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if($curzone != $data["zonename"]) {
							$curzone = $data["zonename"];
							if($curzone != "") $dnsoutput .= "</table>";
							$dnsoutput .= FS::$iMgr->h3("Zone: ".$filter,true)."<table id=\"dnsRecords\"><thead><th id=\"headerSortDown\">".$this->loc->s("Record")."</th><th>Type</th><th>".$this->loc->s("Value")."</th><th>".$this->loc->s("Servers")."</th></tr></thead>";
						}
						if(!isset($dnsrecords[$data["record"]])) 
							$dnsrecords[$data["record"]] = array();

						if(!isset($dnsrecords[$data["record"]][$data["rectype"]]))
							$dnsrecords[$data["record"]][$data["rectype"]] = array();

						if(!isset($dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]]))
							$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]] = array();

						$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]][] = $data["server"];
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

					if(strlen($dnsoutput) > 0) {
						$output .= $dnsoutput."</table>";
						FS::$iMgr->jsSortTable("dnsRecords");
					}
				}
				else if($showmodule == 2) {
					$output .= FS::$iMgr->h3("title-old-records");
					$output .= FS::$iMgr->js("function searchobsolete() {
						$('#obsres').html('".FS::$iMgr->img('styles/images/loader.gif')."');
						$.post('index.php?at=3&mod=".$this->mid."&act=2', { ival: document.getElementsByName('ival')[0].value, obsdata: document.getElementsByName('obsdata')[0].value}, function(data) {
						$('#obsres').html(data);
						});return false;}");
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
					$output .= FS::$iMgr->hidden("obsdata",$filter);
					$output .= "Intervalle (jours) ".FS::$iMgr->numInput("ival")."<br />";
					$output .= FS::$iMgr->JSSubmit("search",$this->loc->s("Search"),"return searchobsolete();");
					$output .= "</form><div id=\"obsres\"></div>";
				}
			}
			return $output;
		}

		private function CreateOrEditServer($create) {
			$output = "";
			$saddr = "";
			$slogin = "";
			$dns = 0;
			$namedpath = "";
			$chrootnamed = "";
			if(!$create) {
				$output = FS::$iMgr->h2("edit-server");
				$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
				if(!$addr || $addr == "") {
					$output .= FS::$iMgr->printError($this->loc->s("err-no-server-get")." !");
					return $output;
				}
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."server_list","login,dns,chrootnamed,namedpath","addr = '".$addr."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$saddr = $addr;
					$slogin = $data["login"];
					$dns = $data["dns"];
					$namedpath = $data["namedpath"];
					$chrootnamed = $data["chrootnamed"];
				}
				else {
					$output .= FS::$iMgr->printError($this->loc->s("err-bad-server")." !");
					return $output;
				}
			}
			
			if(!$create) {
				$output .= "<a href=\"m-".$this->mid.".html\">".$this->loc->s("Return")."</a><br />";
				$output .= $this->showCreateEditErr();	
			}
			
			$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=3");
			
			$output .= "<table>";
			if($create)
				$output .= FS::$iMgr->idxLine($this->loc->s("ip-addr-dns"),"saddr",$saddr);
			else {
				$output .= "<tr><td>".$this->loc->s("ip-addr-dns")."</td><td>".$saddr."</td></tr>";
				$output .= FS::$iMgr->hidden("saddr",$saddr).FS::$iMgr->hidden("edit","1");
			}
			$output .= FS::$iMgr->idxLine($this->loc->s("ssh-user"),"slogin",$slogin);
			$output .= FS::$iMgr->idxLine($this->loc->s("Password"),"spwd","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Password-repeat"),"spwd2","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("named-conf-path"),"namedpath",$namedpath,array("tooltip" => "tooltip-rights"));
			$output .= FS::$iMgr->idxLine($this->loc->s("chroot-path"),"chrootnamed",$chrootnamed,array("tooltip" => "tooltip-chroot"));
			$output .= FS::$iMgr->tableSubmit("Save");
			
			return $output;
		}

		private function showServerList() {
			$output = "";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."server_list","addr,login,dns","dns = '1'");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("Login").
					"</th><th></th></tr>";
				}

				$output .= "<tr id=\"".preg_replace("#[.]#","-",$data["addr"])."tr\"><td><a href=\"index.php?mod=".$this->mid."&addr=".$data["addr"]."\">".$data["addr"];
				$output .= "</td><td>".$data["login"]."</td><td>";
				$output .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=4&srv=".$data["addr"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-dnssrc")."'".$data["addr"]."' ?","Confirm","Cancel")));
				$output .= "</td></tr>";
			}
			if($found) {
				$output .= "</table>";
			}
			return $output;
		}

		private function showCreateEditErr() {
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: return FS::$iMgr->printError($this->loc->s("err-miss-bad-fields"));
				case 2: return FS::$iMgr->printError($this->loc->s("err-unable-conn"));
				case 3: return FS::$iMgr->printError($this->loc->s("err-bad-login")); 
				case 4: return FS::$iMgr->printError($this->loc->s("err-server-exist")); 
				case 5: return FS::$iMgr->printError($this->loc->s("err-bad-server")); 
				case 99: return FS::$iMgr->printError($this->loc->s("err-no-rights"));
			}
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->CreateOrEditServer(true);
				case 2: return $this->showServerList();
				default: return;
			}
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
						FS::$iMgr->redir("mod=".$this->mid);
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
						FS::$iMgr->redir("mod=".$this->mid.($filtr != NULL ? "&f=".$filtr : "")."&sa=".$shA."&saaaa=".$shAAAA."&sns=".$shNS."&scname=".$shCNAME."&ssrv=".$shSRV."&sptr=".$shPTR."&stxt=".$shTXT."&sother=".$shother);
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
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'A'");
					while($data = FS::$dbMgr->Fetch($query)) {
						$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$data["recval"]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
							array("order" => "time_last","ordersens" => 1));
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
								array("order" => "time_last","ordersens" => 1));
							if(!$foundrecent) {
								if(!$found) $found = true;
								$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$data["recval"]."<br />";
							}
						}
					}

					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval","zonename = '".$filter."' AND rectype = 'CNAME'");
					while($data = FS::$dbMgr->Fetch($query)) {
						$toquery = "";
						if($data["recval"][strlen($data["recval"])-1] == ".") {
							$toquery = $data["recval"];
							$toquery[strlen($toquery)-1] = '';
						}
						else
							$toquery = $data["record"].".".$filter;
						$out = array();
						exec("/usr/bin/dig -t A ".$toquery." +short|grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}$'",$out);
						if(count($out) == 0) {
							$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$this->loc->s("Alone")."<br />";
						}
						else {
							$count = count($out);
							for($i=0;$i<$count;$i++) {
								$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$out[$i]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
									array("order" => "time_last","ordersens" => 1));
								while($data2 = FS::$dbMgr->Fetch($query2)) {
									$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
										array("order" => "time_last","ordersens" => 1));
									if(!$foundrecent) {
										if(!$found) $found = true;
										$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].".".$filter."\">".$data["record"].".".$filter."</a> / ".$out[$i]."<br />";
									}
								}
							}
						}
					}
					if($found) {
						echo FS::$iMgr->h3("found-records").$output;
						foreach($obsoletes as $key => $value)
							echo $value;
					}
					else echo FS::$iMgr->printDebug($this->loc->s("no-found-records"));
					FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",3,"User read ".$filter." DNS zone");
					return;
				// Add/Edit DNS server
				case 3:
					$saddr = FS::$secMgr->checkAndSecurisePostData("saddr");
					$slogin = FS::$secMgr->checkAndSecurisePostData("slogin");
					$spwd = FS::$secMgr->checkAndSecurisePostData("spwd");
					$spwd2 = FS::$secMgr->checkAndSecurisePostData("spwd2");
					$namedpath = FS::$secMgr->checkAndSecurisePostData("namedpath");
					$chrootnamed = FS::$secMgr->checkAndSecurisePostData("chrootnamed");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"User don't have rights to add/edit server");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-rights");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					}

					if(!$saddr || !$slogin || !$spwd || !$spwd2 || $spwd != $spwd2 ||
						!$namedpath || !FS::$secMgr->isPath($namedpath) ||
							(!$chrootnamed && !FS::$secMgr->isPath($chrootnamed))
						) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some datas are invalid or wrong for add server");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-miss-bad-fields");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=1");
						return;
					}
					$conn = ssh2_connect($saddr,22);
					if(!$conn) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-unable-conn");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-login");
						else
							FS::$iMgr->redir("mod=".$this->mid."&&err=3");
						return;
					}
				
					if($edit) {	
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","login","addr ='".$saddr."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Unable to add server '".$saddr."': already exists");
							if(FS::isAjaxCall())
								FS::$iMgr->ajaxEcho("err-bad-server");
							else
								FS::$iMgr->redir("mod=".$this->mid."&err=5");
							return;
						}

						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."server_list","addr = '".$saddr."'");
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","login","addr ='".$saddr."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Unable to add server '".$saddr."': already exists");
							if(FS::isAjaxCall())
								FS::$iMgr->ajaxEcho("err-server-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&err=4");
							return;
						}
					}
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."server_list","addr,login,pwd,dns,namedpath,chrootnamed",
					"'".$saddr."','".$slogin."','".$spwd."','1','".$namedpath."','".$chrootnamed."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Added server '".$saddr."' options: dns checking");
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				// Delete DNS server
				case 4: { 
					if(!FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"User don't have rights to remove server");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-rights");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					}
					
					$srv = FS::$secMgr->checkAndSecuriseGetData("srv");
					if($srv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",0,"Removing server '".$srv."' from database");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."server_list","addr = '".$srv."'");
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#".preg_replace("#[.]#","-",$srv)."tr');");
					else
					FS::$iMgr->redir("mod=".$this->mid);
					return;
				}
			}
		}
	};
?>
