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
				$query = FS::$dbMgr->Select($this->sqlTable,"netid,netmask,subnet_short_name","vlanid = '".$search."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$output .= $this->loc->s("subnet-shortname").": <a href=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("ipmanager").
						"&sh=2\">".$data["subnet_short_name"]."</a><br />".
						$this->loc->s("netid").": ".$data["netid"]."<br />".
						$this->loc->s("netmask").": ".$data["netmask"]."<br />";
					$resout .= $this->searcResDiv($output,"title-vlan-ipmanager");
				}
				
				// by shortname
				$output = "";
				
				$query = FS::$dbMgr->Select(PgDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask,vlanid","subnet_short_name = '".$search."'");
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
					$resout .= $this->searcResDiv($output,"title-vlan-ipmanager");
				}
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

