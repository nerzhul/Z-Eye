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
	
	class iSwitchRightsMgmt extends genModule{
		function iSwitchRightsMgmt() { parent::genModule(); $this->loc = new lSwitchRightsMgmt(); }
		
		public function Load() {
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-datas")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-snmpgid-not-found")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-already-exist")); break;
				case 4: $output .= FS::$iMgr->printError($this->loc->s("err-not-found")); break;
				default: break;
			}
			$output .= $this->showMain();
			return $output;
		}

		private function showBySwitch() {
			$output = "<h2>".$this->loc->s("title-rightsbyswitch")."</h4>";
			
			$found = false;
			$query = FS::$dbMgr->Select("device","ip,name");
			$grpoutput = "<h3>".$this->loc->s("group-rights")."</h3>"."<table><tr><th>".$this->loc->s("device")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = "<h3>".$this->loc->s("usr-rights")."</h3>"."<table><tr><th>".$this->loc->s("device")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$grprules = array("read" => array(), "readswdetails" => array(), "readswmodules" => array(), "readswvlans" => array(), "readportstats" => array(), 
					"write" => array(), "writeportmon" => array());
				$usrrules = array("read" => array(), "readswdetails" => array(), "readswmodules" => array(), "readswvlans" => array(), "readportstats" => array(), 
					"write" => array(), "writeportmon" => array());
				// Groups
				$query2 = FS::$dbMgr->Select("z_eye_group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_swip_".$data["ip"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_read")
						array_push($grprules["read"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readportstats")
						array_push($grprules["readportstats"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswdetails")
						array_push($grprules["readswdetails"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswmodules")
						array_push($grprules["readswmodules"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswvlans")
						array_push($grprules["readswvlans"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_write")
						array_push($grprules["write"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_writeportmon")
						array_push($grprules["writeportmon"],$data2["gid"]);
				}
				$first = true;
				foreach($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					switch($key) {
						case "read": $grpoutput .= $this->loc->s("Reading"); break;
						case "readportstats": $grpoutput .= $this->loc->s("Read-port-stats"); break;
						case "readswdetails": $grpoutput .= $this->loc->s("Read-switch-details"); break;
						case "readswmodules": $grpoutput .= $this->loc->s("Read-switch-modules"); break;
						case "readswvlans": $grpoutput .= $this->loc->s("Read-switch-vlan"); break;
						case "write": $grpoutput .= $this->loc->s("Writing"); break;
						case "writeportmon": $grpoutput .= $this->loc->s("Write-port-mon"); break;
					}
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showIPGroups($data["ip"],$key,$values);
				}
				// Users			
				$query2 = FS::$dbMgr->Select("z_eye_user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_swip_".$data["ip"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_read")
						array_push($usrrules["read"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readportstats")
						array_push($usrrules["readportstats"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswdetails")
						array_push($usrrules["readswdetails"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswmodules")
						array_push($usrrules["readswmodules"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_readswvlans")
						array_push($usrrules["readswvlans"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_write")
						array_push($usrrules["write"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_ip_".$data["ip"]."_writeportmon")
						array_push($usrrules["writeportmon"],$data2["uid"]);
				}
				foreach($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					switch($key) {
						case "read": $usroutput .= $this->loc->s("Reading"); break;
						case "readportstats": $usroutput .= $this->loc->s("Read-port-stats"); break;
						case "readswdetails": $usroutput .= $this->loc->s("Read-switch-details"); break;
						case "readswmodules": $usroutput .= $this->loc->s("Read-switch-modules"); break;
						case "readswvlans": $usroutput .= $this->loc->s("Read-switch-vlan"); break;
						case "write": $usroutput .= $this->loc->s("Writing"); break;
						case "writeportmon": $usroutput .= $this->loc->s("Write-port-mon"); break;
					}
					$usroutput .= "</td><td>";
					$usroutput .= $this->showIPGroups($data["ip"],$key,$values);
				}
			}
			if($found) {
				$output .= $grpoutput."</table>";
				$output .= $usroutput."</table>";
			}
			return $output;
		}

		private function showIPUsers($ip,$right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData("z_eye_users","username","uid = '".$values[$i]."'")." ";
					$output .= "<a href=\"index.php?mod=".$this->mid."&act=2&uid=".$values[$i]."&ip=".$ip."&right=".$right."\">".
						FS::$iMgr->img("styles/images/cross.png",15,15)."</a><br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$users = $this->getUsers();
			$found = false;
			foreach($users as $uid => $username) {
				if(!in_array($uid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function showIPGroups($ip,$right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$values[$i]."'")." ";
					$output .= "<a href=\"index.php?mod=".$this->mid."&act=2&gid=".$values[$i]."&ip=".$ip."&right=".$right."\">".
						FS::$iMgr->img("styles/images/cross.png",15,15)."</a><br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("ip",$ip).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$groups = $this->getUserGroups();
			$found = false;
			foreach($groups as $gid => $gname) {
				if(!in_array($gid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function showBySNMPCommunity() {
			$output = "<h2>".$this->loc->s("title-rightsbysnmp")."</h4>";
			
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw");
			$grpoutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$grprules = array();
				$usrrules = array();
				if($data["ro"] == 't') {
					$grprules["read"] = array();
					$usrrules["read"] = array();
					$grprules["readportstats"] = array();
					$usrrules["readportstats"] = array();
					$grprules["readswdetails"] = array();
					$usrrules["readswdetails"] = array();
					$grprules["readswmodules"] = array();
					$usrrules["readswmodules"] = array();
					$grprules["readswvlans"] = array();
					$usrrules["readswvlans"] = array();
				}
				if($data["rw"] == 't') {
					$grprules["write"] = array();
					$usrrules["write"] = array();
					$grprules["writeportmon"] = array();
					$usrrules["writeportmon"] = array();
				}
				$query2 = FS::$dbMgr->Select("z_eye_group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($grprules["read"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readportstats" && $data["ro"] == 't')
						array_push($grprules["readportstats"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswdetails" && $data["ro"] == 't')
						array_push($grprules["readswdetails"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswmodules" && $data["ro"] == 't')
						array_push($grprules["readswmodules"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswvlans" && $data["ro"] == 't')
						array_push($grprules["readswvlans"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($grprules["write"],$data2["gid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_writeportmon" && $data["rw"] == 't')
						array_push($grprules["writeportmon"],$data2["gid"]);
				}
				$first = true;
				foreach($grprules as $key => $values) {
					$grpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					switch($key) {
						case "read": $grpoutput .= $this->loc->s("Reading"); break;
						case "readportstats": $grpoutput .= $this->loc->s("Read-port-stats"); break;
						case "readswdetails": $grpoutput .= $this->loc->s("Read-switch-details"); break;
						case "readswmodules": $grpoutput .= $this->loc->s("Read-switch-modules"); break;
						case "readswvlans": $grpoutput .= $this->loc->s("Read-switch-vlan"); break;
						case "write": $grpoutput .= $this->loc->s("Writing"); break;
						case "writeportmon": $grpoutput .= $this->loc->s("Write-port-mon"); break;
					}
					$grpoutput .= "</td><td>";
					$grpoutput .= $this->showSNMPGroups($data["name"],$key,$values);
				}			
				// Users
				$query2 = FS::$dbMgr->Select("z_eye_user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($usrrules["read"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readportstats" && $data["ro"] == 't')
						array_push($usrrules["readportstats"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswdetails" && $data["ro"] == 't')
						array_push($usrrules["readswdetails"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswmodules" && $data["ro"] == 't')
						array_push($usrrules["readswmodules"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_readswvlans" && $data["ro"] == 't')
						array_push($usrrules["readswvlans"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($usrrules["write"],$data2["uid"]);
					else if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_writeportmon" && $data["rw"] == 't')
						array_push($usrrules["writeportmon"],$data2["uid"]);
				}
				$first = true;
				foreach($usrrules as $key => $values) {
					$usroutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					switch($key) {
						case "read": $usroutput .= $this->loc->s("Reading"); break;
						case "readportstats": $usroutput .= $this->loc->s("Read-port-stats"); break;
						case "readswdetails": $usroutput .= $this->loc->s("Read-switch-details"); break;
						case "readswmodules": $usroutput .= $this->loc->s("Read-switch-modules"); break;
						case "readswvlans": $usroutput .= $this->loc->s("Read-switch-vlan"); break;
						case "write": $usroutput .= $this->loc->s("Writing"); break;
						case "writeportmon": $usroutput .= $this->loc->s("Write-port-mon"); break;
					}
					$usroutput .= "</td><td>";
					$usroutput .= $this->showSNMPUsers($data["name"],$key,$values);
				}			
			}
			if($found) {
				$output .= "<h3>".$this->loc->s("group-rights")."</h3>".$grpoutput."</table>";
				$output .= "<h3>".$this->loc->s("user-rights")."</h3>".$usroutput."</table>";
			}
			return $output;
		}

		private function showSNMPGroups($snmp,$right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$values[$i]."'")." ";
					$output .= "<a href=\"index.php?mod=".$this->mid."&act=2&gid=".$values[$i]."&snmp=".$snmp."&right=".$right."\">".
						FS::$iMgr->img("styles/images/cross.png",15,15)."</a><br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("gid");
			$groups = $this->getUserGroups();
			$found = false;
			foreach($groups as $gid => $gname) {
				if(!in_array($gid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function showSNMPUsers($snmp,$right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$output .= FS::$dbMgr->GetOneData("z_eye_users","username","uid = '".$values[$i]."'")." ";
					$output .= "<a href=\"index.php?mod=".$this->mid."&act=2&uid=".$values[$i]."&snmp=".$snmp."&right=".$right."\">".
						FS::$iMgr->img("styles/images/cross.png",15,15)."</a><br />";
				}
			}
			else
				$output .= $this->loc->s("None")."<br />";
			$tmpoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("snmp",$snmp).FS::$iMgr->hidden("right",$right).FS::$iMgr->select("uid");
			$users = $this->getUsers();
			$found = false;
			foreach($users as $uid => $username) {
				if(!in_array($uid,$values)) {
					if(!$found) $found = true;
					$tmpoutput .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			if($found) $output .= $tmpoutput."</select>".FS::$iMgr->submit("",$this->loc->s("Add"))."</form>";
			return $output;
		}

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select("z_eye_groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function getUsers() {
			$groups = array();
			$query = FS::$dbMgr->Select("z_eye_users","uid,username");
			while($data = FS::$dbMgr->Fetch($query)) {
				$users[$data["uid"]] = $data["username"];
			}
			return $users;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("title-switchrightsmgmt")."</h1>";
			$output .= $this->showBySNMPCommunity();	
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add group right for SNMP/IP community 
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					$snmp = FS::$secMgr->checkAndSecurisePostData("snmp");
					$ip = FS::$secMgr->checkAndSecurisePostData("ip");
					$right = FS::$secMgr->checkAndSecurisePostData("right");

					if((!$gid && !$uid) || (!$snmp && !$ip) || !$right) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}

					if($snmp) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$gid."'") ||
								$right == "read" && 
									!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" && 
									!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								header("Location: index.php?mod=".$this->mid."&err=2");
								return;
							}
							if(FS::$dbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								header("Location: index.php?mod=".$this->mid."&err=3");
								return;
							}
							FS::$dbMgr->Delete("z_eye_group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert("z_eye_group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData("z_eye_users","username","uid = '".$uid."'") ||
								$right == "read" && 
									!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$snmp."' and ro = 't'") ||
								$right == "write" && 
									!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$snmp."' and rw = 't'")) {
								header("Location: index.php?mod=".$this->mid."&err=2");
								return;
							}
							if(FS::$dbMgr->GetOneData("z_eye_user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								header("Location: index.php?mod=".$this->mid."&err=3");
								return;
							}
							FS::$dbMgr->Delete("z_eye_user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert("z_eye_user_rules","uid,rulename,ruleval","'".$uid."','mrule_switchmgmt_snmp_".$snmp."_".$right."','on'");
						}
					}
					else if($ip) {
						if(!FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$gid."'") ||
							!FS::$dbMgr->GetOneData("device","name","ip = '".$ip."'")) {
							header("Location: index.php?mod=".$this->mid."&err=2");
							return;
						}
						if(FS::$dbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
							$ip."_".$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
							header("Location: index.php?mod=".$this->mid."&err=3");
							return;
						}
						FS::$dbMgr->Delete("z_eye_group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
						FS::$dbMgr->Insert("z_eye_group_rules","gid,rulename,ruleval","'".$gid."','mrule_switchmgmt_ip_".$ip."_".$right."','on'");
					}

					header("Location: index.php?mod=".$this->mid);
					return;
				case 2: // Remove group from SNMP community
					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					$snmp = FS::$secMgr->checkAndSecuriseGetData("snmp");
					$ip = FS::$secMgr->checkAndSecuriseGetData("ip");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");

					if((!$uid && !$gid) || (!$ip && !$snmp) || !$right) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}

					if($snmp) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND gid = '".$gid."'")) {
								header("Location: index.php?mod=".$this->mid."&err=4");
								return;
							}
							FS::$dbMgr->Delete("z_eye_group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData("z_eye_user_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
								$snmp."_".$right."' AND uid = '".$uid."'")) {
								header("Location: index.php?mod=".$this->mid."&err=4");
								return;
							}
							FS::$dbMgr->Delete("z_eye_user_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND uid = '".$uid."'");
						}
					}
					else if($ip) {
						if(!FS::$dbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = 'mrule_switchmgmt_ip_".
							$ip."_".$right."' AND gid = '".$gid."'")) {
							header("Location: index.php?mod=".$this->mid."&err=4");
							return;
						}
						FS::$dbMgr->Delete("z_eye_group_rules","rulename = 'mrule_switchmgmt_ip_".$ip."_".$right."' AND gid = '".$gid."'");
					}
					header("Location: index.php?mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
