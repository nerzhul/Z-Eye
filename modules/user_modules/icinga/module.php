<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
			}
			return $output;
		}
		
		private function showGeneralTab() {
			$output = "";
			
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
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=13");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Alias"),"alias","");
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("DisplayName"),"dname","");
			$formoutput .= "<tr><td>".$this->loc->s("Parent")."</td><td>";
			$formoutput .= FS::$iMgr->addList("parent");
			$formoutput .= FS::$iMgr->addElementToList("","",true);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,addr","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$formoutput .= FS::$iMgr->addElementToList($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$formoutput .= "</select></td></tr>";
			
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Address"),"addr","");
			
			// Checks
			$formoutput .= "<tr><td>".$this->loc->s("alivecommand")."</td><td>".$this->genCommandList("checkcommand")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod")."</td></tr>";
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("check-interval"),"checkintval",3);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("retry-check-interval"),"retcheckintval",1);
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("max-check"),"maxcheck",10);
			
			// Global
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("eventhdl-en"),"eventhdlen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("flap-en"),"flapen",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("failpredict-en"),"failpreden",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("perfdata"),"perfdata",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainstatus"),"retstatus",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainnonstatus"),"retnonstatus",true);
			
			// Notifications
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("notif-en"),"notifen",true);
			$formoutput .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod")."</td></tr>";
			$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("notif-interval"),"notifintval",0);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptdown"),"hostoptd",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptunreach"),"hostoptu",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptrec"),"hostoptr",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptflap"),"hostoptf",true);
			$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptsched"),"hostopts",true);
			$formoutput .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg")."</td></tr>";
			
			// icon image
			// statusmap image
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
			$formoutput .= "</table></form>";				
				
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
			
			/*
			 * Host table
			 */
			$found = false;
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,alias,addr","","name");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Address")."</th><th></th></tr>";
				}
				$output .= "<tr><td>".$data["name"]."</td><td>".$data["alias"]."</td><td>".$data["addr"]."</td><td>";
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
			
			return $output;
		}
		
		private function showServicesTab() {
			$output = "";
			
			$tpexist = FS::$pgdbMgr->GetOneData("z_eye_icinga_timeperiods","name","","alias");
			if($tpexist) {
				
				/*
				 * Ajax new service
				 */
				$formoutput = "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
				//$formoutput .= template list
				
				// Global
				$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Description"),"desc","",false,array("length" => 120, "size" => 30));
				// @ TODO support hostlist
				$formoutput .= "<tr><td>".$this->loc->s("Host")."</td><td>".$this->genHostsList("Host")."</td></tr>";
				
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("active-check-en"),"actcheck",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("passive-check-en"),"pascheck",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("parallel-check"),"parcheck",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("obs-over-srv"),"obsess",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("check-freshness"),"freshness",false);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("notif-en"),"notifen",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("eventhdl-en"),"eventhdlen",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("flap-en"),"flapen",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("failpredict-en"),"failpreden",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("perfdata"),"perfdata",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainstatus"),"retstatus",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("retainnonstatus"),"retnonstatus",true);
				
				// Checks
				$formoutput .= "<tr><td>".$this->loc->s("checkcmd")."</td><td>".$this->genCommandList("checkcmd")."</td></tr>";
				$formoutput .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("check-interval"),"checkintval",3);
				$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("retry-check-interval"),"retcheckintval",1);
				$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("max-check"),"maxcheck",10);
				
				// Notifications
				$formoutput .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod")."</td></tr>";
				// $formoutput .= notifoptions
				$formoutput .= FS::$iMgr->addIndexedNumericLine($this->loc->s("notif-interval"),"notifintval",0);
				// @ TODO support for contact only
				$formoutput .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg")."</td></tr>";
				$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
				$formoutput .= "</table>";
			}
			else
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
			
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-service"));
			
			return $output;
		}
		
		private function showTimeperiodsTab() {
			$output = "";
			
			/*
			 * Ajax new Timeperiod
			 * @TODO: support for multiple times in one day, and calendar days
			 */
			
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=4");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","",false,array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Alias"),"alias","",false,array("length" => 120, "size" => 30));
			$formoutput .= "<tr><td>".$this->loc->s("Monday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("mhs","mms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("mhe","mme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Tuesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("tuhs","tums")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("tuhe","tume")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Wednesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("whs","wms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("whe","wme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Thursday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("thhs","thms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("thhe","thme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Friday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("fhs","fms")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("fhe","fme")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Saturday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("sahs","sams")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("sahe","same")."</td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Sunday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("suhs","sums")."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("suhe","sume")."</td></tr>";
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
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
				$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=7");
				$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("is-template"),"istemplate",true);
				//$formoutput .= template list
				$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","");
				$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Email"),"mail","");
				$formoutput .= "<tr><td>".$this->loc->s("srvnotifperiod")."</td><td>".$this->getTimePeriodList("srvnotifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptcrit"),"srvoptc",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptwarn"),"srvoptw",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptunreach"),"srvoptu",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptrec"),"srvoptr",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptflap"),"srvoptf",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("srvoptsched"),"srvopts",true);
				$formoutput .= "<tr><td>".$this->loc->s("srvnotifcmd")."</td><td>".$this->genCommandList("srvnotifcmd")."</td></tr>";
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifperiod")."</td><td>".$this->getTimePeriodList("hostnotifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptdown"),"hostoptd",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptunreach"),"hostoptu",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptrec"),"hostoptr",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptflap"),"hostoptf",true);
				$formoutput .= FS::$iMgr->addIndexedCheckLine($this->loc->s("hostoptsched"),"hostopts",true);
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifcmd")."</td><td>".$this->genCommandList("hostnotifcmd")."</td></tr>";
				$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
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
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=10");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","",false,array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Alias"),"alias","",false,array("length" => 60, "size" => 30));
			$formoutput .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".FS::$iMgr->addList("cts[]","",NULL,true);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contacts","name","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$formoutput .= FS::$iMgr->addElementToList($data["name"],$data["name"]);
			}
			$formoutput .= "</select></td></tr>";
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
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
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Name"),"name","",false,array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->addIndexedLine($this->loc->s("Command"),"cmd","",false,array("length" => 1024, "size" => 30));
			$formoutput .= FS::$iMgr->addTableSubmit("",$this->loc->s("Add"));
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
			$output = FS::$iMgr->addList($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_timeperiods","name,alias","","alias");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->addElementToList($data["alias"],$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genCommandList($name) {
			$output = FS::$iMgr->addList($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_commands","name","","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->addElementToList($data["name"],$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genContactGroupsList($name) {
			$output = FS::$iMgr->addList($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_contactgroups","name,alias","","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->addElementToList($data["name"]." (".$data["alias"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genHostsList($name) {
			$output = FS::$iMgr->addList($name);
			$query = FS::$pgdbMgr->Select("z_eye_icinga_hosts","name,addr","template = 'f'","name");
			while($data = pg_fetch_array($query)) {
				$output .= FS::$iMgr->addElementToList($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
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
					header("Location: index.php?mod=".$this->mid."&sh=8");
					return;
				// Edit command
				case 3:
					// @TODO
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
					header("Location: index.php?mod=".$this->mid."&sh=5");
					return;
				// Edit timeperiod
				case 5:
					//@TODO
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
				
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contacts","alias","name = '".$name."'")) {
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
					header("Location: index.php?mod=".$this->mid."&sh=6");
					return;
				// Edit contact
				case 8:
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
					if(FS::$pgdbMgr->GetOneData("z_eye_icinga_contactgroups","name","member = '".$ctname."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					FS::$pgdbMgr->Delete("z_eye_icinga_contacts","name = '".$ctname."'");
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
					header("Location: index.php?mod=".$this->mid."&sh=7");
					return;
				// Edit contact group
				case 11:
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
					
					header("Location: index.php?mod=".$this->mid."&sh=7");
					return;
				// Add host
				case 13:
					$name = FS::$secMgr->getPost("host","w");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$dname = FS::$secMgr->checkAndSecurisePostData("dname");
					$parent = FS::$secMgr->getPost("parent","w");
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					$checkcommand = FS::$secMgr->checkAndSecurisePostData("checkcommand");
					$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
					$notifperiod = FS::$secMgr->checkAndSecurisePostData("notifperiod");
					$ctg = FS::$secMgr->getPost("ctg","w");
					
					if(!$name || !$alias || !$dname || !$addr || !$checkcommand || !$checkperiod || !$notifperiod || !$ctg) {
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
					
					if(!FS::$pgdbMgr->GetOneData("z_eye_icinga_hosts","name","name = '".$parent."'")) {
						header("Location: index.php?mod=".$this->mid."&sh=2&err=1");
						return;
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
						failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup",
						"'".$name."','".$alias."','".$dname."','".$addr."','".$checkcommand."','".$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
						($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".$notifperiod."','".
						$notifintval."','".($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".
						($hostopts == "on" ? 1 : 0)."','".$ctg."'");
					FS::$pgdbMgr->Insert("z_eye_icinga_host_parents","name,parent","'".$name."','".$parent."'");
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;	
				// Edit host
				case 14:
				
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;	
				// Remove host
				case 15:
					$name = FS::$secMgr->getPost("host","w");
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
					
					// Remove host and links with parents
					FS::$pgdbMgr->Delete("z_eye_icinga_host_parents","name = '".$name."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_host_parents","parent = '".$name."'");
					FS::$pgdbMgr->Delete("z_eye_icinga_hosts","name = '".$name."'");
					header("Location: index.php?mod=".$this->mid."&sh=2");
					return;				
			}
		}
	};
?>
