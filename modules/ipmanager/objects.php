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

	final class dhcpSubnet extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared";
			$this->sqlTablev6 = PGDbConfig::getDbPrefix()."dhcp_subnet_v6_declared";
			$this->sqlAttrId = "netid";
			$this->readRight = "mrule_ipmgmt_subnetmgmt";
			$this->writeRight = "mrule_ipmgmt_subnetmgmt";
			$this->errNotExists = "err-subnet-not-exists";
			$this->errAlreadyExists = "err-subnet-already-exists";

			$this->tMgr = new HTMLTableMgr(array(
				"htmgrid" => "declsubnets",
				"sqltable" => "dhcp_subnet_v4_declared",
				"sqlattrid" => "netid",
				//"attrlist" => array(array("ACL","aclname",""), array("Desc","description","")), @TODO
				"sorted" => true,
				"odivnb" => 1,
				"odivlink" => "netid=",
				"rmcol" => true,
				"rmlink" => "mod=".$this->mid."&act=8&netid",
				"rmconfirm" => "confirm-remove-subnet",
				"trpfx" => "ds",
			));
		}

		public function getSelect($options = array()) {
			$multi = (isset($options["multi"]) && $options["multi"] == true);
			$sqlcond = (isset($options["exclude"])) ? "netid != '".$options["exclude"]."'" : "";
			$none = (isset($options["noneelmt"]) && $options["noneelmt"] == true);
			$selected = (isset($options["selected"])) ? $options["selected"] : array();
			$onlyelements = (isset($options["onlyelmts"]) && $options["onlyelmts"] == true);
			$withcache = (isset($options["withcache"]) && $options["withcache"] == true);

			$netarray = array();
			$output = "";
			$found = false;
			
			if (!$onlyelements) {
				$output .= FS::$iMgr->select($options["name"],array("multi" => $multi));
			}

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",in_array("none",$selected));
			}

			// bufferize with cache first because cache has less datas
			if ($withcache) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_cache","netid,netmask");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					$netarray[$data["netid"]] = sprintf("%s/%s (%s)",
						$data["netid"],$data["netmask"],$this->loc->s("in-cache"));
				}
			}
			
			// Then bufferize with declared datas and override cached datas
			$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",netmask,vlanid,subnet_short_name",$sqlcond,
				array("order" => $this->sqlAttrId));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
				}
				
				if ($data["vlanid"] != 0) {
					$netarray[$data[$this->sqlAttrId]] = sprintf("%s/%s (VLAN %s - %s)",
						$data[$this->sqlAttrId],$data["netmask"],
						$data["vlanid"],$data["subnet_short_name"]);
				}
				else {
					$netarray[$data[$this->sqlAttrId]] = sprintf("%s/%s (%s)",
						$data[$this->sqlAttrId],$data["netmask"],
						$data["subnet_short_name"]);
				}
			}
			
			ksort($netarray);
			foreach ($netarray as $netid => $value) {
				$selectEl = (is_array($selected) ? in_array($netid,$selected) : $netid == $selected);
				$output .= FS::$iMgr->selElmt($value,$netid,$selectEl);
			}
			
			// If no elements found & no empty element
			if (!$found && !$none) {
				return NULL;
			}

			if (!$onlyelements) {
				$output .= "</select>";
			}
			return $output;
		}

		public function Load($netid = "") {
			$this->netid = $netid;
			$this->netmask = "";
			$this->vlanid = 0;
			$this->shortname = "";
			$this->desc = "";
			$this->router = "";
			$this->dns1 = "";
			$this->dns2 = "";
			$this->domainname = "";
			$this->maxleasetime = 0;
			$this->defaultleasetime = 0;
			$this->netidv6 = "";
			$this->prefixlenv6 = 0;

			if ($this->netid) {
				$query = FS::$dbMgr->Select($this->sqlTable,"netmask,vlanid,subnet_short_name,subnet_desc,router,dns1,dns2,domainname,mleasetime,dleasetime",
					$this->sqlAttrId." = '".$this->netid."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->netmask = $data["netmask"];
					$this->vlanid = $data["vlanid"];
					$this->shortname = $data["subnet_short_name"];
					$this->desc = $data["subnet_desc"];
					$this->router = $data["router"];
					$this->dns1 = $data["dns1"];
					$this->dns2 = $data["dns2"];
					$this->domainname = $data["domainname"];
					$this->maxleasetime = $data["mleasetime"];
					$this->defaultleasetime = $data["dleasetime"];
					
					$this->netCalc = new FSNetwork();
					$this->netCalc->setNetAddr($this->netid);
					$this->netCalc->setNetMask($this->netmask);
					
					$query2 = FS::$dbMgr->Select($this->sqlTablev6,"netid,prefixlen","netidv4 = '".$this->netid."'");
					if ($data2 = FS::$dbMgr->Fetch($query2)) {
						$this->netidv6 = $data2["netid"];
						$this->prefixlenv6 = $data2["prefixlen"];
					}
					return true;
				}
				return false;
			}
			return true;
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return "";
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,
						"vlanid","CAST(vlanid as TEXT) ILIKE '".$search."%'",
						array("order" => "vlanid","limit" => "10","group" => "vlanid"));

				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("vlan",$data["vlanid"]);
				}
				
				$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","subnet_short_name",
					"subnet_short_name ILIKE '".$search."%'",
					array("order" => "subnet_short_name","limit" => "10","group" => "subnet_short_name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpsubnet",$data["subnet_short_name"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"netid","CAST(netid as TEXT) ILIKE '".$search."%'",
					array("order" => "netid","limit" => "10","group" => $this->sqlAttrId));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpsubnet",$data["netid"]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				// by VLAN ID
				if (FS::$secMgr->isNumeric($search)) {
					$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,subnet_short_name","vlanid = '".$search."'");
					if ($data = FS::$dbMgr->Fetch($query)) {
						$output .= $this->loc->s("subnet-shortname").": ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("ipmanager").
							"&sh=2", $data["subnet_short_name"])."<br />".
							$this->loc->s("netid").": ".$data["netid"]."<br />".
							$this->loc->s("netmask").": ".$data["netmask"]."<br />";
						FS::$searchMgr->incResultCount();
						$this->storeSearchResult($output,"title-vlan-ipmanager");
					}
				}
				
				// by shortname
				$output = "";
				
				$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,vlanid","subnet_short_name = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					$output .= $this->loc->s("vlanid").": ".FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("ipmanager").
						"&sh=2>", $data["vlanid"])."<br />".
						$this->loc->s("netid").": ".$data["netid"]."<br />".
						$this->loc->s("netmask").": ".$data["netmask"]."<br />";
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-vlan-ipmanager");
				}
				
				// by netid
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,vlanid,subnet_short_name","netid = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					$output .= "<b>".$this->loc->s("subnet-shortname")."</b>: ".
						FS::$iMgr->aLink(FS::$iMgr->getModuleIdByPath("ipmanager")."&sh=2", $data["subnet_short_name"])."<br />".
						"<b>".$this->loc->s("netid")."</b>: ".$data["netid"]."<br />".
						"<b>".$this->loc->s("netmask")."</b>: ".$data["netmask"]."<br />".
						"<b>".$this->loc->s("vlanid")."</b>: ".$data["vlanid"]."<br />";
					FS::$iMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-subnet-ipmanager");
				}
			}
		}
		
		public function isIPIn($ip) {
			return $netCalc->isUsableIP($ip);
		}
		
		public function getNetCalc() {
			return $this->netCalc;
		}
		
		private $netid;
		private $netmask;
		private $netidv6;
		private $prefixlenv6;
		private $vlanid;
		private $shortname;
		private $desc;
		private $router;
		private $dns1;
		private $dns2;
		private $domainname;
		private $maxleasetime;
		private $defaultleasetime;
		private $netCalc;
	};
	
	final class dhcpServer extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_servers";
			$this->readRight = "mrule_ipmanager_servermgmt";
			$this->writeRight = "mrule_ipmanager_servermgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description","description ILIKE '%".$search."%'",array("order" => "description","limit" => "10",
					"group" => "description"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpserver",$data["description"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"alias","alias ILIKE '%".$search."%'",array("order" => "alias","limit" => "10",
					"group" => "alias"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpserver",$data["alias"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"addr","addr ILIKE '%".$search."%'",array("order" => "addr","limit" => "10",
					"group" => "addr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpserver",$data["addr"]);
				}
			}
			else {
				$output = "";
				$found = false;

				$query = FS::$dbMgr->Select($this->sqlTable,"addr,alias,description,osname,dhcptype",
					"description ILIKE '%".$search."%' or alias ILIKE '%".$search."%' or addr = '".$search."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}

					$output .= "<b>".$this->loc->s("DHCP-name")."</b>: ".$data["alias"]."<br />".
						"<b>".$this->loc->s("Address")."</b>: ".$data["addr"]."<br />".
						"<b>".$this->loc->s("Description")."</b>: ".$data["description"]."<br />".
						"<b>".$this->loc->s("os")."</b>: ".$data["osname"]."<br />";
					switch($data["dhcptype"]) {
						case 1:
							$output .= "<b>".$this->loc->s("DHCP-type")."</b>: ISC-DHCPD<br />";
							break;
					}
					$output .= FS::$iMgr->hr();
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-servers");
				}
			}
		}
	};
	
	final class dhcpCluster extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_cluster";
			$this->readRight = "mrule_ipmanager_servermgmt";
			$this->writeRight = "mrule_ipmanager_servermgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"clustername","clustername ILIKE '%".$search."%'",array("order" => "clustername","limit" => "10",
					"group" => "clustername"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpcluster",$data["clustername"]);
				}
			}
			else {
				$clusters = array();
				$output = "";
				$found = false;

				$query = FS::$dbMgr->Select($this->sqlTable,"clustername,dhcpaddr",
					"clustername ILIKE '%".$search."%'",array("order" => "clustername"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}

					if (!isset($clusters[$data["clustername"]])) {
						$clusters[$data["clustername"]] = array();
					}

					$clusters[$data["clustername"]][] = $data["dhcpaddr"];
				}

				if ($found) {
					foreach ($clusters as $cname => $members) {
						$output .= "<b>".$this->loc->s("DHCP-cluster")."</b>: ".$cname."<br /><b>".
							$this->loc->s("Members").":</b><ul>";

						$count = count($members);
						for ($i=0;$i<$count;$i++) {
							$alias = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_servers","alias","addr = '".$members[$i]."'");
							$output .= "<li>".($alias ? $alias." (" : "").$members[$i].($alias ? ")" : "")."</li>";
						}
						$output .= "</ul>".FS::$iMgr->hr();
						FS::$searchMgr->incResultCount();
					}
					$this->storeSearchResult($output,"title-dhcp-cluster");
				}
			}
		}
	};
		
	final class dhcpIP extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_ip";
			$this->sqlCacheTable = PGDbConfig::getDbPrefix()."dhcp_ip_cache";
			$this->readRight = "mrule_ipmanager_read";
			$this->writeRight = "mrule_ipmmgmt_subnetmgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"hostname", "hostname ILIKE '%".$search."%'",
					array("order" => "hostname","limit" => "10","group" => "hostname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcphostname",$data["hostname"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"ip","ip ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("ip",$data["ip"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"ip","ip ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("ip",$data["ip"]);
				}
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"macaddr","macaddr ILIKE '".$search."%'",
					array("order" => "macaddr","limit" => "10","group" => "macaddr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["macaddr"]);
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"macaddr","macaddr ILIKE '".$search."%'",
					array("order" => "macaddr","limit" => "10","group" => "macaddr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("mac",$data["macaddr"]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"ip,macaddr,hostname,comment,reserv",
					"ip = '".$search."' OR CAST(macaddr AS varchar) ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}

					if (strlen($data["ip"]) > 0) {
						$output .= $this->loc->s("link-ip").": ".FS::$iMgr->aLink($this->mid.
							"&s=".$data["ip"], $data["ip"])."<br />";
					}
							
					if (strlen($data["hostname"]) > 0) {
						$output .= "<b>".$this->loc->s("dhcp-hostname")."</b>: ".$data["hostname"]."<br />";
					}

					if (strlen($data["macaddr"]) > 0) {
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: ".FS::$iMgr->aLink($this->mid.
							"&s=".$data["macaddr"], $data["macaddr"])."<br />";
					}

					if (strlen($data["comment"]) > 0) {
						$output .= "<b>".$this->loc->s("comment")."</b>: ".$data["comment"]."<br />";
					}

					if ($data["reserv"] == 't') {
						$output .= "<b>".$this->loc->s("active-reserv")."</b><br />";
					}
					else {
						$output .= $this->loc->s("inactive-reserv")."<br />";
					}

					FS::$searchMgr->incResultCount();
				}
		
				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-distrib-z-eye");
				}
				
				$found = false;
				$output = "";
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"hostname,macaddr,ip,leasetime,distributed,server","hostname ILIKE '%".$search."%'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					$output .= "<b>".$this->loc->s("dhcp-hostname")."</b>: ".$data["hostname"]."<br />";
					if (strlen($data["ip"]) > 0) {
						$output .= "<b>".$this->loc->s("link-ip")."</b>: ".$data["ip"]."<br />";
					}
					
					if (strlen($data["macaddr"]) > 0) {
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: ".
							FS::$iMgr->aLink($this->mid."&s=".$data["macaddr"], $data["macaddr"])."<br />";
					}
					$output .= "<b>".$this->loc->s("attribution-type")."</b>: ".
						($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
					if ($data["distributed"] != 3 && $data["distributed"] != 4) {
						$output .= "<b>".$this->loc->s("Validity")."</b>: ".$data["leasetime"]."<br />";
					}
					$output .= FS::$iMgr->hr();
					FS::$searchMgr->incResultCount();
				}
				
				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-hostname");
				}
				
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"macaddr,hostname,leasetime,distributed,server",
					"ip = '".$search."' OR CAST(macaddr as varchar) = '".$search."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}
					
					if (strlen($data["hostname"]) > 0) {
						$output .= "<b>".$this->loc->s("dhcp-hostname")."</b>: ".$data["hostname"]."<br />";
					}

					if (strlen($data["macaddr"]) > 0) {
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: ".
							FS::$iMgr->aLink($this->mid."&s=".$data["macaddr"], $data["macaddr"])."<br />";
					}

					$output .= "<b>".$this->loc->s("attribution-type")."</b>: ".
						($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
						
					if ($data["distributed"] != 3 && $data["distributed"] != 4) {
						$output .= $this->loc->s("Validity")." : ".$data["leasetime"];
					}
					FS::$searchMgr->incResultCount();
				}
		
				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-distrib");
				}
			}
		}
		
		protected function Load($ip = "") {
			$this->ip = $ip;
			$this->mac = "";
			$this->hostname = "";
			$this->reserv = false;
			$this->comment = "";
			
			if ($this->ip) {
				$query = FS::$dbMgr->Select($this->sqlTable,"macaddr,hostname,reserv,comment","ip = '".$this->ip."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->mac = $data["macaddr"];
					$this->hostname = $data["hostname"];
					$this->reserv = ($data["reserv"] == "t");
					$this->comment = $data["comment"];
					return true;
				}
				return false;
			}
			return true;
		}
		
		private function LoadFromCache($ip = "") {
			$this->ip = $ip;
			$this->mac = "";
			$this->hostname = "";
			$this->reserv = false;
			$this->comment = "";
			
			if ($this->ip) {
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"macaddr,hostname","ip = '".$this->ip."' AND distributed = '3'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->mac = $data["macaddr"];
					$this->hostname = $data["hostname"];
					$this->reserv = true;
					return true;
				}
				return false;
			}
			return true;
		}
			
		public function importIPFromCache() {
			$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
			$subnet = FS::$secMgr->checkAndSecuriseGetData("subnet");

			if (!$subnet || !$ip) {
				$this->log(2,"Import IP from cache: bad datas");
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return false;
			}
			
			$subnetObj = new dhcpSubnet();
			if (!$subnetObj->Load($subnet)) {
				$this->log(2,"Import IP from cache: invalid subnet");
				FS::$iMgr->ajaxEcho("err-subnet-not-exists");
				return false;
			}
			
			if (!FS::$sessMgr->hasRight("mrule_ipmmgmt_ipmgmt")) {
				$this->log(2,"Import IP from cache: no rights");
				FS::$iMgr->ajaxEcho("err-no-rights");
				return false;
			}
			
			if (!FS::$secMgr->isIP($ip)) {
				$this->log(2,"Import IP from cache: bad IP");
				FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-ip"),
					$ip),"",true);
				return false;
			}
			
			// @TODO
			if (!$this->LoadFromCache($ip)) {
				$this->log(2,"Import IP from cache: IP not in cache");
				FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-ip-not-in-cache"),
					$ip),"",true);
				return false;
				
			}
			
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"ip = '".$this->ip."'");
			FS::$dbMgr->Insert($this->sqlTable,"ip,macaddr,hostname,reserv,comment",
				"'".$this->ip."','".$this->mac."','".$this->hostname."','t',''");
			FS::$dbMgr->CommitTr();
			
			return $subnetObj->getNetCalc();
		}
		
		public function injectIPCSV() {
			$csv = FS::$secMgr->checkAndSecurisePostData("csv");
			$sep = FS::$secMgr->checkAndSecurisePostData("sep");
			$repl = FS::$secMgr->checkAndSecurisePostData("repl");
			$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");
			
			if (!$subnet || !$csv || !$sep || $sep != "," && $sep != ";") {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return false;
			}
			
			$subnetObj = new dhcpSubnet();
			if (!$subnetObj->Load($subnet)) {
				FS::$iMgr->ajaxEcho("err-subnet-not-exists");
				return false;
			}
			
			if (!FS::$sessMgr->hasRight("mrule_ipmmgmt_ipmgmt")) {
				$this->log(2,"Import IP via CSV: no rights");
				FS::$iMgr->ajaxEcho("err-no-rights");
				return false;
			}
			
			$csv = preg_replace("#[\r]#","",$csv);
			$lines = preg_split("#[\n]#",$csv);
			if (!$lines) {
				FS::$iMgr->ajaxEcho("err-invalid-csv");
				return false;
			}
			
			$hostList = array();
			$tmpIPList = array();
			$tmpMACList = array();
			
			// @TODO multiple MAC errors
			$count = count($lines);
			for ($i=0;$i<$count;$i++) {
				$entry = preg_split("#[".$sep."]#",$lines[$i]);
				
				// Entry has 3 fields
				if (count($entry) != 3) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-csv-entry"),$entry),"",true);
					return false;
				}
				
				if (!FS::$secMgr->isHostname($entry[0])) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-hostname"),
						$entry[0]),"",true);
					return false;
				}
				
				if (!FS::$secMgr->isIP($entry[2])) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-ip"),
						$entry[2]),"",true);
					return false;
				}
				
				if (!FS::$secMgr->isMacAddr($entry[1])) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-mac"),
						$entry[1]),"",true);
					return false;
				}
				
				// Hostname must be unique in this import
				if (isset($hostList[$entry[0]])) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-csv-entry-multiple-hostname"),
						$entry[0]),"",true);
					return false;
				}
				
				// Hostname mustn't be used if replace is not selected
				if ($repl != "on" && FS::$dbMgr->GetOneData($this->sqlTable,"hostname",
					"hostname = '".$entry[0]."' AND reserv = 't'")) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-hostname-already-used"),
						$entry[0]),"",true);
					return false;
				}
				
				// IP must be in selected subnet
				if (!$subnetObj->isIPIn($entry[2])) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-ip-not-in-subnet"),
						$entry[2],$subnet),"",true);
					return false;
				}
				
				// IP must be unique in this import
				if (in_array($entry[2],$tmpIPList)) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-csv-entry-multiple-ip"),
						$entry[2]),"",true);
					return false;
				}
				
				// IP mustn't be used if replace is not selected
				if ($repl != "on" && FS::$dbMgr->GetOneData($this->sqlTable,"ip",
					"ip = '".$entry[2]."' AND reserv = 't'")) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-ip-already-used"),
						$entry[2]),"",true);
					return false;
				}
				
				// cleanup MAC Address
				$entry[1] = strtolower(preg_replace("#[-]#",":",$entry[1]));
				
				// MAC mustn't be used if replace is not selected
				if ($repl != "on" && FS::$dbMgr->GetOneData($this->sqlTable,"macaddr",
					"macaddr = '".$entry[1]."' AND reserv = 't'")) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-mac-already-used"),
						$entry[1]),"",true);
					return false;
				}
				
				// MAC must be unique in this import
				if (in_array($entry[1],$tmpMACList)) {
					FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-invalid-csv-entry-multiple-mac"),
						$entry[1]),"",true);
					return false;
				}
				
				// Create array dimension by hostname
				$hostList[$entry[0]] = array();
				
				// Create array dimension by IP
				$hostList[$entry[0]][$entry[2]] = $entry[1];
				$tmpIPList[] = $entry[2];
				$tmpMACList[] = $entry[1];
			}
			
			FS::$dbMgr->BeginTr();
			foreach ($hostList as $hostname => $values) {
				foreach($values as $IP => $mac) {
					if ($repl == "on") {
						FS::$dbMgr->Delete($this->sqlTable,"ip = '".$IP."' OR macaddr = '".$mac."' or hostname = '".$hostname."'");
					}
					FS::$dbMgr->Insert($this->sqlTable,"ip,macaddr,hostname,reserv","'".$IP."','".$mac."','".$hostname."','t'");
				}
			}
			FS::$dbMgr->CommitTr();
			return $subnetObj->getNetCalc();
		}
		
		public function getIP() {
			return $this->ip;
		}
		
		private $sqlCacheTable;
		private $ip;
		private $mac;
		private $hostname;
		private $reserv;
		private $comment;
	};
	
	final class dhcpCustomOption extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_custom_option";
			$this->readRight = "mrule_ipmanager_optionsmgmt";
			$this->writeRight = "mrule_ipmanager_optionsmgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optname","optname ILIKE '%".$search."%'",array("order" => "optname","limit" => "10",
					"group" => "optname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpoptions",$data["optname"]);
				}
			}
			else {
				// Custom DHCP options
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"optcode,opttype,optname",
					"optname ILIKE '%".$search."%' AND protectrm = 'f'",array("order" => "optname", "group" => "optname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}

					$output .= "<b>".$this->loc->s("option-name")."</b>: ".$data["optname"]."<br />".
						"<b>".$this->loc->s("option-code")."</b>: ".$data["optcode"]."<br />".
						"<b>".$this->loc->s("option-type")."</b>: ".$data["opttype"]."<br />".
						FS::$iMgr->hr();
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-custom-options");
				}
			}
		}
	};
	
	final class dhcpOption extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_option";
			$this->readRight = "mrule_ipmanager_optionsmgmt";
			$this->writeRight = "mrule_ipmanager_optionsmgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optalias","optalias ILIKE '%".$search."%'",array("order" => "optalias","limit" => "10",
					"group" => "optalias"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcptions",$data["optalias"]);
				}
			}
			else {
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"optalias,optname,optval",
					"optalias ILIKE '%".$search."%'",array("order" => "optalias", "group" => "optalias"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}

					$output .= "<b>".$this->loc->s("option-alias")."</b>: ".$data["optalias"]."<br />".
						"<b>".$this->loc->s("option-name")."</b>: ".$data["optname"]."<br />".
						"<b>".$this->loc->s("option-value")."</b>: ".$data["optval"]."<br />".
						FS::$iMgr->hr();
					FS::$searchMgr->incResultCount();
				}

				if ($found) {
					$this->storeSearchResult($output,"title-dhcp-options");
				}
			}
		}
	};
	
	final class dhcpOptionGroup extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_option_group";
			$this->readRight = "mrule_ipmanager_optionsmgmt";
			$this->writeRight = "mrule_ipmanager_optionsmgmt";
		}
		
		public function search($search, $autocomplete = false) {
			if (!$this->canRead()) {
				return;
			}
			
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optgroup","optgroup ILIKE '%".$search."%'",array("order" => "optgroup","limit" => "10",
					"group" => "optgroup"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					FS::$searchMgr->addAR("dhcpoptions",$data["optgroup"]);
				}
			}
			else {
				$output = "";
				$found = false;
				$optgroups = array();

				$query = FS::$dbMgr->Select($this->sqlTable,"optgroup,optalias",
					"optgroup ILIKE '%".$search."%'",array("order" => "optgroup"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = true;
					}
					
					if (!isset($optgroups[$data["optgroup"]])) {
						$optgroups[$data["optgroup"]] = array();
					}
					$optgroups[$data["optgroup"]][] = $data["optalias"];
				}

				if ($found) {
					foreach ($optgroups as $gname => $members) {
						$output .= "<b>".$this->loc->s("option-group")."</b>: ".$gname."<br />".
							"<b>".$this->loc->s("Members")."</b>: <ul>";

						$count = count($members);
						for ($i=0;$i<$count;$i++) {
							$output .= "<li>".$members[$i]."</li>";
						}
							
						$output .= "</ul>".FS::$iMgr->hr();
						FS::$searchMgr->incResultCount();
					}

					$this->storeSearchResult($output,"title-dhcp-option-groups");
				}
			}
		}
	};
?>

