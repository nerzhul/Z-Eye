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

			$output = FS::$iMgr->select($options["name"],array("multi" => $multi));

			$found = false;
			$query = FS::$dbMgr->Select($this->sqlTable,$this->sqlAttrId.",netmask,subnet_short_name",$sqlcond,
				array("order" => $this->sqlAttrId));

			if ($none) {
				$output .= FS::$iMgr->selElmt($this->loc->s("None"),"none");
			}

                        while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
				}
                                $output .= FS::$iMgr->selElmt($data[$this->sqlAttrId]."/".$data["netmask"]." (".$data["subnet_short_name"].")",
					$data[$this->sqlAttrId]);
                        }

			// If no elements found & no empty element
			if (!$found && !$none) {
				return NULL;
			}

			$output .= $elements."</select>";
			return $output;

		}
	};

