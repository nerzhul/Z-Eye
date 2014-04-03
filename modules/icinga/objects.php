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
				return FS::$iMgr->printError("err-no-right");
			}
			$this->Load($name);

			$output = FS::$iMgr->cbkForm("10").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxIdLine("Name","name",$this->name,array("length" => 60, "size" => 30)).
				FS::$iMgr->idxLine("Alias","alias",array("value" => $this->alias,"length" => 60, "size" => 30));

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
			$this->notifen = true; $this->notifstrategy = "";

			if($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,
					"dname,alias,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,failpreden,
					perfdata,retstatus,retnonstatus,notifen,contactgroup,template,iconid,notif_strategy","name = '".$name."'");
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
					$this->ctg = $data["contactgroup"];	
					$this->notifen = ($data["notifen"] == 't');	
					$this->notifstrategy = $data["notif_strategy"];
				}
				else {
					return false;
				}
				$this->parentlist = array();
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_host_parents","parent","name = '".$name."'");
				while($data = FS::$dbMgr->Fetch($query)) {
					$this->parentlist[] = $data["parent"];
				}
			}
			return true;
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
				FS::$iMgr->idxIdLine("Name","name",$this->name).
				FS::$iMgr->idxLines(array(
					array("is-template","istemplate",array("value" => false,"type" => "chk")),
					array("Alias","alias",array("value" => $this->alias)),
					array("DisplayName","dname",array("value" => $this->dname)),
					array("Icon","",array("type" => "raw", "value" => 
						FS::$iMgr->select("icon").
						FS::$iMgr->selElmt("Aucun","",($this->icon == "")).
						FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_icons","id",
						array("labelfield" => "name","selected" => array($this->icon)))."</select>"))
				)).
				"<tr><td>".$this->loc->s("Parent")."</td><td>";

			$output2 = FS::$iMgr->selElmt($this->loc->s("None"),"none",(count($this->parentlist) == 0));
			$countElmt = 0;

			$query = FS::$dbMgr->Select($this->sqlTable,"name,addr","template = 'f'",array("order" => "name"));
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$output2 .= FS::$iMgr->selElmt($data["name"]." (".$data["addr"].")",$data["name"],in_array($data["name"],$this->parentlist));
			}

			if($countElmt/4 < 4) {
				$countElmt = 16;
			}
			
			$output .= FS::$iMgr->select("parent",array("multi" => true, "size" => round($countElmt/4))).
				$output2."</select></td></tr>".
				FS::$iMgr->idxLine("Address","addr",array("value" => $this->addr));

			$hglist = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name","host = '".$name."' AND hosttype = '1'");
				while($data = FS::$dbMgr->Fetch($query))
					$hglist[] = $data["name"];
			}

			$output .= FS::$iMgr->idxLines(array(
				array("Hostgroups","",array("type" => "raw", "value" => $this->mod->getHostOrGroupList("hostgroups",true,$hglist,"",true))),

			// Checks
				array("alivecommand","",array("type" => "raw", "value" => $this->mod->genCommandList("checkcommand",$this->checkcmd))),
				array("checkperiod","",array("type" => "raw", 
					"value" => (new icingaTimePeriod)->getSelect(array(
						"name" => "checkperiod",
						"selected" => $this->checkperiod
					))
				)),
				array("check-interval","checkintval",array("value" => $this->checkintval, "type" => "num")),
				array("retry-check-interval","retcheckintval",array("value" => $this->retcheckintval, "type" => "num")),
				array("max-check","maxcheck",array("value" => $this->maxcheck, "type" => "num")),

			// Global
				array("eventhdl-en","eventhdlen",array("value" => $this->eventhdlen,"type" => "chk")),
				array("flap-en","flapen",array("value" => $this->flapen,"type" => "chk")),
				array("failpredict-en","failpreden",array("value" => $this->failpreden,"type" => "chk")),
				array("perfdata","perfdata",array("value" => $this->perfdata,"type" => "chk")),
				array("retainstatus","retstatus",array("value" => $this->retstatus,"type" => "chk")),
				array("retainnonstatus","retnonstatus",array("value" => $this->retnonstatus,"type" => "chk")),

			// Notifications
				array("notif-en","notifen",array("value" => $this->notifen,"type" => "chk")),
				array("Notification-strategy","",array("type" => "raw", "value" => 
					(new icingaNotificationStrategy())->getSelect(array(
					"name" => "notifstr",
					"selected" => $this->notifstrategy
				)))),
				array("Contactgroups","",array("type" => "raw", "value" => $this->mod->genContactGroupsList("ctg",$this->ctg)))
			));
			// icon image
			// statusmap image
			$output .= FS::$iMgr->aeTableSubmit($this->name == "");
			return $output;
		}

		public function showSensors($name) {
			if (!$this->Load($name)) {
				return $this->loc->s("err-bad-datas");
			}
			
			return (new icingaSensor())->showReport($name);
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
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$ctg = FS::$secMgr->getPost("ctg","w");
			
			if(!$name || (!FS::$secMgr->isDNSName($name) && !FS::$secMgr->isHostname($name)) || 
				!$alias || !$dname || !$addr || !$checkcommand || !$checkperiod ||
				 !$ctg || $icon && !FS::$secMgr->isNumeric($icon) || $edit && $edit != 1) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
		
			// Checks
			$tpl = FS::$secMgr->checkAndSecurisePostData("istemplate");
			$eventhdlen = FS::$secMgr->checkAndSecurisePostData("eventhdlen");
			$flapen = FS::$secMgr->checkAndSecurisePostData("flapen");
			$failpreden = FS::$secMgr->checkAndSecurisePostData("failpreden");
			$perfdata = FS::$secMgr->checkAndSecurisePostData("perfdata");
			$retstatus = FS::$secMgr->checkAndSecurisePostData("retstatus");
			$retnonstatus = FS::$secMgr->checkAndSecurisePostData("retnonstatus");
			$notifen = FS::$secMgr->checkAndSecurisePostData("notifen");
			$notifstr = FS::$secMgr->checkAndSecurisePostData("notifstr");
			
			// Numerics
			$checkintval = FS::$secMgr->getPost("checkintval","n+");
			$retcheckintval = FS::$secMgr->getPost("retcheckintval","n+");
			$maxcheck = FS::$secMgr->getPost("maxcheck","n+");

			if($checkintval == NULL || $retcheckintval == NULL || $maxcheck == NULL) {
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
			
			if (!(new icingaNotificationStrategy())->exists($notifstr)) {
				FS::$iMgr->ajaxEcho(sprintf("err-notification-strategy-not-exists",$notifstr),"",true);
				return;
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
			
			if(!(new icingaTimePeriod())->exists($checkperiod)) {
				FS::$iMgr->ajaxEcho(sprintf(
					$this->loc->s("err-timeperiod-not-exists"), $checkperiod),
					"",false);
				return;
			}

			FS::$dbMgr->BeginTr();
			
			if($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
			}
			
			FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,
				failpreden,perfdata,retstatus,retnonstatus,notifen,notifperiod,notifintval,hostoptd,hostoptu,hostoptr,hostoptf,hostopts,contactgroup,template,iconid,notif_strategy",
				"'".$name."','".$alias."','".$dname."','".$addr."','".$checkcommand."','".$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
				($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".
				$ctg."','".($tpl == "on" ? 1 : 0)."','".($icon ? $icon : 0)."','".$notifstr."'");

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
		private $notifstrategy;
		
		private $ctg;
	};
	
	final class icingaService extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_services";
			$this->readRight = "mrule_icinga_srv_write";
			$this->writeRight = "mrule_icinga_srv_write";
		}
		
		protected function Load($name = "") {
			$this->name = $name;
			
			$this->hosttype = ""; $this->host = "";
			$this->actcheck = true; $this->pascheck = true; $this->parcheck = true; $this->obsess = true; $this->freshness = false;
			$this->notifen = true; $this->eventhdlen = true; $this->flapen = true; $this->failpreden = true; $this->perfdata = true;
			$this->retstatus = true; $this->retnonstatus = true;
			$this->checkcmd = ""; $this->checkperiod = ""; $this->checkintval = 3; $this->retcheckintval = 1; $this->maxcheck = 10;
			$this->ctg = "";
			if ($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,"host,hosttype,ctg,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
					retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,
					ctg,template,notif_strategy",
					"name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->host = $data["host"];
					$this->hosttype = $data["hosttype"];
					$this->actcheck = ($data["actcheck"] == 't');
					$this->pascheck = ($data["pascheck"] == 't');
					$this->parcheck = ($data["parcheck"] == 't');
					$this->obsess = ($data["obsess"] == 't');
					$this->freshness = ($data["freshness"] == 't');
					$this->notifen = ($data["notifen"] == 't');
					$this->eventhdlen = ($data["eventhdlen"] == 't');
					$this->flapen = ($data["flapen"] == 't');
					$this->failpreden = ($data["failpreden"] == 't');
					$this->perfdata = ($data["perfdata"] == 't');
					$this->retstatus = ($data["retstatus"] == 't');
					$this->retnonstatus = ($data["retnonstatus"] == 't');
					$this->checkcmd = $data["checkcmd"];
					$this->checkperiod = $data["checkperiod"];
					$this->checkintval = $data["checkintval"];
					$this->retcheckintval = $data["retcheckintval"];
					$this->maxcheck = $data["maxcheck"];
					$this->ctg = $data["ctg"];
					$this->notifstrategy = $data["notif_strategy"];
				}
				else {
					return false;
				}
			}
			return true;
		}
		
		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$this->Load($name);

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("16").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxLines(array(
				array("is-template","istemplate",array("value" => false,"type" => "chk")),
				array("Description","desc",array("type" => "idxedit",
					"value" => $name, "length" => 120,
					"size" => 30, "edit" => $name != ""
				)),
				array("Host","",array("type" => "raw", "value" =>
					$this->mod->getHostOrGroupList("host",false,($this->hosttype && $this->host ? array($this->hosttype."$".$this->host) : array()))
				)),
				array("active-check-en","actcheck",array("value" => $this->actcheck,"type" => "chk")),
				array("passive-check-en","pascheck",array("value" => $this->pascheck,"type" => "chk")),
				array("parallel-check","parcheck",array("value" => $this->parcheck,"type" => "chk")),
				array("obs-over-srv","obsess",array("value" => $this->obsess,"type" => "chk")),
				array("check-freshness","freshness",array("value" => $this->freshness,"type" => "chk")),
				
				array("eventhdl-en","eventhdlen",array("value" => $this->eventhdlen,"type" => "chk")),
				array("flap-en","flapen",array("value" => $this->flapen,"type" => "chk")),
				array("failpredict-en","failpreden",array("value" => $this->failpreden,"type" => "chk")),
				array("perfdata","perfdata",array("value" => $this->perfdata,"type" => "chk")),
				array("retainstatus","retstatus",array("value" => $this->retstatus,"type" => "chk")),
				array("retainnonstatus","retnonstatus",array("value" => $this->retnonstatus,"type" => "chk")),
				array("checkcmd","",array("type" => "raw", "value" =>
					$this->mod->genCommandList("checkcmd",$this->checkcmd))),
				array("checkperiod","",array("type" => "raw", "value" =>
					(new icingaTimePeriod())->getSelect(array(
						"name" => "checkperiod",
						"selected" => $this->checkperiod
				)))),
				array("check-interval","checkintval",array("value" => $this->checkintval, "type" => "num")),
				array("retry-check-interval","retcheckintval",array("value" => $this->retcheckintval, "type" => "num")),
				array("max-check","maxcheck",array("value" => $this->maxcheck, "type" => "num")),
				array("notif-en","notifen",array("value" => $this->notifen,"type" => "chk")),
				array("Notification-strategy","",array("type" => "raw", "value" => 
					(new icingaNotificationStrategy())->getSelect(array(
					"name" => "notifstr",
					"selected" => $this->notifstrategy
				)))),
				array("Contactgroups","",array("type" => "raw", "value" =>
					$this->mod->genContactGroupsList("ctg",$this->ctg)))
				)).
				FS::$iMgr->aeTableSubmit($name == "");
			return $output;
		}
		
		public function showSensors($sname) {
			if (!$this->Load($sname)) {
				return $this->loc->s("err-bad-datas");
			}
			
			$states = (new icingaBroker())->readStates();
			
			$totalSensors = 0;
			$totalPbs = 0;
			$totalCrits = 0;
			$totalWarns = 0;
			
			$output = "";
			
			foreach ($states as $host => $hos) {
				foreach ($states[$host] as $hos => $hosvalues) {
					if ($hos == "servicestatus") {
						// Loop sensors
						foreach ($hosvalues as $sensor => $svalues) {
							// We only need this sensor
							if ($sensor != $this->name) {
								continue;
							}
							
							$totalSensors++;
							
							$outstate = "";
							$stylestate = "";
							$timedown = $this->loc->s("Since-icinga-start");

							if ($svalues["current_state"] == 1) {
								$outstate = $this->loc->s("Warn");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}
								$totalWarns++;
								$totalPbs++;
							}
							else if ($svalues["current_state"] == 2) {
								$outstate = $this->loc->s("Critical");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}

								$totalCrits++;
								$totalPbs++;
							}
							else {
								$outstate = $this->loc->s("OK");
							}
							
							$output .= "<tr style=\"background-color:";
							if ($svalues["current_state"] == 1) {
								$output .= "orange";
							}
							else if ($svalues["current_state"] == 2) {
								$output .= "red";
							}
							else {
								$output .= "green";
							}
							
							$plugOut = $svalues["plugin_output"];
							if (isset($svalues["long_plugin_output"]) && strlen($svalues["long_plugin_output"]) > 0) {
								$plugOut .= "<br />".$svalues["long_plugin_output"];
							}
							$plugOut = preg_replace("#\\\n#","<br />",$plugOut);
							
							$output .= ";\"><td>".$host."</td><td>".$outstate.
								"</td><td>".$timedown."</td><td>".$plugOut."</td></tr>"; 
						}
					}
				}
			}
			
			return sprintf("<table><b>%s</b>%s<br /><b>%s</b>%s (<b>%s</b>: %s / <b>%s</b>: %s)%s</table>",
				$this->loc->s("total-sensors"), $totalSensors,
				$this->loc->s("total-problems"), $totalPbs,
				$this->loc->s("Critical"), $totalCrits,
				$this->loc->s("Warn"), $totalWarns,
				$output);
		}
		
		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			}

			$name = trim(FS::$secMgr->checkAndSecurisePostData("desc"));
			$host = FS::$secMgr->checkAndSecurisePostData("host");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$checkcmd = FS::$secMgr->checkAndSecurisePostData("checkcmd");
			$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
			$ctg = FS::$secMgr->getPost("ctg","w");

			if (!$name || preg_match("#[\(]|[\)]|[\[]|[\]]#",$name) || !$host || !$checkcmd || !$checkperiod || !$ctg) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			if ($edit) {
				if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
					FS::$iMgr->ajaxEcho("err-data-not-exist");
					return;
				}
			}
			else {
				if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
					FS::$iMgr->ajaxEcho("err-data-exist");
					return;
				}
			}

			$actcheck = FS::$secMgr->checkAndSecurisePostData("actcheck");
			$pascheck = FS::$secMgr->checkAndSecurisePostData("pascheck");
			$parcheck = FS::$secMgr->checkAndSecurisePostData("parcheck");
			$obsess = FS::$secMgr->checkAndSecurisePostData("obsess");
			$freshness = FS::$secMgr->checkAndSecurisePostData("freshness");
			$notifen = FS::$secMgr->checkAndSecurisePostData("notifen");
			$notifstr = FS::$secMgr->checkAndSecurisePostData("notifstr");

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

			if ($checkintval == NULL || $retcheckintval == NULL || $maxcheck == NULL) {
				echo $this->loc->s("err-bad-data");
				return;
			}
			
			$mt = preg_split("#[$]#",$host);
			if (count($mt) != 2 || ($mt[0] != 1 && $mt[0] != 2)) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcmd."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			
			if (!(new icingaNotificationStrategy())->exists($notifstr)) {
				FS::$iMgr->ajaxEcho(sprintf("err-notification-strategy-not-exists",$notifstr),"",true);
				return;
			}

			if ($mt[0] == 1 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			if ($mt[0] == 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$mt[1]."'")) {
				FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}

			if ($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_services","name = '".$name."'");
			}
			
			FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_services","name,host,hosttype,actcheck,pascheck,parcheck,obsess,freshness,notifen,eventhdlen,flapen,failpreden,perfdata,
				retstatus,retnonstatus,checkcmd,checkperiod,checkintval,retcheckintval,maxcheck,ctg,template,notif_strategy",
				"'".$name."','".$mt[1]."','".$mt[0]."','".($actcheck == "on" ? 1 : 0)."','".($pascheck == "on" ? 1 : 0)."','".($parcheck == "on" ? 1 : 0)."','".($obsess == "on" ? 1 : 0).
				"','".($freshness == "on" ? 1 : 0)."','".($notifen == "on" ? 1 : 0)."','".($eventhdlen == "on" ? 1 : 0)."','".($flapen == "on" ? 1 : 0)."','".
				($failpreden == "on" ? 1 : 0)."','".($perfdata == "on" ? 1 : 0)."','".($retstatus == "on" ? 1 : 0)."','".($retnonstatus == "on" ? 1 : 0)."','".$checkcmd."','".
				$checkperiod."','".$checkintval."','".$retcheckintval."','".$maxcheck."','".$ctg."','".
				($tpl == "on" ? 1 : 0)."','".$notifstr."'");

			$icingaAPI = new icingaBroker();
			
			if (!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEcho("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=4",true);
		}
		
		private $name;
		
		private $host;
		private $hosttype;
		
		private $actcheck;
		private $pascheck;
		private $parcheck;
		private $obsess;
		private $freshness;
		
		private $eventhdlen;
		private $flapen;
		private $failpreden;
		private $perfdata;
		private $retstatus;
		private $retnonstatus;
		private $checkcmd;
		private $checkperiod;
		private $checkintval;
		private $retcheckintval;
		private $maxcheck;
		
		private $notifen;
		private $notifstrategy;
		
		private $ctg;
		
		private $template;
	};
	
	final class icingaSensor extends FSMObj {
		function __construct() {
			parent::__construct();
		}
		
		public function showReport($hostFilter="") {
			$states = (new icingaBroker())->readStates();
			if ($hostFilter && !isset($states[$hostFilter])) {
				return FS::$iMgr->printError("err-no-sensor");
			}
			
			$totalSensors = 0;
			$totalPbs = 0;
			$totalCrits = 0;
			$totalWarns = 0;
			
			$output = "<table>";
			
			// Loop types
			foreach ($states as $host => $gvalues) {
				// If there is a host filter, show only this host
				if ($hostFilter && $hostFilter != $name) {
					continue;
				}
				
				ksort($states[$host]);
				
				$showHostname = true;
				
				foreach ($states[$host] as $hos => $hosvalues) {
					if ($hos == "servicestatus") {
						// Loop sensors
						foreach ($hosvalues as $sensor => $svalues) {
							$totalSensors++;
							
							$outstate = "";
							$stylestate = "";
							$timedown = $this->loc->s("Since-icinga-start");

							if ($svalues["current_state"] == 1) {
								$outstate = $this->loc->s("Warn");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}
								$totalWarns++;
								$totalPbs++;
							}
							else if ($svalues["current_state"] == 2) {
								$outstate = $this->loc->s("Critical");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}

								$totalCrits++;
								$totalPbs++;
							}
							else {
								$outstate = $this->loc->s("OK");
							}
							
							$output .= "<tr style=\"background-color:";
							if ($svalues["current_state"] == 1) {
								$output .= "orange";
							}
							else if ($svalues["current_state"] == 2) {
								$output .= "red";
							}
							else {
								$output .= "green";
							}
							
							$plugOut = $svalues["plugin_output"];
							if (isset($svalues["long_plugin_output"]) && strlen($svalues["long_plugin_output"]) > 0) {
								$plugOut .= "<br />".$svalues["long_plugin_output"];
							}
							$plugOut = preg_replace("#\\\n#","<br />",$plugOut);
							
							$output .= ";\"><td>";
							
							/*
							 * If no host filter we need to see the node
							 * We only show the host name when it's the first sensor
							 */
							
							if ($hostFilter == "") {
								$output .= ($showHostname ? $host : "")."</td><td>";
							}
							
							$output .= $sensor."</td><td>".$outstate.
								"</td><td>".$timedown."</td><td>".$plugOut."</td></tr>"; 
						}
					}
					else if ($hos == "hoststatus") {
						$totalSensors++;
							
						$outstate = "";
						$stylestate = "";
						$timedown = $this->loc->s("Since-icinga-start");

						if ($hosvalues["current_state"] == 1) {
							$outstate = $this->loc->s("Down");
							if ($hosvalues["last_time_up"])
								$timedown = FSTimeMgr::timeSince($hosvalues["last_time_up"]);

							$totalCrits++;
							$totalPbs++;
						}
						else {
							$outstate = $this->loc->s("Up");
						}
						
						$output .= "<tr style=\"background-color:";
						if ($hosvalues["current_state"] == 1) {
							$output .= "red";
						}
						else {
							$output .= "green";
						}
						$plugOut = $hosvalues["plugin_output"];
						if (isset($hosvalues["long_plugin_output"]) && strlen($hosvalues["long_plugin_output"]) > 0) {
							$plugOut .= "<br />".$hosvalues["long_plugin_output"];
						}
						$plugOut = preg_replace("#\\\n#","<br />",$plugOut);
							
						$output .= ";\"><td>";
						
						/*
						 * If no host filter we need to see the node
						 * We only show the host name when it's the first sensor
						 */
						
						if ($hostFilter == "") {
							$output .= ($showHostname ? $host : "")."</td><td>";
						}
						
						$output .= $this->loc->s("Availability")."</td><td>".$outstate.
							"</td><td>".$timedown."</td><td>".$plugOut."</td></tr>"; 
					}
					$showHostname = false;
				}
			}
			
			$output = "<b>".$this->loc->s("total-sensors")."</b>".$totalSensors."<br />".
				"<b>".$this->loc->s("total-problems")."</b>".$totalPbs.
				" ("."<b>".$this->loc->s("Critical")."</b>: ".$totalCrits." / <b>".
				$this->loc->s("Warn")."</b>: ".$totalWarns.")".$output;
				
			$output .= "</table>";
			return $output;
		}
	};
	
	final class icingaTimePeriod extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_timeperiods";
			$this->sqlAttrId = "name";
			$this->readRight = "mrule_icinga_tp_write";
			$this->writeRight = "mrule_icinga_tp_write";
		}
		
		protected function Load($name = "") {
			// @TODO
		}
		
		public function getSelect($options = array()) {
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));
			
			return FS::$iMgr->select($options["name"]).
				FS::$iMgr->selElmtFromDB($this->sqlTable,"name",
					array("labelfield" => "alias",
						"selected" => array($selected),
						"sqlopts" => array("order" => "alias"))).
				"</select>";
		}
	};
			
	final class icingaNotificationStrategy extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_notif_strategy";
			$this->sqlAttrId = "name";
			$this->readRight = "mrule_icinga_notif_write";
			$this->writeRight = "mrule_icinga_notif_write";
		}
		
		protected function Load($name = "") {
			$this->name = $name;
			
			$this->alias = "";
			$this->interval = 0; $this->period = "";
			$this->upDownEvent = true; $this->criticalEvent = true;
			$this->warningEvent = true; $this->unavailableEvent = true;
			$this->flappingEvent = true; $this->recoveryEvent = true;
			$this->scheduledEvent = true;
			
			if ($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,"alias, interval, period, ev_updown, ev_crit, ev_warn, ev_unavailable, ev_flap, ev_recovery, ev_scheduled",
					"name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->alias = $data["alias"];
					$this->interval = $data["interval"];
					$this->period = $data["period"];
					$this->upDownEvent = ($data["ev_updown"] == 't');
					$this->criticalEvent = ($data["ev_crit"] == 't');
					$this->warningEvent = ($data["ev_warn"] == 't');
					$this->unavailableEvent = ($data["ev_unavailable"] == 't');
					$this->flappingEvent = ($data["ev_flap"] == 't');
					$this->recoveryEvent = ($data["ev_recovery"] == 't');
					$this->scheduledEvent = ($data["ev_scheduled"] == 't');
				}
				else {
					return false;
				}
			}
			return true;
		}
		
		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			}
			
			$name = FS::$secMgr->getPost("name","w");
			$alias = FS::$secMgr->checkAndSecurisePostData("alias");
			$hostoptd = FS::$secMgr->checkAndSecurisePostData("hostoptd");
			$srvoptc = FS::$secMgr->checkAndSecurisePostData("srvoptc");
			$srvoptw = FS::$secMgr->checkAndSecurisePostData("srvoptw");
			$srvoptu = FS::$secMgr->checkAndSecurisePostData("srvoptu");
			$srvoptr = FS::$secMgr->checkAndSecurisePostData("srvoptr");
			$srvoptf = FS::$secMgr->checkAndSecurisePostData("srvoptf");
			$srvopts = FS::$secMgr->checkAndSecurisePostData("srvopts");
			$interval = FS::$secMgr->checkAndSecurisePostData("interval");
			$period = FS::$secMgr->checkAndSecurisePostData("period");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			
			if (!$name || !$alias || !$period || $interval === NULL ||
				!FS::$secMgr->isNumeric($interval)) {
				echo FS::$iMgr->ajaxEcho("err-bad-data");
				return;
			}
			
			// Now verify datas
			if($edit) {
				if(!FS::$dbMgr->GetOneData($this->sqlTable,"name",
					"name = '".$name."'")) {
					FS::$iMgr->ajaxEcho("err-data-not-exist");
					return;
				}
			}
			else {
				if(FS::$dbMgr->GetOneData($this->sqlTable,"name",
					"name = '".$name."'")) {
					FS::$iMgr->ajaxEchoNC("err-data-exist");
					return;
				}
			}
			
			// Check if TP exists
			if(!(new icingaTimePeriod())->exists($period)) {
				FS::$iMgr->ajaxEcho(sprintf(
					$this->loc->s("err-timeperiod-not-exists"), $period),
					"",false);
				return;
			}
			
			FS::$dbMgr->BeginTr();
			
			if($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			}
			
			FS::$dbMgr->Insert($this->sqlTable,"name,alias,interval,period,ev_updown,ev_crit,ev_warn,ev_unavailable,ev_flap,ev_recovery,ev_scheduled",
				"'".$name."','".$alias."','".$interval."','".$period."','".
				($hostoptd == "on" ? 't' : 'f')."','".($srvoptc == "on" ? 't' : 'f')."','".
				($srvoptw == "on" ? 't' : 'f')."','".($srvoptu == "on" ? 't' : 'f')."','".
				($srvoptf == "on" ? 't' : 'f')."','".($srvoptr == "on" ? 't' : 'f')."','".
				($srvopts == "on" ? 't' : 'f')."'");
				
			FS::$dbMgr->CommitTr();
			
			$icingaAPI = new icingaBroker();
			
			// Write icinga configuration
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEcho("err-fail-writecfg");
				return;
			}
			
			FS::$iMgr->redir("mod=".$this->mid."&sh=9",true);
		}
		
		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->ajaxEcho("err-no-right");
				return;
			}
			
			$name = FS::$secMgr->checkAndSecuriseGetData("notifstr");
			
			if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
				FS::$iMgr->ajaxEcho("err-data-not-exist");
				return;
			}
			
			if ($hostUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts",
				"name","notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-notification-strategy-used-host"),
					$name,$hostUsed),"",true);
				return;
			}
			
			if ($srvUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services",
				"name","notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEcho(sprintf($this->loc->s("err-notification-strategy-used-service"),
					$name,$srvUsed),"",true);
				return;
			}
			
			$this->removeFromDB($name);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoNC("err-fail-writecfg");
				return;
			}
			
			FS::$iMgr->ajaxEcho("Done","hideAndRemove('#notifstr_".preg_replace("#[. ]#","-",$name)."');");
		}
		
		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			FS::$dbMgr->CommitTr();
		}
		
		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printError("err-no-right");
			}
			
			$this->Load($name);

			$tpSelect = (new icingaTimePeriod())->getSelect(
				array("name" => "period",
					"selected" => $this->period
				)
			);
			
			return FS::$iMgr->cbkForm("22")."<table>".
				FS::$iMgr->idxLines(array(
					array("Name","name",array("type" => "idxedit", 
						"value" => $this->name, "length" => 60,
						"size" => 30, "edit" => $name != "")),
					array("Alias","alias",array("value" => $this->alias, "length" => 60, "size" => 30)),
					array("hostoptdown","hostoptd",array("value" => $this->upDownEvent,"type" => "chk")),
					array("srvoptcrit","srvoptc",array("value" => $this->criticalEvent,"type" => "chk")),
					array("srvoptwarn","srvoptw",array("value" => $this->warningEvent,"type" => "chk")),
					array("srvoptunreach","srvoptu",array("value" => $this->unavailableEvent,"type" => "chk")),
					array("srvoptrec","srvoptr",array("value" => $this->recoveryEvent,"type" => "chk")),
					array("srvoptflap","srvoptf",array("value" => $this->flappingEvent,"type" => "chk")),
					array("srvoptsched","srvopts",array("value" => $this->scheduledEvent,"type" => "chk")),
					array("notif-interval","interval",array("value" => $this->interval, "type" => "num")),
					array("notifperiod","",array("value" => $tpSelect, "type" => "raw"))
				)).
				FS::$iMgr->aeTableSubmit($this->name != "");
		}
		
		public function getSelect($options = array()) {
			$selected = (isset($options["selected"]) ? $options["selected"] : array("none"));
			
			return FS::$iMgr->select($options["name"]).
				FS::$iMgr->selElmtFromDB($this->sqlTable,"name",
					array("labelfield" => "alias",
						"selected" => array($selected),
						"sqlopts" => array("order" => "alias"))).
				"</select>";
		}
		
		private $name;
		private $alias;
		
		private $interval;
		private $period;
		
		// Boolean values
		private $upDownEvent;
		private $criticalEvent;
		private $warningEvent;
		private $unavailableEvent;
		private $flappingEvent;
		private $recoveryEvent;
		private $scheduledEvent;
	};
?>
