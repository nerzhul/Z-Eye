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

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			$found = false;
			$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",netmask,subnet_short_name",$sqlcond,
				array("order" => $this->sqlAttrId));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none",in_array("none",$selected));
			}

                        while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
				}
                                $output .= FS::$iMgr->selElmt($data[$this->sqlAttrId]."/".$data["netmask"]." (".$data["subnet_short_name"].")",
					$data[$this->sqlAttrId],in_array($data[$this->sqlAttrId],$selected));
                        }

			// If no elements found & no empty element
			if (!$found && !$none) {
				return NULL;
			}

			$output .= "</select>";
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
					return true;
				}
				return false;
			}
			return true;
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,
						"vlanid","CAST(vlanid as TEXT) ILIKE '".$search."%'",
						array("order" => "vlanid","limit" => "10","group" => "vlanid"));

				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["vlan"][] = $data["vlanid"];
				}
				
				$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","subnet_short_name",
					"subnet_short_name ILIKE '".$search."%'",
					array("order" => "subnet_short_name","limit" => "10","group" => "subnet_short_name"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["vlan"][] = $data["subnet_short_name"];
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"netid","CAST(netid as TEXT) ILIKE '".$search."%'",
					array("order" => "netid","limit" => "10","group" => $this->sqlAttrId));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpsubnet"][] = $data["netid"];
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				// by VLAN ID
				if (FS::$secMgr->isNumeric($search)) {
					$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,subnet_short_name","vlanid = '".$search."'");
					if ($data = FS::$dbMgr->Fetch($query)) {
						$output .= $this->loc->s("subnet-shortname").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("ipmanager").
							"&sh=2\">".$data["subnet_short_name"]."</a><br />".
							$this->loc->s("netid").": ".$data["netid"]."<br />".
							$this->loc->s("netmask").": ".$data["netmask"]."<br />";
						$resout .= $this->searchResDiv($output,"title-vlan-ipmanager");
					}
				}
				
				// by shortname
				$output = "";
				
				$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,vlanid","subnet_short_name = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					$output .= $this->loc->s("vlanid").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("ipmanager").
						"&sh=2\">".$data["vlanid"]."</a><br />".
						$this->loc->s("netid").": ".$data["netid"]."<br />".
						$this->loc->s("netmask").": ".$data["netmask"]."<br />";
						
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-vlan-ipmanager");
				}
				
				// by netid
				$output = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,vlanid,subnet_short_name","netid = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					$output .= "<b>".$this->loc->s("subnet-shortname")."</b>: <a href=\"index.php?mod=".
						FS::$iMgr->getModuleIdByPath("ipmanager")."&sh=2\">".$data["subnet_short_name"]."</a><br />".
						"<b>".$this->loc->s("netid")."</b>: ".$data["netid"]."<br />".
						"<b>".$this->loc->s("netmask")."</b>: ".$data["netmask"]."<br />".
						"<b>".$this->loc->s("vlanid")."</b>: ".$data["vlanid"]."<br />";
						
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-subnet-ipmanager");
				}
				$found = 0;
				return $resout;
			}
		}

		private $netid;
		private $netmask;
		private $vlanid;
		private $shortname;
		private $desc;
		private $router;
		private $dns1;
		private $dns2;
		private $domainname;
		private $maxleasetime;
		private $defaultleasetime;
	};
	
	final class dhcpServer extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_servers";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"description","description ILIKE '%".$search."%'",array("order" => "description","limit" => "10",
					"group" => "description"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpserver"][] = $data["description"];
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"alias","alias ILIKE '%".$search."%'",array("order" => "alias","limit" => "10",
					"group" => "alias"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpserver"][] = $data["alias"];
				}
				
				$query = FS::$dbMgr->Select($this->sqlTable,"addr","addr ILIKE '%".$search."%'",array("order" => "addr","limit" => "10",
					"group" => "addr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpserver"][] = $data["addr"];
				}
			}
			else {
				$output = "";
				$resout = "";
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
					//$this->nbresults++;
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-servers");
				}
				
				return $resout;
			}
		}
	};
	
	final class dhcpCluster extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_cluster";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"clustername","clustername ILIKE '%".$search."%'",array("order" => "clustername","limit" => "10",
					"group" => "clustername"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpcluster"][] = $data["clustername"];
				}
			}
			else {
				$clusters = array();
				$output = "";
				$resout = "";
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
						//$this->nbresults++;
					}
					$resout .= $this->searchResDiv($output,"title-dhcp-cluster");
				}
				
				return $resout;
			}
		}
	};
		

	final class dhcpIP extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_ip";
			$this->sqlCacheTable = PGDbConfig::getDbPrefix()."dhcp_ip_cache";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"hostname", "hostname ILIKE '%".$search."%'",
					array("order" => "hostname","limit" => "10","group" => "hostname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcphostname"][] = $data["hostname"];
				}
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"ip","ip ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["ip"][] = $data["ip"];
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"ip","ip ILIKE '".$search."%'",
					array("order" => "ip","limit" => "10","group" => "ip"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["ip"][] = $data["ip"];
				}
				
				$query = FS::$dbMgr->Select($this->sqlCacheTable,"macaddr","macaddr ILIKE '".$search."%'",
					array("order" => "macaddr","limit" => "10","group" => "macaddr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["mac"][] = $data["macaddr"];
				}

				$query = FS::$dbMgr->Select($this->sqlTable,"macaddr","macaddr ILIKE '".$search."%'",
					array("order" => "macaddr","limit" => "10","group" => "macaddr"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["mac"][] = $data["macaddr"];
				}
			}
			else {
				$output = "";
				$resout = "";
				$found = false;
				
				$query = FS::$dbMgr->Select($this->sqlTable,"ip,macaddr,hostname,comment,reserv",
					"ip = '".$search."' OR CAST(macaddr AS varchar) = '".$search."'");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if ($found == false) {
						$found = true;
					}
					else {
						$output .= FS::$iMgr->hr();
					}

					if (strlen($data["ip"]) > 0) {
						$output .= $this->loc->s("link-ip").": <a href=\"index.php?mod=".$this->mid.
							"&s=".$data["ip"]."\">".$data["ip"]."</a><br />";
					}
							
					if (strlen($data["hostname"]) > 0) {
						$output .= "<b>".$this->loc->s("dhcp-hostname")."</b>: ".$data["hostname"]."<br />";
					}

					if (strlen($data["macaddr"]) > 0) {
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: <a href=\"index.php?mod=".$this->mid.
							"&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
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

					//$this->nbresults++;
				}
		
				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-distrib-z-eye");
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
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: <a href=\"index.php?mod=".
							$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
					}
					$output .= "<b>".$this->loc->s("attribution-type")."</b>: ".
						($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
					if ($data["distributed"] != 3 && $data["distributed"] != 4) {
						$output .= "<b>".$this->loc->s("Validity")."</b>: ".$data["leasetime"]."<br />";
					}
					$output .= FS::$iMgr->hr();
					//$this->nbresults++;
				}
				
				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-hostname");
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
						$output .= "<b>".$this->loc->s("link-mac-addr")."</b>: <a href=\"index.php?mod=".
							$this->mid."&s=".$data["macaddr"]."\">".$data["macaddr"]."</a><br />";
					}

					$output .= "<b>".$this->loc->s("attribution-type")."</b>: ".
						($data["distributed"] != 3 ? $this->loc->s("dynamic") : $this->loc->s("Static"))." (".$data["server"].")<br />";
						
					if ($data["distributed"] != 3 && $data["distributed"] != 4) {
						$output .= $this->loc->s("Validity")." : ".$data["leasetime"];
					}
					//$this->nbresults++;
				}
		
				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-distrib");
				}
				
				return $resout;
			}
		}
		
		private $sqlCacheTable;
	};
	
	final class dhcpCustomOption extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_custom_option";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optname","optname ILIKE '%".$search."%'",array("order" => "optname","limit" => "10",
					"group" => "optname"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpoptions"][] = $data["optname"];
				}
			}
			else {
				// Custom DHCP options
				$output = "";
				$resout = "";
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
					//$this->nbresults++;
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-custom-options");
					$found = false;
				}
				
				return $resout;
			}
		}
	};
	
	final class dhcpOption extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_option";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optalias","optalias ILIKE '%".$search."%'",array("order" => "optalias","limit" => "10",
					"group" => "optalias"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpoptions"][] = $data["optalias"];
				}
			}
			else {
				$output = "";
				$resout = "";
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
					//$this->nbresults++;
				}

				if ($found) {
					$resout .= $this->searchResDiv($output,"title-dhcp-options");
					$found = false;
				}
				
				return $resout;
			}
		}
	};
	
	final class dhcpOptionGroup extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."dhcp_option_group";
		}
		
		public function search($search, $autocomplete = false, $autoresults = NULL) {
			if ($autocomplete) {
				$query = FS::$dbMgr->Select($this->sqlTable,"optgroup","optgroup ILIKE '%".$search."%'",array("order" => "optgroup","limit" => "10",
					"group" => "optgroup"));
				while ($data = FS::$dbMgr->Fetch($query)) {
					$autoresults["dhcpoptions"][] = $data["optgroup"];
				}
			}
			else {
				$output = "";
				$resout = "";
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
						//$this->nbresults++;
					}

					$resout .= $this->searchResDiv($output,"title-dhcp-option-groups");
				}
				
				return $resout;
			}
		}
	};
?>

