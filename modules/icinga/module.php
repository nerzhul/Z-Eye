<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/icingaBroker.api.php");
	require_once(dirname(__FILE__)."/objects.php");

	if(!class_exists("iIcinga")) {
		
	final class iIcinga extends FSModule {
		function __construct() {
			parent::__construct();
			$this->modulename = "icinga";
			$this->loc = new lIcinga();
			$this->rulesclass = new rIcinga($this->loc);
			$this->menu = $this->loc->s("menu-name");
			$this->icingaAPI = new icingaBroker();
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-icinga"));
			return $this->showTabPanel();
		}

		private function showTabPanel() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");

			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("title-icinga");
				$panElmts = array();
				$panElmts[] = array(1,"mod=".$this->mid,$this->loc->s("General"));
				if (FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
					$panElmts[] = array(2,"mod=".$this->mid,$this->loc->s("Hosts"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
					$panElmts[] = array(3,"mod=".$this->mid,$this->loc->s("Hostgroups"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
					$panElmts[] = array(4,"mod=".$this->mid,$this->loc->s("Services"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
					$panElmts[] = array(5,"mod=".$this->mid,$this->loc->s("Timeperiods"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_notif_write")) {
					$panElmts[] = array(9,"mod=".$this->mid,$this->loc->s("Notification-strategies"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
					$panElmts[] = array(6,"mod=".$this->mid,$this->loc->s("Contacts"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
					$panElmts[] = array(7,"mod=".$this->mid,$this->loc->s("Contactgroups"));
				}
				
				if (FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
					$panElmts[] = array(8,"mod=".$this->mid,$this->loc->s("Commands"));
				}
				
				$output .= FS::$iMgr->tabPan($panElmts,$sh);
				return $output;
			}
			
			if (!$sh) {
				$sh = 1;
			}
			
			switch($sh) {
				case 1: $output .= $this->showGeneralTab(); break;
				case 2: $output .= $this->showHostsTab(); break;
				case 3: $output .= $this->showHostgroupsTab(); break;
				case 4: $output .= $this->showServicesTab(); break;
				case 5: $output .= $this->showTimeperiodsTab(); break;
				case 6: $output .= $this->showContactsTab(); break;
				case 7: $output .= $this->showContactgroupsTab(); break;
				case 8: $output .= $this->showCommandTab(); break;
				case 9: $output .= $this->showNotificationStrategiesTab(); break;
				// @TODO: case 10: service group
			}
			return $output;
		}

		private function showGeneralTab() {
			FS::$iMgr->setURL("?mod=".$this->mid."&sh=1");
			return (new icingaSensor())->showReport();
		}

		private function showHostsTab() {
			FS::$iMgr->setURL("sh=2");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$output = "";
			
			$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","");
			if (!$tpexist) {
				$output .= FS::$iMgr->opendiv(1,$this->loc->s("new-host"));
				return $output;
			}
			
			$ctexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","name","");
			if (!$ctexist) {
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
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"thostList\" width=\"80%\"><thead><tr><th class=\"headerSortDown\" width=\"20%\">".$this->loc->s("Name")."</th><th></th><th width=\"20%\">".
						$this->loc->s("Alias")."</th><th width=\"20%\">".$this->loc->s("Address")."</th><th width=\"15%\">".$this->loc->s("Template").
						"</th><th width=\"20%\">".$this->loc->s("Parent")."</th><th></th></tr></thead>";
				}

				$parentlist = array();
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$data["name"]."'");
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					$parentlist[] = $data2["parent"];
				}
				
				$output .= "<tr id=\"h_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_host_write")) {
					$output .= FS::$iMgr->opendiv(10,$data["name"],array("lnkadd" => "name=".$data["name"]));
				}
				else {
					$output .= $data["name"];
				}
				
				$output .= "</td><td>";
				if ($data["template"] != "t") {
					$output .= FS::$iMgr->iconOpendiv("17","monitor",array("lnkadd" => "host=".$data["name"],
						"iconsize" => 20));
				}
				
				$output .= "</td><td>".$data["alias"]."</td><td>".$data["addr"]."</td><td>";
				if ($data["template"] == "t") {
					$output .= $this->loc->s("Yes");
				}
				else {
					$output .= $this->loc->s("No");
				}
				
				$output .= "</td><td>";
				$found2 = false;
				for ($i=0;$i<count($parentlist);$i++) {
					if ($found2) $output .= ", ";
					else $found2 = true;
					$output .= $parentlist[$i];
				}
				$output .="</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=15&host=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-host")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("thostList");
			}
			return $output;
		}

		private function showHostgroupsTab() {
			FS::$iMgr->setURL("sh=3");
			if (!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			$output = "";

			if (FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
				$output .= FS::$iMgr->opendiv(4,$this->loc->s("new-hostgroup"));
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"thgList\" width=\"80%\"><thead><tr><th class=\"headerSortDown\" width=\"10%\">".$this->loc->s("Name").
						"</th><th width=\"10%\">".$this->loc->s("Alias")."</th><th width=\"80%\">".$this->loc->s("Members")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"hg_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_hg_write"))
					$output .= FS::$iMgr->opendiv(11,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["alias"]."</td><td>";
				$found2 = false;
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host,hosttype","name = '".$data["name"]."'",array("order" => "hosttype,name"));
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					if ($found2) $output .= ", ";
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
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("thgList");
			}
			return $output;
		}
		
		private function showHostgroupForm($name = "") {
			$output = FS::$iMgr->cbkForm("19");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
			// Global
			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30));

			$alias = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","alias","name = '".$name."'");
			$output .= FS::$iMgr->idxLine("Alias","alias",array("value" => $alias,"length" => 60, "size" => 30));

			$hostlist = array();
			if ($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","name = '".$name."'");
				while ($data = FS::$dbMgr->Fetch($query))
					$hostlist[] = $data["hosttype"]."$".$data["host"];
			}	

			$output .= "<tr><td>".$this->loc->s("Members")."</td><td>".$this->getHostOrGroupList("members",true,$hostlist,$name)."</td></tr>";
			$output .= FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}

		private function showServicesTab() {
			FS::$iMgr->setURL("sh=4");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
				return FS::$iMgr->printError("err-no-right");
			}

			$output = "";

			if (FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
				$output .= FS::$iMgr->opendiv(5,$this->loc->s("new-service"));
			}

			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,template","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tsrvList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name").
						"</th><th></th><th>".$this->loc->s("Host").
						"</th><th>".$this->loc->s("Hosttype")."</th><th>".$this->loc->s("Template")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"srv_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
					$output .= FS::$iMgr->opendiv(12,$data["name"],array("lnkadd" => "name=".$data["name"]));
				}
				else {
					$output .= $data["name"];
				}

				$output .= "</td><td>";
				
				if ($data["template"] != "t") {
					$output .= FS::$iMgr->iconOpendiv("18","monitor",array("lnkadd" => "srv=".$data["name"],
						"iconsize" => 20));
				}
				
				$output .= "</td><td>".$data["host"]."</td><td>";
				
				switch($data["hosttype"]) {
					case 1: $output .= "Simple"; break;
					case 2: $output .= "Groupe"; break;
					default: $output .= "unk"; break;
				}
				$output .= "</td><td>";
				if ($data["template"] == "t") $output .= $this->loc->s("Yes");
				else $output .= $this->loc->s("No");
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=18&srv=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-service")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tsrvList");
			}
			return $output;
		}

		private function showTimeperiodsTab() {
			FS::$iMgr->setURL("sh=5");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			$output = "";
			
			/*
			 * Ajax new Timeperiod
			 * @TODO: support for multiple times in one day, and calendar days
			 */
			
			if (FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
				$output .= FS::$iMgr->opendiv(6,$this->loc->s("new-timeperiod"));

			/*
			 * Timeperiod table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"ttpList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias").
						"</th><th>".$this->loc->s("Periods")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"tp_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_tp_write"))
					$output .= FS::$iMgr->opendiv(13,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];

				$output .= "</td><td>".$data["alias"]."</td><td>";
				if ($data["mhs"] != 0 || $data["mms"] != 0 || $data["mhe"] != 0 || $data["mme"] != 0)
					$output .= $this->loc->s("Monday").		" - ".$this->loc->s("From")." ".($data["mhs"] < 10 ? "0" : "").	$data["mhs"].	":".($data["mms"] < 10 ? "0" : "").	$data["mms"].	
					" ".$this->loc->s("To")." ".($data["mhe"] < 10 ? "0" : "").	$data["mhe"].":".($data["mme"] < 10 ? "0" : "").$data["mme"]."<br />";
				if ($data["tuhs"] != 0 || $data["tums"] != 0 || $data["tuhe"] != 0 || $data["tume"] != 0)
					$output .= $this->loc->s("Tuesday").	" - ".$this->loc->s("From")." ".($data["tuhs"] < 10 ? "0" : "").$data["tuhs"].	":".($data["tums"] < 10 ? "0" : "").$data["tums"].	
					" ".$this->loc->s("To")." ".($data["tuhe"] < 10 ? "0" : "").$data["tuhe"].":".($data["tume"] < 10 ? "0" : "").$data["tume"]."<br />";
				if ($data["whs"] != 0 || $data["wms"] != 0 || $data["whe"] != 0 || $data["wme"] != 0)	
					$output .= $this->loc->s("Wednesday").	" - ".$this->loc->s("From")." ".($data["whs"] < 10 ? "0" : "").	$data["whs"].	":".($data["wms"] < 10 ? "0" : "").	$data["wms"].	
					" ".$this->loc->s("To")." ".($data["whe"] < 10 ? "0" : "").	$data["whe"].":".($data["wme"] < 10 ? "0" : "").$data["wme"]."<br />";
				if ($data["thhs"] != 0 || $data["thms"] != 0 || $data["thhe"] != 0 || $data["thme"] != 0)
					$output .= $this->loc->s("Thursday").	" - ".$this->loc->s("From")." ".($data["thhs"] < 10 ? "0" : "").$data["thhs"].	":".($data["thms"] < 10 ? "0" : "").$data["thms"].
					" ".$this->loc->s("To")." ".($data["thhe"] < 10 ? "0" : "").$data["thhe"].":".($data["thme"] < 10 ? "0" : "").$data["thme"]."<br />";
				if ($data["fhs"] != 0 || $data["fms"] != 0 || $data["fhe"] != 0 || $data["fme"] != 0)
					$output .= $this->loc->s("Friday").		" - ".$this->loc->s("From")." ".($data["fhs"] < 10 ? "0" : "").	$data["fhs"].	":".($data["fms"] < 10 ? "0" : "").	$data["fms"].
					" ".$this->loc->s("To")." ".($data["fhe"] < 10 ? "0" : "").	$data["fhe"].":".($data["fme"] < 10 ? "0" : "").$data["fme"]."<br />";
				if ($data["sahs"] != 0 || $data["sams"] != 0 || $data["sahe"] != 0 || $data["same"] != 0)
					$output .= $this->loc->s("Saturday").	" - ".$this->loc->s("From")." ".($data["sahs"] < 10 ? "0" : "").$data["sahs"].	":".($data["sams"] < 10 ? "0" : "").$data["sams"].
					" ".$this->loc->s("To")." ".($data["sahe"] < 10 ? "0" : "").$data["sahe"].":".($data["same"] < 10 ? "0" : "").$data["same"]."<br />";
				if ($data["suhs"] != 0 || $data["sums"] != 0 || $data["suhe"] != 0 || $data["sume"] != 0)
					$output .= $this->loc->s("Sunday").		" - ".$this->loc->s("From")." ".($data["suhs"] < 10 ? "0" : "").$data["suhs"].	":".($data["sums"] < 10 ? "0" : "").$data["sums"].
					" ".$this->loc->s("To")." ".($data["suhe"] < 10 ? "0" : "").$data["suhe"].":".($data["sume"] < 10 ? "0" : "").$data["sume"];
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=6&tp=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-timeperiod")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
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
			if ($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume","name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
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
			$output = FS::$iMgr->cbkForm("4");
			$output .= "<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";

			$output .= FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30));

			$output .= FS::$iMgr->idxLine("Alias","alias",array("value" => $alias,"length" => 120, "size" => 30));
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
			FS::$iMgr->setURL("sh=6");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$output = "";

			if (FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
				$output .= FS::$iMgr->opendiv(7,$this->loc->s("new-contact"));
			}

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name,mail,template","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tctList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Email")."</th><th>Template ?</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"ct_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
					$output .= FS::$iMgr->opendiv(14,$data["name"],array("lnkadd" => "name=".$data["name"]));
				}
				else {
					$output .= $data["name"];
				}

				$output .= "</td><td>".$data["mail"]."</td>
					<td>".($data["template"] == "t" ? $this->loc->s("Yes") : $this->loc->s("No"))."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=9&ct=".$data["name"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-remove-contact")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tctList");
			}
			return $output;
		}

		private function showContactgroupsTab() {
			FS::$iMgr->setURL("sh=7");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$output = "";

			if (FS::$sessMgr->hasRight("mrule_icinga_ctg_write")) {
				$output .= FS::$iMgr->opendiv(8,$this->loc->s("new-contactgroup"));
			}

			/*
			 * Contactgroup table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tctgList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Alias")."</th><th>".$this->loc->s("Members")."</th><th></th></tr></thead>";
				}

				$contacts = array();
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","name = '".$data["name"]."'");
				while ($data2 = FS::$dbMgr->Fetch($query2)) {
					$contacts[] = $data2["member"];
				}

				$output .= "<tr id=\"ctg_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";
				if (FS::$sessMgr->hasRight("mrule_icinga_ctg_write"))
					$output .= FS::$iMgr->opendiv(15,$data["name"],array("lnkadd" => "name=".$data["name"]));
				else
					$output .= $data["name"];
					
				$output .= "</td><td>".$data["alias"]."</td><td>";

				$found2 = false;
				for ($i=0;$i<count($contacts);$i++) {
					if ($found2) $output .= ", ";
					else $found2 = true;
					$output .= $contacts[$i];
				}
				$output .= "</td><td>".FS::$iMgr->removeIcon("mod=".$this->mid."&act=12&ctg=".$data["name"],array("js" => true,
					"confirm" => array($this->loc->s("confirm-remove-contactgroup")."'".$data["name"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tctgList");
			}
			return $output;
		}
		
		private function showNotificationStrategiesTab() {
			FS::$iMgr->setURL("sh=9");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			/*
			 * Ajax new command
			 */
			$output = FS::$iMgr->opendiv(19,$this->loc->s("new-strategy"));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_notif_strategy","name,alias","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tcmdList\"><thead><tr><th class=\"headerSortDown\">".
						$this->loc->s("Name")."</th><th>".
						$this->loc->s("Alias")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"notifstr_".preg_replace("#[. ]#","-",$data["name"])."\"><td>".
					FS::$iMgr->opendiv(20,$data["name"],array("lnkadd" => "name=".$data["name"])).
					"</td><td>".$data["alias"]."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=23&notifstr=".$data["name"],
						array("js" => true,
							"confirm" => array($this->loc->s("confirm-remove-notif-strategy")."'".$data["name"]."' ?", "Confirm","Cancel")
						)
					).
					"</td></tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tcmdList");
			}
			return $output;
		}

		private function showCommandTab() {
			FS::$iMgr->setURL("sh=8");
			
			if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			/*
			 * Ajax new command
			 */
			$output = FS::$iMgr->opendiv(9,$this->loc->s("new-cmd"));

			/*
			 * Command table
			 */
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd,syscmd","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
					$output .= "<table id=\"tcmdList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Name")."</th><th>".$this->loc->s("Command")."</th><th></th></tr></thead>";
				}
				$output .= "<tr id=\"cmd_".preg_replace("#[. ]#","-",$data["name"])."\"><td>";

				// If we can write and it's not a system command
				if (FS::$sessMgr->hasRight("mrule_icinga_cmd_write") && $data["syscmd"] != 't') {
					$output .= FS::$iMgr->opendiv(16,$data["name"],array("lnkadd" => "name=".$data["name"]));
				}
				else {
					$output .= $data["name"];
				}

				$output .= "</td><td>".substr($data["cmd"],0,100).(strlen($data["cmd"]) > 100 ? "..." : "")."</td>";

				if (FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
					$output .= "<td>";
					// If it's not a system command, then we can remove it
					if ($data["syscmd"] != 't') {
						$output .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&cmd=".$data["name"],array("js" => true,
							"confirm" => array($this->loc->s("confirm-remove-command")."'".$data["name"]."' ?","Confirm","Cancel")));
					}
					$output .= "</td>";
				}

				$output .= "</tr>";
			}
			if ($found) {
				$output .= "</table>";
				FS::$iMgr->jsSortTable("tcmdList");
			}
			return $output;
		}

		private function showCommandForm($name="") {
			$command = "";
			$comment = "";
			if ($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_commands","cmd,cmd_comment","name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$command = $data["cmd"];
					$comment = $data["cmd_comment"];
				}
			}
	
			return FS::$iMgr->cbkForm("1").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxIdLine("Name","name",$name,array("length" => 60, "size" => 30, "tooltip" => "tooltip-cmdname")).
				FS::$iMgr->idxLine("Command","cmd",array("type" => "area", "value" => $command,
					"length" => 1024, "size" => 30, "height" => "150",
					"tooltip" => "tooltip-cmd")).
				FS::$iMgr->idxLine("Comment","comment",array("type" => "area", "value" => $comment,
					"length" => 512, "size" => 30, "height" => "100")).
				FS::$iMgr->aeTableSubmit($name == "");
		}

		public function genCommandList($name,$tocheck = NULL) {
			return FS::$iMgr->select($name).
				FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_commands","name",
					array("selected" => array($tocheck),"sqlopts" => array("order" => "name"))).
				"</select>";
		}
		
		public function genContactGroupsList($name,$select = "") {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroups","name,alias","",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["alias"].")",$data["name"],$select == $data["name"] ? true : false);
			}
			$output .= "</select>";
			return $output;
		}
		
		private function genHostsList($name) {
			$output = FS::$iMgr->select($name);
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'",array("order" => "name"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"]);
			}
			$output .= "</select>";
			return $output;
		}
		
		public function getHostOrGroupList($name,$multi,$selected = array(),$ignore = "",$grouponly = false) {
			$hostlist = array();
			if (!$grouponly) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hosts","name,addr","template = 'f'");
				while ($data = FS::$dbMgr->Fetch($query))
					$hostlist[$this->loc->s("Host").": ".$data["name"]." (".$data["addr"].")"] = array(1,$data["name"]);
			}

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroups","name");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if ($data["name"] != $ignore)
					$hostlist[($grouponly ? "" : $this->loc->s("Hostgroup").": ").$data["name"]] = array(2,$data["name"]);
			}

			ksort($hostlist);

			$tmpoutput = "";
			$countElmt = 0;
			foreach ($hostlist as $host => $value) {
				$countElmt++;
				$tmpoutput .= FS::$iMgr->selElmt($host,(!$grouponly ? $value[0]."$" : "").$value[1],in_array((!$grouponly ? $value[0]."$" : "").$value[1],$selected));
			}
			if ($countElmt/4 < 4) $countElmt = 16;
			$output = FS::$iMgr->select($name,array("multi" => $multi, "size" => round($countElmt/4)));
			$output .= $tmpoutput;
			$output .= "</select>";
			return $output;
		}

		private function isForbidCmd($cmd) {
			if ($cmd == "rm" || $cmd == "/bin/rm" || $cmd == "ls" || $cmd == "/bin/ls" || $cmd == "cp" || $cmd == "/bin/cp" || $cmd == "mv" || $cmd == "/bin/mv") 
				return true;

			return false;
		}
				
		public function loadFooterPlugin() {
			// Only users with icinga read right can use this module
			if (FS::$sessMgr->hasRight("mrule_icinga_read")) {
				$pluginTitle = $this->loc->s("Monitor");
				$pluginContent = "";
				
				// First we look at sensor problems
				
				$totalIcingaSensors = 0;
				$totalIcingaWarns = 0;
				$totalIcingaCrits = 0;
				$totalIcingaHS = 0;

				$iStates = $this->icingaAPI->readStates(
					array("plugin_output","current_state","current_attempt",
						"state_type","last_time_ok","last_time_up"));

				// Loop hosts
				foreach ($iStates as $host => $hostvalues) {
					// Loop types
					foreach ($hostvalues as $hos => $hosvalues) {
						if ($hos == "servicestatus") {
							// Loop sensors
							foreach ($hosvalues as $sensor => $svalues) {
								$totalIcingaSensors++;
								if ($svalues["current_state"] > 0) {
									if ($svalues["current_state"] == 1) {
										$totalIcingaWarns++;
									}
									else if ($svalues["current_state"] == 2) {
										$totalIcingaCrits++;
									}
										
									$totalIcingaHS++;
								}
							}
						}
						else if ($hos == "hoststatus") {
							$totalIcingaSensors++;
							if ($hosvalues["current_state"] > 0) {
								$totalIcingaHS++;

								if ($hosvalues["current_state"] == 1) {
									$totalIcingaCrits++;
								}
							}
						}
					}
				}
				
				// If there are bad sensors
				if ($totalIcingaHS > 0) {
					// If there are crits
					if ($totalIcingaCrits > 0) {
						$pluginTitle = sprintf("%s: %s/%s %s",
							$this->loc->s("Services"),
							$totalIcingaSensors - $totalIcingaHS,
							$totalIcingaSensors,
							FS::$iMgr->img("/styles/images/monitor-crit.png",15,15)
						);
					}
					// If we have only warns
					else {
						$pluginTitle = sprintf("%s: %s/%s %s",
							$this->loc->s("Services"),
							$totalIcingaSensors - $totalIcingaHS,
							$totalIcingaSensors,
							FS::$iMgr->img("/styles/images/monitor-warn.png",15,15)
						);
					}
				}
				// No icinga error
				else {
					$pluginTitle = sprintf("%s: %s/%s %s",
							$this->loc->s("Services"),
							$totalIcingaSensors - $totalIcingaHS,
							$totalIcingaSensors,
							FS::$iMgr->img("/styles/images/monitor-ok.png",15,15)
						);
				}
				$this->registerFooterPlugin($pluginTitle, $pluginContent);
			}
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return FS::$iMgr->printError("err-no-timeperiod");
				case 2: return FS::$iMgr->printError("err-no-contactgroups");
				case 3: 
					return (new icingaHost())->showForm();
				case 4:
					if (!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						return $this->loc->s("err-no-rights");
					}

					$hostexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","");
					if (!$hostexist) {
						return FS::$iMgr->printError("err-no-hosts");
					}
					
					return $this->showHostgroupForm();
				case 5:
					if (!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						return FS::$iMgr->printError("err-no-rights");
					}
						
					$tpexist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","");
					if ($tpexist) {
						return (new icingaService())->showForm();
					}
					
					return FS::$iMgr->printError("err-no-timeperiods");
				case 6:
					if (!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						return $this->loc->s("err-no-rights");
					}

					return $this->showTimeperiodForm();
				case 7:
					if (!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						return $this->loc->s("err-no-rights");
					}

					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","","alias")) {
						return (new icingaContact)->showForm();
					}
					else {
						return FS::$iMgr->printError("err-no-timeperiod");
					}
				case 8:
					if (!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						return $this->loc->s("err-no-rights");
					}
					
					return (new icingaCtg())->showForm();
				case 9: return $this->showCommandForm();
				case 10:
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					return (new icingaHost())->showForm($name);
				case 11:
					if (!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						return $this->loc->s("err-no-rights");
					}

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showHostgroupForm($name);
				case 12:
					if (!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						return $this->loc->s("err-no-rights");
					}

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return (new icingaService())->showForm($name);
				case 13:
					if (!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						return $this->loc->s("err-no-rights");
					}

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showTimeperiodForm($name);
				case 14:
					if (!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						return $this->loc->s("err-no-rights");
					}

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return (new icingaContact)->showForm($name);
				case 15:
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return (new icingaCtg())->showForm($name);
				case 16:
					if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						return $this->loc->s("err-no-rights");
					}

					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return $this->showCommandForm($name);
				case 17:
					$name = FS::$secMgr->checkAndSecuriseGetData("host");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}

					return (new icingaHost())->showSensors($name);
				case 18:
					$name = FS::$secMgr->checkAndSecuriseGetData("srv");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}
					return (new icingaService())->showSensors($name);
				case 19:
					if (!FS::$sessMgr->hasRight("mrule_icinga_notif_write")) {
						return $this->loc->s("err-no-rights");
					}
					
					return (new icingaNotificationStrategy())->showForm();
				case 20:
					if (!FS::$sessMgr->hasRight("mrule_icinga_notif_write")) {
						return $this->loc->s("err-no-rights");
					}
					
					$name = FS::$secMgr->checkAndSecuriseGetData("name");
					if (!$name) {
						return $this->loc->s("err-bad-datas");
					}
					
					return (new icingaNotificationStrategy())->showForm($name);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Add/Edit command
				case 1:
					if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 

					$cmdname = FS::$secMgr->checkAndSecurisePostData("name");
					$cmd = FS::$secMgr->checkAndSecurisePostData("cmd");
					$comment = FS::$secMgr->checkAndSecurisePostData("comment");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					
					if (!$cmdname || !$cmd || !preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$#",$cmdname) || $edit && $edit != 1) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if ($edit) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
							echo $this->loc->s("err-not-found");
							return;
						}
					}
					else if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						echo $this->loc->s("err-data-exist");
						return;
					}
					
					// Verify if it's a system command and forbid if it's a system command		
					$sysCmd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","syscmd","name = '".$cmdname."'");
					if ($sysCmd == 't') {
						FS::$iMgr->ajaxEcho("err-cannot-modify-system-command");
						return;
					}
					
					$tmpcmd = preg_replace("#\\\$USER1\\\$#","/usr/local/libexec/nagios/",$cmd);
					$tmpcmd = preg_split("#[ ]#",$tmpcmd);
					$out = "";
					exec("if [ -f ".$tmpcmd[0]." ] && [ -x ".$tmpcmd[0]." ]; then echo 0; else echo 1; fi;",$out);
					if (!is_array($out) || count($out) != 1 || $out[0] != 0 || $this->isForbidCmd($tmpcmd[0])) {
						FS::$iMgr->ajaxEcho("err-binary-not-found");
						return;
					} 

					if ($edit) {
						FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");
					}
					
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_commands","name,cmd,cmd_comment",
						"'".$cmdname."','".$cmd."','".$comment."'");
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=8",true);
					return;
				// Remove command
				case 2:
					if (!FS::$sessMgr->hasRight("mrule_icinga_cmd_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					// @TODO forbid remove when use (host + service)
					$cmdname = FS::$secMgr->checkAndSecuriseGetData("cmd");
					if (!$cmdname) {
						FS::$iMgr->ajaxEchoNC("err-bad-data");
						return;
					}
					
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","cmd","name = '".$cmdname."'")) {
						FS::$iMgr->ajaxEchoNC("err-data-not-exist");
						return;
					}

					// Verify if it's a system command and forbid if it's a system command		
					$sysCmd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","syscmd","name = '".$cmdname."'");
					if ($sysCmd == 't') {
						FS::$iMgr->ajaxEcho("err-cannot-modify-system-command");
						return;
					}
					
					// Forbid remove if command is used
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","srvcmd = '".$cmdname."'")) {
						FS::$iMgr->ajaxEchoNC("err-binary-used");
						return;
					}
					
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","name","hostcmd = '".$cmdname."'")) {
						FS::$iMgr->ajaxEchoNC("err-binary-used");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_commands","name = '".$cmdname."'");
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEchoNC("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#cmd_".preg_replace("#[. ]#","-",$cmdname)."');");
					return;
				// Add/Edit timeperiod
				case 4:
					if (!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 
			
					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if (!$name || !$alias || preg_match("#[ ]#",$name)) {
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

					if ($mhs == NULL || $mms == NULL || $tuhs == NULL || $tums == NULL || $whs == NULL || $wms == NULL || 
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

					if ($mhe == NULL || $mme == NULL || $tuhe == NULL || $tume == NULL || $whe == NULL || $wme == NULL || 
						$thhe == NULL || $thme == NULL || $fhe == NULL || $fme == NULL || $sahe == NULL || $same == NULL || 
						$suhe == NULL || $sume == NULL || $mhe > 23 || $mme > 59 || $tuhe > 23 || $tume > 59 || 
						$whe > 23 || $wme > 59 || $thhe > 23 || $thme > 59 || $fhe > 23 || $fme > 59 || $sahe > 23 || $same > 59 ||
						$suhe > 23 || $sume > 59) {
						echo $this->loc->s("err-bad-data");
						return;
					}

					if (!$mhs && !$mms && !$tuhs && !$tums && !$whs && !$wms && !$thhs && !$thms && !$fhs && !$fms && !$sahs && !$sams && !$suhs && !$sums &&
						!$mhe && !$mme && !$tuhe && !$tume && !$whe && !$wme && !$thhe && !$thme && !$fhe && !$fme && !$sahe && !$same && !$suhe && !$sume) {
						echo $this->loc->s("err-bad-data");
						return;
					}
							 

					if ($edit) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
							return;
						}
					}
					else {
						if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}

					if ($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_timeperiods","name,alias,mhs,mms,tuhs,tums,whs,wms,thhs,thms,fhs,fms,sahs,sams,suhs,sums,mhe,mme,tuhe,tume,whe,wme,thhe,thme,fhe,fme,sahe,same,suhe,sume",
						"'".$name."','".$alias."','".$mhs."','".$mms."','".$tuhs."','".$tums."','".$whs."','".$wms."','".$thhs."','".$thms."','".$fhs."','".$fms."','".$sahs."','".$sams."','".$suhs."','".$sums.
						"','".$mhe."','".$mme."','".$tuhe."','".$tume."','".$whe."','".$wme."','".$thhe."','".$thme."','".$fhe."','".$fme."','".$sahe."','".$same."','".$suhe."','".$sume."'");
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=5",true);
					return;
				// Delete timeperiod
				case 6:
					if (!FS::$sessMgr->hasRight("mrule_icinga_tp_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$tpname = FS::$secMgr->checkAndSecuriseGetData("tp");
					if (!$tpname) {
						FS::$iMgr->ajaxEchoNC("err-bad-data");
						return;
					}
					
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","alias","name = '".$tpname."'")) {
						FS::$iMgr->ajaxEchoNC("err-bad-data");
						return;
					}
					
					// Forbid remove if timeperiod is used
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_notif_strategy","name","period = '".$tpname."'")) {
						FS::$iMgr->ajaxEchoNC("err-binary-used");
						return;
					}
					
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","checkperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEchoNC("err-binary-used");
						return;
					}
					
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","checkperiod = '".$tpname."'")) {
						FS::$iMgr->ajaxEchoNC("err-binary-used");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_timeperiods","name = '".$tpname."'");
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEchoNC("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#tp_".preg_replace("#[. ]#","-",$tpname)."');");
					return;
				// Add/Edit contact
				case 7:
					(new icingaContact())->Modify();
					return;
				// Delete contact
				case 9:
					if (!FS::$sessMgr->hasRight("mrule_icinga_ct_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$ctname = FS::$secMgr->checkAndSecuriseGetData("ct");
					if (!$ctname) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$ctname."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// Forbid remove if in existing contact group
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name","member = '".$ctname."'")) {
						FS::$iMgr->ajaxEcho("err-contact-used");
						return;
					}
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contacts","name = '".$ctname."'");
					
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ct_".preg_replace("#[. ]#","-",$ctname)."');");
					return;
				// Add/Edit contact group
				case 10:
					(new icingaCtg())->Modify();
					return;
				// Delete contact group
				case 12:
					(new icingaCtg())->Remove();
					return;
				// Add/Edit host
				case 13:
					(new IcingaHost())->Modify();
					return;
				// Remove host
				case 15:
					(new IcingaHost())->Remove();
					return;
				// Add/Edit service
				case 16:
					(new icingaService())->Modify();
					return;
				// remove service
				case 18:
					if (!FS::$sessMgr->hasRight("mrule_icinga_srv_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("srv");
					if (!$name) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// Not exists
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","name = '".$name."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
					
					// membertype 1 = service, 2 = servicegroup
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_servicegroups","member = '".$name."' AND membertype = 1");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_services","name = '".$name."'");
					FS::$dbMgr->CommitTr();
					
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#srv_".preg_replace("#[. ]#","-",$name)."');");
					return;
				// Add/Edit hostgroup
				case 19:
					if (!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						echo $this->loc->s("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecurisePostData("name");
					$alias = FS::$secMgr->checkAndSecurisePostData("alias");
					$members = FS::$secMgr->checkAndSecurisePostData("members");
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");
					if (!$name || !$alias || preg_match("#[ ]#",$name)) {
						echo $this->loc->s("err-bad-data");
						return;
					}
					
					if ($edit) {
						if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-not-exist");
                                                        return;
                                                }
					}
					else {
						if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
							echo $this->loc->s("err-data-exist");
							return;
						}
					}
					
					if ($members) {
						$count = count($members);
						for ($i=0;$i<$count;$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if (count($mt) != 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
								echo $this->loc->s("err-bad-data");
								return;
							}
						}
						if ($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name = '".$name."'");
						for ($i=0;$i<$count;$i++) {
							$mt = preg_split("#[$]#",$members[$i]);
							if (count($mt) == 2 && ($mt[0] == 1 || $mt[0] == 2))
								FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","'".$name."','".$mt[1]."','".$mt[0]."'");
						}
					}
					else {
						FS::$iMgr->ajaxEchoNC("err-bad-data");
						return;
					}

					if ($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroups","name,alias","'".$name."','".$alias."'");
					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->redir("mod=".$this->mid."&sh=3",true);
					return;
				// remove hostgroup
				case 21:
					if (!FS::$sessMgr->hasRight("mrule_icinga_hg_write")) {
						FS::$iMgr->ajaxEcho("err-no-right");
						return;
					} 

					$name = FS::$secMgr->checkAndSecuriseGetData("hg");
					if (!$name) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Not exists
					if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$name."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}

					// Used
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","name","host = '".$name."' AND hosttype = '2'")) {
						FS::$iMgr->ajaxEcho("err-hg-used");
						return;
					}

					// Delete hostgroup and members
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name = '".$name."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '2'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroups","name = '".$name."'");
					FS::$dbMgr->CommitTr();

					if (!$this->icingaAPI->writeConfiguration()) {
						FS::$iMgr->ajaxEcho("err-fail-writecfg");
						return;
					}
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#hg_".preg_replace("#[. ]#","-",$name)."');");
					return;
				// Add/Edit notification strategy
				case 22:
					(new icingaNotificationStrategy())->Modify();
					return;
				// Remove notification strategy
				case 23:
					(new icingaNotificationStrategy())->Remove();
					return;
			}
		}

		private $icingaAPI;
	};
	
	}
	
	$module = new iIcinga();
?>
