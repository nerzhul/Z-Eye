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
	require_once(dirname(__FILE__)."/../netdisco/netdiscoCfg.api.php");
	
	class iSNMPMgmt extends genModule{
		function iSNMPMgmt() { parent::genModule(); $this->loc = new lSNMPMgmt(); }
		
		public function Load() {
			FS::$iMgr->setCurrentModule($this);
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-write-fail")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-already-exist")); break;
				case 4: $output .= FS::$iMgr->printError($this->loc->s("err-not-exist")); break;
				case 5: $output .= FS::$iMgr->printError($this->loc->s("err-read-fail")); break;
				case 6: $output .= FS::$iMgr->printError($this->loc->s("err-readorwrite")); break;
				case -1: $output .= FS::$iMgr->printDebug($this->loc->s("mod-ok")); break;
				default: break;
			}
			$output .= $this->showMain();
			return $output;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("snmp-communities")."</h1>";
			$found = false;

			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1")."<ul class=\"ulform\">";
			$formoutput .= "<li>".FS::$iMgr->input("name","",20,64,$this->loc->s("snmp-community"))."</li>";
			$formoutput .= "<li>".FS::$iMgr->check("ro",array("label" => $this->loc->s("Read"), "tooltip" => "tooltip-read"))."</li>";
			$formoutput .= "<li>".FS::$iMgr->check("rw",array("label" => $this->loc->s("Write"), "tooltip" => "tooltip-write"))."</li>";
			$formoutput .= "<li>".FS::$iMgr->submit("",$this->loc->s("Save"))."</li>";
			$formoutput .= "</ul></form>";

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Add-community"));

			$tmpoutput = "<table><tr><th>".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Read")."</th><th>".$this->loc->s("Write")."</th><th></th></tr>";
			$query = FS::$dbMgr->Select("z_eye_snmp_communities","name,ro,rw","","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$tmpoutput .= "<tr><td>".$data["name"]."</td><td>".($data["ro"] == 't' ? "X" : "")."</td><td>".($data["rw"] == 't' ? "X": "")."</td><td>".
					FS::$iMgr->removeIcon("index.php?mod=".$this->mid."&act=2&snmp=".$data["name"])."</td></tr>";
			}
			if($found) $output .= $tmpoutput."</table>";	
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add SNMP community
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$ro = FS::$secMgr->checkAndSecurisePostData("ro");
					$rw = FS::$secMgr->checkAndSecurisePostData("rw");

					if(!$name || $ro && $ro != "on" || $rw && $rw != "on") {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Adding data");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}

					if(FS::$dbMgr->GetOneData("z_eye_snmp_communities","name = '".$name."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",1,"Community '".$name."' already in DB");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=3");
						return;
					}

					// User must choose read and/or write
					if($ro != "on" && $rw != "on") {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=6");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					
					FS::$dbMgr->Insert("z_eye_snmp_communities","name,ro,rw","'".$name."','".($ro == "on" ? 't' : 'f')."','".
						($rw == "on" ? 't' : 'f')."'");

					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				case 2: // Remove SNMP community
					$name = FS::$secMgr->checkAndSecuriseGetData("snmp");
					if(!$name) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Deleting data");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					if(!FS::$dbMgr->GetOneData("z_eye_snmp_communities","name","name = '".$name."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Community '".$name."' not in DB");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=4");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					FS::$dbMgr->Delete("z_eye_snmp_communities","name = '".$name."'");
					FS::$dbMgr->Delete("z_eye_user_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
					FS::$dbMgr->Delete("z_eye_group_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				default: break;
			}
		}
	};
?>
