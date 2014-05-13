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

	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/modules/Network.FS.class.php");
	require_once(dirname(__FILE__)."/objects.php");

	if(!class_exists("iDNSManager")) {
		
	final class iDNSManager extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lDNSManager();
			$this->rulesclass = new rDNSMgmt($this->loc);
			$this->menu = $this->loc->s("menu-name");
			$this->modulename = "dnsmgmt";
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-dns"));
			return $this->showMain();
		}

		private function showMain() {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";

			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-dns");

				$tabs[] = array(1,"mod=".$this->mid,$this->loc->s("DNS-zones"));
				$tabs[] = array(6,"mod=".$this->mid,$this->loc->s("Zone-Mgmt"));
				$tabs[] = array(2,"mod=".$this->mid,$this->loc->s("DNSSec-Mgmt"));
				$tabs[] = array(5,"mod=".$this->mid,$this->loc->s("ACL-Mgmt"));
				$tabs[] = array(4,"mod=".$this->mid,$this->loc->s("Server-Mgmt"));
				$tabs[] = array(3,"mod=".$this->mid,$this->loc->s("Advanced-tools"));
				$output .= FS::$iMgr->tabPan($tabs,$sh);
			}
			else {
				switch($sh) {
					case 1: $output .= $this->showRecordMgmt(); break;
					case 2: $output .= $this->showDNSSecMgmt(); break;
					case 3: $output .= $this->showAdvancedTools(); break;
					case 4: $output .= $this->showServerMgmt(); break;
					case 5: $output .= $this->showACLList(); break;
					case 6: $output .= $this->showZoneMgmt(); break;
				}
			}
			return $output;
		}

		private function showZoneMgmt() {
			FS::$iMgr->setURL("sh=6");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("Zone-Mgmt"));
			$dnsZone = new dnsZone();
			return $dnsZone->renderAll();
		}

		private function showDNSSecMgmt() {
			FS::$iMgr->setURL("sh=2");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("DNSSec-Mgmt"));
			$dnsTSIG = new dnsTSIGKey();
			$output = $dnsTSIG->renderAll();
			return $output;
		}

		private function showRecordMgmt() {
			FS::$iMgr->setURL("sh=1");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("DNS-zones"));
			$output = "";
			if (FS::$sessMgr->hasRight("mrule_dnsmgmt_write")) {
				$found = false;
			}

			$filter = FS::$secMgr->checkAndSecuriseGetData("f");

			$shA = FS::$secMgr->checkAndSecuriseGetData("sa");
			if ($shA == NULL) $shA = 1;

			$shAAAA = FS::$secMgr->checkAndSecuriseGetData("saaaa");
			if ($shAAAA == NULL) $shAAAA = 1;

			$shNS = FS::$secMgr->checkAndSecuriseGetData("sns");
			if ($shNS == NULL) $shNS = 1;

			$shCNAME = FS::$secMgr->checkAndSecuriseGetData("scname");
			if ($shCNAME == NULL) $shCNAME = 1;

			$shSRV = FS::$secMgr->checkAndSecuriseGetData("ssrv");
			if ($shSRV == NULL) $shSRV = 1;
			
			$shPTR = FS::$secMgr->checkAndSecuriseGetData("sptr");
			if ($shPTR == NULL) $shPTR = 1;
			
			$shTXT = FS::$secMgr->checkAndSecuriseGetData("stxt");
			if ($shTXT == NULL) $shTXT = 1;

			$shother = FS::$secMgr->checkAndSecuriseGetData("sother");
			if ($shother == NULL) $shother = 1;

			$formoutput = FS::$iMgr->cbkForm("1").
				FS::$iMgr->select("f");

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_cache","zonename","",array("order" => "zonename","group" => "zonename"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				// Skip hint zone
				if ($data["zonename"] != ".") {
					if (!$found) {
						$found = true;
					}
					$formoutput .= FS::$iMgr->selElmt($data["zonename"],$data["zonename"],($filter == $data["zonename"]));
				}
			}
			if ($found) {
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
				$output .= FS::$iMgr->printError("no-data-found");
			}

			return $output;
		}

		private function showRecords($dnszone) {
			$output = "";

			$shA = FS::$secMgr->checkAndSecurisePostData("sa");
			if ($shA == "on") $shA = true;
			else $shA = false;
			
			$shAAAA = FS::$secMgr->checkAndSecurisePostData("saaaa");
			if ($shAAAA == "on") $shAAAA = true;
			else $shAAAA = false;
			
			$shNS = FS::$secMgr->checkAndSecurisePostData("sns");
			if ($shNS == "on") $shNS = true;
			else $shNS = false;
			
			$shCNAME = FS::$secMgr->checkAndSecurisePostData("scname");
			if ($shCNAME == "on") $shCNAME = true;
			else $shCNAME = false;
			
			$shSRV = FS::$secMgr->checkAndSecurisePostData("ssrv");
			if ($shSRV == "on") $shSRV = true;
			else if ($shSRV > 0) $shSRV = true;
			else $shSRV = false;
			
			$shPTR = FS::$secMgr->checkAndSecurisePostData("sptr");
			if ($shPTR == "on") $shPTR = true;
			else $shPTR = false;
			
			$shTXT = FS::$secMgr->checkAndSecurisePostData("stxt");
			if ($shTXT == "on") $shTXT = true;
			else $shTXT = false;
			
			$shother = FS::$secMgr->checkAndSecurisePostData("sother");
			if ($shother == "on") $shother = true;
			else $shother = false;
			
			if (!$dnszone) {
				return $output;
			}
			
			$rectypef = "";
			if (!$shA || !$shAAAA || !$shNS || !$shCNAME || !$shPTR || !$shSRV || !$shTXT || !$shother) {
				$rectypef .= " AND rectype IN (";
				$found = false;
				if ($shA) {
					$rectypef .= "'A'";
					$found = true;
				}
				if ($shAAAA) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'AAAA'";
				}
				if ($shNS) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'NS'";
				}
				if ($shCNAME) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'CNAME'";
				}
				if ($shPTR) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'PTR'";
				}
				if ($shSRV) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'SRV'";
				}
				if ($shTXT) {
					if ($found) $rectypef .= ",";
					else $found = true;
					$rectypef .= "'TXT'";
				}
				
				$rectypef .= ")";
				if ($shother) $rectypef .= " OR rectype NOT IN ('A','AAAA','CNAME','NS','PTR','SRV','TXT')";
			}
			
			$first = true;
			$administrable = false;
			$dnszone2 = $dnszone;
			$dnsrecords = array();
			
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","zonename,record,rectype,recval,server","zonename = '".$dnszone."'".$rectypef,
				array("order" => "zonename,record","ordersens" => 2));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if ($first) {
					$first = false;
					$output .= FS::$iMgr->h3("Zone: ".$dnszone,true);
					
					// We must use DNS zone without the ending dot
					
					if (preg_match("#(.*)\.$#",$dnszone)) {
						$dnszone2 = substr($dnszone,0,strlen($dnszone)-1);
					}
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dns_zones",
						"zonename","zonename = '".$dnszone2."'")) {
						$output .= FS::$iMgr->opendiv(11,$this->loc->s("Add-Record"),array("lnkadd" => "zn=".$dnszone2));
						$administrable = true;
					}
					$output .= "<table id=\"dnsRecords\"><thead><th id=\"headerSortDown\">".
						$this->loc->s("Record")."</th><th>Type</th><th>".$this->loc->s("Value")."</th><th>".$this->loc->s("Servers")."</th>";
					if ($administrable) {
						$output .= "<th></th>";
					}
					$output .= "</tr></thead>";
				}
				if (!isset($dnsrecords[$data["record"]])) 
					$dnsrecords[$data["record"]] = array();

				if (!isset($dnsrecords[$data["record"]][$data["rectype"]]))
					$dnsrecords[$data["record"]][$data["rectype"]] = array();

				if (!isset($dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]]))
					$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]] = array();

				if (!in_array($data["server"],$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]]))
					$dnsrecords[$data["record"]][$data["rectype"]][$data["recval"]][] = $data["server"];
			}
			foreach ($dnsrecords as $recordname => $records) {
				foreach ($records as $recordtype => $records2) {
					foreach ($records2 as $recordval => $servers) {
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
						$output .= "<tr style=\"$style\"><td style=\"padding: 2px\">";
						if ($recordtype != "SOA" && $recordname != "@") {
							$output .= FS::$iMgr->opendiv(11,$recordname,
								array("lnkadd" => "zn=".$dnszone2."&recname=".$recordname."&rectype=".$recordtype.
									"&recvalue=".$recordval)
							);
						}
						else {
							$output .= $recordname;
						}
						$output .= "</td><td>".$recordtype."</td><td>";
						if ($recordtype == "A" || $recordtype == "AAAA") {
							$output .= FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("search")."&s=".$recordval, $recordval);
						}
						else {
							$output .= $recordval;
						}
						$output .= "</td><td>";
						$count = count($servers);
						for ($i=0;$i<$count;$i++) {
							$output .= $servers[$i];
							if ($i != count($servers)) $output .= "<br />";
						}
						if ($administrable) {
							$output .= sprintf("<td>%s</td>",
								$recordtype != "SOA" ? 
									FS::$iMgr->removeIcon(14,"zn=".$dnszone2.
									"&rc=".$recordname."&rct=".$recordtype."&rcv=".$recordval,
									array("js" => true,
										"confirmtext" => "confirm-remove-record",
										"confirmval" => $recordname
									))
									: "");
						}
						$output .= "</td></tr>";
					}
				}
			}

			if (strlen($output) > 0) {
				$output .= "</table>";
			}
			else {
				$output = FS::$iMgr->printError("err-no-records");
			}
			return $output;
		}

		private function showAdvancedTools() {
			FS::$iMgr->setURL("sh=3");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("Advanced-tools"));
			
			$output = FS::$iMgr->h3("title-old-records").
				FS::$iMgr->cbkForm("2").
				"Intervalle (jours) ".FS::$iMgr->numInput("ival")."<br />".
				FS::$iMgr->submit("search",$this->loc->s("Search")).
				"</form><div id=\"obsres\"></div>";
			return $output;
		}

		private function showACLList() {
			FS::$iMgr->setURL("sh=5");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("ACL-Mgmt"));
			$acl = new dnsACL();
			return $acl->renderAll();
		}

		private function showServerMgmt() {
			FS::$iMgr->setURL("sh=4");
			FS::$iMgr->setTitle($this->loc->s("title-dns")." > ".$this->loc->s("Server-Mgmt"));
			$cluster = new dnsCluster();
			$output = $cluster->renderAll();

			$server = new dnsServer();
			$output .= $server->renderAll();

			return $output;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: 
					$server = new dnsServer();
					return $server->showForm();
				case 2:
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if (!$addr) {
						return $this->loc->s("err-bad-datas");
					}

					$server = new dnsServer();
					return $server->showForm($addr);
				case 3: 
					$dnsTSIG = new dnsTSIGKey();
					return $dnsTSIG->showForm();
				case 4:
					$keyalias = FS::$secMgr->checkAndSecuriseGetData("keyalias");
					if (!$keyalias) {
						return $this->loc->s("err-bad-datas");
					}

					$dnsTSIG = new dnsTSIGKey();
					return $dnsTSIG->showForm($keyalias);
				case 5:
					$dnsACL = new dnsACL();
					return $dnsACL->showForm();
				case 6:
					$aclname = FS::$secMgr->checkAndSecuriseGetData("aclname");
					if (!$aclname) {
						return $this->loc->s("err-bad-datas");
					}

					$dnsACL = new dnsACL();
					return $dnsACL->showForm($aclname);
				case 7:
					$dnsCluster = new dnsCluster();
					return $dnsCluster->showForm();
				case 8:
					$clustername = FS::$secMgr->checkAndSecuriseGetData("clustername");
					if (!$clustername) {
						return $this->loc->s("err-bad-datas");
					}

					$dnsCluster = new dnsCluster();
					return $dnsCluster->showForm($clustername);
				case 9:
					$dnsZone = new dnsZone();
					return $dnsZone->showForm();
				case 10:
					$zonename = FS::$secMgr->checkAndSecuriseGetData("zonename");
					if (!$zonename) {
						return $this->loc->s("err-bad-datas");
					}

					$dnsZone = new dnsZone();
					return $dnsZone->showForm($zonename);
				case 11:
					$zonename = FS::$secMgr->checkAndSecuriseGetData("zn");
					if (!$zonename) {
						return $this->loc->s("err-bad-datas");
					}
					
					$dnsRecord = new dnsRecord();
					return $dnsRecord->showForm($zonename);
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

					if ($dnszone == NULL && $shA == NULL && $shAAAA == NULL && $shNS == NULL && $shCNAME == NULL && $shSRV == NULL && $shPTR == NULL && $shTXT == NULL && $shother == NULL) {
						$this->log(2,"Getting zone: Some values are wrong");
						FS::$iMgr->ajaxEcho("err-bad-datas");
					}
					else {
						FS::$iMgr->js("$('#recordlist').html('".FS::$secMgr->cleanForJS($this->showRecords($dnszone))."');");
						FS::$iMgr->ajaxEcho("Done".FS::$iMgr->jsSortTable("dnsRecords"));
					}
					return;
				case 2:
					$interval = FS::$secMgr->checkAndSecurisePostData("ival");
					if (!$interval || !FS::$secMgr->isNumeric($interval) ||
						$interval < 1) {
						FS::$iMgr->ajaxEcho("err-invalid-req");
						$this->log(2,"Invalid data when searching obsolete datas");
						return;
					}

					$found = false;
					$output = "";

					$obsoletes = array();
					// Search deprecated records
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval,zonename","rectype = 'A'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						if ($data["recval"] == "")
							continue;

						$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$data["recval"]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
							array("order" => "time_last","ordersens" => 1));
						while ($data2 = FS::$dbMgr->Fetch($query2)) {
							$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
								array("order" => "time_last","ordersens" => 1));
							if (!$foundrecent) {
								if (!$found) $found = true;
								$obsoletes[$data["record"]] = FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("search").
									"&s=".$data["record"].".".$data["zonename"], $data["record"].".".$data["zonename"])." / ".$data["recval"]."<br />";
							}
						}
					}

					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,recval,zonename","rectype = 'CNAME'");
					while ($data = FS::$dbMgr->Fetch($query)) {
						$toquery = "";
						if ($data["recval"][strlen($data["recval"])-1] == ".") {
							$toquery = $data["recval"];
							$toquery[strlen($toquery)-1] = '';
						}
						else
							$toquery = $data["record"].".".$data["zonename"];
						$out = array();
						# pipe spaces are very important
						exec("/usr/bin/dig +short -t A ".$toquery." | grep -ve \"^;\" | grep -ve \"^$\" | grep '^[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}\.[0-9]\{1,3\}$'",$out);
						if (count($out) == 0 || $out == "") {
							$obsoletes[$data["record"]] = FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("search")."&s=".$data["record"].
								".".$data["zonename"], $data["record"].".".$data["zonename"])." / ".$this->loc->s("Alone")."<br />";
						}
						else {
							$count = count($out);
							for ($i=0;$i<$count;$i++) {
								if ($out[$i] == "")
									continue;
								$query2 = FS::$dbMgr->Select("node_ip","mac,time_last","ip = '".$out[$i]."' AND active = 't' AND time_last < NOW() - INTERVAL '".$interval." day'",
									array("order" => "time_last","ordersens" => 1));
								while ($data2 = FS::$dbMgr->Fetch($query2)) {
									$foundrecent = FS::$dbMgr->GetOneData("node","switch","mac = '".$data2["mac"]."' AND time_last > NOW() - INTERVAL '".$interval." day'",
										array("order" => "time_last","ordersens" => 1));
									if (!$foundrecent) {
										if (!$found) $found = true;
										$obsoletes[$data["record"]] = FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("search").
										"&s=".$data["record"].".".$data["zonename"], $data["record"].".".$data["zonename"])." / ".$out[$i]."<br />";
									}
								}
							}
						}
					}
					$output = "";
					if ($found) {
						$output = FS::$iMgr->h3("found-records").$output;
						foreach ($obsoletes as $key => $value)
							$output .= $value;
					}
					else {
						$output .= FS::$iMgr->printDebug($this->loc->s("no-found-records"));
					}

					$js = "$('#obsres').html('".FS::$secMgr->cleanForJS($output)."');";
					FS::$iMgr->js($js);

					FS::$iMgr->ajaxEcho("Done");
					$this->log(0,"search old records for DNS zones");
					return;
				// Add/Edit DNS server
				case 3:
					$server = new dnsServer();
					$server->Modify();
					return;
				// Delete DNS server
				case 4: 
					$server = new dnsServer();
					$server->Remove();
					return;
				// Add/Edit TSIG key
				case 5:
					$tsig = new dnsTSIGKey();
					$tsig->Modify();
					return;
				// Remove TSIG key
				case 6:
					$tsig = new dnsTSIGKey();
					$tsig->Remove();
					return;
				// Add/Edit ACL
				case 7:
					$acl = new dnsACL();
					$acl->Modify();
					return;
				// Remove ACL
				case 8:
					$acl = new dnsAcl();
					$acl->Remove();
					return;
				// Add/Edit cluster
				case 9:
					$cluster = new dnsCluster();
					$cluster->Modify();
					return;
				// Remove Cluster
				case 10:
					$cluster = new dnsCluster();
					$cluster->Remove();
					return;
				// Add/Edit zone
				case 11:
					$zone = new dnsZone();
					$zone->Modify();
					return;
				// Remove zone
				case 12:
					$zone = new dnsZone();
					$zone->Remove();
					return;
				// Add/Modify record
				case 13:
					$record = new dnsRecord();
					$record->Modify();
					return;
				case 14:
					$record = new dnsRecord();
					$record->Remove();
					return;
			}
		}
	};
	
	}
	
	$module = new iDNSManager();
?>
