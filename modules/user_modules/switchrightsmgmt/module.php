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

		private function showBySNMPCommunity() {
			$output = "<h2>".$this->loc->s("title-rightsbysnmp")."</h4>";
			
			$found = false;
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw");
			$tmpoutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$rules = array();
				if($data["ro"] == 't')
					$rules["read"] = array();
				if($data["rw"] == 't')
					$rules["write"] = array();
				$query2 = FS::$dbMgr->Select("z_eye_group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($rules["read"],$data2["gid"]);
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($rules["write"],$data2["gid"]);
				}
				$first = true;
				foreach($rules as $key => $values) {
					$tmpoutput .= "<tr><td>".($first ? $data["name"] : "")."</td><td>";
					if($first) $first = false;
					switch($key) {
						case "read": $tmpoutput .= $this->loc->s("Reading"); break;
						case "write": $tmpoutput .= $this->loc->s("Writing"); break;
					}
					$tmpoutput .= "</td><td>";
					$tmpoutput .= $this->showSNMPGroups($data["name"],$key,$values);
				}			
			}
			if($found) $output .= $tmpoutput."</table>";
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

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select("z_eye_groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("title-switchrightsmgmt")."</h1>";
			$output .= $this->showBySNMPCommunity();	
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add group right for SNMP community 
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					$snmp = FS::$secMgr->checkAndSecurisePostData("snmp");
					$right = FS::$secMgr->checkAndSecurisePostData("right");

					if(!$gid || !$snmp || !$right) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}

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
					header("Location: index.php?mod=".$this->mid);
					return;
				case 2: // Remove group from SNMP community
					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$snmp = FS::$secMgr->checkAndSecuriseGetData("snmp");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");

					if(!$gid || !$snmp || !$right) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = 'mrule_switchmgmt_snmp_".
						$snmp."_".$right."' AND gid = '".$gid."'")) {
						header("Location: index.php?mod=".$this->mid."&err=4");
						return;
					}
					FS::$dbMgr->Delete("z_eye_group_rules","rulename = 'mrule_switchmgmt_snmp_".$snmp."_".$right."' AND gid = '".$gid."'");
					header("Location: index.php?mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
