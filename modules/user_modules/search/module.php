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

	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");

	class iSearch extends genModule{
		function iSearch() { 
			parent::genModule();
			$this->loc = new lSearch();
			$this->autoresults = array("device" => array(), "dnsrecord" => array(), "ip" => array(),
				"mac" => array(), "nbdomain" => array(), "nbname" => array(), "portname" => array(),
				"prise" => array(), "vlan" => array());
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("Search"));
			$output = "";
			$autosearch = FS::$secMgr->checkAndSecuriseGetData("term");
			$search = FS::$secMgr->checkAndSecuriseGetData("s");
			if($search)
				$output .= $this->findRefsAndShow($search);
			else if($autosearch)
				$output .= $this->findRefsAndShow($autosearch,true);
			else
				$output .= FS::$iMgr->printError($this->loc->s("err-no-search"));
			return $output;
		}

		private function findRefsAndShow($search,$autocomp=false) {
			$output = "";
			if(!$autocomp) {
				$output .= FS::$iMgr->h1($this->loc->s("Search").": ".$search,true);
				if(FS::$secMgr->isMacAddr($search)) {
					$output .= $this->showMacAddrResults($search);
				}
				else if(FS::$secMgr->isIP($search)) {
					$output .= $this->showIPAddrResults($search);
				}
				else if(is_numeric($search)) {
					$output .= $this->showNumericResults($search);
				}
				else {
					$tmpoutput = $this->showNamedInfos($search);
					if(strlen($tmpoutput) > 0)
						$output .= $tmpoutput;
					else
						$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
				}
				FS::$log->i(FS::$sessMgr->getUserName(),"search",0,"searching '".$search."'");
			}
			else {
				if(preg_match('#^([0-9A-F]{2}:)#i',$search) || preg_match('#([0-9A-F]{2}-)#i',$search))
					$this->showMacAddrResults($search,true);
				else if(preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.#",$search))
					$this->showIPAddrResults($search,true);
				else if(is_numeric($search))
					$this->showNumericResults($search,true);
				else
					$this->showNamedInfos($search,true);
				$output = "[";
				if(is_array($this->autoresults)) {
					$outresults = array();
					foreach($this->autoresults as $key => $values) {
						for($i=0;$i<count($values);$i++) {
							array_push($outresults,$values[$i]);
						}
					}
					$outresults = array_unique($outresults);
					sort($outresults);
					for($i=0;$i<count($outresults) && $i<10;$i++) {
						if($i!=0) $output .= ",";
						$output .= "{\"id\":\"".$outresults[$i]."\",\"value\":\"".$outresults[$i]."\"}";
					}
				}
				$output .= "]";
			}
			return $output;
		}

		private function showNumericResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$nbresults = 0;

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$swmodid = FS::$iMgr->getModuleIdByPath("switches");

					// Prise number
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","ip,port,prise","prise ILIKE '".$search."%'",array("order" => "port"));
					$devprise = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("Ref-plug")."<div id=\"searchres\">";
						}
						$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
						if(!isset($devprise[$swname]))
							$devprise[$swname] = array();

						$devprise[$swname][$data["port"]] = $data["prise"];
					}
					if($found) {
						foreach($devprise as $device => $devport) {
							$tmpoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
							foreach($devport as $port => $prise) {
								$convport = preg_replace("#\/#","-",$port);
								$tmpoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
								$tmpoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> (".$this->loc->s("Plug")." ".$prise.")</li>";
							}
							$tmpoutput .= "</ul>";
						}
						$tmpoutput .= "</div>";
					}
					$found = 0;

					// VLAN on a device
					$query = FS::$dbMgr->Select("device_vlan","ip,description","vlan = '".$search."'",array("order" => "ip"));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($dname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'")) {
							if($found == 0) {
								$found = 1;
								$tmpoutput .= FS::$iMgr->h2("title-vlan-device")."<div id=\"searchres\">";
							}
							$tmpoutput .= "<li> <a href=\"index.php?mod=".$swmodid."&d=".$dname."&fltr=".$search."\">".$dname."</a> (".$data["description"].")<br />";
						}
					}

					if($found) $tmpoutput .= "</div>";
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","prise","prise ILIKE '".$search."%'",array("order" => "prise","limit" => "10","group" => "prise"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["prise"],$data["prise"]);
					
					$query = FS::$dbMgr->Select("device_vlan","vlan","vlan ILIKE '".$search."%'",array("order" => "vlan","limit" => "10","group" => "vlan"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["vlan"],$data["vlan"]);
				}
			}

			if(!$autocomp) {
				if(strlen($tmpoutput) > 0)
					$output .= $tmpoutput;
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));

				return $output;
			}
		}

		private function showNamedInfos($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$nbresults = 0;

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$swmodid = FS::$iMgr->getModuleIdByPath("switches");

					// Devices
					$query = FS::$dbMgr->Select("device","mac,ip,description,model","name ILIKE '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$tmpoutput .= FS::$iMgr->h2("Network-device")."<div id=\"searchres\">";
						$tmpoutput .= "<b>".$this->loc->s("Informations")."<i>: </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						if(strlen($data["mac"]) > 0)
							$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a> - ";
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
						$nbresults++;
					}

					// Prise number
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","ip,port,prise","prise ILIKE '".$search."%'",array("order" => "port"));
					$devprise = array();
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("Ref-plug")."<div id=\"searchres\">";
						}
						$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
						if(!isset($devprise[$swname]))
							$devprise[$swname] = array();

						$devprise[$swname][$data["port"]] = $data["prise"];
						$nbresults++;
					}
					if($found) {
						foreach($devprise as $device => $devport) {
							$tmpoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
							foreach($devport as $port => $prise) {
								$convport = preg_replace("#\/#","-",$port);
								$tmpoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
								$tmpoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ";
								$tmpoutput .= "<br /><b>".$this->loc->s("Plug").":</b> ".$prise."</li>";
							}
							$tmpoutput .= "</ul><br />";
						}
						$tmpoutput .= "</div>";
					}
					$found = 0;

					// Search device_ports
					$devportname = array();
					$query = FS::$dbMgr->Select("device_port","ip,port,name","name ILIKE '%".$search."%'",array("order" => "ip,port"));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0)
							$found = 1;
						$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
						$prise =  FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["ip"]."' AND port = '".$data["port"]."'");
						if(!isset($devportname[$swname]))
							$devportname[$swname] = array();

						$devportname[$swname][$data["port"]] = array($data["name"],$prise);

						$nbresults++;
					}

					if($found) {
						$tmpoutput .= FS::$iMgr->h2("Ref-desc")."<div id=\"searchres\">";
						foreach($devportname as $device => $devport) {
							$tmpoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
							foreach($devport as $port => $portdata) {
								$convport = preg_replace("#\/#","-",$port);
								$tmpoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
								$tmpoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ";
								$tmpoutput .= "<br /><b>".$this->loc->s("Description").":</b> ".$portdata[0];
								if($portdata[1]) $tmpoutput .= "<br /><b>".$this->loc->s("Plug").":</b> ".$portdata[1];
								$tmpoutput .= "</li>";
							}
							$tmpoutput .= "</ul>";
						}
						$tmpoutput .= "</div>";
					}
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select("device","name","name ILIKE '".$search."%'",array("order" => "name","limit" => "10","group" => "name"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["device"],$data["name"]);

					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","prise","prise ILIKE '".$search."%'",array("order" => "prise","limit" => "10","group" => "prise"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["prise"],$data["prise"]);

					$query = FS::$dbMgr->Select("device_port","name","name ILIKE '".$search."%'",array("order" => "name","limit" => "10","group" => "name"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["portname"],$data["name"]);
				}	
			}

			if(FS::$sessMgr->hasRight("mrule_dnsmgmt_read")) {
				// DNS infos
				$searchsplit = preg_split("#\.#",$search);
				$count = count($searchsplit);
				if($count >= 1) {
					$hostname = $searchsplit[0];
					$dnszone = "";
					for($i=1;$i<$count;$i++) {
						$dnszone .= $searchsplit[$i];
						if($i != $count-1)
							$dnszone .= ".";
					}
					if(!$autocomp && $count > 1) {
						$curserver = "";
						$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","rectype,recval,server","record ILIKE '".$hostname."' AND zonename ILIKE '".$dnszone."'",
							array("order" => "server"));
						while($data = FS::$dbMgr->Fetch($query)) {
							if($found == 0) {
								$found = 1;
								$tmpoutput .= FS::$iMgr->h2("title-dns-records")."<div id=\"searchres\">";
							}
							switch($data["rectype"]) {
								case "A": $tmpoutput .= $this->loc->s("ipv4-addr").": "; break;
								case "AAAA": $tmpoutput .= $this->loc->s("ipv6-addr").": "; break;
								case "CNAME": $tmpoutput .= $this->loc->s("Alias").": "; break;
								default: $tmpoutput .= $this->loc->s("Other")." (".$data["rectype"]."): "; break;
							}
							if($curserver != $data["server"]) {
								$curserver = $data["server"];
								$tmpoutput .= FS::$iMgr->h3($data["server"],true);
							}
							if(FS::$secMgr->isIP($data["recval"]))
								$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["recval"]."\">".$data["recval"]."</a>";
							else
								$tmpoutput .= $data["recval"];
							$tmpoutput .= "<br />";
							if($data["server"]) {
								$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
								if($out != NULL) {
									$tmpoutput .= FS::$iMgr->h4("dig-results");
									$tmpoutput .= preg_replace("#[\n]#","<br />",$out);
								}
							}
							$nbresults++;
						}
						if($found) $tmpoutput .= "</div>";
						$found = 0;
					}
					else if($autocomp) {
						if($count > 1) {
							$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,zonename","record ILIKE '".$hostname."' AND zonename ILIKE '".$dnszone."%'",
								array("order" => "record,zonename","limit" => "10"));
							while($data = FS::$dbMgr->Fetch($query))
								array_push($this->autoresults["dnsrecord"],$data["record"].".".$data["zonename"]);
						}
						else if($count == 1) {
							$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","record,zonename","record ILIKE '".$hostname."%'",array("order" => "record,zonename","limit" => "10"));
							while($data = FS::$dbMgr->Fetch($query))
								array_push($this->autoresults["dnsrecord"],$data["record"].".".$data["zonename"]);
						}
					}
				}
			}

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					// Netbios INFOS
					$query = FS::$dbMgr->Select("node_nbt","mac,ip,domain,nbname,nbuser,time_first,time_last","domain ILIKE '".$search."' OR nbname ILIKE '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-netbios")."<div id=\"searchres\">";
							$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Node")."</th><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("First-view")."</th><th>".$this->loc->s("Last-view")."</th></tr>";
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a></td><td>";
						$tmpoutput .= "\\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>";
						$tmpoutput .= ($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a></td><td>".$fst[0]."</td><td>".$lst[0]."</td></tr>";
						$nbresults++;
					}

					if($found) $tmpoutput .= "</table></div>";
					$found = 0;

					$tmpoutput .= $this->showRadiusInfos($search);
				}
				else {
					$query = FS::$dbMgr->Select("node_nbt","domain","domain ILIKE '".$search."%'",
						array("order => "domain","limit" => "10","group" => "domain"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["nbdomain"],$data["domain"]);

					$query = FS::$dbMgr->Select("node_nbt","nbname","nbname ILIKE '".$search."%'",
						array("order" => "nbname","limit" => "10","group" => "nbname"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["nbname"],$data["nbname"]);
				}
			}

			if(!$autocomp) {
				if(strlen($tmpoutput) > 0)
					$output .= FS::$iMgr->h2($this->loc->s("title-res-nb").": ".$nbresults,true).$tmpoutput;

				return $output;
			}
		}

		private function showIPAddrResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$lastmac = "";
			$nbresults = 0;
			
			if(FS::$sessMgr->hasRight("mrule_dnsmgmt_read")) {
				if(!$autocomp) {
					$curserver = "";
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","zonename,record,server","recval ILIKE '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-dns-assoc")."<div id=\"searchres\">";
						}
						if($curserver != $data["server"]) {
							$curserver = $data["server"];
							$tmpoutput .= FS::$iMgr->h4($data["server"],true);
						}
						$tmpoutput .= $data["record"].".".$data["zonename"]."<br />";
						// Resolve with DIG to search what the DNS thinks
						if($data["server"]) {
							$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
							if($out != NULL) {
								$tmpoutput .= FS::$iMgr->h4("dig-results");
								$tmpoutput .= preg_replace("#[\n]#","<br />",$out);
							}
						}
						$nbresults++;
					}

					if($found) $tmpoutput .= "</div>";
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","recval","recval ILIKE '".$search."%'",array("order" => "recval","limit" => "10","group" => "recval"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["dnsrecord"],$data["recval"]);
				}
			}

			if(FS::$sessMgr->hasRight("mrule_ipmanager_read")) {
				if(!$autocomp) {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","macaddr,hostname,leasetime,distributed,server","ip = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-dhcp-distrib")."<div id=\"searchres\">";
						}
						if(strlen($data["hostname"]) > 0)
							$tmpoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
						if(strlen($data["macaddr"]) > 0)
							$tmpoutput .= $this->loc->s("link-mac-addr").": <a href=\"index.php?mod=".$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
						$tmpoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
						if($data["distributed"] != 3 && $data["distributed"] != 4)
							$tmpoutput .= $this->loc->s("Validity")." : ".$data["leasetime"]."<br />";
						$tmpoutput .= "<br />";
						$nbresults++;
					}
			
					if($found) $tmpoutput .= "</div>";
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip","ip ILIKE '".$search."%'",array("order" => "ip","limit" => "10","group" => "ip"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["ip"],$data["ip"]);

				}
			}
			
			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$query = FS::$dbMgr->Select("node_ip","mac,time_first,time_last","ip = '".$search."'",array("order" => "time_last","ordersens" => 1));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-mac-addr")."<div id=\"searchres\">";
							$lastmac = $data["mac"];
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
						$nbresults++;
					}
				
					if($found) $tmpoutput .= "</div>";
					$found = 0;

					if($lastmac) {
						$query = FS::$dbMgr->Select("node","switch,port,time_first,time_last","mac ILIKE '".$lastmac."' AND active = 't'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
						if($data = FS::$dbMgr->Fetch($query)) {
							$tmpoutput .= FS::$iMgr->h2("title-last-device")."<div id=\"searchres\">";
							$fst = preg_split("#\.#",$data["time_first"]);
							$lst = preg_split("#\.#",$data["time_last"]);
							$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
							$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
							$convport = preg_replace("#\/#","-",$data["port"]);
							$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
							$tmpoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
							$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
							if($piece) $tmpoutput .= "/ ".$this->loc->s("Plug")." ".$piece;
							$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
							$tmpoutput .= "</div>";
						}
					}

					$query = FS::$dbMgr->Select("node_nbt","domain,nbname,nbuser,time_first,time_last","ip = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-netbios")."<div id=\"searchres\">";
						}

						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);

						$tmpoutput .= $this->loc->s("netbios-machine").": \\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>";
						$tmpoutput .= "\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a><br />";
						$tmpoutput .= $this->loc->s("netbios-user").": ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";
						$tmpoutput .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
						$nbresults++;
					}

					if($found) $tmpoutput .= "</div>";
					$found = 0;

					// Devices
					$query = FS::$dbMgr->Select("device","mac,name,description,model","ip = '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$tmpoutput .= FS::$iMgr->h2("Network-device")."<div id=\"searchres\">";
						$tmpoutput .= "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a>)<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
						$nbresults++;
					}
				}
				else {
					$query = FS::$dbMgr->Select("node_ip","ip","host(ip) ILIKE '".$search."%'",array("order" => "ip","limit" => "10","group" => "ip"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["ip"],$data["ip"]);

					$query = FS::$dbMgr->Select("node_nbt","ip","host(ip) ILIKE '".$search."%'",array("order" => "ip","limit" => "10","group" => "ip"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["ip"],$data["ip"]);

					$query = FS::$dbMgr->Select("device","ip","host(ip) ILIKE '".$search."%'",array("order" => "ip","limit" => "10","group" => "ip"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["ip"],$data["ip"]);
				}
			}

			if(!$autocomp) {
				if(strlen($tmpoutput) > 0)
					$output .= FS::$iMgr->h2($this->loc->s("title-res-nb").": ".$nbresults,true).$tmpoutput;
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
				return $output;
			}
		}

		private function showRadiusInfos($search,$autocomp=false) {
			$output = "";
			if(!$autocomp) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."radius_db_list","addr,port,dbname,login,pwd,dbtype,tradcheck,tradreply,tradusrgrp,tradacct");
				while($data = FS::$dbMgr->Fetch($query)) {
					$radSQLMgr = new AbstractSQLMgr();
					$radSQLMgr->setConfig($data["dbtype"],$data["dbname"],$data["port"],$data["addr"],$data["login"],$data["pwd"]);
					$radSQLMgr->Connect();

					$found = 0;
					// Format MAC addr for radius users
					if(FS::$secMgr->isMacAddr($search))
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].$search[6].$search[7].$search[9].$search[10].$search[12].$search[13].$search[15].$search[16];
					else
						$tmpsearch = $search;
					$query2 = $radSQLMgr->Select($data["tradcheck"],"username","username = '".$tmpsearch."'",array("limit" => 1));
					while($data2 = $radSQLMgr->Fetch($query2)) {
						if(!$found) {
							$found = 1;
							$output .= $this->loc->s("Username").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("radius")."&h=".$data["addr"]."&p=".$data["port"]."&r=".$data["dbname"]."&radentrytype=1&radentry=".$data2["username"]."\">".$data2["username"]."</a>";
						}
					}
					if(!$found) {
						$query2 = $radSQLMgr->Select($data["tradreply"],"username","username = '".$tmpsearch."'",array("limit" => 1));
						while($data2 = $radSQLMgr->Fetch($query2)) {
							if(!$found) {
								$found = 1;
								$output .= $this->loc->s("Username").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("radius")."&h=".$data["addr"]."&p=".$data["port"]."&r=".$data["dbname"]."&radentrytype=1&radentry=".$data2["username"]."\">".$data2["username"]."</a>";
							}
						}
					}
					
					if($found) {
						$found = 0;
						$query2 = $radSQLMgr->Select($data["tradusrgrp"],"groupname","username = '".$tmpsearch."'");
						while($data2 = $radSQLMgr->Fetch($query2)) {
							if(!$found) {
								$found = 1;
								$output .= FS::$iMgr->h3("Groups")."<ul>";
							}
							$output .= "<li>".$data2["groupname"]."</li>";
						}
						if($found) $output .= "</ul>";
					}

					if(FS::$secMgr->isMacAddr($search)) {
						// Format mac addr for some accounting
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].".".$search[6].$search[7].$search[9].$search[10].".".$search[12].$search[13].$search[15].$search[16];
						$found = 0;
						$query2 = $radSQLMgr->Select($data["tradacct"],"username,calledstationid,acctstarttime,acctstoptime","callingstationid = '".$tmpsearch."'");
						if($data2 = $radSQLMgr->Fetch($query2)) {
							if($found == 0) {
								$found = 1;
								$output .= FS::$iMgr->h2("title-8021x-users")."<div id=\"searchres\">";
							}
							$fst = preg_split("#\.#",$data2["acctstarttime"]);
							$lst = preg_split("#\.#",$data2["acctstoptime"]);
							$output .= $this->loc->s("User").": ".$data2["username"]." / ".$this->loc->s("Device").": <a href=\"index.php?mod=".
							$this->mid."&s=".$data2["calledstationid"]."\">".$data2["calledstationid"]."</a>";
							$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
						}

						if($found) $output .= "</div>";
						$found = 0;
						$totinbw = 0;
						$totoutbw = 0;
						$query2 = $radSQLMgr->Select($data["tradacct"],"calledstationid, SUM(acctinputoctets) as input, SUM(acctoutputoctets) as output, MIN(acctstarttime) as fst, MAX(acctstoptime) as lst","callingstationid = '".$tmpsearch."'",array("group" => "calledstationid"));
						if($data2 = $radSQLMgr->Fetch($query2)) {
							if($found == 0) {
								$found = 1;
								$output .= FS::$iMgr->h2("title-8021x-bw")."<div id=\"searchres\">";
							}
							if($data2["input"] > 1024*1024*1024)
								$inputbw = round($data2["input"]/1024/1024/1024,2)."Go";
							else if($data2["input"] > 1024*1024)
								$inputbw = round($data2["input"]/1024/1024,2)."Mo";
							else if($data2["input"] > 1024)
								$inputbw = round($data2["input"]/1024,2)."Ko";
							else
								$inputbw = $data2["input"]." ".$this->loc->s("Bytes");
								
							if($data2["output"] > 1024*1024*1024)
								$outputbw = round($data2["output"]/1024/1024/1024,2)."Go";
							else if($data2["output"] > 1024*1024)
								$outputbw = round($data2["output"]/1024/1024,2)."Mo";
							else if($data2["output"] > 1024)
								$outputbw = round($data2["output"]/1024,2)."Ko";
							else
								$outputbw = $data2["output"]." ".$this->loc->s("Bytes");
							$fst = preg_split("#\.#",$data2["fst"]);
							$lst = preg_split("#\.#",$data2["lst"]);
							$output .= $this->loc->s("Device").": ".$data2["calledstationid"]." Download: ".$inputbw." / Upload: ".$outputbw. "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
							(strlen($lst[0]) > 0 ? $this->loc->s("Between") : $this->loc->s("Since"))." ".$fst[0].(strlen($lst[0]) > 0 ? " ".$this->loc->s("and-the")." ".$lst[0] : "").")<br /><hr>";
							$totinbw += $data2["input"];
							$totoutbw += $data2["output"];
						}
						if($found) {
							if(totinbw > 1024*1024*1024)
								$inputbw = round(totinbw/1024/1024/1024,2)."Go";
							else if($totinbw > 1024*1024)
								$inputbw = round($data2["input"]/1024/1024,2)."Mo";
							else if(totinbw > 1024)
								$inputbw = round($totinbw/1024,2)."Ko";
							else
								$inputbw = $totinbw." ".$this->loc->s("Bytes");
								
							if($totoutbw > 1024*1024*1024)
								$outputbw = round($totoutbw/1024/1024/1024,2)."Go";
							else if($totoutbw > 1024*1024)
								$outputbw = round($data2["output"]/1024/1024,2)."Mo";
							else if($totoutbw > 1024)
								$outputbw = round($totoutbw/1024,2)."Ko";
							else
								$outputbw = $totoutbw." ".$this->loc->s("Bytes");
							$output .= "<b>".$this->loc->s("Total")."</b> Download: ".$inputbw." / Upload: ".$outputbw."</div>";
						}
					}
					$found = 0;
					if(FS::$secMgr->isMacAddr($search))
						$tmpsearch = $search[0].$search[1].$search[3].$search[4].$search[6].$search[7].$search[9].$search[10].$search[12].$search[13].$search[15].$search[16];
					else
						$tmpsearch = $search;
					$query2 = $radSQLMgr->Select($data["tradacct"],"calledstationid,acctterminatecause,acctstarttime,acctterminatecause,acctstoptime,acctinputoctets,acctoutputoctets",
						"username = '".$tmpsearch."'",array("order" => "acctstarttime","ordersens" => 1,"limit" => 10));
					while($data2 = $radSQLMgr->Fetch($query2)) {
						if($found == 0) {
							$found = 1;
							$output .= FS::$iMgr->h2("Accounting")."<div id=\"searchres\">
							<table><tr><th>".$this->loc->s("Device")."</th><th>".$this->loc->s("start-session")."</th><th>".$this->loc->s("end-session")."</th><th>".$this->loc->s("Upload")."</th>
							<th>".$this->loc->s("Download")."</th><th>".$this->loc->s("end-session-cause")."</th></tr>";
						}
						if($data2["acctinputoctets"] > 1024*1024*1024)
								$inputbw = round($data2["acctinputoctets"]/1024/1024/1024,2)." Go";
						else if($data2["acctinputoctets"] > 1024*1024)
								$inputbw = round($data2["acctinputoctets"]/1024/1024,2)." Mo";
						else if($data2["acctinputoctets"] > 1024)
								$inputbw = round($data2["acctinputoctets"]/1024,2)." Ko";
						else
								$inputbw = $data2["acctinputoctets"]." ".$this->loc->s("Bytes");

						if($data2["acctoutputoctets"] > 1024*1024*1024)
								$outputbw = round($data2["acctoutputoctets"]/1024/1024/1024,2)." Go";
						else if($data2["acctoutputoctets"] > 1024*1024)
								$outputbw = round($data2["acctoutputoctets"]/1024/1024,2)." Mo";
						else if($data2["acctoutputoctets"] > 1024)
								$outputbw = round($data2["acctoutputoctets"]/1024,2)." Ko";
						else
								$outputbw = $data2["acctoutputoctets"]." ".$this->loc->s("Bytes");
						
						$macdev = "";
						if(strlen($data2["calledstationid"]) > 0) {
							$devportmac = preg_replace("[-]",":",$data2["calledstationid"]);
							if(FS::$secMgr->isMacAddr($devportmac)) {
								$macdevip = FS::$dbMgr->GetOneData("device_port","ip","mac = '".strtolower($devportmac)."'");
								$macdev = FS::$dbMgr->GetOneData("device","name","ip = '".$macdevip."'");
							}
							else if(preg_match('#^([0-9A-Fa-f]{4}[.]){2}[0-9A-Fa-f]{4}$#',$devportmac)) {
								$tmpmac = $devportmac[0].$devportmac[1].":".$devportmac[2].$devportmac[3].":".$devportmac[5].$devportmac[6].":".$devportmac[7].$devportmac[8].":".$devportmac[10].$devportmac[11].":".$devportmac[12].$devportmac[13];
								$macdevip = FS::$dbMgr->GetOneData("device_port","ip","mac = '".strtolower($tmpmac)."'");
								$macdev = FS::$dbMgr->GetOneData("device","name","ip = '".$macdevip."'");
							}
						}
						$output .= "<tr><td>".(strlen($macdev) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$macdev."\">".$macdev."</a>" : $this->loc->s("Unknown"))."</td>";
						$output .= "<td>".date("d-m-y H:i:s",strtotime($data2["acctstarttime"]))."</td><td>";
						$output .= ($data2["acctstoptime"] != NULL ? date("d-m-y H:i:s",strtotime($data2["acctstoptime"])) : "");
						$output .= "</td><td>".$inputbw."</td><td>".$outputbw."</td>";
						$output .= "<td>".$data2["acctterminatecause"]."</td></tr>";

					}
					if($found) {
						$output .= "</table></div>";
						$output = FS::$iMgr->h2($this->loc->s("Radius-Server")." (".$data["dbname"]."@".$data["addr"].":".$data["port"].")",true).$output;
					}
				}
				return $output;
			}
		}

		private function showMacAddrResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$search = preg_replace("#[-]#",":",$search);

			if(!$autocomp) {
				$company = FS::$dbMgr->GetOneData("oui","company","oui = '".substr($search,0,8)."'");
				if($company)
		 		$tmpoutput .= FS::$iMgr->h2("Manufacturer")."<div id=\"searchres\">".$company."</div>";
			}

			if(FS::$sessMgr->hasRight("mrule_ipmanager_read")) {
				if(!$autocomp) {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,hostname,leasetime,distributed,server","macaddr = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-dhcp-distrib")."<div id=\"searchres\">";
						}
						if(strlen($data["hostname"]) > 0)
							$tmpoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
						if(strlen($data["ip"]) > 0)
							$tmpoutput .= $this->loc->s("link-ip").": <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />";
						$tmpoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("static"))." (".$data["server"].")<br />";
						if($data["distributed"] != 3)
							$tmpoutput .= $this->loc->s("Validity")." : ".$data["leasetime"]."<br />";
					}
					if($found) $tmpoutput .= "</div>";
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","macaddr","macaddr ILIKE '".$search."%'",array("order" => "macaddr","limit" => "10","group" => "macaddr"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["mac"],$data["macaddr"]);
				}
			}

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$query = FS::$dbMgr->Select("node_ip","ip,time_first,time_last","mac = '".$search."'",array("order" => "time_last","ordersens" => 2));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-ip-addr")."<div id=\"searchres\">";
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
					}
				
					if($found) $tmpoutput .= "</div>";
					$found = 0;
					
					$query = FS::$dbMgr->Select("node","switch,port,time_first,time_last","mac = '".$search."'",array("order" => "time_last","ordersens" => 2));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-network-places")."<div id=\"searchres\">";
						}
							$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
						$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
						$convport = preg_replace("#\/#","-",$data["port"]);
						$tmpoutput .=  "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
						$tmpoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
						$tmpoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
						$tmpoutput .= ($piece == NULL ? "" : " / Prise ".$piece);
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
					}

					if($found) $tmpoutput .= "</div>";
					$found = 0;

					$query = FS::$dbMgr->Select("node_nbt","nbname,domain,nbuser,time_first,time_last","mac = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput .= FS::$iMgr->h2("title-netbios-name")."<div id=\"searchres\">";
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= ($data["domain"] != "" ? "\\\\<a href=\"index.php?mod=".$this->mid."&nb=".$data["domain"]."\">".$data["domain"]."</a>" : "").
						"\\<a href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
						$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
					}

					if($found) $tmpoutput .= "</div>";
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select("node_ip","mac","text(mac) ILIKE '".$search."%'",array("order" => "mac","limit" => "10","group" => "mac"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["mac"],$data["mac"]);

					$query = FS::$dbMgr->Select("node","mac","text(mac) ILIKE '".$search."%'",array("order" => "mac","limit" => "10","group" => "mac"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["mac"],$data["mac"]);

					$query = FS::$dbMgr->Select("node_nbt","mac","text(mac) ILIKE '".$search."%'",array("order" => "mac","limit" => "10","group" => "mac"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["mac"],$data["mac"]);
				}
			}
	
			if(FS::$sessMgr->hasRight("mrule_radius_read")) {
				if(!$autocomp)
					$tmpoutput .= $this->showRadiusInfos($search);
				else
					$this->showRadiusInfos($search,true);
			}
			
			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					// Devices
					$query = FS::$dbMgr->Select("device","ip,name,description,model","mac = '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$tmpoutput .= FS::$iMgr->h2("Network-device")."<div id=\"searchres\">";
						$tmpoutput .= "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						$tmpoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$tmpoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br /></div>";
					}
				}
				else {
					$query = FS::$dbMgr->Select("device","mac","text(mac) ILIKE '".$search."%'",array("order" => "mac","limit" => "10","group" => "mac"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["mac"],$data["mac"]);
				}
			}
			if(!$autocomp) {
				if(strlen($tmpoutput) > 0)
					$output .= $tmpoutput;
				else
					$output .= FS::$iMgr->printError($this->loc->s("err-no-res"));
				return $output;
			}
		}
		private $autoresults;
	};
?>
