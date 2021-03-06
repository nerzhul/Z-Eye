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
	
	final class icingaContact extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_contacts";
			$this->sqlAttrId = "name";
			$this->readRight = "ct_write";
			$this->writeRight = "ct_write";
		}
		
		protected function Load($name = "") {
			$this->name = $name;
			$this->mail = "";
			$this->template = false;
			$this->srvnotifcmd = "notify-service-by-email";
			$this->hostnotifcmd = "notify-host-by-email";
			$this->hostnotifstrategy = "";
			$this->servicenotifstrategy = "";
			
			if ($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,"mail,template,srvcmd,hostcmd,template,host_notif_strategy,service_notif_strategy",
					"name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->mail = $data["mail"];
					$this->template = $data["template"] == 't';
					$this->srvnotifcmd = $data["srvcmd"];
					$this->hostnotifcmd = $data["hostcmd"];
					$this->hostnotifstrategy = $data["host_notif_strategy"];
					$this->servicenotifstrategy = $data["service_notif_strategy"];
				}
			}
		}
		
		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show contact informations");
			}
			
			$this->Load($name);
			
			return FS::$iMgr->cbkForm("7").
				"<table><tr><th>"._("Option")."</th><th>"._("Value")."</th></tr>".
				FS::$iMgr->idxLines(array(
					array("Name","name",array("type" => "idxedit", "value" => $this->name, "edit" => $this->name != "")),
					array("Email","mail",array("value" => $this->mail)),
					array("Notification-strategy-services","",array("type" => "raw", "value" => 
						(new icingaNotificationStrategy())->getSelect(array(
							"name" => "snotifstr",
							"selected" => $this->servicenotifstrategy
					)))),
					array("srvnotifcmd","",array("type" => "raw", "value" =>
						$this->mod->genCommandList("srvnotifcmd",$this->srvnotifcmd))),
					array("Notification-strategy-hosts","",array("type" => "raw", "value" => 
						(new icingaNotificationStrategy())->getSelect(array(
							"name" => "hnotifstr",
							"selected" => $this->hostnotifstrategy
					)))),
					array("hostnotifcmd","",array("type" => "raw", "value" =>
						$this->mod->genCommandList("hostnotifcmd",$this->hostnotifcmd)))
				)).
				FS::$iMgr->aeTableSubmit($name == "");
		}
		
		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a contact");
				return;
			} 

			$name = FS::$secMgr->getPost("name","w");
			$mail = FS::$secMgr->checkAndSecurisePostData("mail");
			$srvnotifcmd = FS::$secMgr->checkAndSecurisePostData("srvnotifcmd");
			$hostnotifcmd = FS::$secMgr->checkAndSecurisePostData("hostnotifcmd");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$snotifstr = FS::$secMgr->checkAndSecurisePostData("snotifstr");
			$hnotifstr = FS::$secMgr->checkAndSecurisePostData("hnotifstr");
			$istpl = FS::$secMgr->checkAndSecurisePostData("istemplate");
			
			if (!$name || !$mail || preg_match("#[ ]#",$name) ||
				!$srvnotifcmd ||
				!$hostnotifcmd || !$snotifstr || !$hnotifstr) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}	

			if ($edit) {
				// If contact doesn't exist
				if (!$this->exists($name)) {
					FS::$iMgr->ajaxEchoError("err-data-not-exist");
					return;
				}
			}
			else {
				// If contact exist
				if ($this->exists($name)) {
					FS::$iMgr->ajaxEchoError("err-data-exist");
					return;
				}
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$srvnotifcmd."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$hostnotifcmd."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			if (!(new icingaNotificationStrategy())->exists($hnotifstr)) {
				FS::$iMgr->ajaxEchoError(sprintf("err-notification-strategy-not-exists",$hnotifstr),"",true);
				return;
			}
			
			if (!(new icingaNotificationStrategy())->exists($snotifstr)) {
				FS::$iMgr->ajaxEchoError(sprintf("err-notification-strategy-not-exists",$snotifstr),"",true);
				return;
			}

			FS::$dbMgr->BeginTr();
			
			if ($edit) {
				FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			}
			
			FS::$dbMgr->Insert($this->sqlTable,
				"name,mail,template,srvcmd,hostcmd,host_notif_strategy,service_notif_strategy",
				"'".$name."','".$mail."','".($istpl == "on" ? 1 : 0)."','".
				$srvnotifcmd."','".$hostnotifcmd."','".
				$hnotifstr."','".$snotifstr."'");

			FS::$dbMgr->CommitTr();
			
			$icingaAPI = new icingaBroker();
			if (!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=6",true);
		}
		
		private $name;
		private $mail;
		private $template;
		private $servicenotifstrategy;
		private $srvnotifcmd;
		private $hostnotifstrategy;
		private $hostnotifcmd;
	}

	final class icingaCtg extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_contactgroups";
			$this->readRight = "ctg_write";
			$this->writeRight = "ctg_write";
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
				return FS::$iMgr->printNoRight("show contact informations");
			}
			$this->Load($name);

			$output = FS::$iMgr->cbkForm("10").
				"<table><tr><th>"._("Option")."</th><th>"._("Value")."</th></tr>".
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

			$output .= "<tr><td>"._("Contacts")."</td><td>".
				FS::$iMgr->select("cts",array("multi" => true, "size" => round($countElmt/4))).
				$output2."</select></td></tr>".
				FS::$iMgr->aeTableSubmit($this->name == "");
			return $output;
		}

		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a contactgroup");
				return;
			}

			$name = FS::$secMgr->getPost("name","w");
			$alias = FS::$secMgr->checkAndSecurisePostData("alias");
			$cts = FS::$secMgr->checkAndSecurisePostData("cts");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");

			if(!$name || !$alias || !$cts || $cts == "") {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			// ctg exists
			if($edit) {
				if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoError("err-data-not-exist");
					return;
				}
			}
			else {
				if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contactgroups","alias","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoError("err-data-exist");
					return;
				}
			}

			// some members don't exist
			$count = count($cts);
			for($i=0;$i<$count;$i++) {
				if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts","mail","name = '".$cts[$i]."'")) {
					FS::$iMgr->ajaxEchoError("err-bad-data");
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
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=7",true);
		}

		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a contactgroup");
				return;
			} 

			// @TODO forbid remove when used (service, service_group)
			$ctgname = FS::$secMgr->checkAndSecuriseGetData("ctg");
			if(!$ctgname) {
				FS::$iMgr->ajaxEchoErrorNC("err-bad-data");
				return;
			}

			if(!FS::$dbMgr->GetOneData($this->sqlTable,"alias","name = '".$ctgname."'")) {
					FS::$iMgr->ajaxEchoErrorNC("err-bad-data");
				return;
			}

			if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","contactgroup = '".$ctgname."'")) {
				FS::$iMgr->ajaxEchoErrorNC("err-ctg-used");
				return;
			}

			$this->removeFromDB($ctgname);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#ctg_".preg_replace("#[. ]#","-",$ctgname)."');");
		}

		private $name;
		private $alias;
		private $contacts;
	};

	final class icingaHost extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_hosts";
			$this->readRight = "host_write";
			$this->writeRight = "host_write";
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
				return FS::$iMgr->printNoRight("show host informations");
			}

			$this->Load($name);

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("13").
				"<table><tr><th>"._("Option")."</th><th>"._("Value")."</th></tr>".
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
				"<tr><td>"._("Parent")."</td><td>";

			$output2 = FS::$iMgr->selElmt(_("None"),"none",(count($this->parentlist) == 0));
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
				return _("err-bad-datas");
			}
			
			return (new icingaSensor())->showReport($name);
		}
		
		public function Modify() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify an host");
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
				FS::$iMgr->ajaxEchoError("err-bad-data");
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
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			// Now verify datas
			if($edit) {
				if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoError("err-data-not-exist");
					return;
				}
			}
			else {
				if(FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoErrorNC("err-data-exist");
					return;
				}
			}
			
			if (!(new icingaNotificationStrategy())->exists($notifstr)) {
				FS::$iMgr->ajaxEchoError(sprintf("err-notification-strategy-not-exists",$notifstr),"",true);
				return;
			}
			
			if($parent && !in_array("none",$parent)) {
				$count = count($parent);
				for($i=0;$i<$count;$i++) {
					if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$parent[$i]."'")) {
						FS::$iMgr->ajaxEchoError("err-bad-data");
						return;
					}
				}
			}
			
			if($hg && is_array($hg)) {
				$count = count($hg);
				for($i=0;$i<$count;$i++) {
					if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$hg[$i]."'")) {
						FS::$iMgr->ajaxEchoError("err-bad-data");
						return;
					}
				}
			}

			if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcommand."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			if(!(new icingaTimePeriod())->exists($checkperiod)) {
				FS::$iMgr->ajaxEchoError(sprintf(
					_("err-timeperiod-not-exists"), $checkperiod),
					"",false);
				return;
			}

			FS::$dbMgr->BeginTr();
			
			if($edit) {
				FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."icinga_hosts","name = '".$name."'");
			}
			
			FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."icinga_hosts","name,alias,dname,addr,alivecommand,checkperiod,checkinterval,retrycheckinterval,maxcheck,eventhdlen,flapen,
				failpreden,perfdata,retstatus,retnonstatus,notifen,contactgroup,template,iconid,notif_strategy",
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
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			FS::$iMgr->redir("mod=".$this->mid."&sh=2",true);
		}

		public function Remove() {
			if (!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove an host");
				return;
			} 

			$name = FS::$secMgr->checkAndSecuriseGetData("host");
			if(!$name) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			// Not exists
			if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","addr","name = '".$name."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			$this->removeFromDB($name);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#h_".preg_replace("#[. ]#","-",$name)."');");
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
			$this->readRight = "srv_write";
			$this->writeRight = "srv_write";
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
				return FS::$iMgr->printNoRight("show service form");
			}
			
			$this->Load($name);

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("16").
				"<table><tr><th>"._("Option")."</th><th>"._("Value")."</th></tr>".
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
				return _("err-bad-datas");
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
							$timedown = _("Since-icinga-start");

							if ($svalues["current_state"] == 1) {
								$outstate = _("Warn");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}
								$totalWarns++;
								$totalPbs++;
							}
							else if ($svalues["current_state"] == 2) {
								$outstate = _("Critical");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}

								$totalCrits++;
								$totalPbs++;
							}
							else {
								$outstate = _("OK");
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
				_("total-sensors"), $totalSensors,
				_("total-problems"), $totalPbs,
				_("Critical"), $totalCrits,
				_("Warn"), $totalWarns,
				$output);
		}
		
		public function Modify() {
			if(!$this->canWrite()) {
				FS::$iMgr->echoNoRights("modify a service");
				return;
			}

			$name = trim(FS::$secMgr->checkAndSecurisePostData("desc"));
			$host = FS::$secMgr->checkAndSecurisePostData("host");
			$edit = FS::$secMgr->checkAndSecurisePostData("edit");
			$checkcmd = FS::$secMgr->checkAndSecurisePostData("checkcmd");
			$checkperiod = FS::$secMgr->checkAndSecurisePostData("checkperiod");
			$ctg = FS::$secMgr->getPost("ctg","w");

			if (!$name || preg_match("#[\(]|[\)]|[\[]|[\]]#",$name) || !$host || !$checkcmd || !$checkperiod || !$ctg) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			if ($edit) {
				if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoError("err-data-not-exist");
					return;
				}
			}
			else {
				if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services","host","name = '".$name."'")) {
					FS::$iMgr->ajaxEchoError("err-data-exist");
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
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			$mt = preg_split("#[$]#",$host);
			if (count($mt) != 2 || ($mt[0] != 1 && $mt[0] != 2)) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_commands","name","name = '".$checkcmd."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}

			if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_timeperiods","name","name = '".$checkperiod."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			if (!(new icingaNotificationStrategy())->exists($notifstr)) {
				FS::$iMgr->ajaxEchoError(sprintf("err-notification-strategy-not-exists",$notifstr),"",true);
				return;
			}

			if ($mt[0] == 1 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts","name","name = '".$mt[1]."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			if ($mt[0] == 2 && !FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hostgroups","name","name = '".$mt[1]."'")) {
				FS::$iMgr->ajaxEchoError("err-bad-data");
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
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
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
							$timedown = _("Since-icinga-start");

							if ($svalues["current_state"] == 1) {
								$outstate = _("Warn");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}
								$totalWarns++;
								$totalPbs++;
							}
							else if ($svalues["current_state"] == 2) {
								$outstate = _("Critical");
								if ($svalues["last_time_ok"]) {
									$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
								}

								$totalCrits++;
								$totalPbs++;
							}
							else {
								$outstate = _("OK");
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
						$timedown = _("Since-icinga-start");

						if ($hosvalues["current_state"] == 1) {
							$outstate = _("Down");
							if ($hosvalues["last_time_up"])
								$timedown = FSTimeMgr::timeSince($hosvalues["last_time_up"]);

							$totalCrits++;
							$totalPbs++;
						}
						else {
							$outstate = _("Online");
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
						
						$output .= _("Availability")."</td><td>".$outstate.
							"</td><td>".$timedown."</td><td>".$plugOut."</td></tr>"; 
					}
					$showHostname = false;
				}
			}
			
			$output = "<b>"._("total-sensors")."</b>".$totalSensors."<br />".
				"<b>"._("total-problems")."</b>".$totalPbs.
				" ("."<b>"._("Critical")."</b>: ".$totalCrits." / <b>".
				_("Warn")."</b>: ".$totalWarns.")".$output;
				
			$output .= "</table>";
			return $output;
		}
		
		public function genDefaultScreenContainer() {
			$totalSensors = 0;
			$warnSensors = 0;
			$critSensors = 0;
			$oosSensors = 0;
			
			$alerts = array();
			
			$problems = array();
			$output = "";
			$outBuffer = "";
			
			if ($iStates = (new icingaBroker())->readStates(
				array("plugin_output","current_state","current_attempt",
				"max_attempts","state_type","last_time_ok",
				"last_time_up"))) {

			// Loop hosts
			foreach ($iStates as $host => $hostvalues) {
				// Loop types
				foreach ($hostvalues as $hos => $hosvalues) {
					if ($hos == "servicestatus") {
						// Loop sensors
						foreach ($hosvalues as $sensor => $svalues) {
							$totalSensors++;
							if ($svalues["current_state"] > 0) {
								$timedown = _("Since-icinga-start");
								$bgcolor = "orange";

								// Initialize host error array
								if (!isset($problems[$host])) {
									/*
									* Fields:
									* 1: label for accordion
									* 2: accordion buffer for this entry
									* 3: warning count
									* 4: critical count
									*/
									$problems[$host] = array($host,"",0,0);
								}

								if ($svalues["current_state"] == 1) {
									if ($svalues["last_time_ok"]) {
										$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
									}
									$problems[$host][2]++;
									$warnSensors++;
									
									$bgcolor = "orange";
								}
								else if ($svalues["current_state"] == 2) {
									if ($svalues["last_time_ok"]) {
										$timedown = FSTimeMgr::timeSince($svalues["last_time_ok"]);
									}

									$problems[$host][3]++;
									$critSensors++;
									
									$bgcolor = "red";
								}
									
								$oosSensors++;
								
								$problems[$host][1] .= sprintf(
									"<tr style=\"background-color:%s;\"><td>%s</td>
									<td>%s</td><td>%s</td></tr>",
	                                $bgcolor, $sensor, $timedown,
	                                $svalues["plugin_output"]
								);
										
							}
						}
					}
					else if ($hos == "hoststatus") {
						$totalSensors++;
						if ($hosvalues["current_state"] > 0) {
							$oosSensors++;
							$bgcolor = "orange";
							$timedown = _("Since-icinga-start");

							// Initialize host error array
							if (!isset($problems[$host])) {
								/*
								* Fields:
								* 1: label for accordion
								* 2: accordion buffer for this entry
								* 3: warning count
								* 4: critical count
								*/
								$problems[$host] = array($host,"",0,0);
							}
							
							if ($hosvalues["current_state"] == 1) {
								if ($hosvalues["last_time_up"])
									$timedown = FSTimeMgr::timeSince($hosvalues["last_time_up"]);

								$problems[$host][3]++;
								$this->criticinga++;
								
								$bgcolor = "red";
							}
							
							$problems[$host][1] .= sprintf(
								"<tr style=\"background-color:%s;\"><td>%s</td>
								<td>%s</td><td>%s</td></tr>",
	                            $bgcolor, _("Availability"),
	                            $timedown,
	                            $hosvalues["plugin_output"]
							);
						}
					}
				}
			}
			}

			if ($oosSensors > 0) {
				$outBuffer .= "<table>";
				foreach ($problems as $key => $values) {
					// Create the host Label
					$label = $problems[$key][0].": ".
						($problems[$key][2]+$problems[$key][3])." ".
						_("alert-s")." (";

					$bgcolor = "orange";
					
					if ($problems[$key][2] > 0) {
						$label .= $problems[$key][2]." "._("warning-s");
					}

					if ($problems[$key][3] > 0) {
						if ($problems[$key][2] > 0) {
							$label .= " / ";
						}
						$label .= $problems[$key][3]." "._("critical-s");
						$bgcolor = "red";
					}
					$label .= ")";
					
					$outBuffer .= sprintf("<tr style=\"background-color:%s;
						font-size:14px;font-weight:bold;\">
						<td colspan=\"3\">%s</td></tr>%s",
						$bgcolor, $label, $values[1]);
				}
				
				$outBuffer .= "</table>";

				$js = "";

				if (($oosSensors / $totalSensors) > 15.0 || $critSensors > 0) {
					$js = "$('#accicingah3').css('background-color','#4A0000');".
						"$('#accicingah3').css('background-image','linear-gradient(#4A0000, #8A0000)');".
						"$('#accicingah3').css('background-image','-webkit-linear-gradient(#4A0000, #8A0000)');";
				}
				else {
					$js = "$('#accicingah3').css('background-color','#ff8e00');".
						"$('#accicingah3').css('background-image','linear-gradient(#ff4e00, #ff8e00)');".
						"$('#accicingah3').css('background-image','-webkit-linear-gradient(#ff4e00, #ff8e00)');";
				}				
			}
			else {
				$js = "$('#accicingah3').css('background-color','#008A00');".
					"$('#accicingah3').css('background-image','linear-gradient(#004A00, #008A00)');".
					"$('#accicingah3').css('background-image','-webkit-linear-gradient(#004A00, #008A00)');";
			}
			
			FS::$iMgr->js($js);
			
			$alerts["icinga"] = array("<b>"._("state-srv")."</b> ".
				FS::$iMgr->progress("shealth",
					$totalSensors-$oosSensors,$totalSensors),
					$outBuffer);

			if ($oosSensors > 0) {
				$alerts["icinga"][0] .= "<br />".($oosSensors)." ".
					_("alert-on")." ".$totalSensors." ".
						_("sensors");
			}
			
			$output .= "<div id=\"speedreport\">".
				FS::$iMgr->accordion("icingarep",$alerts).
				"</div>";
				
				
			return $output;
		}
		
	};
	
	final class icingaTimePeriod extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_timeperiods";
			$this->sqlAttrId = "name";
			$this->readRight = "tp_write";
			$this->writeRight = "tp_write";
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
	
	final class icingaCommand extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_commands";
			$this->sqlAttrId = "name";
			$this->readRight = "cmd_write";
			$this->writeRight = "cmd_write";
		}
		
		protected function Load($name = "") {
			$this->name = $name;
			
			$this->cmd = "";
			$this->comment = "";
			$this->isSysCmd = false;
			
			if ($name) {
				$query = FS::$dbMgr->Select($this->sqlTable,
					"syscmd,cmd,cmd_comment","name = '".$name."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->cmd = $data["cmd"];
					$this->comment = $data["cmd_comment"];
					$this->isSysCmd = ($data["syscmd"] == 't');
				}
			}
		}
		
		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show command form");
			}
			
			$this->Load($name);
			
			return FS::$iMgr->cbkForm("1").
				"<table><tr><th>"._("Option")."</th><th>".
				_("Value")."</th></tr>".
				FS::$iMgr->idxLines(array(
					array("Name","name",array("type" => "idxedit",
						"value" => $name,
						"length" => 60, "size" => 30, 
						"tooltip" => "tooltip-cmdname",
						"edit" => $name != "")),
					array("Command","cmd",array("type" => "area", 
					"value" => $this->cmd,
					"length" => 1024, "size" => 30, "height" => "150",
					"tooltip" => "tooltip-cmd")),
					array("Comment","comment",array("type" => "area",
					"value" => $this->comment,
					"length" => 512, "size" => 30, "height" => "100"))
				)).
				FS::$iMgr->aeTableSubmit($name == "");
		}
		
		private $name;
		private $isSysCmd;
		private $cmd;
		private $comment;
	};	
	final class icingaNotificationStrategy extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_notif_strategy";
			$this->sqlAttrId = "name";
			$this->readRight = "notif_write";
			$this->writeRight = "notif_write";
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
				FS::$iMgr->echoNoRights("modify a command");
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
				FS::$iMgr->ajaxEchoError("err-bad-data");
				return;
			}
			
			// Now verify datas
			if($edit) {
				if(!$this->exists($name)) {
					FS::$iMgr->ajaxEchoError("err-data-not-exist");
					return;
				}
			}
			else {
				if($this->exists($name)) {
					FS::$iMgr->ajaxEchoErrorNC("err-data-exist");
					return;
				}
			}
			
			// Check if TP exists
			if(!(new icingaTimePeriod())->exists($period)) {
				FS::$iMgr->ajaxEchoError(sprintf(
					_("err-timeperiod-not-exists"), $period),
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
				FS::$iMgr->ajaxEchoError("err-fail-writecfg");
				return;
			}
			
			FS::$iMgr->redir("mod=".$this->mid."&sh=9",true);
		}
		
		public function Remove() {
			if(!$this->canWrite()) {
				FS::$iMgr->echoNoRights("remove a command");
				return;
			}
			
			$name = FS::$secMgr->checkAndSecuriseGetData("notifstr");
			
			if(!FS::$dbMgr->GetOneData($this->sqlTable,"name","name = '".$name."'")) {
				FS::$iMgr->ajaxEchoError("err-data-not-exist");
				return;
			}
			
			if ($hostUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_hosts",
				"name","notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEchoError(sprintf(_("err-notification-strategy-used-host"),
					$name,$hostUsed),"",true);
				return;
			}
			
			if ($srvUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_services",
				"name","notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEchoError(sprintf(_("err-notification-strategy-used-service"),
					$name,$srvUsed),"",true);
				return;
			}
			
			if ($ctUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts",
				"name","host_notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEchoError(sprintf(_("err-notification-strategy-used-contact"),
					$name,$ctUsed),"",true);
				return;
			}
			
			if ($ctUsed = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."icinga_contacts",
				"name","service_notif_strategy = '".$name."'")) {
				FS::$iMgr->ajaxEchoError(sprintf(_("err-notification-strategy-used-contact"),
					$name,$ctUsed),"",true);
				return;
			}
			
			$this->removeFromDB($name);

			$icingaAPI = new icingaBroker();
			if(!$icingaAPI->writeConfiguration()) {
				FS::$iMgr->ajaxEchoErrorNC("err-fail-writecfg");
				return;
			}
			
			FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#notifstr_".preg_replace("#[. ]#","-",$name)."');");
		}
		
		protected function removeFromDB($name) {
			FS::$dbMgr->BeginTr();
			FS::$dbMgr->Delete($this->sqlTable,"name = '".$name."'");
			FS::$dbMgr->CommitTr();
		}
		
		public function showForm($name = "") {
			if (!$this->canRead()) {
				return FS::$iMgr->printNoRight("show notification strategy form");
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
