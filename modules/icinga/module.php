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

	require_once(dirname(__FILE__)."/icingaBroker.api.php");

	final class iIcinga extends FSModule {
		function __construct($locales) {
			parent::__construct($locales);
			$this->icingaAPI = new icingaBroker();
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-icinga"));
			$output = $this->showTabPanel();
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
				$output .= FS::$iMgr->opendiv(1,$this->loc->s("new-host"));
				return $output;
			}
			
			$ctexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","name","");
			if(!$ctexist) {
				$output .= FS::$iMgr->opendiv(2,$this->loc->s("new-host"));
				return $output;
			}

			/*
			 * Ajax new host
			 */
			$output .= FS::$iMgr->opendiv(3,$this->loc->s("new-host"));

			/*
			 * Host table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,addr,template","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"thostList\" width=\"80%\"><thead><tr><th class=\"headerSortDown\" width=\"20%\">".$this->loc->s("Name")."</th><th width=\"20%\">".
						$this->loc->s("Alias")."</th><th width=\"20%\">".$this->loc->s("Address")."</th><th width=\"15%\">".$this->loc->s("Template").
						"</th><th width=\"20%\">".$this->loc->s("Parent")."</th><th></th></tr></thead>";
				}

				$parentlist = array();
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					array_push($parentlist,$data2["parent"]);
				}
				
				$output .= "<tr id=\"h_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_host_write"))
					$output .= FS::$iMgr->opendiv(10,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["alias"]."</td><td>".$data["addr"]."</td><td>";
				if($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td>";
				$found2 = false;
				for($i=0;$i<count($parentlist);$i++) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $parentlist[$i];
				}
				$output .="</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=15&host=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-host")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("thostList");
			}
			return $output;
		}

		private function showHostForm($name = "") {
			$dname = ""; $icon = ""; $alias = ""; $address = ""; $parentlist = array();
			$checkcmd = "check-host-alive"; $checkperiod = ""; $checkintval = 3; $retcheckintval = 1; $maxcheck = 10;
			$eventhdlen = true; $flapen = true; $failpreden = true; $perfdata = true; $retstatus = true; $retnonstatus = true;
			$notifen = true; $notifperiod = ""; $notifintval = 0; $ctg = "";
			$hostoptd = true; $hostoptu = true; $hostoptr = true; $hostoptf = true; $hostopts = true;
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","dname,alias,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid","name = '".$name."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$dname = $data["dname"];
					$alias = $data["alias"];
					$addr = $data["addr"];
					$icon = $data["iconid"];
					$checkcmd = $data["alivecommand"];
					$checkperiod = $data["checkperiod"];
					$checkintval = $data["checkinterval"];
					$retcheckintval = $data["retrycheckinterval"];
					$maxcheck = $data["maxcheck"];
					$eventhdlen = ($data["eventhdlen"] == 't');	
					$flapen = ($data["flapen"] == 't');	
					$failpreden = ($data["failpreden"] == 't');	
					$perfdata = ($data["perfdata"] == 't');	
					$retstatus = ($data["retstatus"] == 't');	
					$retnonstatus = ($data["retnonstatus"] == 't');	
					$notifen = ($data["notifen"] == 't');	
					$notifperiod = $data["notifperiod"];
					$notifintval = $data["notifintval"];
					$ctg = $data["contactgroup"];	
					$hostoptd = $data["hostoptd"];
					$hostoptu = $data["hostoptu"];
					$hostoptr = $data["hostoptr"];
					$hostoptf = $data["hostoptf"];
					$hostopts = $data["hostopts"];
				}
				$parentlist = array();
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$name."'");
				while($data = FS::$dbMgr->Fetch($query)) {
					array_push($parentlist,$data["parent"]);
				}
			}

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=13");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$output .= template list
			$output .= FS::$iMgr->idxIdLine("Name","name",$name);
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias);
			$output .= FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname",$dname);
			$output .= "<tr><td>".$this->loc->s("Icon")."</td><td>";
			$output .= FS::$iMgr->select("icon");
			$output .= FS::$iMgr->selElmt("Aucun","",($icon == ""));

			$output .= FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_icons","name","id",array($icon));

			$output .= "</select></td></tr>";
			$output .= "<tr><td>".$this->loc->s("Parent")."</td><td>";

			$output2 = FS::$iMgr->selElmt($this->loc->s("None"),"none",(count($parentlist) == 0));
			$countElmt = 0;

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$output2 .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"],in_array($data["name"],$parentlist));
			}

			if($countElmt/4 < 4) $countElmt = 16;
			$output .= FS::$iMgr->select("parent","",NULL,true,array("size" => round($countElmt/4)));
			$output .= $output2;
			$output .= "</select></td></tr>";

			$output .= FS::$iMgr->idxLine($this->loc->s("Address"),"addr",$addr);

			$hglist = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name","host = '".$name."' AND hosttype = '1'");
				while($data = FS::$dbMgr->Fetch($query))
					array_push($hglist,$data["name"]);
			}

			$output .= "<tr><td>".$this->loc->s("Hostgroups")."</td><td>".$this->getHostOrGroupList("hostgroups",false,$hglist,"",true)."</td></tr>";

			// Checks
			$output .= "<tr><td>".$this->loc->s("alivecommand")."</td><td>".$this->genCommandList("checkcommand",$checkcmd)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod",$checkperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => $checkintval, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => $retcheckintval, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => $maxcheck, "type" => "num"));

			// Global
			$output .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",$eventhdlen,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",$flapen,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",$failpreden,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",$perfdata,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",$retstatus,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",$retnonstatus,array("type" => "chk"));

			// Notifications
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",$notifen,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod",$notifperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => $notifintval, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",$hostoptd,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",$hostoptu,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",$hostoptr,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",$hostoptf,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",$hostopts,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg",$ctg)."</td></tr>";
			// icon image
			// statusmap image
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showHostgroupsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-data-not-exist")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-data-exist")); break;
			}
			if(FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
				$output .= FS::$iMgr->opendiv(4,$this->loc->s("new-hostgroup"));

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"thgList\" width=\"80%\"><thead><tr><th class=\"headerSortDown\" width=\"10%\">".$this->loc->s("Name").
						"</th><th width=\"10%\">".$this->loc->s("Alias")."</th><th width=\"80%\">".$this->loc->s("Members")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"hg_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
					$output .= FS::$iMgr->opendiv(11,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["alias"]."</td><td>";
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
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("thgList");
			}
			return $output;
		}
		
		private function showHostgroupForm($name = "") {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=19");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			// Global
			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30));

			$alias = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","alias","name = '".$name."'");
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias,array("length" => 60, "size" => 30));

			$hostlist = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","name = '".$name."'");
				while($data = FS::$dbMgr->Fetch($query))
					array_push($hostlist,$data["hosttype"]."$".$data["host"]);
			}	

			$output .= "<tr><td>".$this->loc->s("Members")."</td><td>".$this->getHostOrGroupList("members",true,$hostlist,$name)."</td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showServicesTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));

			$output = "";

			if(FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
				$output .= FS::$iMgr->opendiv(5,$this->loc->s("new-service"));

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,template","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"tsrvList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Host").
						"</th><th>".$this->loc->s("Hosttype")."</th><th>".$this->loc->s("Template")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"srv_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_srv_write"))
					$output .= FS::$iMgr->opendiv(12,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["host"]."</td><td>";
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
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tsrvList");
			}
			return $output;
		}

		private function showServiceForm($name = "") {
			$hosttype = ""; $host = "";
			$actcheck = true; $pascheck = true; $parcheck = true; $obsess = true; $freshness = false;
			$notifen = true; $eventhdlen = true; $flapen = true; $failpreden = true; $perfdata = true; $retstatus = true; $retnonstatus = true;
			$checkcmd = ""; $checkperiod = ""; $checkintval = 3; $retcheckintval = 1; $maxcheck = 10;
			$notifperiod = ""; $srvoptc = true; $srvoptw = true; $srvoptu = true; $srvoptr = true; $srvoptf = true; $srvopts = true; 
			$notifintval = 0; $ctg = "";
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","host,hosttype,ctg,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
					retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,notifperiod,srvoptc,srvoptw,srvoptu,srvoptr,srvoptf,srvopts,notifintval,ctg,template",
					"name = '".$name."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$host = $data["host"];
					$hosttype = $data["hosttype"];
					$actcheck = ($data["actcheck"] == 't');
					$pascheck = ($data["pascheck"] == 't');
					$parcheck = ($data["parcheck"] == 't');
					$obsess = ($data["obsess"] == 't');
					$freshness = ($data["freshness"] == 't');
					$notifen = ($data["notifen"] == 't');
					$eventhdlen = ($data["eventhdlen"] == 't');
					$flapen = ($data["flapen"] == 't');
					$failpreden = ($data["failpreden"] == 't');
					$perfdata = ($data["perfdata"] == 't');
					$retstatus = ($data["retstatus"] == 't');
					$retnonstatus = ($data["retnonstatus"] == 't');
					$checkcmd = $data["checkcmd"];
					$checkperiod = $data["checkperiod"];
					$checkintval = $data["checkintval"];
					$retcheckintval = $data["retcheckintval"];
					$maxcheck = $data["maxcheck"];
					$notifperiod = $data["notifperiod"];
					$notifintval = $data["notifintval"];
					$srvoptc = ($data["srvoptc"] == 't');
					$srvoptw = ($data["srvoptw"] == 't');
					$srvoptu = ($data["srvoptu"] == 't');
					$srvoptr = ($data["srvoptr"] == 't');
					$srvoptf = ($data["srvoptf"] == 't');
					$srvopts = ($data["srvopts"] == 't');
					$ctg = $data["ctg"];
					
				}
			}
			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=16");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$output .= template list

			// Global
			$output .= FS::$iMgr->idxIdLine("Description","desc",$name,array("length" => 120, "size" => 30));
			// @ TODO support hostlist
			$output .= "<tr><td>".$this->loc->s("Host")."</td><td>".$this->getHostOrGroupList("host",false,($hosttype && $host ? array($hosttype."$".$host) : array()))."</td></tr>";

			$output .= FS::$iMgr->idxLine($this->loc->s("active-check-en"),"actcheck",$actcheck,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("passive-check-en"),"pascheck",$pascheck,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("parallel-check"),"parcheck",$parcheck,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("obs-over-srv"),"obsess",$obsess,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("check-freshness"),"freshness",$freshness,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",$notifen,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",$eventhdlen,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",$flapen,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",$failpreden,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",$perfdata,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",$retstatus,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",$retnonstatus,array("type" => "chk"));

			// Checks
			$output .= "<tr><td>".$this->loc->s("checkcmd")."</td><td>".$this->genCommandList("checkcmd",$checkcmd)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->getTimePeriodList("checkperiod",$checkperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => $checkintval, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => $retcheckintval, "type" => "num"));
			$output .= FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => $maxcheck, "type" => "num"));

			// Notifications
			$output .= "<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->getTimePeriodList("notifperiod",$notifperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",$srvoptc,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",$srvoptw,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",true,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",true,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",true,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",true,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => $notifintval, "type" => "num"));
			// @ TODO support for contact not only contactlist
			$output .= "<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->genContactGroupsList("ctg",$ctg)."</td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
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
			
			if(FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
				$output .= FS::$iMgr->opendiv(6,$this->loc->s("new-timeperiod"));

			/*
			 * Timeperiod table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"ttpList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias").
						"</th><th>".$this->loc->s("Periods")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"tp_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
					$output .= FS::$iMgr->opendiv(13,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["alias"]."</td><td>";
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
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("ttpList");
			}
			return $output;
		}

		private function showTimeperiodForm($name = "") {
			$alias = "";
			$mhs = 0;  $mms = 0;  $mhe = 0;  $mme = 0;
			$tuhs = 0; $tums = 0; $tuhe = 0; $tume = 0;
			$whs = 0;  $wms = 0;  $whe = 0;  $wme = 0;
			$thhs = 0; $thms = 0; $thhe = 0; $thme = 0;
			$fhs = 0;  $fms = 0;  $fhe = 0;  $fme = 0;
			$sahs = 0; $sams = 0; $sahe = 0; $same = 0;
			$suhs = 0; $sums = 0; $suhe = 0; $sume = 0;
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","name = '".$name."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$alias = $data["alias"];
					$mhs = $data["mhs"]; $mms = $data["mms"]; $mhe = $data["mhe"]; $mme = $data["mme"];
					$tuhs = $data["tuhs"]; $tums = $data["tums"]; $tuhe = $data["tuhe"]; $tume = $data["tume"];
					$whs = $data["whs"]; $wms = $data["wms"]; $whe = $data["whe"]; $wme = $data["wme"];
					$thhs = $data["thhs"]; $thms = $data["thms"]; $thhe = $data["thhe"]; $thme = $data["thme"];
					$fhs = $data["fhs"]; $fms = $data["fms"]; $fhe = $data["fhe"]; $fme = $data["fme"];
					$sahs = $data["sahs"]; $sams = $data["sams"]; $sahe = $data["sahe"]; $same = $data["same"];
					$suhs = $data["suhs"]; $sums = $data["sums"]; $suhe = $data["suhe"]; $sume = $data["sume"];
				}
			}

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=4");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";

			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30));

			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias,array("length" => 120, "size" => 30));
			$output .= "<tr><td>".$this->loc->s("Monday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("mhs","mms",$mhs,$mms)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("mhe","mme",$mhe,$mme)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Tuesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("tuhs","tums",$tuhs,$tums)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("tuhe","tume",$tuhe,$tume)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Wednesday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("whs","wms",$whs,$wms)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("whe","wme",$whe,$wme)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Thursday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("thhs","thms",$thhs,$thms)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("thhe","thme",$thhe,$thme)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Friday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("fhs","fms",$fhs,$fms)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("fhe","fme",$fhe,$fme)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Saturday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("sahs","sams",$sahs,$sams)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("sahe","same",$sahe,$same)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("Sunday")."</td><td>".$this->loc->s("From")." ".FS::$iMgr->hourlist("suhs","sums",$suhs,$sums)."<br />".
				$this->loc->s("To")." ".FS::$iMgr->hourlist("suhe","sume",$suhe,$sume)."</td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showContactsTab() {
			if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) 
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			$output = "";

			if(FS::$sessMgr->hasRight("mrule_icinga_ct_write"))
				$output .= FS::$iMgr->opendiv(7,$this->loc->s("new-contact"));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,template","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"tctList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Email")."</th><th>Template ?</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"ct_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_ct_write"))
					$output .= FS::$iMgr->opendiv(14,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["mail"]."</td>
					<td>".($data["template"] == "t" ? $this->loc->s("Yes") : $this->loc->s("No"))."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=9&ct=".$data["name"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-contact")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tctList");
			}
			return $output;
		}

		private function showContactForm($name = "") {
			$mail = ""; $template = false;
			$srvnotifperiod = ""; $srvnotifcmd = "notify-service-by-email"; $srvoptc = true; $srvoptw = true; $srvoptu = true; $srvoptr = true; $srvoptf = true; $srvopts = true;
			$hostnotifperiod = ""; $hostnotifcmd = "notify-host-by-email"; $hostoptd = true; $hostoptu = true; $hostoptr = true; $hostoptf = true; $hostopts = true;
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","mail,template,srvperiod,srvcmd,hostperiod,hostcmd,hoptd,hoptu,hoptr,hoptf,hopts,soptc,soptw,soptu,soptr,soptf,sopts,template",
					"name = '".$name."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$mail = $data["mail"];
					$template = $data["template"] == 't';
					$srvnotifperiod = $data["srvperiod"];
					$srvnotifcmd = $data["srvcmd"];
					$srvoptc = ($data["soptc"] == 't');
					$srvoptw = ($data["soptw"] == 't');
					$srvoptu = ($data["soptu"] == 't');
					$srvoptr = ($data["soptr"] == 't');
					$srvoptf = ($data["soptf"] == 't');
					$srvopts = ($data["sopts"] == 't');
					$hostnotifperiod = $data["hostperiod"];
					$hostnotifcmd = $data["hostcmd"];
					$hostoptd = ($data["hoptd"] == 't');
					$hostoptu = ($data["hoptu"] == 't');
					$hostoptr = ($data["hoptr"] == 't');
					$hostoptf = ($data["hoptf"] == 't');
					$hostopts = ($data["hopts"] == 't');
				}
			}

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=7");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",$template,array("type" => "chk"));
			//$output .= template list
			$output .= FS::$iMgr->idxIdLine("Name","name",$name);
			$output .= FS::$iMgr->idxLine($this->loc->s("Email"),"mail",$mail);
			$output .= "<tr><td>".$this->loc->s("srvnotifperiod")."</td><td>".$this->getTimePeriodList("srvnotifperiod",$srvnotifperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptcrit"),"srvoptc",$srvoptc,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptwarn"),"srvoptw",$srvoptw,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptunreach"),"srvoptu",$srvoptu,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptrec"),"srvoptr",$srvoptr,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptflap"),"srvoptf",$srvoptf,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("srvoptsched"),"srvopts",$srvopts,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("srvnotifcmd")."</td><td>".$this->genCommandList("srvnotifcmd",$srvnotifcmd)."</td></tr>";
			$output .= "<tr><td>".$this->loc->s("hostnotifperiod")."</td><td>".$this->getTimePeriodList("hostnotifperiod",$hostnotifperiod)."</td></tr>";
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",$hostoptd,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",$hostoptu,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",$hostoptr,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",$hostoptf,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",$hostopts,array("type" => "chk"));
			$output .= "<tr><td>".$this->loc->s("hostnotifcmd")."</td><td>".$this->genCommandList("hostnotifcmd",$hostnotifcmd)."</td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
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

			if(FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
				$output .= FS::$iMgr->opendiv(8,$this->loc->s("new-contactgroup"));

			/*
			 * Contactgroup table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"tctgList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Members")."</th><th></th></tr></thead>";
				}

				$contacts = array();
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","name = '".$data["name"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					array_push($contacts,$data2["member"]);
				}

				$output .= "<tr id=\"ctg_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";
				if(FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
					$output .= FS::$iMgr->opendiv(15,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];
					
				$output .= "</td><td>".$data["alias"]."</td><td>";

				$found2 = false;
				for($i=0;$i<count($contacts);$i++) {
					if($found2) $output .= ", ";
					else $found2 = true;
					$output .= $contacts[$i];
				}
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=12&ctg=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-contactgroup")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tctgList");
			}
			return $output;
		}

		private function showContactgroupForm($name = "") {
			$alias = "";
			$contacts = array();
			if($name) {
				$alias = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","name = '".$name."'");
                        	$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","name = '".$data["name"]."'");
                        	while($data = FS::$dbMgr->Fetch($query2)) {
                	                array_push($contacts,$data2["member"]);
             	        	}
			}
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=10");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30));
			$output .= FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$alias,array("length" => 60, "size" => 30));
			$countElmt = 0;
			$output2 = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$output2 .= FS::$iMgr->selElmt($data["name"],$data["name"],in_array($data["name"],$contacts));
			}
			if($countElmt/4 < 4) $countElmt = 16;
			$output .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".FS::$iMgr->select("cts","",NULL,true,array("size" => round($countElmt/4)));
			$output .= $output2;
			$output .= "</select></td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
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
			$output .= FS::$iMgr->opendiv(9,$this->loc->s("new-cmd"));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd","",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = true;
					$output .= "<table id=\"tcmdList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Command")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"cmd_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_cmd_write"))
					$output .= FS::$iMgr->opendiv(16,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".substr($data["cmd"],0,100).(strlen($data["cmd"]) > 100 ? "..." : "")."</td>";

				if(FS::$sessMgr->hasRight("mrule_icinga_cmd_write"))
					$output .= "<td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&cmd=".$data["name"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-command")."'".$data["name"]."' ?","Confirm","Cancel")))."</td>";

				$output .= "</tr>";
			}
			if($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tcmdList");
			}
			return $output;
		}

		private function showCommandForm($name="") {
			$value = "";
			if($name) {
				$value = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$name."'");
			}
	
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30, "tooltip" => "tooltip-cmdname"));
			$output .= FS::$iMgr->idxLine($this->loc->s("Command"),"cmd",$value,array("length" => 1024, "size" => 30, "tooltip" => "tooltip-cmd"));
			$output .= FS::$iMgr->aeTableSubmit($name == "");

			return $output;
		}

		private function getTimePeriodList($name,$select = "") {
			$output = FS::$iMgr->select($name);
			$output .= FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name",array($select),array("order" => "alias"));
			$output .= "</select>";
			return $output;
		}

		private function genCommandList($name,$tocheck = NULL) {
			$output = FS::$iMgr->select($name);
			$output .= FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_commands","name","name",array($tocheck),array("order" => "name"));
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

		private function isForbidCmd($cmd) {
			if($cmd == "rm" || $cmd == "/bin/rm" || $cmd == "ls" || $cmd == "/bin/ls" || $cmd == "cp" || $cmd == "/bin/cp" || $cmd == "mv" || $cmd == "/bin/mv") 
				return true;

			return false;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return FS::$iMgr->printError($this->loc->s("err-no-timeperiod"));
				case 2: return FS::$iMgr->printError($this->loc->s("err-no-contactgroups"));
				case 3: return $this->showHostForm();
				case 4:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
						return $this->loc->s("err-no-rights");

					$hostexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","");
					if(!$hostexist)
						return FS::$iMgr->printError($this->loc->s("err-no-hosts"));
					return $this->showHostgroupForm();
				case 5:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
						return $this->loc->s("err-no-rights");
						
					$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","");
					if($tpexist)
						return $this->showServiceForm();
					return FS::$iMgr->printError($this->loc->s("err-no-service"));
				case 6:
					if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
						return $this->loc->s("err-no-rights");

					return $this->showTimeperiodForm();
				case 7:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write"))
						return $this->loc->s("err-no-rights");

					$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","","alias");
					if($tpexist)
						return $this->showContactForm();	
					else
						return FS::$iMgr->printError($this->loc->s("err-no-contact"));
				case 8:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
						return $this->loc->s("err-no-rights");

					return $this->showContactgroupForm();
				case 9: return $this->showCommandForm();
				case 10:
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					return $this->showHostForm($name);
				case 11:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showHostgroupForm($name);
				case 12:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showServiceForm($name);
				case 13:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) 
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showTimeperiodForm($name);
				case 14:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write"))
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showContactForm($name);
				case 15:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showContactgroupForm($name);
				case 16:
					if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write"))
						return $this->loc->s("err-no-rights");

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if(!$name)
						return $this->loc->s("err-bad-datas");

					return $this->showCommandForm($name);	
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Add/Edit command
				case 1:
					if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 

					$cmdname = FS::$secMgr->checkAndSecurisePostData("name");
					$cmd = FS::$secMgr->checkAndSecurisePostData("cmd");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					
					if(!$cmdname || !$cmd || !preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$cmdname) || $edit && $edit != 1) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
							echo $this->loc->s("err-not-found");
							return;
						}
					}
					else if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						echo $this->loc->s("err-data-exist");
						return;
					}
					
					$tmpcmd = preg_replace("#\\\$USER1\\\$#","/usr/local/libexec/nagios/",$cmd);
					$tmpcmd = preg_split("#[ ]#",$tmpcmd);
					$out = "";
					exec("if [ -f ".$tmpcmd[0]." ] && [ -x ".$tmpcmd[0]." ]; then echo 0; else echo 1; fi;",$out);
					if(!is_array($out) || count($out) != 1 || $out[0] != 0 || $this->isForbidCmd($tmpcmd[0])) {
						echo $this->loc->s("err-binary-not-found");
						return;
					} 

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");	
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd","'".$cmdname."','".$cmd."'");
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->redir("mod=".$this->mid."&sh=8&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=8",true);
					return;
				// Remove command
				case 2:
					if(!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					// @TODO forbid remove when use (host + service)
					$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
					if(!$cmdname) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						FS::$iMgr->ajaxEcho("err-data-not-exist");
						return;
					}
					
					// Forbid remove if command is used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","srvcmd = '".$cmdname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","hostcmd = '".$cmdname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#cmd_".preg_replace("#[. ]#","-",$cmdname)."');");
					return;
				// Add/Edit timeperiod
				case 4:
					if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 
			
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						echo $this->loc->s("err-bad-data");
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
						echo $this->loc->s("err-bad-data");
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
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!$mhs && !$mms && !$tuhs && !$tums && !$whs && !$wms && !$thhs && !$thms && !$fhs && !$fms && !$sahs && !$sams && !$suhs && !$sums &&
						!$mhe && !$mme && !$tuhe && !$tume && !$whe && !$wme && !$thhe && !$thme && !$fhe && !$fme && !$sahe && !$same && !$suhe && !$sume) {
						echo $this->loc->s("err-bad-data");
						return;
					}
							 

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume",
						"'".$name."','".$alias."','".$mhs."','".$mms."','".$tuhs."','".$tums."','".$whs."','".$wms."','".$thhs."','".$thms."','".$fhs."','".$fms."','".$sahs."','".$sams."','".$suhs."','".$sums.
						"','".$mhe."','".$mme."','".$tuhe."','".$tume."','".$whe."','".$wme."','".$thhe."','".$thme."','".$fhe."','".$fme."','".$sahe."','".$same."','".$suhe."','".$sume."'");
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->redir("mod=".$this->mid."&sh=5&err=5");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=5",true);
					return;
				// Delete timeperiod
				case 6:
					if(!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$tpname = FS::$secMgr->checkAndSecuriseGetData("tp");
					if(!$tpname) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$tpname."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// @ TODO forbid remove when used (service + host / groups ??)
					
					// Forbid remove if timeperiod is used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","srvperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","hostperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","checkperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}
					
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","notifperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEcho("err-binary-used");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$tpname."'");
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#tp_".preg_replace("#[. ]#","-",$tpname)."');");
					return;
				// Add/Edit contact
				case 7:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						echo $this->loc->s("err-no-right");
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
						echo $this->loc->s("err-bad-data");
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
							echo $this->loc->s("err-data-not-exist");
							return;
						}
					}
					else {
						// If contact exist
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}

					// Timeperiods don't exist
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$srvnotifperiod."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$hostnotifperiod."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$srvnotifcmd."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$hostnotifcmd."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contacts","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,template,srvperiod,srvcmd,hostperiod,hostcmd,soptc,soptw,soptu,soptr,soptf,sopts,hoptd,hoptu,hoptr,hoptf,hopts",
						"'".$name."','".$mail."','".($istpl == "on" ? 1 : 0)."','".$srvnotifperiod."','".$srvnotifcmd."','".$hostnotifperiod."','".$hostnotifcmd."','".($srvoptc == "on" ? 1 : 0)."','".
						($srvoptw == "on" ? 1 : 0)."','".($srvoptu == "on" ? 1 : 0)."','".($srvoptr == "on" ? 1 : 0)."','".($srvoptf == "on" ? 1 : 0)."','".($srvopts == "on" ? 1 : 0)."','".
						($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".($hostopts == "on" ? 1 : 0)."'");

					if(!$this->icingaAPI->writeConfiguration()) {
						echo $this->loc->s("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=6",true);
					return;
				// Delete contact
				case 9:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$ctname = FS::$secMgr->checkAndSecuriseGetData("ct");
					if(!$ctname) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$ctname."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// Forbid remove if in existing contact group
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name","member = '".$ctname."'")) {
						FS::$iMgr->ajaxEcho("err-contact-used");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contacts","name = '".$ctname."'");
					
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ct_".preg_replace("#[. ]#","-",$ctname)."');");
					return;
				// Add/Edit contact group
				case 10:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 

					$name = FS::$secMgr->getPost("name","w");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$cts = FS::$secMgr->checkAndSecurisePostData("cts");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$name || !$alias || !$cts || $cts == "") {
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					// ctg exists
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}

					// some members don't exist
					$count = count($cts);
					for($i=0;$i<$count;$i++) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$cts[$i]."'")) {
							echo $this->loc->s("err-bad-data");
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

					if(!$this->icingaAPI->writeConfiguration()) {
						echo $this->loc->s("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=7",true);
					return;
				// Delete contact group
				case 12:
					if(!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					// @TODO forbid remove when used (service, service_group)
					$ctgname = FS::$secMgr->checkAndSecuriseGetData("ctg");
					if(!$ctgname) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$ctgname."'")) {
							FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","contactgroup = '".$ctgname."'")) {
						FS::$iMgr->ajaxEcho("err-ctg-used");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name = '".$ctgname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroups","name = '".$ctgname."'");

					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ctg_".preg_replace("#[. ]#","-",$ctgname)."');");
					return;
				// Add/Edit host
				case 13:
					if(!FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
						echo $this->loc->s("err-no-right");
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
						echo $this->loc->s("err-bad-data");
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
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					// Now verify datas
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
                                                        return;
                                                }
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}
					
					if($parent && !in_array("none",$parent)) {
						$count = count($parent);
						for($i=0;$i<$count;$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$parent[$i]."'")) {
								echo $this->loc->s("err-bad-data");
								return;
							}
						}
					}
					
					if($hg && is_array($hg)) {
						$count = count($hg);
						for($i=0;$i<$count;$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$hg[$i]."'")) {
								echo $this->loc->s("err-bad-data");
								return;
							}
						}
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcommand."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$notifperiod."'")) {
						echo $this->loc->s("err-bad-data");
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
					
					if(!$this->icingaAPI->writeConfiguration()) {
						echo $this->loc->s("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=2",true);
					return;
				// Remove host
				case 15:
					if(!FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("host");
					if(!$name) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","addr","name = '".$name."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Remove host and links with parents and hostgroups
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","parent = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '1'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
					FS::$dbMgr->CommitTr();

					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#h_".preg_replace("#[. ]#","-",$name)."');");
					return;
				// Add/Edit service
				case 16:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						echo $this->loc->s("err-no-right");
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
						echo $this->loc->s("err-bad-data");
						return;
					}

					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
							return;
						}
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
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
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					$mt = preg_split("#[$]#",$host);
					if(count($mt) != 2 || ($mt[0] != 1 && $mt[0] != 2)) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcmd."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$notifperiod."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if($mt[0] == 1 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
						echo $this->loc->s("err-bad-data");
						return;
					}
					if($mt[0] == 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$mt[1]."'")) {
						echo $this->loc->s("err-bad-data");
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

					if(!$this->icingaAPI->writeConfiguration()) {
						echo $this->loc->s("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
					return;
				// remove service
				case 18:
					if(!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("srv");
					if(!$name) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","name = '".$name."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// membertype 1 = service, 2 = servicegroup
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_servicegroups","member = '".$name."' AND membertype = 1");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_services","name = '".$name."'");
					FS::$dbMgr->CommitTr();
					
					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#srv_".preg_replace("#[. ]#","-",$name)."');");
					return;
				// Add/Edit hostgroup
				case 19:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$members = FS::$secMgr->checkAndSecurisePostData("members");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if(!$name || !$alias || preg_match("#[ ]#",$name)) {
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					if($edit) {
						if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
                                                        return;
                                                }
					}
					else {
						if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}
					
					if($members) {
						$count = count($members);
						for($i=0;$i<$count;$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if(count($mt) != 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
								echo $this->loc->s("err-bad-data");
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
						echo $this->loc->s("err-bad-data");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","'".$name."','".$alias."'");
					if(!$this->icingaAPI->writeConfiguration()) {
						echo $this->loc->s("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					return;
				// remove hostgroup
				case 21:
					if(!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("hg");
					if(!$name) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Not exists
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Used
					if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","host = '".$name."' AND hosttype = '2'")) {
						FS::$iMgr->ajaxEcho("err-hg-used");
						return;
					}

					// Delete hostgroup and members
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '2'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->CommitTr();

					if(!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#hg_".preg_replace("#[. ]#","-",$name)."');");
					return;
			}
		}

		private $icingaAPI;
	};
?>
