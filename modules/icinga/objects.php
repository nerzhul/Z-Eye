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

	final class icingaCtg extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_contactgroups";
			$this->readRight = "mrule_icinga_ctg_write";
			$this->writeRight = "mrule_icinga_ctg_write";
		}

		protected function Load($name = "") {
			$this->name = $name;
			$this->alias = "";
			$this->contacts = array();
			if($name) {
				$this->alias = FS::$dbMgr->GetOneData($this->sqlTable,"name",
					"name = '".$name."'");

                        	$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member",
					"name = '".$name."'");
                        	while($data = FS::$dbMgr->Fetch($query)) {
                	                $this->contacts[] = $data["member"];
             	        	}
			}
		}

		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name = '".$name."'");
			FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printError($this->loc->s("err-no-right"));
			}
			$this->Load($name);

			$output = FS::$iMgr->cbkForm("10").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxIdLine("Name","name",$this->name,array("length" => 60, "size" => 30)).
				FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$this->alias,array("length" => 60, "size" => 30));

			$countElmt = 0;
			$output2 = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_contacts","name","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$output2 .= FS::$iMgr->selElmt($data["name"],$data["name"],in_array($data["name"],$this->contacts));
			}
			if($countElmt/4 < 4) $countElmt = 16;

			$output .= "<tr><td>".$this->loc->s("Contacts")."</td><td>".
				FS::$iMgr->select("cts",array("multi" => true, "size" => round($countElmt/4))).
				$output2."</select></td></tr>".
				FS::$iMgr->aeTableSubmit($this->name == "");
			return $output;
		}

		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			$name = FS::$secMgr->getPost("name","w");
			$alias = FS::$secMgr->checkAndSecurisePostData("alias");
			$cts = FS::$secMgr->checkAndSecurisePostData("cts");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if(!$name || !$alias || !$cts || $cts == "") {
				echo FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			
			// ctg exists
			if($edit) {
				if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
					echo FS::$iMgr->ajaxEcho("err-data-not-exist");
					return;
				}
			}
			else {
				if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
					FS::$iMgr->ajaxEcho("err-data-exist");
					return;
				}
			}

			// some members don't exist
			$count = count($cts);
			for($i=0;$i<$count;$i++) {
				if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$cts[$i]."'")) {
					FS::$iMgr->ajaxEcho("err-bad-data");
					return;
				}
			}

			FS::$dbMgr->BeginTr();
			if($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name = '".$name."'");
			}
			// Add it
			FS::$dbMgr->Insert($this->sqlTable,"name,alias","'".$name."','".$alias."'");
			for($i=0;$i<$count;$i++) {
				FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_contactgroup_members","name,member","'".$name."','".$cts[$i]."'");
			}
			FS::$dbMgr->CommitTr();

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEcho("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=7",true);
		}

		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			} 

			// @TODO forbid remove when used (service, service_group)
			$ctgname = FS::$secMgr->checkAndSecuriseGetData("ctg");
			if(!$ctgname) {
				FS::$iMgr->ajaxEchoNC("err-bad-data");
				return;
			}

			if(!FS::$dbMgr->GetOneData($this->sqlTable,"alias","name = '".$ctgname."'")) {
					FS::$iMgr->ajaxEchoNC("err-bad-data");
				return;
			}

			if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","contactgroup = '".$ctgname."'")) {
				FS::$iMgr->ajaxEchoNC("err-ctg-used");
				return;
			}

			$this->removeFromDB($ctgname);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoNC("err-fail-writecfg");
				return;
			}
			FS::$iMgr->ajaxEcho("Done","hideAndRemove('#ctg_".preg_replace("#[. ]#","-",$ctgname)."');");
		}

		private $name;
		private $alias;
		private $contacts;
	};

	final class icingaHost extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_hosts";
			$this->readRight = "mrule_icinga_host_write";
			$this->writeRight = "mrule_icinga_host_write";
		}

		protected function Load($name = "") {
			$this->name = $name;
			$this->dname = ""; $this->icon = ""; $this->alias = ""; $this->addr = ""; $this->parentlist = array();
			$this->checkcmd = "check-host-alive"; $this->checkperiod = ""; $this->checkintval = 3; $this->retcheckintval = 1;
			$this->maxcheck = 10;
			$this->eventhdlen = true; $this->flapen = true; $this->failpreden = true; $this->perfdata = true;
			$this->retstatus = true; $this->retnonstatus = true;
			$this->notifen = true; $this->notifperiod = ""; $this->notifintval = 0; $this->ctg = "";
			$this->hostoptd = true; $this->hostoptu = true; $this->hostoptr = true; $this->hostoptf = true; $this->hostopts = true;

			if($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,
					"dname,alias,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid","name = '".$name."'");
				if($data = FS::$dbMgr->Fetch($query)) {
					$this->dname = $data["dname"];
					$this->alias = $data["alias"];
					$this->addr = $data["addr"];
					$this->icon = $data["iconid"];
					$this->checkcmd = $data["alivecommand"];
					$this->checkperiod = $data["checkperiod"];
					$this->checkintval = $data["checkinterval"];
					$this->retcheckintval = $data["retrycheckinterval"];
					$this->maxcheck = $data["maxcheck"];
					$this->eventhdlen = ($data["eventhdlen"] == 't');	
					$this->flapen = ($data["flapen"] == 't');	
					$this->failpreden = ($data["failpreden"] == 't');	
					$this->perfdata = ($data["perfdata"] == 't');	
					$this->retstatus = ($data["retstatus"] == 't');	
					$this->retnonstatus = ($data["retnonstatus"] == 't');	
					$this->notifen = ($data["notifen"] == 't');	
					$this->notifperiod = $data["notifperiod"];
					$this->notifintval = $data["notifintval"];
					$this->ctg = $data["contactgroup"];	
					$this->hostoptd = $data["hostoptd"];
					$this->hostoptu = $data["hostoptu"];
					$this->hostoptr = $data["hostoptr"];
					$this->hostoptf = $data["hostoptf"];
					$this->hostopts = $data["hostopts"];
				}
				$this->parentlist = array();
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$name."'");
				while($data = FS::$dbMgr->Fetch($query)) {
					$this->parentlist[] = $data["parent"];
				}
			}
		}

		protected function removeFromDB($name) {
			// Remove host and links with parents and hostgroups
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","name = '".$name."'");
			FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","parent = '".$name."'");
			FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '1'");
			FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			FS::$dbMgr->CommitTr();
		}

		public function showForm($name = "") {
			if (!$this->canRead()) {
				return $this->loc->s("err-no-right");
			}

			$this->Load($name);

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("13").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$output .= template list
			$output .= FS::$iMgr->idxIdLine("Name","name",$this->name).
				FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$this->alias).
				FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname",$this->dname).

				"<tr><td>".$this->loc->s("Icon")."</td><td>".
				FS::$iMgr->select("icon").
				FS::$iMgr->selElmt("Aucun","",($this->icon == "")).
				FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_icons","id",array("labelfield" => "name","selected" => array($this->icon))).
				"</select></td></tr>".

				"<tr><td>".$this->loc->s("Parent")."</td><td>";

			$output2 = FS::$iMgr->selElmt($this->loc->s("None"),"none",(count($this->parentlist) == 0));
			$countElmt = 0;

			$query = FS::$dbMgr->Select($this->sqlTable,"name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$output2 .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"],in_array($data["name"],$this->parentlist));
			}

			if($countElmt/4 < 4) $countElmt = 16;
			$output .= FS::$iMgr->select("parent",array("multi" => true, "size" => round($countElmt/4))).
				$output2."</select></td></tr>".
				FS::$iMgr->idxLine($this->loc->s("Address"),"addr",$this->addr);

			$hglist = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name","host = '".$name."' AND hosttype = '1'");
				while($data = FS::$dbMgr->Fetch($query))
					$hglist[] = $data["name"];
			}

			$output .= "<tr><td>".$this->loc->s("Hostgroups")."</td><td>".$this->mod->getHostOrGroupList("hostgroups",true,$hglist,"",true)."</td></tr>";

			// Checks
			$output .= "<tr><td>".$this->loc->s("alivecommand")."</td><td>".$this->mod->genCommandList("checkcommand",$this->checkcmd)."</td></tr>".
				"<tr><td>".$this->loc->s("checkperiod")."</td><td>".$this->mod->getTimePeriodList("checkperiod",$this->checkperiod)."</td></tr>".
				FS::$iMgr->idxLine($this->loc->s("check-interval"),"checkintval","",array("value" => $this->checkintval, "type" => "num")).
				FS::$iMgr->idxLine($this->loc->s("retry-check-interval"),"retcheckintval","",array("value" => $this->retcheckintval, "type" => "num")).
				FS::$iMgr->idxLine($this->loc->s("max-check"),"maxcheck","",array("value" => $this->maxcheck, "type" => "num"));

			// Global
			$output .= FS::$iMgr->idxLine($this->loc->s("eventhdl-en"),"eventhdlen",$this->eventhdlen,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("flap-en"),"flapen",$this->flapen,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("failpredict-en"),"failpreden",$this->failpreden,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("perfdata"),"perfdata",$this->perfdata,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("retainstatus"),"retstatus",$this->retstatus,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("retainnonstatus"),"retnonstatus",$this->retnonstatus,array("type" => "chk"));

			// Notifications
			$output .= FS::$iMgr->idxLine($this->loc->s("notif-en"),"notifen",$this->notifen,array("type" => "chk")).
			"<tr><td>".$this->loc->s("notifperiod")."</td><td>".$this->mod->getTimePeriodList("notifperiod",$this->notifperiod)."</td></tr>".
				FS::$iMgr->idxLine($this->loc->s("notif-interval"),"notifintval","",array("value" => $this->notifintval, "type" => "num")).
				FS::$iMgr->idxLine($this->loc->s("hostoptdown"),"hostoptd",$this->hostoptd,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("hostoptunreach"),"hostoptu",$this->hostoptu,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("hostoptrec"),"hostoptr",$this->hostoptr,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("hostoptflap"),"hostoptf",$this->hostoptf,array("type" => "chk")).
				FS::$iMgr->idxLine($this->loc->s("hostoptsched"),"hostopts",$this->hostopts,array("type" => "chk")).
				"<tr><td>".$this->loc->s("Contactgroups")."</td><td>".$this->mod->genContactGroupsList("ctg",$this->ctg)."</td></tr>";
			// icon image
			// statusmap image
			$output .= FS::$iMgr->aeTableSubmit($this->name == "");
			return $output;
		}

		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
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
			if(!$name || (!FS::$secMgr->isDNSName($name) && !FS::$secMgr->isHostname($name)) || 
				!$alias || !$dname || !$addr || !$checkcommand || !$checkperiod ||
				 !$notifperiod || !$ctg || $icon && !FS::$secMgr->isNumeric($icon) || $edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-data");
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
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			
			// Now verify datas
			if($edit) {
				if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
					FS::$iMgr->ajaxEcho("err-data-not-exist");
					return;
				}
			}
			else {
				if(FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoNC("err-data-exist");
					return;
				}
			}
			
			if($parent && !in_array("none",$parent)) {
				$count = count($parent);
				for($i=0;$i<$count;$i++) {
					if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$parent[$i]."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
				}
			}
			
			if($hg && is_array($hg)) {
				$count = count($hg);
				for($i=0;$i<$count;$i++) {
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$hg[$i]."'")) {
						FS::$iMgr->ajaxEcho("err-bad-data");
						return;
					}
				}
			}

			if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcommand."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			
			if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$notifperiod."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			FS::$dbMgr->BeginTr();
			if($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
			}
			FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,
				failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid",
				"'".$name."','".$alias."','".$dname."','".$addr."','".$checkcommand."','".$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
				($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".$notifperiod."','".
				$notifintval."','".($hostoptd == "on" ? 1 : 0)."','".($hostoptu == "on" ? 1 : 0)."','".($hostoptr == "on" ? 1 : 0)."','".($hostoptf == "on" ? 1 : 0)."','".
				($hostopts == "on" ? 1 : 0)."','".$ctg."','".($tpl == "on" ? 1 : 0)."','".($icon ? $icon : 0)."'");

			if($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_host_parents","name = '".$name."'");
			}
			if($parent && !in_array("none",$parent)) {
				$count = count($parent);
				for($i=0;$i<$count;$i++)
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_host_parents","name,parent","'".$name."','".$parent[$i]."'");
			}

			if($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","host = '".$name."' AND hosttype = '1'");
			}
			if($hg && is_array($hg)) {
				$count = count($hg);
				for($i=0;$i<$count;$i++)
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name,host,hosttype","'".$hg[$i]."','".$name."','1'");
			}
			FS::$dbMgr->CommitTr();
			
			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				echo $this->loc->s("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=2",true);
		}

		public function Remove() {
			if (!$this->canWrite()) {
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

			$this->removeFromDB($name);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEcho("err-fail-writecfg");
				return;
			}
			FS::$iMgr->ajaxEcho("Done","hideAndRemove('#h_".preg_replace("#[. ]#","-",$name)."');");
		}

		private $name;
		private $dname;
		private $icon;
		private $alias;
		private $addr;
		private $parentlist;

		private $checkcmd;
		private $checkperiod;
		private $checkintval;
		private $retcheckintval;
		private $maxcheck;
		private $eventhdlen;
		private $flapen;
		private $failpreden;
		private $perfdata;
		private $retstatus;
		private $retnonstatus;

		private $notifen;
		private $notifperiod;
		private $notifintval;
		private $ctg;
		private $hostoptd;
		private $hostoptu;
		private $hostoptr;
		private $hostoptf;
		private $hostopts;
	};
?>
