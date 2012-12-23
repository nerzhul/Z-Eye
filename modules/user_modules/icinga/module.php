<?php
	/*
	* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	
	class iIcinga extends genModule{
		function iIcinga() { parent::genModule(); $this->loc = new lIcinga(); }
		public function Load() {
			$output = $this->showTabPanel();
			return $output;
		}
		
		private function showTabPanel() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			
			if(!FS::isAjaxCall()) {
				$output .= "<h1>".$this->loc->s("title-icinga")."</h1>";
				$output .= "<div id=\"contenttabs\"><ul>";
				$output .= FS::$iMgr->tabPanElmt(1,"index.php?mod=".$this->mid,$this->loc->s("General"),$sh);
				$output .= FS::$iMgr->tabPanElmt(2,"index.php?mod=".$this->mid,$this->loc->s("Hosts"),$sh);
				$output .= FS::$iMgr->tabPanElmt(3,"index.php?mod=".$this->mid,$this->loc->s("Hostgroups"),$sh);
				$output .= FS::$iMgr->tabPanElmt(4,"index.php?mod=".$this->mid,$this->loc->s("Services"),$sh);
				$output .= FS::$iMgr->tabPanElmt(5,"index.php?mod=".$this->mid,$this->loc->s("Timeperiods"),$sh);
				$output .= FS::$iMgr->tabPanElmt(6,"index.php?mod=".$this->mid,$this->loc->s("Contacts"),$sh);
				$output .= FS::$iMgr->tabPanElmt(7,"index.php?mod=".$this->mid,$this->loc->s("Contactgroups"),$sh);
				$output .= FS::$iMgr->tabPanElmt(8,"index.php?mod=".$this->mid,$this->loc->s("Commands"),$sh);
				$output .= "</ul></div>";
				$output .= "<script type=\"text/javascript\">$('#contenttabs').tabs({ajaxOptions: { error: function(xhr,status,index,anchor) {";
				$output .= "$(anchor.hash).html(\"".$this->loc->s("fail-tab")."\");}}});</script>";
				return $output;
			}
			
			if(!$sh) $sh = 1;
			
			switch($sh) {
				case 1: $output .= $this->showGeneralTab(); break;
				case 2: $output .= $this->showHostsTab(); break;
				case 3: $output .= $this->showHostgroupsTab(); break;
				case 4: $output .= $this->showServicesTab(); break;
				case 5: $output .= $this->showTimeperiodsTab(); break;
				case 6: $output .= $this->showContactsTab(); break;
				case 7: $output .= $this->showContactgroupsTab(); break;
				case 8: $output .= $this->showCommandTab(); break;
				// @TODO: case 9: service group
			}
			return $output;
		}
		
		private function showGeneralTab() {
			$output = FS::$iMgr->printError($this->loc->s("not-implemented"));
			
			return $output;
		}
		
		private function showHostsTab() {
			$output = "";
			
			$tpexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","");
			if(!$tpexist) {
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
				return $output;
			}
			
			$ctexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_contactgroups","name","");
			if(!$ctexist) {
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-contactgroups"));
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
				return $output;
			}
			
			/*
			 * Ajax new host
			 */
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=13");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","");
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","");
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname","");
			$formoutput .= "<tr><td>".$this->loc->s("Icon")."</td><td>";
			$formoutput .= FS::$iMgr->select("icon");
			$formoutput .= FS::$iMgr->selElmt("Aucun","");
			$query = FS::$pgdbMgr->Select("z_eye_icinga_icons","id,name","","name");
			while($data = pg_fetch_array($query))
				$formoutput .= FS::$iMgr->selElmt($data["name"],$data["id"]);
			$formoutput .= "</select></td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Parent")."</td><td>";
			$formoutput .= FS::$iMgr->select("parent[]","",NULL,true);
			$formoutput .= FS::$iMgr->selElmt($this->loc->s("None"),"none",true);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,addr","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$formoutput .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$formoutput .= "</select></td></tr>";
			
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Address"),"addr","");
			
			// Checks
			$formoutput .= "<tr><td>".$this->loc->s("alivecommand")."</td><td>".$this->genCommandList("checkcommand","check-host-alive")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod")."</td></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => 3, "type" => "num"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => 1, "type" => "num"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => 10, "type" => "num"));
			
			// Global
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",true,array("type" => "chk"));
			
			// Notifications
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",true,array("type" => "chk"));
			$formoutput .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod")."</td></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => 0, "type" => "num"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",true,array("type" => "chk"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",true,array("type" => "chk"));
			$formoutput .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg")."</td></tr>";
			
			// icon image
			// statusmap image
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";				
				
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
			
			/*
			 * Host table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,alias,addr,template","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Address")."</th><th>".$this->loc->s("Template")."</th><th>".$this->loc->s("Parent")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["alias"]."</td><td>".$data["addr"]."</td><td>";
				if($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td>";
				$found2 = false;
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = pg_fetch_array($query2)) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $data2["parent"];
				}
				$output .="</td><td><a href=\"index.php?mod=".$this->mid."&act=15&host=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showHostgroupsTab() {
			$output = "";
			$hostexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","");
			if(!$hostexist)
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-hosts"));
			else {
				/*
				 * Ajax new hostgroup
				 */
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=19");
				$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				// Global
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","",array("length" => 60, "size" => 30));
				$formoutput .= "<tr><td>".$this->loc->s("Members")."</td><td>".$this->getHostOrGroupList("members[]",true)."</td></tr>";
				$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
				$formoutput .= "</table></form>";
			}
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-hostgroup"));

			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hostgroups","name,alias","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Members")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["alias"]."</td><td>";
				$found2 = false;
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."'","hosttype,name");
				while($data2 = pg_fetch_array($query2)) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $data2["host"]." (";
					switch($data2["hosttype"]) {
						case 1: $output .= $this->loc->s("Host"); break;
						case 2: $output .= $this->loc->s("Hostgroup"); break;
						default: $output .= "unk"; break;
					}
					$output .= ")";
				}
				$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&act=21&hg=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showServicesTab() {
			$output = "";
			
			$tpexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","");
			if($tpexist) {
				/*
				 * Ajax new service
				 */
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=16");
				$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
				//$formoutput .= template list
				
				// Global
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Description"),"desc","",array("length" => 120, "size" => 30));
				// @ TODO support hostlist
				$formoutput .= "<tr><td>".$this->loc->s("Host")."</td><td>".$this->getHostOrGroupList("host",false)."</td></tr>";
				
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("active-check-en"),"actcheck",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("passive-check-en"),"pascheck",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("parallel-check"),"parcheck",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("obs-over-srv"),"obsess",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("check-freshness"),"freshness",false,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",true,array("type" => "chk"));
				
				// Checks
				$formoutput .= "<tr><td>".$this->loc->s("checkcmd")."</td><td>".$this->genCommandList("checkcmd")."</td></tr>";
				$formoutput .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => 3, "type" => "num"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => 1, "type" => "num"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => 10, "type" => "num"));
				
				// Notifications
				$formoutput .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => 0, "type" => "num"));
				// @ TODO support for contact not only contactlist
				$formoutput .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg")."</td></tr>";
				$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
				$formoutput .= "</table></form>";
			}
			else
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-service"));
			
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_services","name,host,hosttype,template,ctg","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Host")."</th><th>".$this->loc->s("Hosttype")."</th><th>".$this->loc->s("Template")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["host"]."</td><td>";
				switch($data["hosttype"]) {
					case 1: $output .= "Simple"; break;
					case 2: $output .= "Groupe"; break;
					default: $output .= "unk"; break;
				}
				$output .= "</td><td>";
				if($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&act=18&srv=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showTimeperiodsTab() {
			$output = "";
			
			/*
			 * Ajax new Timeperiod
			 * @TODO: support for multiple times in one day, and calendar days
			 */
			
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=4");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","",array("length" => 120, "size" => 30));
			$formoutput .= "<tr><td>".$this->loc->s("Monday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("mhs","mms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("mhe","mme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Tuesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("tuhs","tums")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("tuhe","tume")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Wednesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("whs","wms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("whe","wme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Thursday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("thhs","thms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("thhe","thme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Friday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("fhs","fms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("fhe","fme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Saturday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("sahs","sams")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("sahe","same")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Sunday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("suhs","sums")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("suhe","sume")."</td></tr>";
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-timeperiod"));
			
			/*
			 * Timeperiod table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Periods")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["alias"]."</td><td>";
				if($data["mhs"] != 0 || $data["mms"] != 0 || $data["mhe"] != 0 || $data["mme"] != 0)
					$output .= $this->loc->s("Monday").		" - ".$this->loc->s("From")." ".($data["mhs"] < 10 ? "0" : "").	$data["mhs"].	":".($data["mms"] < 10 ? "0" : "").	$data["mms"].	
					" ".$this->loc->s("To")." ".($data["mhe"] < 10 ? "0" : "").	$data["mhe"].":".($data["mme"] < 10 ? "0" : "").$data["mme"]."<br />";
				if($data["tuhs"] != 0 || $data["tums"] != 0 || $data["tuhe"] != 0 || $data["tume"] != 0)
					$output .= $this->loc->s("Tuesday").	" - ".$this->loc->s("From")." ".($data["tuhs"] < 10 ? "0" : "").$data["tuhs"].	":".($data["tums"] < 10 ? "0" : "").$data["tums"].	
					" ".$this->loc->s("To")." ".($data["tuhe"] < 10 ? "0" : "").$data["tuhe"].":".($data["tume"] < 10 ? "0" : "").$data["tume"]."<br />";
				if($data["whs"] != 0 || $data["wms"] != 0 || $data["whe"] != 0 || $data["wme"] != 0)	
					$output .= $this->loc->s("Wednesday").	" - ".$this->loc->s("From")." ".($data["whs"] < 10 ? "0" : "").	$data["whs"].	":".($data["wms"] < 10 ? "0" : "").	$data["wms"].	
					" ".$this->loc->s("To")." ".($data["whe"] < 10 ? "0" : "").	$data["whe"].":".($data["wme"] < 10 ? "0" : "").$data["wme"]."<br />";
				if($data["thhs"] != 0 || $data["thms"] != 0 || $data["thhe"] != 0 || $data["thme"] != 0)
					$output .= $this->loc->s("Thursday").	" - ".$this->loc->s("From")." ".($data["thhs"] < 10 ? "0" : "").$data["thhs"].	":".($data["thms"] < 10 ? "0" : "").$data["thms"].
					" ".$this->loc->s("To")." ".($data["thhe"] < 10 ? "0" : "").$data["thhe"].":".($data["thme"] < 10 ? "0" : "").$data["thme"]."<br />";
				if($data["fhs"] != 0 || $data["fms"] != 0 || $data["fhe"] != 0 || $data["fme"] != 0)
					$output .= $this->loc->s("Friday").		" - ".$this->loc->s("From")." ".($data["fhs"] < 10 ? "0" : "").	$data["fhs"].	":".($data["fms"] < 10 ? "0" : "").	$data["fms"].
					" ".$this->loc->s("To")." ".($data["fhe"] < 10 ? "0" : "").	$data["fhe"].":".($data["fme"] < 10 ? "0" : "").$data["fme"]."<br />";
				if($data["sahs"] != 0 || $data["sams"] != 0 || $data["sahe"] != 0 || $data["same"] != 0)
					$output .= $this->loc->s("Saturday").	" - ".$this->loc->s("From")." ".($data["sahs"] < 10 ? "0" : "").$data["sahs"].	":".($data["sams"] < 10 ? "0" : "").$data["sams"].
					" ".$this->loc->s("To")." ".($data["sahe"] < 10 ? "0" : "").$data["sahe"].":".($data["same"] < 10 ? "0" : "").$data["same"]."<br />";
				if($data["suhs"] != 0 || $data["sums"] != 0 || $data["suhe"] != 0 || $data["sume"] != 0)
					$output .= $this->loc->s("Sunday").		" - ".$this->loc->s("From")." ".($data["suhs"] < 10 ? "0" : "").$data["suhs"].	":".($data["sums"] < 10 ? "0" : "").$data["sums"].
					" ".$this->loc->s("To")." ".($data["suhe"] < 10 ? "0" : "").$data["suhe"].":".($data["sume"] < 10 ? "0" : "").$data["sume"];
				$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&act=6&tp=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."
					</a></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showContactsTab() {
			$output = "";
			$tpexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","","alias");
			if($tpexist) {
				/*
				 * Ajax new contact
				 */
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=7");
				$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",true,array("type" => "chk"));
				//$formoutput .= template list
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","");
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Email"),"mail","");
				$formoutput .= "<tr><td>".$this->loc->s("srvnotifperiod")."</td><td>".$this->getTimePeriodList("srvnotifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",true,array("type" => "chk"));
				$formoutput .= "<tr><td>".$this->loc->s("srvnotifcmd")."</td><td>".$this->genCommandList("srvnotifcmd")."</td></tr>";
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifperiod")."</td><td>".$this->getTimePeriodList("hostnotifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",true,array("type" => "chk"));
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifcmd")."</td><td>".$this->genCommandList("hostnotifcmd")."</td></tr>";
				$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
				$formoutput .= "</table></form>";
			}
			else
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-contact"));
			
			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contacts","name,mail,template","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Email")."</th><th>Template ?</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["mail"]."</td><td>".($data["template"] == "t" ? $this->loc->s("Yes") : $this->loc->s("No"))."</td><td>
						<a href=\"index.php?mod=".$this->mid."&act=9&ct=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."
						</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showContactgroupsTab() {
			$output = "";
			
			/*
			 * Ajax new contactgroup
			 */
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=10");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","",array("length" => 60, "size" => 30));
			$formoutput .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".FS::$iMgr->select("cts[]","",NULL,true);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contacts","name","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$formoutput .= FS::$iMgr->selElmt($data["name"],$data["name"]);
			}
			$formoutput .= "</select></td></tr>";
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-contactgroup"));
			
			/*
			 * Contactgroup table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contactgroups","name,alias","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Members")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["alias"]."</td><td>";
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_contactgroup_members","name,member","name = '".$data["name"]."'");
				$found2 = false;
				while($data2 = pg_fetch_array($query2)) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $data2["member"];
				}
				$output .= "</td><td><a href=\"index.php?mod=".$this->mid."&act=12&ctg=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."
						</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function showCommandTab() {
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
			}
			
			/*
			 * Ajax new command
			 */
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Command"),"cmd","",array("length" => 1024, "size" => 30));
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-cmd"));
			
			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name,cmd","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Command")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".substr($data["cmd"],0,100).(strlen($data["cmd"]) > 100 ? "..." : "")."</td><td>
						<a href=\"index.php?mod=".$this->mid."&act=2&cmd=".$data["name"]."\">".FS::$iMgr->img("styles/images/cross.png",15,15)."
						</a></td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function getTimePeriodList($name) {
			$output = FS::$iMgr->select($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_timeperiods","name,alias","","alias");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->selElmt($data["alias"],$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genCommandList($name,$tocheck = NULL) {
			$output = FS::$iMgr->select($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name","","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->selElmt($data["name"],$data["name"],$tocheck != NULL && $tocheck == $data["name"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genContactGroupsList($name) {
			$output = FS::$iMgr->select($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contactgroups","name,alias","","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["alias"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genHostsList($name) {
			$output = FS::$iMgr->select($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,addr","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function getHostOrGroupList($name,$multi) {
			$output = FS::$iMgr->select($name,"",NULL,$multi);
			
			$hostlist = array();
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,addr","template = 'f'");
			while($data = pg_fetch_array($query))
				$hostlist[$this->loc->s("Host").": ".$data["name"]." (".$data["addr"].")"] = array(1,$data["name"]);

			$query = FS::$pgdbMgr->Select("z_eye_icinga_hostgroups","name");
			while($data = pg_fetch_array($query))
				$hostlist[$this->loc->s("Hostgroup").": ".$data["name"]] = array(2,$data["name"]);

			ksort($hostlist);

			foreach($hostlist as $host => $value)
				$output .= FS::$iMgr->selElmt($host,$value[0]."$".$value[1]);

			$output .= "</select>";
			return $output;
		}

		private function writeConfiguration() {
			$path = dirname(__FILE__)."/../../../datas/icinga-config/";
				
			/*
			 *  Write commands
			 */
			 
			$file = fopen($path."commands.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name,cmd");
			while($data = pg_fetch_array($query))
				fwrite($file,"define command {\n\tcommand_name\t".$data["name"]."\n\tcommand_line\t".$data["cmd"]."\n}\n\n");
			
			fclose($file);
			
			/*
			 *  Write contact & contactgroups
			 */
			 
			$file = fopen($path."contacts.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contacts","name,mail,srvperiod,srvcmd,hostperiod,hostcmd,hoptd,hoptu,hoptr,hoptf,hopts,soptc,soptw,soptu,soptr,soptf,sopts","template = 'f'");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define contact {\n\tcontact_name\t".$data["name"]."\n\tservice_notification_period\t".$data["srvperiod"]."\n\thost_notification_period\t".$data["hostperiod"]."\n\t");
				fwrite($file,"service_notification_commands\t".$data["srvcmd"]."\n\thost_notification_commands\t".$data["hostcmd"]."\n\temail\t".$data["mail"]."\n\t");
				
				$found = false;
				if($data["hoptd"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"d");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptu"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptr"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hoptf"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hopts"] == "t") {
					if(!$found) fwrite($file,"host_notification_options\t");
					fwrite($file,"s");
					$found = true;
				}
				
				$found = false;
				if($data["soptc"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"c");
					$found = true;
				}
				
				if($found) fwrite($file,",");
				if($data["soptw"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"w");
					$found = true;
				}
				
				if($found) fwrite($file,",");
				if($data["soptu"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				
				if($data["soptr"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				
				if($data["soptf"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["sopts"] == "t") {
					if(!$found) fwrite($file,"\n\tservice_notification_options\t");
					fwrite($file,"s");
				}
				fwrite($file,"\n}\n\n");
			}
			
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contactgroups","name,alias");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define contactgroup {\n\tcontactgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]."\n\tmembers\t");
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_contactgroup_members","member","name = '".$data["name"]."'");
				$found = false;
				while($data2 = pg_fetch_array($query2)) {
					if($found) fwrite($file,",");
					else $found = true;
					fwrite($file,$data2["member"]);
				}
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 *  Timeperiods
			 */
			 
			$file = fopen($path."timeperiods.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define timeperiod {\n\ttimeperiod_name\t".$data["name"]."\n\talias\t".$data["alias"]);
				if(strtotime($data["mhs"].":".$data["mms"]) < strtotime($data["mhe"].":".$data["mme"]))
					fwrite($file,"\n\tmonday\t".$data["mhs"].":".$data["mms"]."-".$data["mhe"].":".$data["mme"]);
				if(strtotime($data["tuhs"].":".$data["tums"]) < strtotime($data["tuhe"].":".$data["tume"]))
					fwrite($file,"\n\ttuesday\t".$data["tuhs"].":".$data["tums"]."-".$data["tuhe"].":".$data["tume"]);
				if(strtotime($data["whs"].":".$data["wms"]) < strtotime($data["whe"].":".$data["wme"]))
					fwrite($file,"\n\twednesday\t".$data["whs"].":".$data["wms"]."-".$data["whe"].":".$data["wme"]);
				if(strtotime($data["thhs"].":".$data["thms"]) < strtotime($data["thhe"].":".$data["thme"]))
					fwrite($file,"\n\tthursday\t".$data["thhs"].":".$data["thms"]."-".$data["thhe"].":".$data["thme"]);
				if(strtotime($data["fhs"].":".$data["fms"]) < strtotime($data["fhe"].":".$data["fme"]))
					fwrite($file,"\n\tfriday\t".$data["fhs"].":".$data["fms"]."-".$data["fhe"].":".$data["fme"]);
				if(strtotime($data["sahs"].":".$data["sams"]) < strtotime($data["sahe"].":".$data["same"]))
					fwrite($file,"\n\tsaturday\t".$data["sahs"].":".$data["sams"]."-".$data["sahe"].":".$data["same"]);
				if(strtotime($data["suhs"].":".$data["sums"]) < strtotime($data["suhe"].":".$data["sume"]))
					fwrite($file,"\n\tsunday\t".$data["suhs"].":".$data["sums"]."-".$data["suhe"].":".$data["sume"]);
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 *  Write hosts
			 */
			 
			$file = fopen($path."hosts.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,
			perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,iconid","template = 'f'");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define host {\n\thost_name\t".$data["name"]."\n\talias\t".$data["alias"]."\n\tdisplay_name\t".$data["dname"]."\n\taddress\t".$data["addr"]."\n\tcheck_command\t");
				fwrite($file,$data["alivecommand"]."\n\tcheck_period\t".$data["checkperiod"]."\n\tcheck_interval\t".$data["checkinterval"]."\n\tretry_interval\t".$data["retrycheckinterval"]."\n\t");
				fwrite($file,"max_check_attempts\t".$data["maxcheck"]."\n\tevent_handler_enabled\t".($data["eventhdlen"] == "t" ? 1 : 0)."\n\tflap_detection_enabled\t".($data["flapen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tprocess_perf_data\t".($data["perfdata"] == "t" ? 1 : 0)."\n\tretain_status_information\t");
				fwrite($file,($data["retstatus"] == "t" ? 1 : 0)."\n\tretain_nonstatus_information\t".($data["retnonstatus"] == "t" ? 1 : 0)."\n\tnotifications_enabled\t".($data["notifen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tnotification_period\t".$data["notifperiod"]."\n\tnotification_interval\t".$data["notifintval"]."\n\t");
				
				$found = false;
				if($data["hostoptd"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"d");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptu"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptr"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostoptf"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["hostopts"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"s");
				}
				
				fwrite($file,"\n\tcontact_groups\t".$data["contactgroup"]);
				
				$found = false;
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = pg_fetch_array($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tparents\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["parent"]);
				}
				if($data["iconid"] && FS::$secMgr->isNumeric($data["iconid"])) {
					$iconpath = FS::$pgdbMgr->GetOneData("z_eye_icinga_icons","path","id = '".$data["iconid"]."'");
					if($iconpath) {
						fwrite($file,"\n\ticon_image\t".$iconpath);
						fwrite($file,"\n\tstatusmap_image\t".$iconpath);
					}
				}
				fwrite($file,"\n}\n\n");
			}
			fclose($file);
			
			/*
			 * Hostgroups config
			 */
			
			$file = fopen($path."hostgroups.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hostgroups","name,alias");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define hostgroup {\n\thostgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]);
				$found = false;
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '1'");
				while($data2 = pg_fetch_array($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tmembers\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["host"]);
					
				}
				$found = false;
				$query2 = FS::$pgdbMgr->Select("z_eye_icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '2'");
				while($data2 = pg_fetch_array($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\thostgroup_members\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["host"]);
				}
				fwrite($file,"\n}\n\n");
			}
			
			fclose($file);
			
			/*
			 * Services config
			 */
			 
			$file = fopen($path."services.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
			retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptf,srvopts,notifintval,ctg,srvoptr",
			"template = 'f'");
			while($data = pg_fetch_array($query)) {
				fwrite($file,"define service {\n\tservice_description\t".$data["name"]."\n\tcheck_command\t".$data["checkcmd"]."\n\t");
				if($data["hosttype"] == 1)
					fwrite($file,"host_name\t".$data["host"]);
				else
					fwrite($file,"hostgroup_name\t".$data["host"]);
				fwrite($file,"\n\tcheck_period\t".$data["checkperiod"]."\n\tcheck_interval\t".$data["checkintval"]."\n\tretry_interval\t".$data["retcheckintval"]."\n\t");
				fwrite($file,"max_check_attempts\t".$data["maxcheck"]."\n\tevent_handler_enabled\t".($data["eventhdlen"] == "t" ? 1 : 0)."\n\tflap_detection_enabled\t".($data["flapen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tprocess_perf_data\t".($data["perfdata"] == "t" ? 1 : 0)."\n\tretain_status_information\t");
				fwrite($file,($data["retstatus"] == "t" ? 1 : 0)."\n\tretain_nonstatus_information\t".($data["retnonstatus"] == "t" ? 1 : 0)."\n\tnotifications_enabled\t".($data["notifen"] == "t" ? 1 : 0));
				fwrite($file,"\n\tnotification_period\t".$data["notifperiod"]."\n\tnotification_interval\t".$data["notifintval"]."\n\tactive_checks_enabled\t".($data["actcheck"] == "t" ? 1 : 0));
				fwrite($file,"\n\tpassive_checks_enabled\t".($data["pascheck"] == "t" ? 1 : 0)."\n\tobsess_over_service\t".($data["obsess"] == "t" ? 1 : 0)."\n\tcheck_freshness\t".($data["freshness"] == "t" ? 1 : 0));
				fwrite($file,"\n\tfailure_prediction_enabled\t".($data["failpreden"] == "t" ? 1 : 0)."\n\tparallelize_check\t".($data["parcheck"] == "t" ? 1 : 0)."\n\tcontact_groups\t".$data["ctg"]."\n\t");
				
				$found = false;
				if($data["srvoptc"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"c");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptw"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"w");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptu"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"u");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptr"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"r");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvoptf"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"f");
					$found = true;
				}
				if($found) fwrite($file,",");
				if($data["srvopts"] == "t") {
					if(!$found) fwrite($file,"notification_options\t");
					fwrite($file,"s");
				}
				fwrite($file,"\n}\n\n");
			}
			fclose($file);
			
			/*
			 * Restarter
			 */
			 
			$file = fopen("/tmp/icinga_restart","w+");
			if(!$file)
				return false;
			fwrite($file,"1");
			fclose($file);
			
			return true;
		}
		public function handlePostDatas($act) {
			switch($act) {
				// Add command
				case 1:
					$cmdname = FS::$secMgr->checkAndSecurisePostData("name");
					$cmd = FS::$secMgr->checkAndSecurisePostData("cmd");
					
					if(!$cmdname || !$cmd || preg_match("#[ ]#",$cmdname)) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=1");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","cmd","name = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=3");
						return;
					}
					
					// @TODO verify paths
					
					FS::$pgdbMgr->Insert("z_eye_icinga_commands","name,cmd","'".$cmdname."','".$cmd."'");
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Remove command
				case 2:
					// @TODO forbid remove when use (host + service)
					$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
					if(!$cmdname) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","cmd","name = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=2");
						return;
					}
					
					// Forbid remove if command is used
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","name","srvcmd = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=4");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","name","hostcmd = '".$cmdname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=4");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_commands","name = '".$cmdname."'");
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Edit command
				case 3:
					// @TODO
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=8&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Add timeperiod
				case 4:
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					
					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					$mhs = FS::$secMgr->getPost("mhs","n+=");
					$mms = FS::$secMgr->getPost("mms","n+=");
					$tuhs = FS::$secMgr->getPost("tuhs","n+=");
					$tums = FS::$secMgr->getPost("tums","n+=");
					$whs = FS::$secMgr->getPost("whs","n+=");
					$wms = FS::$secMgr->getPost("wms","n+=");
					$thhs = FS::$secMgr->getPost("thhs","n+=");
					$thms = FS::$secMgr->getPost("thms","n+=");
					$fhs = FS::$secMgr->getPost("fhs","n+=");
					$fms = FS::$secMgr->getPost("fms","n+=");
					$sahs = FS::$secMgr->getPost("sahs","n+=");
					$sams = FS::$secMgr->getPost("sams","n+=");
					$suhs = FS::$secMgr->getPost("suhs","n+=");
					$sums = FS::$secMgr->getPost("sums","n+=");
					
					if($mhs == NULL || $mms == NULL || $tuhs == NULL || $tums == NULL || $whs == NULL || $wms == NULL || 
						$thhs == NULL || $thms == NULL || $fhs == NULL || $fms == NULL || $sahs == NULL || $sams == NULL || 
						$suhs == NULL || $sums == NULL || $mhs > 23 || $mms > 59 || $tuhs > 23 || $tums > 59 || 
						$whs > 23 || $wms > 59 || $thhs > 23 || $thms > 59 || $fhs > 23 || $fms > 59 || $sahs > 23 || $sams > 59 ||
						$suhs > 23 || $sums > 59) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					$mhe = FS::$secMgr->getPost("mhe","n+=");
					$mme = FS::$secMgr->getPost("mme","n+=");
					$tuhe = FS::$secMgr->getPost("tuhe","n+=");
					$tume = FS::$secMgr->getPost("tume","n+=");
					$whe = FS::$secMgr->getPost("whe","n+=");
					$wme = FS::$secMgr->getPost("wme","n+=");
					$thhe = FS::$secMgr->getPost("thhe","n+=");
					$thme = FS::$secMgr->getPost("thme","n+=");
					$fhe = FS::$secMgr->getPost("fhe","n+=");
					$fme = FS::$secMgr->getPost("fme","n+=");
					$sahe = FS::$secMgr->getPost("sahe","n+=");
					$same = FS::$secMgr->getPost("same","n+=");
					$suhe = FS::$secMgr->getPost("suhe","n+=");
					$sume = FS::$secMgr->getPost("sume","n+=");
					
					if($mhe == NULL || $mme == NULL || $tuhe == NULL || $tume == NULL || $whe == NULL || $wme == NULL || 
						$thhe == NULL || $thme == NULL || $fhe == NULL || $fme == NULL || $sahe == NULL || $same == NULL || 
						$suhe == NULL || $sume == NULL || $mhe > 23 || $mme > 59 || $tuhe > 23 || $tume > 59 || 
						$whe > 23 || $wme > 59 || $thhe > 23 || $thme > 59 || $fhe > 23 || $fme > 59 || $sahe > 23 || $same > 59 ||
						$suhe > 23 || $sume > 59) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","alias","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=3");
						return;
					}
					
					FS::$pgdbMgr->Insert("z_eye_icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume",
						"'".$name."','".$alias."','".$mhs."','".$mms."','".$tuhs."','".$tums."','".$whs."','".$wms."','".$thhs."','".$thms."','".$fhs."','".$fms."','".$sahs."','".$sams."','".$suhs."','".$sums.
						"','".$mhe."','".$mme."','".$tuhe."','".$tume."','".$whe."','".$wme."','".$thhe."','".$thme."','".$fhe."','".$fme."','".$sahe."','".$same."','".$suhe."','".$sume."'");
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=5");
					return;
				// Edit timeperiod
				case 5:
					//@TODO
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=5");
					return;
				// Delete timeperiod
				case 6:
					$tpname = FS::$secMgr->checkAndSecuriseGetData("tp");
					if(!$tpname) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","alias","name = '".$tpname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=2");
						return;
					}
					
					// @ TODO forbid remove when used (service + host / groups ??)
					
					// Forbid remove if timeperiod is used
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","name","srvperiod = '".$tpname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","name","hostperiod = '".$tpname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","checkperiod = '".$tpname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","notifperiod = '".$tpname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_timeperiods","name = '".$tpname."'");
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=5");
					return;
				// Add contact
				case 7:
					$name = FS::$secMgr->getPost("name","w");
					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					$srvnotifperiod = FS::$secMgr->getPost("srvnotifperiod","w");
					$srvnotifcmd = FS::$secMgr->checkAndSecurisePostData("srvnotifcmd");
					$hostnotifperiod = FS::$secMgr->getPost("hostnotifperiod","w");
					$hostnotifcmd = FS::$secMgr->checkAndSecurisePostData("hostnotifcmd");
					if(!$name || !$mail || preg_match("#[ ]#",$name) || !$srvnotifperiod || !$srvnotifcmd || !$hostnotifperiod || !$hostnotifcmd) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
						return;
					}
					
					$istpl = FS::$secMgr->checkAndSecurisePostData("istemplate");
					$srvoptc = FS::$secMgr->checkAndSecurisePostData("srvoptc");
					$srvoptw = FS::$secMgr->checkAndSecurisePostData("srvoptw");
					$srvoptu = FS::$secMgr->checkAndSecurisePostData("srvoptu");
					$srvoptr = FS::$secMgr->checkAndSecurisePostData("srvoptr");
					$srvoptf = FS::$secMgr->checkAndSecurisePostData("srvoptf");
					$srvopts = FS::$secMgr->checkAndSecurisePostData("srvopts");
					$hostoptd = FS::$secMgr->checkAndSecurisePostData("hostoptd");
					$hostoptu = FS::$secMgr->checkAndSecurisePostData("hostoptu");
					$hostoptr = FS::$secMgr->checkAndSecurisePostData("hostoptr");
					$hostoptf = FS::$secMgr->checkAndSecurisePostData("hostoptf");
					$hostopts = FS::$secMgr->checkAndSecurisePostData("hostopts");
				
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","name","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=3");
						return;
					}
					
					// Timeperiods don't exist
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$srvnotifperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$hostnotifperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","name","name = '".$srvnotifcmd."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","name","name = '".$hostnotifcmd."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					FS::$pgdbMgr->Insert("z_eye_icinga_contacts","name,mail,template,srvperiod,srvcmd,hostperiod,hostcmd,soptc,soptw,soptu,soptr,soptf,sopts,hoptd,hoptu,hoptr,hoptf,hopts",
						"'".$name."','".$mail."','".($istpl == "on" ? 1 : 0)."','".$srvnotifperiod."','".$srvnotifcmd."','".$hostnotifperiod."','".$hostnotifcmd."','".($srvoptc == "on" ? 1 : 0)."','".
						($srvoptw == "on" ? 1 : 0)."','".($srvoptu == "on" ? 1 : 0)."','".($srvoptr == "on" ? 1 : 0)."','".($srvoptf == "on" ? 1 : 0)."','".($srvopts == "on" ? 1 : 0)."','".
						($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".($hostopts == "on" ? 1 : 0)."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=6");
					return;
				// Edit contact
				case 8:
					// @TODO
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=6");
					return;
				// Delete contact
				case 9:
					$ctname = FS::$secMgr->checkAndSecuriseGetData("ct");
					if(!$ctname) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","mail","name = '".$ctname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=2");
						return;
					}
					
					// Forbid remove if in existing contact group
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contactgroup_members","name","member = '".$ctname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_contacts","name = '".$ctname."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=6");
					return;
				// Add contact group
				case 10:
					$name = FS::$secMgr->getPost("name","w");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$cts = FS::$secMgr->checkAndSecurisePostData("cts");
					
					if(!$name || !$alias || !$cts || $cts == "") {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=1");
						return;
					}
					
					// ctg exists
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contactgroups","alias","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=3");
						return;
					}
					
					// some members don't exist
					for($i=0;$i<count($cts);$i++) {
						if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","mail","name = '".$cts[$i]."'")) {
							header("Location: index.php?mod=".$this->mid."&sh=7&err=1");
							return;
						}
					}
					
					// Add it
					FS::$pgdbMgr->Insert("z_eye_icinga_contactgroups","name,alias","'".$name."','".$alias."'");
					for($i=0;$i<count($cts);$i++) {
						FS::$pgdbMgr->Insert("z_eye_icinga_contactgroup_members","name,member","'".$name."','".$cts[$i]."'");
					}
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=7");
					return;
				// Edit contact group
				case 11:
					// @TODO
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=7");
					return;
				// Delete contact group
				case 12:
					// @TODO forbid remove when used (service, service_group)
					$ctgname = FS::$secMgr->getPost("ctg","w");
					if(!$ctgname) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_contactgroups","alias","name = '".$ctgname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=2");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","contactgroup = '".$ctgname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=4");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_contactgroup_members","name = '".$ctgname."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_contactgroups","name = '".$ctgname."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=7&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=7");
					return;
				// Add host
				case 13:
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$dname = FS::$secMgr->checkAndSecurisePostData("dname");
					$parent = FS::$secMgr->checkAndSecurisePostData("parent");
					$icon = FS::$secMgr->checkAndSecurisePostData("icon");
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					$checkcommand = FS::$secMgr->checkAndSecurisePostData("checkcommand");
					$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
					$notifperiod = FS::$secMgr->checkAndSecurisePostData("notifperiod");
					$ctg = FS::$secMgr->getPost("ctg","w");
					
					if(!$name || preg_match("#[ ]#",$name) || !$alias || !$dname || !$addr || !$checkcommand || !$checkperiod ||
						 !$notifperiod || !$ctg || $icon && !FS::$secMgr->isNumeric($icon)) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
				
					// Checks
					$tpl = FS::$secMgr->checkAndSecurisePostData("istemplate");
					$hostoptd = FS::$secMgr->checkAndSecurisePostData("hostoptd");
					$hostoptu = FS::$secMgr->checkAndSecurisePostData("hostoptu");
					$hostoptr = FS::$secMgr->checkAndSecurisePostData("hostoptr");
					$hostoptf = FS::$secMgr->checkAndSecurisePostData("hostoptf");
					$hostopts = FS::$secMgr->checkAndSecurisePostData("hostopts");
					$eventhdlen = FS::$secMgr->checkAndSecurisePostData("eventhdlen");
					$flapen = FS::$secMgr->checkAndSecurisePostData("flapen");
					$failpreden = FS::$secMgr->checkAndSecurisePostData("failpreden");
					$perfdata = FS::$secMgr->checkAndSecurisePostData("perfdata");
					$retstatus = FS::$secMgr->checkAndSecurisePostData("retstatus");
					$retnonstatus = FS::$secMgr->checkAndSecurisePostData("retnonstatus");
					$notifen = FS::$secMgr->checkAndSecurisePostData("notifen");
					
					// Numerics
					$checkintval = FS::$secMgr->getPost("checkintval","n+");
					$retcheckintval = FS::$secMgr->getPost("retcheckintval","n+");
					$maxcheck = FS::$secMgr->getPost("maxcheck","n+");
					$notifintval = FS::$secMgr->getPost("notifintval","n+=");
					
					if($checkintval == NULL || $retcheckintval == NULL || $maxcheck == NULL || $notifintval == NULL) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					// Now verify datas
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=3");
						return;
					}
					
					if($parent && !in_array("none",$parent)) {
						for($i=0;$i<count($parent);$i++) {
							if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","name = '".$parent[$i]."'")) {
								header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
								return;
							}
						}
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","name","name = '".$checkcommand."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$checkperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$notifperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}

					FS::$pgdbMgr->Insert("z_eye_icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,
						failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid",
						"'".$name."','".$alias."','".$dname."','".$addr."','".$checkcommand."','".$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
						($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".$notifperiod."','".
						$notifintval."','".($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".
						($hostopts == "on" ? 1 : 0)."','".$ctg."','".($tpl == "on" ? 1 : 0)."','".$icon."'");
					if($parent && !in_array("none",$parent)) {
						for($i=0;$i<count($parent);$i++) {
							FS::$pgdbMgr->Insert("z_eye_icinga_host_parents","name,parent","'".$name."','".$parent[$i]."'");
						}
					}
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;	
				// Edit host
				case 14:
					// @TODO
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;	
				// Remove host
				case 15:
					$name = FS::$secMgr->checkAndSecuriseGetData("host");
					if(!$name) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					// Not exists
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","addr","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=2");
						return;
					}
					// @ TODO forbid remove when used
					
					// Remove host and links with parents and hostgroups
					FS::$pgdbMgr->Delete("z_eye_icinga_host_parents","name = '".$name."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_host_parents","parent = '".$name."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_hostgroup_members","member = '".$name."' AND hosttype = 1");
					FS::$pgdbMgr->Delete("z_eye_icinga_hosts","name = '".$name."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;
				// add service
				case 16:
					$name = trim(FS::$secMgr->checkAndSecurisePostData("desc"));
					$host = FS::$secMgr->checkAndSecurisePostData("host");
					$checkcmd = FS::$secMgr->getPost("checkcmd","w");
					$checkperiod = FS::$secMgr->getPost("checkperiod","w");
					$notifperiod = FS::$secMgr->getPost("notifperiod","w");
					$ctg = FS::$secMgr->getPost("ctg","w");

					if(!$name || preg_match("#[\(]|[\)]|[\[]|[\]]#",$name) || !$host || !$checkcmd || !$checkperiod || !$notifperiod || !$ctg) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					$srvoptw = FS::$secMgr->checkAndSecurisePostData("srvoptw");
					$srvoptc = FS::$secMgr->checkAndSecurisePostData("srvoptc");
					$srvoptu = FS::$secMgr->checkAndSecurisePostData("srvoptu");
					$srvoptr = FS::$secMgr->checkAndSecurisePostData("srvoptr");
					$srvoptf = FS::$secMgr->checkAndSecurisePostData("srvoptf");
					$srvopts = FS::$secMgr->checkAndSecurisePostData("srvopts");
					
					$actcheck = FS::$secMgr->checkAndSecurisePostData("actcheck");
					$pascheck = FS::$secMgr->checkAndSecurisePostData("pascheck");
					$parcheck = FS::$secMgr->checkAndSecurisePostData("parcheck");
					$obsess = FS::$secMgr->checkAndSecurisePostData("obsess");
					$freshness = FS::$secMgr->checkAndSecurisePostData("freshness");
					$notifen = FS::$secMgr->checkAndSecurisePostData("notifen");

					$eventhdlen = FS::$secMgr->checkAndSecurisePostData("eventhdlen");
					$flapen = FS::$secMgr->checkAndSecurisePostData("flapen");
					$failpreden = FS::$secMgr->checkAndSecurisePostData("failpreden");
					$perfdata = FS::$secMgr->checkAndSecurisePostData("perfdata");
					$retstatus = FS::$secMgr->checkAndSecurisePostData("retstatus");
					$retnonstatus = FS::$secMgr->checkAndSecurisePostData("retnonstatus");
					$tpl = FS::$secMgr->checkAndSecurisePostData("istemplate");

					$checkintval = FS::$secMgr->getPost("checkintval","n+");
					$retcheckintval = FS::$secMgr->getPost("retcheckintval","n+");
					$maxcheck = FS::$secMgr->getPost("maxcheck","n+");
					$notifintval = FS::$secMgr->getPost("notifintval","n+=");
					
					if($checkintval == NULL || $retcheckintval == NULL || $maxcheck == NULL || $notifintval == NULL) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					$mt = preg_split("#[$]#",$host);
					if(count($mt) != 2 || ($mt[0] != 1 && $mt[0] != 2)) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_commands","name","name = '".$checkcmd."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$checkperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","name = '".$notifperiod."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					if($mt[0] == 1 && !FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","name = '".$mt[1]."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					if($mt[0] == 2 && !FS::$pgdbMgr->GetOneData("z_eye_icinga_hostgroups","name","name = '".$mt[1]."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}

					
					FS::$pgdbMgr->Insert("z_eye_icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
						retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptr,srvoptf,srvopts,notifintval,ctg,template",
						"'".$name."','".$mt[1]."','".$mt[0]."','".($actcheck == "on" ? 1 : 0)."','".($pascheck == "on" ? 1 : 0)."','".($parcheck == "on" ? 1 : 0)."','".($obsess == "on" ? 1 : 0).
						"','".($freshness == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
						($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".$checkcmd."','".
						$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".$notifperiod."','".($srvoptc == "on" ? 1 : 0)."','".($srvoptw == "on" ? 1 : 0)."','".
						($srvoptu == "on" ? 1 : 0)."','".($srvoptr == "on" ? 1 : 0)."','".($srvoptf == "on" ? 1 : 0)."','".($srvopts == "on" ? 1 : 0)."','".$notifintval."','".$ctg."','".
						($tpl == "on" ? 1 : 0)."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=4");
					return;
				// edit service
				case 17:
					// @TODO
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=4");
					return;
				// remove service
				case 18:
					$name = FS::$secMgr->checkAndSecuriseGetData("srv");
					if(!$name) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					// Not exists
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_services","name","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=2");
						return;
					}
					
					// membertype 1 = service, 2 = servicegroup
					FS::$pgdbMgr->Delete("z_eye_icinga_servicegroups","member = '".$name."' AND membertype = 1");
					FS::$pgdbMgr->Delete("z_eye_icinga_services","name = '".$name."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=4&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=4");
					return;
				// Add hostgroup
				case 19:
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$members = FS::$secMgr->checkAndSecurisePostData("members");
					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_hostgroups","name","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}
					
					if($members) {
						for($i=0;$i<count($members);$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if(count($mt) != 2 && !FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","name = '".$mt[1]."'")) {
								header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
						for($i=0;$i<count($members);$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if(count($mt) == 2 && ($mt[0] == 1 || $mt[0] == 2))
								FS::$pgdbMgr->Insert("z_eye_icinga_hostgroup_members","name,host,hosttype","'".$name."','".$mt[1]."','".$mt[0]."'");
						}
					}
					else {
						header("Location: index.php?mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					FS::$pgdbMgr->Insert("z_eye_icinga_hostgroups","name,alias","'".$name."','".$alias."'");
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=3");
					return;
				// Edit hostgroup
				case 20:
					// @TODO
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=3");
					return;
				// remove hostgroup
				case 21:
					$name = FS::$secMgr->checkAndSecuriseGetData("hg");
					if(!$name) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=1");
						return;
					}
					
					// Not exists
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_hostgroups","name","name = '".$name."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}
					
					// Used
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_services","name","host = '".$name."' AND hosttype = '2'")) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=2");
						return;
					}
				
					// Delete hostgroup and members
					FS::$pgdbMgr->Delete("z_eye_icinga_hostgroup_members","name = '".$name."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_hostgroup_members","host = '".$name."' AND hosttype = 2");
					FS::$pgdbMgr->Delete("z_eye_icinga_hostgroups","name = '".$name."'");
					
					if(!$this->writeConfiguration()) {
						header("Location: index.php?mod=".$this->mid."&sh=3&err=5");
						return;
					}
					header("Location: index.php?mod=".$this->mid."&sh=3");
					return;
			}
		}
	};
?>
