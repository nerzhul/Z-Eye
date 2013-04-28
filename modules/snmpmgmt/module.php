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
	
	require_once(dirname(__FILE__)."/../netdisco/netdiscoCfg.api.php");
	
	class iSNMPMgmt extends FSModule{
		function iSNMPMgmt($locales) { parent::FSModule($locales); }
		
		public function Load() {
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
			$output = FS::$iMgr->h1("snmp-communities");
			FS::$iMgr->setTitle("snmp-communities");
			$found = false;

			$formoutput = $this->showCommunityForm();
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("Add-community"),array("width" => 400));

			// Div for Ajax modifications
			$output .= "<div id=\"snmptable\">";
			$tmpoutput = $this->showSNMPTableHead();

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) $found = true;
				$tmpoutput .= $this->tableCommunityLine($data["name"],$data["ro"] == 't',$data["rw"] == 't');
			}
			if($found) $output .= $tmpoutput."</table>".FS::$iMgr->jsSortTable("snmpList");
			$output .= "</div>";
			return $output;
		}

		private function showSNMPTableHead() {
			FS::$iMgr->setJSBuffer(1);
			return "<table id=\"snmpList\"><thead><tr id=\"snmpthead\"><th class=\"headerSortDown\">".$this->loc->s("snmp-community")."</th><th>".$this->loc->s("Read")."</th><th>".$this->loc->s("Write")."</th><th></th></tr></thead>";
		}

		private function showCommunityForm($name = "", $ro = false, $rw = false) {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1")."<table>";
			if($name)
				$output .= "<tr><td>".$this->loc->s("snmp-community")."</td><td>".$name.FS::$iMgr->hidden("name",$name).FS::$iMgr->hidden("edit",1)."</td></tr>";
			else
				$output .= FS::$iMgr->idxLine($this->loc->s("snmp-community"),"name",$name,array("length" => 64, "size" => 20));
			$output .= FS::$iMgr->idxLine($this->loc->s("Read"),"ro",$ro,array("type" => "chk", "tooltip" => "tooltip-read"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Write"),"rw",$rw,array("type" => "chk", "tooltip" => "tooltip-write"));
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			return $output;
		}

		private function tableCommunityLine($name,$ro,$rw) {
			FS::$iMgr->setJSBuffer(1);
			return "<tr id=\"".FS::$iMgr->formatHTMLId($name)."tr\"><td>".FS::$iMgr->opendiv($this->showCommunityForm($name,$ro,$rw),$name,array("width" => 400)).
				"</td><td>".($ro ? "X" : "")."</td><td>".($rw ? "X": "")."</td><td>".
				FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&snmp=".$name,array("js" => true, "confirm" => 
					array($this->loc->s("confirm-remove-community")."'".$name."' ?","Confirm","Cancel")))."</td></tr>";
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // Add SNMP community
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$ro = FS::$secMgr->checkAndSecurisePostData("ro");
					$rw = FS::$secMgr->checkAndSecurisePostData("rw");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || $ro && $ro != "on" || $rw && $rw != "on" || $edit && $edit != 1) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Adding data");
						FS::$iMgr->ajaxEcho("err-invalid-data");
						return;
					}

					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$name."'");
					if($edit) {
						if(!$exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",1,"Community '".$name."' not exists");
							FS::$iMgr->ajaxEcho("err-not-exist");
							return;
						}
					}
					else {
						if($exist) {
							FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",1,"Community '".$name."' already in DB");
							FS::$iMgr->ajaxEcho("err-already-exist");
							return;
						}
					}

					// User must choose read and/or write
					if($ro != "on" && $rw != "on") {
						FS::$iMgr->ajaxEcho("err-readorwrite");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						FS::$iMgr->ajaxEcho("err-read-netdisco");
						return;
					}
					
					FS::$dbMgr->BeginTr();
					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snmp_communities","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."snmp_communities","name,ro,rw","'".$name."','".($ro == "on" ? 't' : 'f')."','".
						($rw == "on" ? 't' : 'f')."'");
					FS::$dbMgr->CommitTr();

					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);

					$js = "";

					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."snmp_communities","name");
					if($count == 1) {
						$jscontent = $this->showSNMPTableHead()."</table>";
						$js .= "$('#snmptable').html('".addslashes($jscontent)."'); $('#snmptable').show('slow');";
					}

					if($edit) $js .= "hideAndRemove('#".$name."tr'); setTimeout(function() {";
					$jscontent = $this->tableCommunityLine($name,$ro == "on",$rw == "on");
					$js .= "$('".addslashes($jscontent)."').insertAfter('#snmpthead');";
					if($edit) $js .= "},1000);";	
					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				case 2: // Remove SNMP community
					$name = FS::$secMgr->checkAndSecuriseGetData("snmp");
					if(!$name) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Invalid Deleting data");
						FS::$iMgr->ajaxEcho("err-invalid-data");
						return;
					}
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snmp_communities","name","name = '".$name."'")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Community '".$name."' not in DB");
						FS::$iMgr->ajaxEcho("err-not-exist");
						return;
					}

					$netdiscoCfg = readNetdiscoConf();
					if(!is_array($netdiscoCfg)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"netdisco",2,"Reading error on netdisco.conf");
						FS::$iMgr->ajaxEcho("err-read-fail");
						return;
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."snmp_communities","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename ILIKE 'mrule_switchmgmt_snmp_".$name."_%'");
					writeNetdiscoConf($netdiscoCfg["dnssuffix"],$netdiscoCfg["nodetimeout"],$netdiscoCfg["devicetimeout"],$netdiscoCfg["pghost"],$netdiscoCfg["dbname"],$netdiscoCfg["dbuser"],$netdiscoCfg["dbpwd"],$netdiscoCfg["snmptimeout"],$netdiscoCfg["snmptry"],$netdiscoCfg["snmpver"],$netdiscoCfg["firstnode"]);
					
					$js = "hideAndRemove('#".$name."tr');";
					$count = FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."snmp_communities","name");
					if($count == 0)
						$js .= "$('#snmptable').hide('slow',function() { $('#snmptable').html(''); });";
					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				default: break;
			}
		}
	};
?>
