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

	class iIcinga extends genModule{
		function iIcinga() { parent::genModule(); $this->loc = new lIcinga(); }
		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-icinga"));
			$edit = FS::$secMgr->checkAndSecuriseGetData("edit");
			switch($edit) {
				case 2: $output = $this->editHost(); break;
				case 3: $output = $this->editHostgroup(); break;
				case 4: $output = $this->editService(); break;
				case 5: $output = $this->editTimeperiod(); break;
				case 6: $output = $this->editContact(); break;
				case 7: $output = $this->editContactgroup(); break;
				case 8: $output = $this->editCmd(); break;
				default:
					$output = $this->showTabPanel();
					break;
			}
			return $output;
		}

		private function showTabPanel() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$err = FS::$secMgr->checkAndSecuriseGetData("err");

			if($err == 99)
				$output .= FS::$iMgr->printError($this->loc->s("err-no-right"));

			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-icinga");
				$panElmts = array();
				//array_push($panElmts,array(1,"mod=".$this->mid,$this->loc->s("General")));
				if(FS::$sessMgr->hasRight("mrule_icinga_host_write"))
					array_push($panElmts,array(2,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Hosts")));
				if(FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
					array_push($panElmts,array(3,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Hostgroups")));
				if(FS::$sessMgr->hasRight("mrule_icinga_srv_write"))
					array_push($panElmts,array(4,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Services")));
				if(FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
					array_push($panElmts,array(5,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Timeperiods")));
				if(FS::$sessMgr->hasRight("mrule_icinga_ct_write"))
					array_push($panElmts,array(6,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Contacts")));
				if(FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
					array_push($panElmts,array(7,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Contactgroups")));
				if(FS::$sessMgr->hasRight("mrule_icinga_cmd_write"))
					array_push($panElmts,array(8,"mod=".$this->mid.($err ? "&err=".$err : ""),$this->loc->s("Commands")));
				$output .= FS::$iMgr->tabPan($panElmts,$sh);
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
			if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			
			$output = "";
			
			$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","");
			if(!$tpexist) {
				FS::$iMgr->setJSBuffer(1);
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
				return $output;
			}
			
			$ctexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","name","");
			if(!$ctexist) {
				FS::$iMgr->setJSBuffer(1);
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-contactgroups"));
				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-host"));
				return $output;
			}

			/*
			 * Ajax new host
			 */
			FS::$iMgr->setJSBuffer(1);
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=13",array("id" => "hostfrm"));
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$formoutput .= template list
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","");
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","");
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname","");
			$formoutput .= "<tr><td>".$this->loc->s("Icon")."</td><td>";
			$formoutput .= FS::$iMgr->select("icon");
			$formoutput .= FS::$iMgr->selElmt("Aucun","");

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_icons","id,name","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query))
				$formoutput .= FS::$iMgr->selElmt($data["name"],$data["id"]);

			$formoutput .= "</select></td></tr>";
			$formoutput .= "<tr><td>".$this->loc->s("Parent")."</td><td>";
			$formoutput2 = FS::$iMgr->selElmt($this->loc->s("None"),"none",true);
			$countElmt = 0;

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$formoutput2 .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"]);
			}

			if($countElmt/4 < 4) $countElmt = 16;
			$formoutput .= FS::$iMgr->select("parent[]","",NULL,true,array("size" => round($countElmt/4)));
			$formoutput .= $formoutput2;
			$formoutput .= "</select></td></tr>";

			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Address"),"addr","");

			$formoutput .= "<tr><td>".$this->loc->s("Hostgroups")."</td><td>".$this->getHostOrGroupList("hostgroups[]",false,array(),"",true)."</td></tr>";

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
			$formoutput .= "</table>";
			$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=13","hostfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true))."</form>";

			$output .= FS::$iMgr->opendiv("<center>".$formoutput."</center>",$this->loc->s("new-host"),array("width" => 600));

			/*
			 * Host table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,addr,template","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table width=\"80%\"><tr><th width=\"20%\">".$this->loc->s("Name")."</th><th width=\"20%\">".$this->loc->s("Alias")."</th><th width=\"20%\">".$this->loc->s("Address")."</th><th width=\"15%\">".$this->loc->s("Template")."</th><th width=\"20%\">".$this->loc->s("Parent")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"h_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=2&host=".$data["name"]."\">".
					$data["name"]."</a></td><td>".$data["alias"]."</td><td>".$data["addr"]."</td><td>";
				if($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td>";
				$found2 = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $data2["parent"];
				}
				$output .="</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=15&host=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-host")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editHost() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_host_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$host = FS::$secMgr->checkAndSecuriseGetData("host");
			// @TODO: log
			if(!$host) {
				return FS::$iMgr->printError($this->loc->s("err-no-host"));
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid","name = '".$host."'");
			$hostdata = FS::$dbMgr->Fetch($query);
			if(!$hostdata) {
				return FS::$iMgr>printError($this->loc->s("err-no-host"));
			}
			$output = FS::$iMgr->h1("title-host-edit");	

			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=13",array("id" => "hostfrm")).
				FS::$iMgr->hidden("edit",1).FS::$iMgr->hidden("name",$host);
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",$hostdata["template"] == "t" ? true : false,array("type" => "chk"));
			//$formoutput .= template list
			$output .= "<tr><td>".$this->loc->s("Name")."</td><td>".$host."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$hostdata["alias"]);
			$output .= FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname",$hostdata["dname"]);
			$output .= "<tr><td>".$this->loc->s("Icon")."</td><td>";
			$output .= FS::$iMgr->select("icon");
			$output .= FS::$iMgr->selElmt("Aucun","",$hostdata["iconid"] == "" ? true : false);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_icons","id,name","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query))
				$output .= FS::$iMgr->selElmt($data["name"],$data["id"],$hostdata["iconid"] == $data["id"] ? true : false);
			$output .= "</select></td></tr>";

			$parentlist = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$host."'");
			while($data = FS::$dbMgr->Fetch($query))
				array_push($parentlist,$data["parent"]);
			
			$output .= "<tr><td>".$this->loc->s("Parent")."</td><td>";
			$tmpoutput = FS::$iMgr->selElmt($this->loc->s("None"),"none",count($parentlist) > 0 ? false : true);
			$countElmt = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				if($data["name"] != $host)
					$tmpoutput .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"],in_array($data["name"],$parentlist));
			}
			if($countElmt/4 < 4) $countElmt = 16;
			$output .= FS::$iMgr->select("parent[]","",NULL,true,array("size" => round($countElmt/4)));
			$output .= $tmpoutput;
			$output .= "</select></td></tr>";
			
			$output .= FS::$iMgr->idxLine($this->loc->s("Address"),"addr",$hostdata["addr"]);

			$hglist = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name","host = '".$host."' AND hosttype = '1'");
			while($data = FS::$dbMgr->Fetch($query))
				array_push($hglist,$data["name"]);
			
			$output .= "<tr><td>".$this->loc->s("Hostgroups")."</td><td>".$this->getHostOrGroupList("hostgroups[]",false,$hglist,"",true)."</td></tr>";

			// Checks
			$output .= "<tr><td>".$this->loc->s("alivecommand")."</td><td>".$this->genCommandList("checkcommand",$hostdata["alivecommand"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod",$hostdata["checkperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => $hostdata["checkinterval"], "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => $hostdata["retrycheckinterval"], "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => $hostdata["maxcheck"], "type" => "num"));
			
			// Global
			$output .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",$hostdata["eventhdlen"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",$hostdata["flapen"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",$hostdata["failpreden"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",$hostdata["perfdata"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",$hostdata["retstatus"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",$hostdata["retnonstatus"] == "t" ? true : false,array("type" => "chk"));
			
			// Notifications
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",$hostdata["notifen"] == "t" ? true : false,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod",$hostdata["notifperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => $hostdata["notifintval"], "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",$hostdata["hostoptd"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",$hostdata["hostoptu"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",$hostdata["hostoptr"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",$hostdata["hostoptf"] == "t" ? true : false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",$hostdata["hostopts"] == "t" ? true : false,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg",$hostdata["contactgroup"])."</td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=13","hostfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}
		
		private function showHostgroupsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			$hostexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","");
			FS::$iMgr->setJSBuffer(1);
			if(!$hostexist)
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-hosts"));
			else {
				/*
				 * Ajax new hostgroup
				 */
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=19",array("id" => "hgfrm"));
				$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
				// Global
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","",array("length" => 60, "size" => 30));
				$formoutput .= "<tr><td>".$this->loc->s("Members")."</td><td>".$this->getHostOrGroupList("members[]",true)."</td></tr>";
				$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
				$formoutput .= "</table></form>";
				$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=19","subpop #hgfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			}
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
			}	
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-hostgroup"),array("width" => 460));

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table width=\"80%\"><tr><th width=\"10%\">".$this->loc->s("Name")."</th><th width=\"10%\">".$this->loc->s("Alias")."</th><th width=\"80%\">".$this->loc->s("Members")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"hg_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=3&hg=".$data["name"]."\">".
					$data["name"]."</a></td><td>".$data["alias"]."</td><td>";
				$found2 = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."'",array("order" => "hosttype,name"));
				while($data2 = FS::$dbMgr->Fetch($query2)) {
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
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=21&hg=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-hostgroup")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}
		
		private function editHostgroup() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$hostgroup = FS::$secMgr->checkAndSecuriseGetData("hg");
                        // @TODO: log
                        if(!$hostgroup) {
                                return FS::$iMgr->printError($this->loc->s("err-no-hostgroup"));
                        }

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","name = '".$hostgroup."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$alias = $data["alias"];
			}
			else {
                                return FS::$iMgr->printError($this->loc->s("err-no-hostgroup"));
                        }
			$output = FS::$iMgr->h1("title-hostgroup-edit");
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=19",array("id" => "hgfrm"));
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>
				<tr><td>".$this->loc->s("Name")."</td><td>".$hostgroup."</td></tr>";
			$output .= FS::$iMgr->hidden("name",$hostgroup).FS::$iMgr->hidden("edit",1);
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias,array("length" => 60, "size" => 30));

			$hostlist = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","name = '".$hostgroup."'");
			while($data = FS::$dbMgr->Fetch($query))
				array_push($hostlist,$data["hosttype"]."$".$data["host"]);

			$output .= "<tr><td>".$this->loc->s("Members")."</td><td>".$this->getHostOrGroupList("members[]",true,$hostlist,$hostgroup)."</td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=19","hgfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}

		private function showServicesTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));

			$output = "";

			$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","");
			if($tpexist) {
				/*
				 * Ajax new service
				 */
				FS::$iMgr->setJSBuffer(1);
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=16",array("id" => "srvfrm"));
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
				$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=16","subpop #srvfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			}
			else
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-service"));

			$output .= FS::$iMgr->opendiv("<center>".$formoutput."</center>",$this->loc->s("new-service"),array("width" => 700));

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,template,ctg","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Host")."</th><th>".$this->loc->s("Hosttype")."</th><th>".$this->loc->s("Template")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"srv_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=4&srv=".$data["name"]."\">".
					$data["name"]."</a></td><td>".$data["host"]."</td><td>";
				switch($data["hosttype"]) {
					case 1: $output .= "Simple"; break;
					case 2: $output .= "Groupe"; break;
					default: $output .= "unk"; break;
				}
				$output .= "</td><td>";
				if($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=18&srv=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-service")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editService() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$srv = FS::$secMgr->checkAndSecuriseGetData("srv");
                        // @TODO: log
                        if(!$srv) {
                                return FS::$iMgr->printError($this->loc->s("err-no-service"));
                        }

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
				retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptr,srvoptf,srvopts,notifintval,ctg,template",
				"name = '".$srv."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$host = $data["host"];
			}
			else {
                                return FS::$iMgr->printError($this->loc->s("err-no-service"));
                        }

			$output = FS::$iMgr->h1("title-edit-service").FS::$iMgr->form("index.php?mod=".$this->mid."&act=16",array("id" => "srvfrm"));
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",$data["template"] == 't',array("type" => "chk"));
			//$formoutput .= template list

			// Global
			$output .= "<tr><td>".$this->loc->s("Description")."</td><td>".$data["name"]."</td></tr>";
			$output .= FS::$iMgr->hidden("desc",$data["name"]).FS::$iMgr->hidden("edit",1);
			// @ TODO support hostlist
			$output .= "<tr><td>".$this->loc->s("Host")."</td><td>".$this->getHostOrGroupList("host",false,array($data["hosttype"]."$".$data["host"]))."</td></tr>";

			$output .= FS::$iMgr->idxLine($this->loc->s("active-check-en"),"actcheck",$data["actcheck"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("passive-check-en"),"pascheck",$data["pascheck"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("parallel-check"),"parcheck",$data["parcheck"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("obs-over-srv"),"obsess",$data["obsess"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("check-freshness"),"freshness",$data["freshness"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",$data["notifen"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",$data["eventhdlen"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",$data["flapen"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",$data["failpreden"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",$data["perfdata"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",$data["retstatus"] == "t",array("type" => "chk"));
			$ioutput .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",$data["retnonstatus"] == "t",array("type" => "chk"));

			// Checks
			$output .= "<tr><td>".$this->loc->s("checkcmd")."</td><td>".$this->genCommandList("checkcmd",$data["checkcmd"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod",$data["checkperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval",$data["checkintval"],array("value" => 3, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval",$data["retcheckintval"],array("value" => 1, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck",$data["maxcheck"],array("value" => 10, "type" => "num"));

			// Notifications
			$output .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod",$data["notifperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",$data["srvoptc"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",$data["srvoptw"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",$data["srvoptu"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",$data["srvoptr"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",$data["srvoptf"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",$data["srvopts"],array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval",$data["notifintval"],array("value" => 0, "type" => "num"));
			// @ TODO support for contact not only contactlist
			$output .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg",$data["ctg"])."</td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=16","srvfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}

		private function showTimeperiodsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			
			/*
			 * Ajax new Timeperiod
			 * @TODO: support for multiple times in one day, and calendar days
			 */
			
			FS::$iMgr->setJSBuffer(1);
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=4",array("id" => "tpfrm"));
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
			$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=4","subpop #tpfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-timeperiod"),array("width" => 580));

			/*
			 * Timeperiod table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias").
						"</th><th>".$this->loc->s("Periods")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"tp_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=5&tp=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["alias"]."</td><td>";
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
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=6&tp=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-timeperiod")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editTimeperiod() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$tp = FS::$secMgr->checkAndSecuriseGetData("tp");
			if(!$tp) {
                                return FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","name = '".$tp."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$name = $data["name"];
			}
			else
                                return FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));

			$output = FS::$iMgr->h1("title-edit-timeperiod").FS::$iMgr->form("index.php?mod=".$this->mid."&act=4",array("id" => "tpfrm"));
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->hidden("name",$data["name"]).FS::$iMgr->hidden("edit",1);
			$output .= "<tr><td>".$this->loc->s("Name")."</td><td>".$data["name"]."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$data["alias"],array("length" => 120, "size" => 30));
			$output .= "<tr><td>".$this->loc->s("Monday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("mhs","mms",$data["mhs"],$data["mms"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("mhe","mme",$data["mhe"],$data["mme"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Tuesday")."</td><td>".$this->loc->s("From")." ".	
				FS::$iMgr->hourlist("tuhs","tums",$data["tuhs"],$data["tums"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("tuhe","tume",$data["tuhe"],$data["tume"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Wednesday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("whs","wms",$data["whs"],$data["wms"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("whe","wme",$data["whe"],$data["wme"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Thursday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("thhs","thms",$data["thhs"],$data["thms"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("thhe","thme",$data["thhe"],$data["thme"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Friday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("fhs","fms",$data["fhs"],$data["fms"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("fhe","fme",$data["fhe"],$data["fme"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Saturday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("sahs","sams",$data["sahs"],$data["sams"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("sahe","same",$data["sahe"],$data["same"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Sunday")."</td><td>".$this->loc->s("From")." ".
				FS::$iMgr->hourlist("suhs","sums",$data["suhs"],$data["sums"])."<br />".$this->loc->s("To")." ".FS::$iMgr->hourlist("suhe","sume",$data["suhe"],$data["sume"])."</td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=4","tpfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}

		private function showContactsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","","alias");
			if($tpexist) {
				/*
				 * Ajax new contact
				 */
				FS::$iMgr->setJSBuffer(1);
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=7",array("id" => "ctfrm"));
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
				$formoutput .= "<tr><td>".$this->loc->s("srvnotifcmd")."</td><td>".$this->genCommandList("srvnotifcmd","notify-service-by-email")."</td></tr>";
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifperiod")."</td><td>".$this->getTimePeriodList("hostnotifperiod")."</td></tr>";
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",true,array("type" => "chk"));
				$formoutput .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",true,array("type" => "chk"));
				$formoutput .= "<tr><td>".$this->loc->s("hostnotifcmd")."</td><td>".$this->genCommandList("hostnotifcmd","notify-host-by-email")."</td></tr>";
				$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
				$formoutput .= "</table></form>";
				$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=7","subpop #ctfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			}
			else
				$formoutput = FS::$iMgr->printError($this->loc->s("err-no-contact"));

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-contact"),array("width" => 600));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,template","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Email")."</th><th>Template ?</th><th></th></tr>";
				}
				$output .= "<tr id=\"ct_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=6&ct=".$data["name"]."\">".$data["name"]."</a></td><td>".$data["mail"]."</td>
					<td>".($data["template"] == "t" ? $this->loc->s("Yes") : $this->loc->s("No"))."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=9&ct=".$data["name"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-contact")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editContact() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$contact = FS::$secMgr->checkAndSecuriseGetData("ct");
			if(!$contact) {
                                return FS::$iMgr->printError($this->loc->s("err-no-contact"));
			}
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,srvperiod,srvcmd,hostperiod,hostcmd,hoptd,hoptu,hoptr,hoptf,hopts,soptc,soptw,soptu,soptr,soptf,sopts,template",
				"name = '".$contact."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$test = $data["name"];
			}
			else
                                return FS::$iMgr->printError($this->loc->s("err-no-contact"));

			$output = FS::$iMgr->h1("title-edit-contact").FS::$iMgr->form("index.php?mod=".$this->mid."&act=7",array("id" => "ctfrm"));
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",$data["template"] == "t",array("type" => "chk"));
			//$output .= template list
			$output .= FS::$iMgr->hidden("name",$data["name"]).FS::$iMgr->hidden("edit",1);
			$output .= "<tr><td>".$this->loc->s("Name")."</td><td>".$data["name"]."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("Email"),"mail",$data["mail"]);
			$output .= "<tr><td>".$this->loc->s("srvnotifperiod")."</td><td>".$this->getTimePeriodList("srvnotifperiod",$data["srvperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",$data["soptc"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",$data["soptw"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",$data["soptu"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",$data["soptr"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",$data["soptf"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",$data["sopts"] == "t",array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("srvnotifcmd")."</td><td>".$this->genCommandList("srvnotifcmd",$data["srvcmd"])."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("hostnotifperiod")."</td><td>".$this->getTimePeriodList("hostnotifperiod",$data["hostperiod"])."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",$data["hoptd"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",$data["hoptu"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",$data["hoptr"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",$data["hoptf"] == "t",array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",$data["hopts"] == "t",array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("hostnotifcmd")."</td><td>".$this->genCommandList("hostnotifcmd",$data["hostcmd"])."</td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=7","ctfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}
		private function showContactgroupsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
			}
			
			/*
			 * Ajax new contactgroup
			 */
			FS::$iMgr->setJSBuffer(1);
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=10",array("id" => "ctgfrm"));
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias","",array("length" => 60, "size" => 30));
			$countElmt = 0;
			$formoutput2 = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$formoutput2 .= FS::$iMgr->selElmt($data["name"],$data["name"]);
			}
			if($countElmt/4 < 4) $countElmt = 16;
			$formoutput .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".FS::$iMgr->select("cts[]","",NULL,true,array("size" => round($countElmt/4)));
			$formoutput .= $formoutput2;
			$formoutput .= "</select></td></tr>";
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";
			$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=10","subpop #ctgfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-contactgroup"),array("width" => 500));

			/*
			 * Contactgroup table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Members")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"ctg".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=7&cg=".$data["name"]."\">".
					$data["name"]."</a></td><td>".$data["alias"]."</td><td>";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","name = '".$data["name"]."'");
				$found2 = false;
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $data2["member"];
				}
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=12&ctg=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-contactgroup")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editContactgroup() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$cg = FS::$secMgr->checkAndSecuriseGetData("cg");
			if(!$cg)
				return FS::$iMgr->printError($this->loc->s("err-no-contactgroup"));

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","name = '".$cg."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$alias = $data["alias"];
			}
			else {
                                return FS::$iMgr->printError($this->loc->s("err-no-hostgroup"));
                        }
			$output = FS::$iMgr->h1("title-edit-contactgroup").FS::$iMgr->form("index.php?mod=".$this->mid."&act=10",array("id" => "ctgfrm"));
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>
				<tr><td>".$this->loc->s("Name")."</td><td>".$cg."</td></tr>";
			$output .= FS::$iMgr->hidden("name",$cg).FS::$iMgr->hidden("edit",1);
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias,array("length" => 60, "size" => 30));
			$tmpoutput = "";

			$contacts = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","member","name = '".$cg."'");
			while($data = FS::$dbMgr->Fetch($query))
				array_push($contacts,$data["member"]);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$tmpoutput .= FS::$iMgr->selElmt($data["name"],$data["name"],in_array($data["name"],$contacts));
			}
			if($countElmt/4 < 4) $countElmt = 16;
			$output .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".FS::$iMgr->select("cts[]","",NULL,true,array("size" => round($countElmt/4)));
			$output .= $tmpoutput;
			$output .= "</select></td></tr>";
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=10","ctgfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}

		private function showCommandTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
				case 4: $output .= Fs::$iMgr->printError($this->loc->s("err-binary-not-found")); break;
			}
			
			/*
			 * Ajax new command
			 */
			FS::$iMgr->setJSBuffer(1);
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1",array("id" => "cmdfrm"));
			$formoutput .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Name"),"name","",array("length" => 60, "size" => 30, "tooltip" => "tooltip-cmdname"));
			$formoutput .= FS::$iMgr->idxLine($this->loc->s("Command"),"cmd","",array("length" => 1024, "size" => 30, "tooltip" => "tooltip-cmd"));
			$formoutput .= FS::$iMgr->tableSubmit($this->loc->s("Add"));
			$formoutput .= "</table></form>";
			$formoutput .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=1","subpop #cmdfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));

			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-cmd"),array("width" => 500));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table><tr><th>".$this->loc->s("Name")."</th><th>".$this->loc->s("Command")."</th><th></th></tr>";
				}
				$output .= "<tr id=\"cmd_".preg_replace("#[. ]#","-",$data["name"])."\"><td><a href=\"index.php?mod=".$this->mid."&edit=8&cmd=".$data["name"]."\">".
					$data["name"]."</a></td><td>".substr($data["cmd"],0,100).(strlen($data["cmd"]) > 100 ? "..." : "")."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&cmd=".$data["name"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-command")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) $output .= "</table>";
			return $output;
		}

		private function editCmd() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
			// TODO: log
			if(!$cmdname) {
				return FS::$iMgr->printError($this->loc->s("err-no-cmd"));
			}

			$cmd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'");
			if(!$cmd) {
				return FS::$iMgr->printError($this->loc->s("err-cmd-doesnt-exist"));
			}
			$output = FS::$iMgr->h1("title-cmd-edit");
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1",array("id" => "cmdfrm")).
				FS::$iMgr->hidden("name",$cmdname).FS::$iMgr->hidden("edit",1).
				"<ul class=\"ulform\"><li><b>".$this->loc->s("Name").":</b> ";
			$cmd = htmlentities($cmd);
			$output .= $cmdname."</li><li><b>".$this->loc->s("Command").":</b> ".FS::$iMgr->input("cmd",$cmd,30,200)."</li><li>".
				FS::$iMgr->submit("",$this->loc->s("Save"))."</ul></form>";
			$output .= FS::$iMgr->callbackNotification("index.php?mod=".$this->mid."&act=1","cmdfrm",array("snotif" => $this->loc->s("Modification"), "lock" => true));
			return $output;
		}
		
		private function getTimePeriodList($name,$select = "") {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias","",array("order" => "alias"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["alias"],$data["name"],$select == $data["name"]);
			}
			$output .= "</select>";
			return $output;
		}

		private function genCommandList($name,$tocheck = NULL) {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["name"],$data["name"],$tocheck != NULL && $tocheck == $data["name"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genContactGroupsList($name,$select = "") {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["alias"].")",$data["name"],$select == $data["name"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genHostsList($name) {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function getHostOrGroupList($name,$multi,$selected = array(),$ignore = "",$grouponly = false) {
			$hostlist = array();
			if(!$grouponly) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'");
				while($data = FS::$dbMgr->Fetch($query))
					$hostlist[$this->loc->s("Host").": ".$data["name"]." (".$data["addr"].")"] = array(1,$data["name"]);
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name");
			while($data = FS::$dbMgr->Fetch($query)) {
				if($data["name"] != $ignore)
					$hostlist[($grouponly ? "" : $this->loc->s("Hostgroup").": ").$data["name"]] = array(2,$data["name"]);
			}

			ksort($hostlist);

			$tmpoutput = "";
			$countElmt = 0;
			foreach($hostlist as $host => $value) {
				$countElmt++;
				$tmpoutput .= FS::$iMgr->selElmt($host,(!$grouponly ? $value[0]."$" : "").$value[1],in_array((!$grouponly ? $value[0]."$" : "").$value[1],$selected));
			}
			if($countElmt/4 < 4) $countElmt = 16;
			$output = FS::$iMgr->select($name,"",NULL,$multi,array("size" => round($countElmt/4)));
			$output .= $tmpoutput;
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd");
			while($data = FS::$dbMgr->Fetch($query))
				fwrite($file,"define command {\n\tcommand_name\t".$data["name"]."\n\tcommand_line\t".$data["cmd"]."\n}\n\n");
			
			fclose($file);
			
			/*
			 *  Write contact & contactgroups
			 */
			 
			$file = fopen($path."contacts.cfg","w+");
			if(!$file)
				return false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,srvperiod,srvcmd,hostperiod,hostcmd,hoptd,hoptu,hoptr,hoptf,hopts,soptc,soptw,soptu,soptr,soptf,sopts","template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
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
			
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define contactgroup {\n\tcontactgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]."\n\tmembers\t");
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","member","name = '".$data["name"]."'");
				$found = false;
				while($data2 = FS::$dbMgr->Fetch($query2)) {
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume");
			while($data = FS::$dbMgr->Fetch($query)) {
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,
			perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,iconid","template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
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
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tparents\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["parent"]);
				}
				if($data["iconid"] && FS::$secMgr->isNumeric($data["iconid"])) {
					$iconpath = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_icons","path","id = '".$data["iconid"]."'");
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias");
			while($data = FS::$dbMgr->Fetch($query)) {
				fwrite($file,"define hostgroup {\n\thostgroup_name\t".$data["name"]."\n\talias\t".$data["alias"]);
				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '1'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					if(!$found) {
						$found = true;
						fwrite($file,"\n\tmembers\t");
					}
					else fwrite($file,",");
					fwrite($file,$data2["host"]);
					
				}
				$found = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."' AND hosttype = '2'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
			retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptf,srvopts,notifintval,ctg,srvoptr",
			"template = 'f'");
			while($data = FS::$dbMgr->Fetch($query)) {
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

		private function isForbidCmd($cmd) {
			if($cmd == "rm" || $cmd == "/bin/rm" || $cmd == "ls" || $cmd == "/bin/ls" || $cmd == "cp" || $cmd == "/bin/cp" || $cmd == "mv" || $cmd == "/bin/mv") 
				return true;

			return false;
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Add/Edit command
				case 1:
					if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$cmdname = FS::$secMgr->checkAndSecurisePostData("name");
					$cmd = FS::$secMgr->checkAndSecurisePostData("cmd");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					
					if(!$cmdname || !$cmd || !preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$cmdname) || $edit && $edit != 1) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=1");
						return;
					}

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_sommands","cmd","name = '".$cmdname."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-not-found");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=2");
							return;
						}
					}
					else if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-data-exist");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=3");
						return;
					}
					
					$tmpcmd = preg_replace("#\\\$USER1\\\$#","/usr/local/libexec/nagios/",$cmd);
					$tmpcmd = preg_split("#[ ]#",$tmpcmd);
					$out = "";
					exec("if [ -f ".$tmpcmd[0]." ] && [ -x ".$tmpcmd[0]." ]; then echo 0; else echo 1; fi;",$out);
					if(!is_array($out) || count($out) != 1 || $out[0] != 0 || $this->isForbidCmd($tmpcmd[0])) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-binary-not-found");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=4");
						return;
					} 

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");	
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd","'".$cmdname."','".$cmd."'");
					if(!$this->writeConfiguration()) {
						FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=8",true);
					return;
				// Remove command
				case 2:
					if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					// @TODO forbid remove when use (host + service)
					$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
					if(!$cmdname) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=1");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-data-not-exist");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=2");
						return;
					}
					
					// Forbid remove if command is used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","srvcmd = '".$cmdname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=5");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","hostcmd = '".$cmdname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=5");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#cmd_".preg_replace("#[. ]#","-",$cmdname)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=8");
					return;
				// Add/Edit timeperiod
				case 4:
					if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 
			
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
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
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
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
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}

					if(!$mhs && !$mms && !$tuhs && !$tums && !$whs && !$wms && !$thhs && !$thms && !$fhs && !$fms && !$sahs && !$sams && !$suhs && !$sums &&
						!$mhe && !$mme && !$tuhe && !$tume && !$whe && !$wme && !$thhe && !$thme && !$fhe && !$fme && !$sahe && !$same && !$suhe && !$sume) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}
							 

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=2");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=3");
							return;
						}
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume",
						"'".$name."','".$alias."','".$mhs."','".$mms."','".$tuhs."','".$tums."','".$whs."','".$wms."','".$thhs."','".$thms."','".$fhs."','".$fms."','".$sahs."','".$sams."','".$suhs."','".$sums.
						"','".$mhe."','".$mme."','".$tuhe."','".$tume."','".$whe."','".$wme."','".$thhe."','".$thme."','".$fhe."','".$fme."','".$sahe."','".$same."','".$suhe."','".$sume."'");
					if(!$this->writeConfiguration()) {
						FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=5",true);
					return;
				// Delete timeperiod
				case 6:
					if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$tpname = FS::$secMgr->checkAndSecuriseGetData("tp");
					if(!$tpname) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$tpname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=2");
						return;
					}
					
					// @ TODO forbid remove when used (service + host / groups ??)
					
					// Forbid remove if timeperiod is used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","srvperiod = '".$tpname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","hostperiod = '".$tpname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","checkperiod = '".$tpname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=4");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","notifperiod = '".$tpname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-binary-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=4");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$tpname."'");
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#tp_".preg_replace("#[. ]#","-",$tpname)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=5");
					return;
				// Add/Edit contact
				case 7:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->getPost("name","w");
					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					$srvnotifperiod = FS::$secMgr->getPost("srvnotifperiod","w");
					$srvnotifcmd = FS::$secMgr->checkAndSecurisePostData("srvnotifcmd");
					$hostnotifperiod = FS::$secMgr->getPost("hostnotifperiod","w");
					$hostnotifcmd = FS::$secMgr->checkAndSecurisePostData("hostnotifcmd");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if(!$name || !$mail || preg_match("#[ ]#",$name) || !$srvnotifperiod || !$srvnotifcmd || !$hostnotifperiod || !$hostnotifcmd) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
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

					if($edit) {
						// If contact doesn't exist
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=2");
							return;
						}
					}
					else {
						// If contact exist
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=3");
							return;
						}
					}

					// Timeperiods don't exist
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$srvnotifperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=4");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$hostnotifperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=4");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$srvnotifcmd."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=4");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$hostnotifcmd."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=4");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contacts","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,template,srvperiod,srvcmd,hostperiod,hostcmd,soptc,soptw,soptu,soptr,soptf,sopts,hoptd,hoptu,hoptr,hoptf,hopts",
						"'".$name."','".$mail."','".($istpl == "on" ? 1 : 0)."','".$srvnotifperiod."','".$srvnotifcmd."','".$hostnotifperiod."','".$hostnotifcmd."','".($srvoptc == "on" ? 1 : 0)."','".
						($srvoptw == "on" ? 1 : 0)."','".($srvoptu == "on" ? 1 : 0)."','".($srvoptr == "on" ? 1 : 0)."','".($srvoptf == "on" ? 1 : 0)."','".($srvopts == "on" ? 1 : 0)."','".
						($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".($hostopts == "on" ? 1 : 0)."'");

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=6",true);
					return;
				// Delete contact
				case 9:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$ctname = FS::$secMgr->checkAndSecuriseGetData("ct");
					if(!$ctname) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=1");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$ctname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=2");
						return;
					}
					
					// Forbid remove if in existing contact group
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name","member = '".$ctname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-contact-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=4");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contacts","name = '".$ctname."'");
					
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=6&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ct_".preg_replace("#[. ]#","-",$ctname)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=6");
					return;
				// Add/Edit contact group
				case 10:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->getPost("name","w");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$cts = FS::$secMgr->checkAndSecurisePostData("cts");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || !$alias || !$cts || $cts == "") {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
						return;
					}
					
					// ctg exists
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=2");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=3");
							return;
						}
					}

					// some members don't exist
					$count = count($cts);
					for($i=0;$i<$count;$i++) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$cts[$i]."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-bad-data");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
							return;
						}
					}

					if($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroups","name = '".$name."'");
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name = '".$name."'");
					}
					// Add it
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","'".$name."','".$alias."'");
					for($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","'".$name."','".$cts[$i]."'");
					}

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=7",true);
					return;
				// Delete contact group
				case 12:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					// @TODO forbid remove when used (service, service_group)
					$ctgname = FS::$secMgr->checkAndSecuriseGetData("ctg");
					if(!$ctgname) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$ctgname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
					FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=2");
						return;
					}

					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","contactgroup = '".$ctgname."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-ctg-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=4");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name = '".$ctgname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroups","name = '".$ctgname."'");

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=7&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ctg_".preg_replace("#[. ]#","-",$ctgname)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=7");
					return;
				// Add/Edit host
				case 13:
					if(!FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$dname = FS::$secMgr->checkAndSecurisePostData("dname");
					$parent = FS::$secMgr->checkAndSecurisePostData("parent");
					$hg = FS::$secMgr->checkAndSecurisePostData("hostgroups");
					$icon = FS::$secMgr->checkAndSecurisePostData("icon");
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					$checkcommand = FS::$secMgr->checkAndSecurisePostData("checkcommand");
					$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
					$notifperiod = FS::$secMgr->checkAndSecurisePostData("notifperiod");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					$ctg = FS::$secMgr->getPost("ctg","w");
					if(!$name || preg_match("#[ ]#",$name) || !$alias || !$dname || !$addr || !$checkcommand || !$checkperiod ||
						 !$notifperiod || !$ctg || $icon && !FS::$secMgr->isNumeric($icon) || $edit && $edit != 1) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
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
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					// Now verify datas
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
                                                        	FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=3");
                                                        return;
                                                }
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=3");
							return;
						}
					}
					
					if($parent && !in_array("none",$parent)) {
						$count = count($parent);
						for($i=0;$i<$count;$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$parent[$i]."'")) {
								if(FS::isAjaxCall())
									echo $this->loc->s("err-bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
								return;
							}
						}
					}
					
					if($hg && is_array($hg)) {
						$count = count($hg);
						for($i=0;$i<$count;$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$hg[$i]."'")) {
								if(FS::isAjaxCall())
									echo $this->loc->s("err-bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
								return;
							}
						}
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcommand."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$notifperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,
						failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid",
						"'".$name."','".$alias."','".$dname."','".$addr."','".$checkcommand."','".$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
						($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".$notifperiod."','".
						$notifintval."','".($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".
						($hostopts == "on" ? 1 : 0)."','".$ctg."','".($tpl == "on" ? 1 : 0)."','".($icon ? $icon : 0)."'");

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","name = '".$name."'");
					if($parent && !in_array("none",$parent)) {
						$count = count($parent);
						for($i=0;$i<$count;$i++)
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_host_parents","name,parent","'".$name."','".$parent[$i]."'");
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '1'");
					if($hg && is_array($hg)) {
						$count = count($hg);
						for($i=0;$i<$count;$i++)
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","'".$hg[$i]."','".$name."','1'");
					}
					
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=2",true);
					return;
				// Remove host
				case 15:
					if(!FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("host");
					if(!$name) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=1");
						return;
					}

					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","addr","name = '".$name."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=2");
						return;
					}

					// Remove host and links with parents and hostgroups
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","parent = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '1'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
					FS::$dbMgr->CommitTr();

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=2&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#h_".preg_replace("#[. ]#","-",$name)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=2");
					return;
				// Add/Edit service
				case 16:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = trim(FS::$secMgr->checkAndSecurisePostData("desc"));
					$host = FS::$secMgr->checkAndSecurisePostData("host");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					$checkcmd = FS::$secMgr->checkAndSecurisePostData("checkcmd");
					$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
					$notifperiod = FS::$secMgr->checkAndSecurisePostData("notifperiod");
					$ctg = FS::$secMgr->getPost("ctg","w");

					if(!$name || preg_match("#[\(]|[\)]|[\[]|[\]]#",$name) || !$host || !$checkcmd || !$checkperiod || !$notifperiod || !$ctg) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=2");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=3");
							return;
						}
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
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					$mt = preg_split("#[$]#",$host);
					if(count($mt) != 2 || ($mt[0] != 1 && $mt[0] != 2)) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcmd."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$notifperiod."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if($mt[0] == 1 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}
					if($mt[0] == 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$mt[1]."'")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_services","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
						retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptr,srvoptf,srvopts,notifintval,ctg,template",
						"'".$name."','".$mt[1]."','".$mt[0]."','".($actcheck == "on" ? 1 : 0)."','".($pascheck == "on" ? 1 : 0)."','".($parcheck == "on" ? 1 : 0)."','".($obsess == "on" ? 1 : 0).
						"','".($freshness == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
						($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".$checkcmd."','".
						$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".$notifperiod."','".($srvoptc == "on" ? 1 : 0)."','".($srvoptw == "on" ? 1 : 0)."','".
						($srvoptu == "on" ? 1 : 0)."','".($srvoptr == "on" ? 1 : 0)."','".($srvoptf == "on" ? 1 : 0)."','".($srvopts == "on" ? 1 : 0)."','".$notifintval."','".$ctg."','".
						($tpl == "on" ? 1 : 0)."'");

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// remove service
				case 18:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("srv");
					if(!$name) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=1");
						return;
					}
					
					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","name = '".$name."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=2");
						return;
					}
					
					// membertype 1 = service, 2 = servicegroup
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_servicegroups","member = '".$name."' AND membertype = 1");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_services","name = '".$name."'");
					FS::$dbMgr->CommitTr();
					
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=4&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#srv_".preg_replace("#[. ]#","-",$name)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=4");
					return;
				// Add/Edit hostgroup
				case 19:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$members = FS::$secMgr->checkAndSecurisePostData("members");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}
					
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-not-exist");
							else
                                                        	FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
                                                        return;
                                                }
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							if(FS::isAjaxCall())
								echo $this->loc->s("err-data-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=3");
							return;
						}
					}
					
					if($members) {
						$count = count($members);
						for($i=0;$i<$count;$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if(count($mt) != 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
								if(FS::isAjaxCall())
									echo $this->loc->s("err-bad-data");
								else
									FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
								return;
							}
						}
						if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name = '".$name."'");
						for($i=0;$i<$count;$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if(count($mt) == 2 && ($mt[0] == 1 || $mt[0] == 2))
								FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","'".$name."','".$mt[1]."','".$mt[0]."'");
						}
					}
					else {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=1");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","'".$name."','".$alias."'");
					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							echo $this->loc->s("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					return;
				// remove hostgroup
				case 21:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-no-right");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=99");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("hg");
					if(!$name) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=1");
						return;
					}

					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
						return;
					}

					// Used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","host = '".$name."' AND hosttype = '2'")) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-hg-used");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=2");
						return;
					}

					// Delete hostgroup and members
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '2'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->CommitTr();

					if(!$this->writeConfiguration()) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-fail-writecfg");
						else
							FS::$iMgr->redir("mod=".$this->mid."&sh=3&err=5");
						return;
					}
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#hg_".preg_replace("#[. ]#","-",$name)."');");
					else
						FS::$iMgr->redir("mod=".$this->mid."&sh=3");
					return;
			}
		}
	};
?>
