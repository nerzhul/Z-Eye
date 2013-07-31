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

	final class icingaHost extends FSMObj {
		function __construct() {
			parent::__construct();
			$this->sqlTable = PGDbConfig::getDbPrefix()."icinga_hosts";
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

		public function showForm($name = "") {
			$this->Load($name);

			FS::$iMgr->setJSBuffer(1);
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=13").
				"<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>".
				FS::$iMgr->idxLine($this->loc->s("is-template"),"istemplate",false,array("type" => "chk"));
			//$output .= template list
			$output .= FS::$iMgr->idxIdLine("Name","name",$this->name).
				FS::$iMgr->idxLine($this->loc->s("Alias"),"alias",$this->alias).
				FS::$iMgr->idxLine($this->loc->s("DisplayName"),"dname",$this->dname).

				"<tr><td>".$this->loc->s("Icon")."</td><td>".
				FS::$iMgr->select("icon").
				FS::$iMgr->selElmt("Aucun","",($this->icon == "")).
				FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."icinga_icons","name","id",array($this->icon)).
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
			$output .= FS::$iMgr->select("parent","",NULL,true,array("size" => round($countElmt/4))).
				$output2."</select></td></tr>".
				FS::$iMgr->idxLine($this->loc->s("Address"),"addr",$this->addr);

			$hglist = array();
			if($name) {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."icinga_hostgroup_members","name","host = '".$name."' AND hosttype = '1'");
				while($data = FS::$dbMgr->Fetch($query))
					$hglist[] = $data["name"];
			}

			$output .= "<tr><td>".$this->loc->s("Hostgroups")."</td><td>".$this->mod->getHostOrGroupList("hostgroups",false,$hglist,"",true)."</td></tr>";

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
