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

	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	final class iSearch extends FSModule {
		function __construct($locales) { 
			parent::__construct($locales);
			$this->autoresults = array("device" => array(), "dhcphostname" => array(), "dnsrecord" => array(), "ip" => array(),
				"mac" => array(), "nbdomain" => array(), "nbname" => array(), "portname" => array(),
				"prise" => array(), "room" => array(), "vlan" => array());
			$this->nbresults = 0;
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

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$swmodid = FS::$iMgr->getModuleIdByPath("switches");

					$tmpoutput .= $this->showPlugResults($search);
					$tmpoutput .= $this->showRoomResults($search);

					// VLAN on a device
					$locoutput = "";
					$query = FS::$dbMgr->Select("device_vlan","ip,description","vlan = '".$search."'",array("order" => "ip"));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($dname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'")) {
							if($found == 0)
								$found = 1;
							$locoutput .= "<li> <a href=\"index.php?mod=".$swmodid."&d=".$dname."&fltr=".$search."\">".$dname."</a> (".$data["description"].")<br />";
						}
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-vlan-device");
					$found = 0;
				}
				else {
					$this->fetchPlugAutoResults($search);
					$this->fetchRoomAutoResults($search);
					
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

			if(FS::$sessMgr->hasRight("mrule_switches_read")) {
				if(!$autocomp) {
					$swmodid = FS::$iMgr->getModuleIdByPath("switches");

					// Devices
					$query = FS::$dbMgr->Select("device","mac,ip,description,model","name ILIKE '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$locoutput = "<b>".$this->loc->s("Informations")."<i>: </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						if(strlen($data["mac"]) > 0)
							$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a> - ";
						$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
						$locoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$locoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br />";
						$tmpoutput .= $this->divEncapResults($locoutput,"Network-device");
						$this->nbresults++;
					}

					$tmpoutput .= $this->showPlugResults($search);

					$tmpoutput .= $this->showRoomResults($search);

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

						$this->nbresults++;
					}

					if($found) {
						$locoutput = "";
						foreach($devportname as $device => $devport) {
							if($locoutput != "") $locoutput .= FS::$iMgr->hr();
							$locoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
							foreach($devport as $port => $portdata) {
								$convport = preg_replace("#\/#","-",$port);
								$locoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
								$locoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ";
								$locoutput .= "<br /><b>".$this->loc->s("Description").":</b> ".$portdata[0];
								if($portdata[1]) $locoutput .= "<br /><b>".$this->loc->s("Plug").":</b> ".$portdata[1];
								$locoutput .= "</li>";
							}
							$locoutput .= "</ul>";
						}
						$tmpoutput .= $this->divEncapResults($locoutput,"Ref-desc");
					}
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select("device","name","name ILIKE '".$search."%'",array("order" => "name","limit" => "10","group" => "name"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["device"],$data["name"]);

					$this->fetchPlugAutoResults($search);
					$this->fetchRoomAutoResults($search);

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
						$locoutput = "";
						while($data = FS::$dbMgr->Fetch($query)) {
							if($found == 0) {
								$found = 1;
							}
							if($curserver != $data["server"]) {
								$curserver = $data["server"];
								$locoutput .= FS::$iMgr->h3($data["server"],true);
							}
							switch($data["rectype"]) {
								case "A": $locoutput .= $this->loc->s("ipv4-addr").": "; break;
								case "AAAA": $locoutput .= $this->loc->s("ipv6-addr").": "; break;
								case "CNAME": $locoutput .= $this->loc->s("Alias").": "; break;
								default: $locoutput .= $this->loc->s("Other")." (".$data["rectype"]."): "; break;
							}
							if(FS::$secMgr->isIP($data["recval"]))
								$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["recval"]."\">".$data["recval"]."</a>";
							else
								$locoutput .= $data["recval"];
							$locoutput .= "<br />";
							if($data["server"]) {
								$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
								if($out != NULL) {
									$locoutput .= FS::$iMgr->h4("dig-results");
									$locoutput .= preg_replace("#[\n]#","<br />",$out);
								}
							}
							$this->nbresults++;
						}
						if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-dns-records");
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
					$locoutput = "";
					$query = FS::$dbMgr->Select("node_nbt","mac,ip,domain,nbname,nbuser,time_first,time_last","domain ILIKE '%".$search."%' OR nbname ILIKE '%".$search."%'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$tmpoutput = "<table class=\"standardTable\"><tr><th>".$this->loc->s("Node")."</th><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("First-view")."</th><th>".$this->loc->s("Last-view")."</th></tr>";
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$tmpoutput .= "<tr><td><a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a></td><td>";
						$tmpoutput .= "\\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a></td><td>";
						$tmpoutput .= ($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")." @ <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a></td><td>".$fst[0]."</td><td>".$lst[0]."</td></tr>";
						$this->nbresults++;
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput."</table>","title-netbios");
					$found = 0;

					$tmpoutput .= $this->showRadiusInfos($search);
				}
				else {
					$query = FS::$dbMgr->Select("node_nbt","domain","domain ILIKE '%".$search."%'",
						array("order" => "domain","limit" => "10","group" => "domain"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["nbdomain"],$data["domain"]);

					$query = FS::$dbMgr->Select("node_nbt","nbname","nbname ILIKE '%".$search."%'",
						array("order" => "nbname","limit" => "10","group" => "nbname"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["nbname"],$data["nbname"]);
				}
			}

			if(FS::$sessMgr->hasRight("mrule_ipmanager_read")) {
				if(!$autocomp) {
					$locoutput = "";
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","hostname,macaddr,ip,leasetime,distributed,server","hostname ILIKE '%".$search."%'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						$locoutput .= "<b>".$this->loc->s("dhcp-hostname")."</b>: ".$data["hostname"]."<br />";
						if(strlen($data["ip"]) > 0)
							$locoutput .= "<b>".$this->loc->s("link-ip")."</b>: ".$data["ip"]."<br />";
						if(strlen($data["macaddr"]) > 0)
							$locoutput .= "<b>".$this->loc->s("link-mac-addr")."</b>: <a href=\"index.php?mod=".$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
						$output .= "<b>".$this->loc->s("attribution-type")."</b>: ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
						if($data["distributed"] != 3 && $data["distributed"] != 4)
							$locoutput .= "<b>".$this->loc->s("Validity")."</b>: ".$data["leasetime"]."<br />";
						$locoutput .= FS::$iMgr->hr();
						$this->nbresults++;
					}
			
					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-dhcp-hostname");
					$found = 0;
				}
				else {
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","hostname","hostname ILIKE '%".$search."%'",array("order" => "hostname","limit" => "10","group" => "hostname"));
					while($data = FS::$dbMgr->Fetch($query))
						array_push($this->autoresults["dhcphostname"],$data["hostname"]);

				}
			}
			
			if(!$autocomp) {
				if(strlen($tmpoutput) > 0)
					$output .= FS::$iMgr->h2($this->loc->s("title-res-nb").": ".$this->nbresults,true).$tmpoutput;

				return $output;
			}
		}

		private function showIPAddrResults($search,$autocomp=false) {
			$output = "";
			$tmpoutput = "";
			$found = 0;
			$lastmac = "";
			
			if(FS::$sessMgr->hasRight("mrule_dnsmgmt_read")) {
				if(!$autocomp) {
					$curserver = "";
					$locoutput = "";
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dns_zone_record_cache","zonename,record,server","recval ILIKE '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						if($curserver != $data["server"]) {
							$curserver = $data["server"];
							$locoutput .= FS::$iMgr->h4($data["server"],true);
						}
						$locoutput .= $data["record"].".".$data["zonename"].FS::$iMgr->hr();
						// Resolve with DIG to search what the DNS thinks
						if($data["server"]) {
							$out = shell_exec("/usr/bin/dig @".$data["server"]." +short ".$search);
							if($out != NULL) {
								$locoutput .= FS::$iMgr->h4("dig-results");
								$locoutput .= preg_replace("#[\n]#",FS::$iMgr->hr(),$out);
							}
						}
						$this->nbresults++;
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-dns-assoc");
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
					$locoutput = "";
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","macaddr,hostname,leasetime,distributed,server","ip = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						else $locoutput .= FS::$iMgr->hr();
						if(strlen($data["hostname"]) > 0)
							$locoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
						if(strlen($data["macaddr"]) > 0)
							$locoutput .= $this->loc->s("link-mac-addr").": <a href=\"index.php?mod=".$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
						$locoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
						if($data["distributed"] != 3 && $data["distributed"] != 4)
							$locoutput .= $this->loc->s("Validity")." : ".$data["leasetime"];
						$this->nbresults++;
					}
			
					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-dhcp-distrib");
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
					$locoutput = "";
					$query = FS::$dbMgr->Select("node_ip","mac,time_first,time_last","ip = '".$search."'",array("order" => "time_last","ordersens" => 1));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
							$lastmac = $data["mac"];
						}
						else
							$locoutput .= FS::$iMgr->hr();
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")";
						$this->nbresults++;
					}
				
					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-mac-addr");
					$found = 0;

					if($lastmac) {
						$locoutput = "";
						$query = FS::$dbMgr->Select("node","switch,port,time_first,time_last","mac ILIKE '".$lastmac."' AND active = 't'",array("order" => "time_last","ordersens" => 1,"limit" => 1));
						if($data = FS::$dbMgr->Fetch($query)) {
							$fst = preg_split("#\.#",$data["time_first"]);
							$lst = preg_split("#\.#",$data["time_last"]);
							$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
							$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
							$convport = preg_replace("#\/#","-",$data["port"]);
							$locoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
							$locoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
							$locoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
							if($piece) $tmpoutput .= "/ ".$this->loc->s("Plug")." ".$piece;
							$locoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
							$tmpoutput .= $this->divEncapResults($locoutput,"title-last-device");
						}
					}

					$locoutput = "";
					$query = FS::$dbMgr->Select("node_nbt","domain,nbname,nbuser,time_first,time_last","ip = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						else $locoutput .= FS::$iMgr->hr();

						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);

						$locoutput .= $this->loc->s("netbios-machine").": \\\\<a href=\"index.php?mod=".$this->mid."&s=".$data["domain"]."\">".$data["domain"]."</a>";
						$locoutput .= "\\<a href=\"index.php?mod=".$this->mid."&s=".$data["nbname"]."\">".$data["nbname"]."</a><br />";
						$locoutput .= $this->loc->s("netbios-user").": ".($data["nbuser"] != "" ? $data["nbuser"] : "[UNK]")."@".$search."<br />";
						$locoutput .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")";
						$this->nbresults++;
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-netbios");
					$found = 0;

					// Devices
					$query = FS::$dbMgr->Select("device","mac,name,description,model","ip = '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$locoutput = "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
						$locoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["mac"]."\">".$data["mac"]."</a>)<br />";
						$locoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$locoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br />";
						$tmpoutput = $this->divEncapResults($locoutput,"Network-device");
						$this->nbresults++;
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
					$output .= FS::$iMgr->h2($this->loc->s("title-res-nb").": ".$this->nbresults,true).$tmpoutput;
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

					$raddatas = false;

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
						$raddatas = true;
						$query2 = $radSQLMgr->Select($data["tradusrgrp"],"groupname","username = '".$tmpsearch."'");
						while($data2 = $radSQLMgr->Fetch($query2)) {
							if(!$found) {
								$found = 1;
								$output .= "<ul>";
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
								$raddatas = true;
							}
							$fst = preg_split("#\.#",$data2["acctstarttime"]);
							$lst = preg_split("#\.#",$data2["acctstoptime"]);
							$locoutput = $this->loc->s("User").": ".$data2["username"]." / ".$this->loc->s("Device").": <a href=\"index.php?mod=".
							$this->mid."&s=".$data2["calledstationid"]."\">".$data2["calledstationid"]."</a>";
							$output .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
						}

						if($found) $output = FS::$iMgr->h3("title-8021x-users").$output;
						$found = 0;
						$totinbw = 0;
						$totoutbw = 0;
						$locoutput = "";
						$query2 = $radSQLMgr->Select($data["tradacct"],"calledstationid, SUM(acctinputoctets) as input, SUM(acctoutputoctets) as output, MIN(acctstarttime) as fst, MAX(acctstoptime) as lst","callingstationid = '".$tmpsearch."'",array("group" => "calledstationid"));
						if($data2 = $radSQLMgr->Fetch($query2)) {
							if($found == 0) {
								$found = 1;
								$raddatas = true;
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
							$locoutput .= $this->loc->s("Device").": ".$data2["calledstationid"]." Download: ".$inputbw." / Upload: ".$outputbw. "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
							(strlen($lst[0]) > 0 ? $this->loc->s("Between") : $this->loc->s("Since"))." ".$fst[0].(strlen($lst[0]) > 0 ? " ".$this->loc->s("and-the")." ".$lst[0] : "").")<br />".FS::$iMgr->hr();
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
							$tmpoutput = "<b>".$this->loc->s("Total")."</b> Download: ".$inputbw." / Upload: ".$outputbw."</div>";
							$output .= FS::$iMgr->h3("title-8021x-bw").$locoutput;
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
							$raddatas = true;
							$output .= "<table><tr><th>".$this->loc->s("Device")."</th><th>".$this->loc->s("start-session")."</th><th>".$this->loc->s("end-session")."</th><th>".$this->loc->s("Upload")."</th>
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
						$output .= "<tr><td>".(strlen($macdev) > 0 ? "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$macdev."\">".$macdev."</a>" : $this->loc->s("Unknown"))."</td>
							<td>".date("d-m-y H:i:s",strtotime($data2["acctstarttime"]))."</td><td>".
							($data2["acctstoptime"] != NULL ? date("d-m-y H:i:s",strtotime($data2["acctstoptime"])) : "").
						"</td><td>".$inputbw."</td><td>".$outputbw."</td>
						<td>".$data2["acctterminatecause"]."</td></tr>";
					}
					if($found) {
						$output .= "</table>";
					}
					if($raddatas) { 
						$output = $this->divEncapResults(FS::$iMgr->h3($this->loc->s("Radius-Server")." (".$data["dbname"].
							"@".$data["addr"].":".$data["port"].")",true).FS::$iMgr->hr().$output,"",true);
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
		 		$tmpoutput .= $this->divEncapResults($company,"Manufacturer");
			}

			if(FS::$sessMgr->hasRight("mrule_ipmanager_read")) {
				if(!$autocomp) {
					$locoutput = "";
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_ip_cache","ip,hostname,leasetime,distributed,server","macaddr = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						else $locoutput .= FS::$iMgr->hr();
						if(strlen($data["hostname"]) > 0)
							$locoutput .= $this->loc->s("dhcp-hostname").": ".$data["hostname"]."<br />";
						if(strlen($data["ip"]) > 0)
							$locoutput .= $this->loc->s("link-ip").": <a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />";
						$locoutput .= $this->loc->s("attribution-type").": ".($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("static"))." (".$data["server"].")<br />";
						if($data["distributed"] != 3)
							$locoutput .= $this->loc->s("Validity")." : ".$data["leasetime"]."<br />";
					}
					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-dhcp-distrib");
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
					$locoutput = "";
					$query = FS::$dbMgr->Select("node_ip","ip,time_first,time_last","mac = '".$search."'",array("order" => "time_last","ordersens" => 2));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						else $locoutput .= FS::$iMgr->hr();
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
					}
				
					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-ip-addr");
					$found = 0;
					
					$locoutput = "";
					$query = FS::$dbMgr->Select("node","switch,port,time_first,time_last","mac = '".$search."'",array("order" => "time_last","ordersens" => 2));
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
							$switch = FS::$dbMgr->GetOneData("device","name","ip = '".$data["switch"]."'");
						$piece = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."switch_port_prises","prise","ip = '".$data["switch"]."' AND port = '".$data["port"]."'");
						$convport = preg_replace("#\/#","-",$data["port"]);
						$locoutput .=  "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."\">".$switch."</a> ";
						$locoutput .= "[<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."#".$convport."\">".$data["port"]."</a>] ";
						$locoutput .= "<a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$switch."&p=".$data["port"]."\">".FS::$iMgr->img("styles/images/pencil.gif",10,10)."</a>";
						$locoutput .= ($piece == NULL ? "" : " / Prise ".$piece);
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$locoutput .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Entre le ".$fst[0]." et le ".$lst[0].")<br />";
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-network-places");
					$found = 0;

					$locoutput = "";
					$query = FS::$dbMgr->Select("node_nbt","nbname,domain,nbuser,time_first,time_last","mac = '".$search."'");
					while($data = FS::$dbMgr->Fetch($query)) {
						if($found == 0) {
							$found = 1;
						}
						$fst = preg_split("#\.#",$data["time_first"]);
						$lst = preg_split("#\.#",$data["time_last"]);
						$locoutput .= ($data["domain"] != "" ? "\\\\<a href=\"index.php?mod=".$this->mid."&nb=".$data["domain"]."\">".$data["domain"]."</a>" : "").
						"\\<a href=\"index.php?mod=".$this->mid."&node=".$data["nbname"]."\">".$data["nbname"]."</a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".
						$this->loc->s("Between")." ".$fst[0]." ".$this->loc->s("and-the")." ".$lst[0].")<br />";
					}

					if($found) $tmpoutput .= $this->divEncapResults($locoutput,"title-netbios-name");
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
					$locoutput = "";
					$query = FS::$dbMgr->Select("device","ip,name,description,model","mac = '".$search."'");
					if($data = FS::$dbMgr->Fetch($query)) {
						$locoutput .= "<b><i>".$this->loc->s("Name").": </i></b><a href=\"index.php?mod=".$this->mid."&s=".$data["name"]."\">".$data["name"]."</a><br />";
						$locoutput .= "<b><i>".$this->loc->s("Informations").": </i></b><a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("switches")."&d=".$search."\">".$search."</a> (";
						$locoutput .= "<a href=\"index.php?mod=".$this->mid."&s=".$data["ip"]."\">".$data["ip"]."</a>)<br />";
						$locoutput .= "<b><i>".$this->loc->s("Model").":</i></b> ".$data["model"]."<br />";
						$locoutput .= "<b><i>".$this->loc->s("Description").": </i></b>".preg_replace("#\\n#","<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$data["description"])."<br />";
						$tmpoutput .= $this->divEncapResults($locoutput,"Network-device");
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

		// Prise number
		private function showPlugResults($search) {	
			$found = 0;
			$tmpoutput = "";
			$swmodid = FS::$iMgr->getModuleIdByPath("switches");

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","ip,port,prise","prise ILIKE '".$search."%'",array("order" => "port"));
			$devprise = array();
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == 0) {
					$found = 1;
				}
				$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
				if(!isset($devprise[$swname]))
					$devprise[$swname] = array();

				$devprise[$swname][$data["port"]] = $data["prise"];
				$this->nbresults++;
			}
			if($found) {
				$locoutput = "";
				foreach($devprise as $device => $devport) {
					$locoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
					foreach($devport as $port => $prise) {
						$convport = preg_replace("#\/#","-",$port);
						$locoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
						$locoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ";
						$locoutput .= "<br /><b>".$this->loc->s("Plug").":</b> ".$prise."</li>";
					}
					$locoutput .= "</ul><br />";
				}
				$tmpoutput .= $this->divEncapResults($locoutput,"Ref-plug");
			}
			return $tmpoutput;
		}

		private function fetchPlugAutoResults($search) {	
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","prise","prise ILIKE '".$search."%'",array("order" => "prise","limit" => "10","group" => "prise"));
			while($data = FS::$dbMgr->Fetch($query))
				array_push($this->autoresults["prise"],$data["prise"]);
		}

		// Room number
		private function showRoomResults($search) {	
			$found = 0;
			$tmpoutput = "";
			$swmodid = FS::$iMgr->getModuleIdByPath("switches");

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","ip,port,room","room ILIKE '".$search."%'",array("order" => "port"));
			$devroom = array();
			while($data = FS::$dbMgr->Fetch($query)) {
				if($found == 0) {
					$found = 1;
				}
				$swname = FS::$dbMgr->GetOneData("device","name","ip = '".$data["ip"]."'");
				if(!isset($devroom[$swname]))
					$devroom[$swname] = array();

				$devroom[$swname][$data["port"]] = $data["room"];
				$this->nbresults++;
			}
			if($found) {
				$locoutput = "";
				foreach($devroom as $device => $devport) {
					$locoutput .= $this->loc->s("Device").": <a href=\"index.php?mod=".$swmodid."&d=".$device."\">".$device."</a><ul>";
					foreach($devport as $port => $room) {
						$convport = preg_replace("#\/#","-",$port);
						$locoutput .= "<li><a href=\"index.php?mod=".$swmodid."&d=".$device."#".$convport."\">".$port."</a> ";
						$locoutput .= "<a href=\"index.php?mod=".$swmodid."&d=".$device."&p=".$port."\">".FS::$iMgr->img("styles/images/pencil.gif",12,12)."</a> ";
						$locoutput .= "<br /><b>".$this->loc->s("Room").":</b> ".$room."</li>";
					}
					$locoutput .= "</ul><br />";
				}
				$tmpoutput .= $this->divEncapResults($locoutput,"Ref-room");
			}
			return $tmpoutput;
		}

		private function fetchRoomAutoResults($search) {
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."switch_port_prises","room","room ILIKE '".$search."%'",array("order" => "room","limit" => "10","group" => "room"));
			while($data = FS::$dbMgr->Fetch($query))
				array_push($this->autoresults["room"],$data["room"]);
		}

		private function divEncapResults($output,$title,$minwidth=false) {
			return "<div id=\"searchres\"".($minwidth ? " style=\"width: auto; min-width:400px;\"" : "").">".
				($title != "" ? FS::$iMgr->h3($title).FS::$iMgr->hr() : "").$output."</div>";
		}

		private $autoresults;
		private $nbresults;
	};
?>
