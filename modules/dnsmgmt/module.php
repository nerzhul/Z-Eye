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
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
			$output = "";

			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-dns");

				if($addr && FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
					$output .= $this->CreateOrEditServer(false);
				}
				else {
					
					$tabs[] = array(1,"mod=".$this->mid,$this->loc->s("DNS-zones"));
					$tabs[] = array(2,"mod=".$this->mid,$this->loc->s("DNSSec-Mgmt"));
					$tabs[] = array(3,"mod=".$this->mid,$this->loc->s("Advanced-tools"));
					$output .= FS::$iMgr->tabPan($tabs,$sh);
				}
			}
			else {
				switch($sh) {
					case 1: $output .= $this->showZoneMgmt(); break;
					case 2: $output .= $this->showDNSSecMgmt(); break;
					case 3: $output .= $this->showAdvancedTools(); break;
				}
			}
			return $output;
		}

		private function showDNSSecMgmt() {
			$output = FS::$iMgr->opendiv(3,$this->loc->s("define-tsig-key"),array("line" => true));
			$tMgr = new HTMLTableMgr(array(
				"tabledivid" => "tsiglist",
				"tableid" => "tsigtable",
				"firstlineid" => "tsigftr",
				"sqltable" => "dns_tsig",
				"sqlattrid" => "keyalias",
				"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
					array("algorithm","keyalgo","sr",array(1 => "HMAC-MD5", 2 => "HMAC-SHA1", 3 => "HMAC-SHA256")),
					array("Value","keyvalue","")),
				"sorted" => true,
				"odivnb" => 4,
				"odivlink" => "keyalias=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=6&keyalias",
				"rmconfirm" => "confirm-remove-tsig",
				"trpfx" => "tsigk",
			));
			$output .= $tMgr->render();
			return $output;
		}

		private function showTSIGForm($keyalias = "") {
			$keyid = ""; $keyvalue = ""; $keyalgo = "";
			if($keyalias) {
				if($data = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."dns_tsig","keyid,keyalgo,keyvalue",
					"keyalias = '".$keyalias."'")) {
					$keyid = $data["keyid"];
					$keyalgo = $data["keyalgo"];
					$keyvalue = $data["keyvalue"];
				}
				else {
					return $this->loc->s("err-tsig-key-not-exists");
				}
			}

			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=5")."<table>".
				FS::$iMgr->idxLine($this->loc->s("key-alias"),"keyalias",$keyalias,array("type" => "idxedit", "length" => 64,
					"edit" => $keyalias != "")).
				FS::$iMgr->idxLine($this->loc->s("key-id"),"keyid",$keyid,array("length" => 32, "value" => $keyid)).
				"<tr><td>".$this->loc->s("algorithm")."</td><td>".FS::$iMgr->select("keyalgo").
					FS::$iMgr->selElmt("HMAC-MD5",1,$keyalgo == 1).FS::$iMgr->selElmt("HMAC-SHA1",2,$keyalgo == 2).
					FS::$iMgr->selElmt("HMAC-SHA256",3,$keyalgo == 3)."</select>".
				FS::$iMgr->idxLine($this->loc->s("Value"),"keyvalue",$keyvalue,array("length" => 128, "size" => 30, "value" => $keyvalue)).
				FS::$iMgr->aeTableSubmit($keyalias == "");

			return $output;
		}

		private function showZoneMgmt() {
			$output = "";
			if(FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
				$output .= $this->showCreateEditErr();

				$output .= FS::$iMgr->opendiv(1,$this->loc->s("add-server"),array("line" => true));

				$found = false;
				if($exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","addr","dns = '1'"))
					$output .= FS::$iMgr->opendiv(2,$this->loc->s("modify-servers"));
			}

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

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

			$formoutput = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1").
				FS::$iMgr->select("f");

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_cache","zonename","",array("order" => "zonename"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$formoutput .= FS::$iMgr->selElmt($data["zonename"],$data["zonename"],($filter == $data["zonename"] ? true : false));
			}
			if($found) {
				$output .= $formoutput.
					"</select><br />".
					FS::$iMgr->check("sa",array("check" => $shA))."A ".
					FS::$iMgr->check("saaaa",array("check" => $shAAAA))."AAAA ".
					FS::$iMgr->check("scname",array("check" => $shCNAME))."CNAME ".
					FS::$iMgr->check("sns",array("check" => $shNS))."NS ".
					FS::$iMgr->check("ssrv",array("check" => $shSRV))."SRV ".
					FS::$iMgr->check("stxt",array("check" => $shTXT))."TXT ".
					FS::$iMgr->check("sptr",array("check" => $shPTR))."PTR ".
					FS::$iMgr->check("sother",array("check" => $shother)).$this->loc->s("Others").
					"<br />".
					FS::$iMgr->submit("",$this->loc->s("Filter")).
					"</form><div id=\"recordlist\"></div>";
			}
			else {
				$output .= FS::$iMgr->printError($this->loc->s("no-data-found"));
			}

			return $output;
		}

		private function showRecords($dnszone) {
			$output = "";

			$shA = FS::$secMgr->checkAndSecurisePostData("sa");
			if($shA == "on") $shA = true;
			else $shA = false;
			
			$shAAAA = FS::$secMgr->checkAndSecurisePostData("saaaa");
			if($shAAAA == "on") $shAAAA = true;
			else $shAAAA = false;
			
			$shNS = FS::$secMgr->checkAndSecurisePostData("sns");
			if($shNS == "on") $shNS = true;
			else $shNS = false;
			
			$shCNAME = FS::$secMgr->checkAndSecurisePostData("scname");
			if($shCNAME == "on") $shCNAME = true;
			else $shCNAME = false;
			
			$shSRV = FS::$secMgr->checkAndSecurisePostData("ssrv");
			if($shSRV == "on") $shSRV = true;
			else if($shSRV > 0) $shSRV = true;
			else $shSRV = false;
			
			$shPTR = FS::$secMgr->checkAndSecurisePostData("sptr");
			if($shPTR == "on") $shPTR = true;
			else $shPTR = false;
			
			$shTXT = FS::$secMgr->checkAndSecurisePostData("stxt");
			if($shTXT == "on") $shTXT = true;
			else $shTXT = false;
			
			$shother = FS::$secMgr->checkAndSecurisePostData("sother");
			if($shother == "on") $shother = true;
			else $shother = false;
			
			if(!$dnszone) {
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
			
			$first = true;
			$dnsrecords = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","zonename,record,rectype,recval,server","zonename = '".$dnszone."'".$rectypef,
				array("order" => "zonename,record","ordersens" => 2));
			while($data = FS::$dbMgr->Fetch($query)) {
				if($first) {
					$first = false;
					$output .= FS::$iMgr->h3("Zone: ".$dnszone,true)."<table id=\"dnsRecords\"><thead><th id=\"headerSortDown\">".
						$this->loc->s("Record")."</th><th>Type</th><th>".$this->loc->s("Value")."</th><th>".$this->loc->s("Servers")."</th></tr></thead>";
				}
				if(!isset($dnsrecords[$data["record"]])) 
					$dnsrecords[$data["record"]] = array();

				if(!isset($dnsrecords[$data["record"]][$data["rectype"]]))
					$dnsrecords[$data["record"]][$data["rectype"]] = array();

				if(!isset($dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]]))
					$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]] = array();

				if(!in_array($data["server"],$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]]))
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
						$output .= "<tr style=\"$style\"><td style=\"padding: 2px\">".$recordname."</td><td>".$recordtype."</td><td>";
						if($recordtype == "A")
							$output .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&node=".$recordval."\">".$recordval."</a>";
						else
							$output .= $recordval;
						$output .= "</td><td>";
						$count = count($servers);
						for($i=0;$i<$count;$i++) {
							$output .= $servers[$i];
							if($i != count($servers)) $output .= "<br />";
						}
						$output .= "</td></tr>";
					}
				}
			}

			if(strlen($output) > 0) {
				$output .= "</table>";
			}
			else {
				$output = FS::$iMgr->printError($this->loc->s("err-no-records"));
			}
			return $output;
		}

		private function showAdvancedTools() {
			$output = FS::$iMgr->h3("title-old-records").
				FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=2").
				"Intervalle (jours) ".FS::$iMgr->numInput("ival")."<br />".
				FS::$iMgr->submit("search",$this->loc->s("Search")).
				"</form><div id=\"obsres\"></div>";
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
				case 3: return $this->showTSIGForm();
				case 4:
					$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
					if(!$keyalias)
						return $this->loc->s("err-bad-datas");
					return $this->showTSIGForm($keyalias);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$dnszone = FS::$secMgr->checkAndSecurisePostData("f");
					$shA = FS::$secMgr->checkAndSecurisePostData("sa");
					$shAAAA = FS::$secMgr->checkAndSecurisePostData("saaaa");
					$shNS = FS::$secMgr->checkAndSecurisePostData("sns");
					$shCNAME = FS::$secMgr->checkAndSecurisePostData("scname");
					$shSRV = FS::$secMgr->checkAndSecurisePostData("ssrv");
					$shPTR = FS::$secMgr->checkAndSecurisePostData("sptr");
					$shTXT = FS::$secMgr->checkAndSecurisePostData("stxt");
					$shother = FS::$secMgr->checkAndSecurisePostData("sother");

					if($dnszone == NULL && $shA == NULL && $shAAAA == NULL && $shNS == NULL && $shCNAME == NULL && $shSRV == NULL && $shPTR == NULL && $shTXT == NULL && $shother == NULL) {
						FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",2,"Getting zone: Some values are wrong");
						FS::$iMgr->ajaxEcho("err-bad-datas");
					}
					else {
						FS::$iMgr->js("$('#recordlist').html('".addslashes($this->showRecords($dnszone))."');");
						FS::$iMgr->ajaxEcho("Done".FS::$iMgr->jsSortTable("dnsRecords"));
					}
					return;
				case 2:
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if(!$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						echo FS::$iMgr->printError($this->loc->s("err-invalid-req"));
						FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",2,"Invalid data when searching obsolete datas");
						return;
					}

					$found = false;
					$output = "";

					$obsoletes = array();
					// Search deprecated records
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval,zonename","rectype = 'A'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($data["recval"] == "")
							continue;

						$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$data["recval"]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
							array("order" => "time_last","ordersens" => 1));
						while($data2 = FS::$dbMgr->Fetch($query2)) {
							$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
								array("order" => "time_last","ordersens" => 1));
							if(!$foundrecent) {
								if(!$found) $found = true;
								$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search").
									"&s=".$data["record"].".".$data["zonename"]."\">".$data["record"].".".$data["zonename"]."</a> / ".$data["recval"]."<br />";
							}
						}
					}

					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval,zonename","rectype = 'CNAME'");
					while($data = FS::$dbMgr->Fetch($query)) {
						$toquery = "";
						if($data["recval"][strlen($data["recval"])-1] == ".") {
							$toquery = $data["recval"];
							$toquery[strlen($toquery)-1] = '';
						}
						else
							$toquery = $data["record"].".".$data["zonename"];
						$out = array();
						# pipe spaces are very important
						exec("/usr/bin/dig +short -t A ".$toquery." | grep -ve \"^;\" | grep -ve \"^$\" | grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}$'",$out);
						if(count($out) == 0 || $out == "") {
							$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].
								".".$data["zonename"]."\">".$data["record"].".".$data["zonename"]."</a> / ".$this->loc->s("Alone")."<br />";
						}
						else {
							$count = count($out);
							for($i=0;$i<$count;$i++) {
								if($out[$i] == "")
									continue;
								$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$out[$i]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
									array("order" => "time_last","ordersens" => 1));
								while($data2 = FS::$dbMgr->Fetch($query2)) {
									$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
										array("order" => "time_last","ordersens" => 1));
									if(!$foundrecent) {
										if(!$found) $found = true;
										$obsoletes[$data["record"]] = "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("search").
										"&s=".$data["record"].".".$data["zonename"]."\">".$data["record"].".".$data["zonename"]."</a> / ".$out[$i]."<br />";
									}
								}
							}
						}
					}
					$output = "";
					if($found) {
						$output = FS::$iMgr->h3("found-records").$output;
						foreach($obsoletes as $key => $value)
							$output .= $value;
					}
					else {
						$output .= FS::$iMgr->printDebug($this->loc->s("no-found-records"));
					}

					$js = "$('#obsres').html('".addslashes($output)."');";
					FS::$iMgr->js($js);

					FS::$iMgr->ajaxEcho("Done");
					FS::$log->i(FS::$sessMgr->getUserName(),"dnsmgmt",3,"search old records for DNS zones");
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
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					if(!$saddr || !$slogin || !$spwd || !$spwd2 || $spwd != $spwd2 ||
						!$namedpath || !FS::$secMgr->isPath($namedpath) ||
							(!$chrootnamed && !FS::$secMgr->isPath($chrootnamed))
						) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"Some datas are invalid or wrong for add server");
						FS::$iMgr->ajaxEcho("err-miss-bad-fields");
						return;
					}
					$conn = ssh2_connect($saddr,22);
					if(!$conn) {
						FS::$iMgr->ajaxEcho("err-unable-conn");
						return;
					}
					if(!ssh2_auth_password($conn,$slogin,$spwd)) {
						FS::$iMgr->ajaxEcho("err-bad-login");
						return;
					}
				
					if($edit) {	
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","login","addr ='".$saddr."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Unable to add server '".$saddr."': already exists");
							FS::$iMgr->ajaxEcho("err-bad-server");
							return;
						}

						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."server_list","addr = '".$saddr."'");
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."server_list","login","addr ='".$saddr."'")) {
							FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",1,"Unable to add server '".$saddr."': already exists");
							FS::$iMgr->ajaxEcho("err-server-exist");
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
						FS::$iMgr->ajaxEcho("err-no-rights");
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
				// Add/Edit TSIG key
				case 5:
					if(!FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"User don't have rights to remove server");
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}
					
					$keyalias = FS::$secMgr->checkAndSecurisePostData("keyalias");
					$keyid = FS::$secMgr->checkAndSecurisePostData("keyid");
					$keyalgo = FS::$secMgr->checkAndSecurisePostData("keyalgo");
					$keyvalue = FS::$secMgr->checkAndSecurisePostData("keyvalue");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$keyalias || !$keyid || !$keyalgo || !FS::$secMgr->isNumeric($keyalgo) || !$keyvalue ||
						$edit && $edit != 1) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig","keyalias","keyalias = '".$keyalias."'");
					if($edit) {
						if(!$exist) {
							FS::$iMgr->ajaxEcho("err-tsig-key-not-exists");
							return;
						}
					}
					else {
						if($exist) {
							FS::$iMgr->ajaxEcho("err-tsig-key-already-exists");
							return;
						}
						$exist = FS::$dbMgr->GetOneEntry(PGDbConfig::getDbPrefix()."dns_tsig","keyalias","keyid = '".$keyid.
							"' AND keyalgo = '".$keyalgo."' AND keyvalue = '".$keyvalue."'");
						if($exist) {
							FS::$iMgr->ajaxEcho("err-tsig-key-exactly-same");
							return;
						}
					}
					
					if(!FS::$secMgr->isHostname($keyid)) {
						FS::$iMgr->ajaxEcho("err-tsig-key-id-invalid");
						return;
					}

					if($keyalgo < 1 || $keyalgo > 3) {
						FS::$iMgr->ajaxecho("err-tsig-key-algo-invalid");
						return;
					}

					FS::$dbMgr->BeginTr();
					if($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dns_tsig","keyalias = '".$keyalias."'");
					}
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."dns_tsig","keyalias,keyid,keyalgo,keyvalue","'".$keyalias."','".
						$keyid."','".$keyalgo."','".$keyvalue."'");
					FS::$dbMgr->CommitTr();

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "tsiglist",
						"tableid" => "tsigtable",
						"firstlineid" => "tsigftr",
						"sqltable" => "dns_tsig",
						"sqlattrid" => "keyalias",
						"sqlcond" => "keyalias = '".$keyalias."'",
						"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
							array("algorithm","keyalgo","sr",array("1" => "HMAC-MD5", "2" => "HMAC-SHA1", "3" => "HMAC-SHA256")),
							array("Value","keyvalue","")),
						"sorted" => true,
						"odivnb" => 4,
						"odivlink" => "keyalias=",
						"rmcol" => true,
						"rmlink" => "mod=".$this->mid."&act=6&keyalias",
						"rmconfirm" => "confirm-remove-tsig",
						"trpfx" => "tsigk",
					));

					$js = $tMgr->addLine($keyalias,$edit);
					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				// Remove TSIG key
				case 6:
					if(!FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"servermgmt",2,"User don't have rights to remove server");
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}

					$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
					if(!$keyalias) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_tsig","keyalias","keyalias = '".$keyalias."'")) {
						FS::$iMgr->ajaxEcho("err-tsig-key-not-exists");
						return;
					}

					$tMgr = new HTMLTableMgr(array(
						"tabledivid" => "tsiglist",
						"tableid" => "tsigtable",
						"firstlineid" => "tsigftr",
						"sqltable" => "dns_tsig",
						"sqlattrid" => "keyalias",
						"attrlist" => array(array("key-alias","keyalias",""), array("key-id","keyid",""),
							array("algorithm","keyalgo","sr",array("1" => "HMAC-MD5", "2" => "HMAC-SHA1", "3" => "HMAC-SHA256")),
							array("Value","keyvalue","")),
						"sorted" => true,
						"odivnb" => 4,
						"odivlink" => "keyalias=",
						"rmcol" => true,
						"rmlink" => "mod=".$this->mid."&act=6&keyalias",
						"rmconfirm" => "confirm-remove-tsig",
						"trpfx" => "tsigk",
					));

					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."dns_tsig","keyalias = '".$keyalias."'");
					FS::$dbMgr->CommitTr();

					$js = $tMgr->removeLine(FS::$iMgr->formatHTMLId($keyalias));
					FS::$iMgr->ajaxEcho("Done",$js);
					return;
			}
		}
	};
?>
