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
				$rules = array("read" => array(), "write" => array());
				$query2 = FS::$dbMgr->Select("z_eye_group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_switchmgmt_snmp_".$data["name"]."_%'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_read" && $data["ro"] == 't')
						array_push($rules["read"],$data["gid"]);
					if($data2["rulename"] == "mrule_switchmgmt_snmp_".$data["name"]."_write" && $data["rw"] == 't')
						array_push($rules["write"],$data["gid"]);
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
					$count = count($values);
					if($count) {
						for($i=0;$i<count($values);$i++)
							$tmpoutput .= $values."<br />";
					}
					else
						$tmpoutput .= $this->loc->s("None");
				}			
			}
			if($found) $output .= $tmpoutput."</table>";
			return $output;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("title-switchrightsmgmt")."</h1>";
			$output .= $this->showBySNMPCommunity();	
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				default: break;
			}
		}
	};
?>
