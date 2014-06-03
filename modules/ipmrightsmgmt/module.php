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

	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");

	if(!class_exists("iIPMRightsMgmt")) {

	final class iIPMRightsMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lIPMRightsMgmt();
			$this->modulename = "ipmrightsmgmt";
			$this->rulesclass = new rIPMRightsMgmt($this->loc);
			$this->menu = $this->loc->s("menu-name");
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-ipmrightsmgmt"));

			$output = $this->showMain();
			return $output;
		}

		private function showSubnetForm() {
			$output = "";
			$tmpoutput = "";

			$found = false;

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid,netmask","",
				array("order" => "netid"));
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = true;
				}
				$tmpoutput .= FS::$iMgr->selElmt($data["netid"]."/".$data["netmask"],$data["netid"]);
			}

			if ($found) {
				$output .= FS::$iMgr->cbkForm("3").
					FS::$iMgr->select("subnet").$tmpoutput."</select> ".
					FS::$iMgr->Submit("","Filter")."</form>".
					"<div id=\"subnetrights\"></div>";
			}
			else {
				return FS::$iMgr->printError("err-no-subnet");
			}

			return $output;
		}

		private function showSubnetRights($subnet) {
			FS::$iMgr->setURL("sh=2");

			$output = "";
			$found = false;

			$grpoutput = FS::$iMgr->h1("group-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = FS::$iMgr->h1("user-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";

			$grprules = $this->initSubnetRules();
			$usrrules = $this->initSubnetRules();
			// Groups
			$first = true;
			$grprules = $this->loadSubnetRules($grprules,1,$subnet,"");
			foreach ($grprules as $key => $values) {
				if ($first) $first = false;
				$grpoutput .= "<tr><td>".$this->getRightForKey($key);
				$grpoutput .= "</td><td>";
				$grpoutput .= $this->showSubnetGroups($key,$values,$subnet);
				$grpoutput .= "</td></tr>";
			}
			$output .= $grpoutput."</table>";

			// Users
			$usrrules = $this->loadSubnetRules($usrrules,2,$subnet,"");
			$first = true;
			foreach ($usrrules as $key => $values) {
				if ($first) $first = false;
				$usroutput .= "<tr><td>".$this->getRightForKey($key);
				$usroutput .= "</td><td>";
				$usroutput .= $this->showSubnetUsers($key,$values,$subnet);
				$usroutput .= "</td></tr>";
			}
			$output .= $usroutput."</table>";
			return $output;
		}

		private function showGlobalRights() {
			FS::$iMgr->setURL("sh=1");

			$output = "";
			$found = false;

			$grpoutput = FS::$iMgr->h1("group-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = FS::$iMgr->h1("user-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";

			$grprules = $this->initRules();
			$usrrules = $this->initRules();
			// Groups
			$first = true;
			$grprules = $this->loadGlobalRules($grprules,1);
			foreach ($grprules as $key => $values) {
				if ($first) $first = false;
				$grpoutput .= "<tr><td>".$this->getRightForKey($key);
				$grpoutput .= "</td><td>";
				$grpoutput .= $this->showGlobalGroups($key,$values);
				$grpoutput .= "</td></tr>";
			}
			$output .= $grpoutput."</table>";

			// Users
			$usrrules = $this->loadGlobalRules($usrrules,2);
			$first = true;
			foreach ($usrrules as $key => $values) {
				if ($first) $first = false;
				$usroutput .= "<tr><td>".$this->getRightForKey($key);
				$usroutput .= "</td><td>";
				$usroutput .= $this->showGlobalUsers($key,$values);
				$usroutput .= "</td></tr>";
			}
			$output .= $usroutput."</table>";
			return $output;
		}

		private function initSubnetRules($rulefilter = "") {
			$rules = array();
			$rulelist = array("history","rangemgmt","ipmgmt");
			for ($i=0;$i<count($rulelist);$i++) {
				if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulelist[$i] == $rulefilter)
					$rules[$rulelist[$i]] = array();
			}
			return $rules;
		}


		private function initRules($rulefilter = "") {
			$rules = array();
			$rulelist = array("read","history","servermgmt","advancedtools","optionsmgmt",
				"optionsgrpmgmt","subnetmgmt","rangemgmt","ipmgmt");
			for ($i=0;$i<count($rulelist);$i++) {
				if (strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulelist[$i] == $rulefilter)
					$rules[$rulelist[$i]] = array();
			}
			return $rules;
		}

		private function loadSubnetRules($rules,$type,$subnet,$rulefilter="") {
			$subnet = preg_replace("#[.]#","_",$subnet);
			$idx = "";
			if ($type == 1) {
				$idx = "gid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					$subnet."_".($rulefilter ? $rulefilter : "%")."'");
			}
			else if ($type == 2) {
				$idx = "uid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					$subnet."_".($rulefilter ? $rulefilter : "%")."'");
			}
			else {
				return NULL;
			}

			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$ruleidx = preg_replace("#mrule_ipmmgmt_".$subnet."_#","",$data2["rulename"]);
				switch($ruleidx) {
					case "rangemgmt": case "ipmgmt": case "history":
						$rules[$ruleidx][] = $data2[$idx];
						break;
				}
			}
			return $rules;
		}

		private function loadGlobalRules($rules,$type,$rulefilter="") {
			$idx = "";
			if ($type == 1) {
				$idx = "gid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					($rulefilter ? $rulefilter : "%")."'");
			}
			else if ($type == 2) {
				$idx = "uid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					($rulefilter ? $rulefilter : "%")."'");
			}
			else {
				return NULL;
			}

			while ($data2 = FS::$dbMgr->Fetch($query2)) {
				$ruleidx = preg_replace("#mrule_ipmmgmt_#","",$data2["rulename"]);
				switch($ruleidx) {
					case "read": case "servermgmt": case "advancedtools": case "optionsmgmt":
					case "optionsgrpmgmt": case "subnetmgmt": case "rangemgmt": case "ipmgmt":
					case "history":
						$rules[$ruleidx][] = $data2[$idx];
						break;
				}
			}
			return $rules;
		}

		private function showSubnetUsers($right,$values,$subnet) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("u","subnet",$username,$values[$i],$right,$subnet);
				}
			}
			$output .= "<span id=\"anchusrr_".FS::$iMgr->formatHTMLId("u".$subnet."l-".$right)."\" style=\"display:none;\"></span>";
			$idsfx = FS::$iMgr->formatHTMLId($right.$subnet);
			$tmpoutput = FS::$iMgr->cbkForm("1").
				FS::$iMgr->hidden("subnet",$subnet).FS::$iMgr->hidden("right",$right).
				"<span id=\"lu".$idsfx."\">";
			$output .= $tmpoutput.$this->userSelect("uid".$idsfx,$values)."</span></form>";
			return $output;
		}

		private function showGlobalUsers($right,$values) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("u","global",$username,$values[$i],$right,"1");
				}
			}
			$output .= "<span id=\"anchusrr_".FS::$iMgr->formatHTMLId("uglbl-".$right)."\" style=\"display:none;\"></span>";
			$idsfx = $right."glbl";
			$tmpoutput = FS::$iMgr->cbkForm("1");
			$tmpoutput .= FS::$iMgr->hidden("global",1).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$idsfx."\">";
			$output .= $tmpoutput.$this->userSelect("uid".$idsfx,$values)."</span></form>";
			return $output;
		}

		private function showSubnetGroups($right,$values,$subnet) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("g","subnet",$gname,$values[$i],$right,$subnet);
				}
			}
			$output .= "<span id=\"anchgrpr_".FS::$iMgr->formatHTMLId("g".$subnet."-".$right)."\" style=\"display:none;\"></span>";

			$idsfx = FS::$iMgr->formatHTMLId($right.$subnet);

			$tmpoutput = FS::$iMgr->cbkForm("1").
				FS::$iMgr->hidden("subnet",$subnet).FS::$iMgr->hidden("right",$right).
				"<span id=\"lg".$idsfx."\">";
			$output .= $tmpoutput.$this->groupSelect("gid".$idsfx,$values)."</span></form>";
			return $output;
		}

		private function showGlobalGroups($right,$values) {
			$output = "";

			$count = count($values);
			if ($count) {
				for ($i=0;$i<count($values);$i++) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("g","global",$gname,$values[$i],$right,"1");
				}
			}
			$output .= "<span id=\"anchgrpr_".FS::$iMgr->formatHTMLId("gglbl-".$right)."\" style=\"display:none;\"></span>";
			$idsfx = $right."glbl";
			$tmpoutput = FS::$iMgr->cbkForm("1");
			$tmpoutput .= FS::$iMgr->hidden("global","1").FS::$iMgr->hidden("right",$right)."<span id=\"lg".$idsfx."\">";
			$output .= $tmpoutput.$this->groupSelect("gid".$idsfx,$values)."</span></form>";
			return $output;
		}

		/*
		* $type: g (group) u (user)
		* $type2: glbl
		*/
		private function showRemoveSpan($type,$type2,$name,$id,$right,$value2) {
			$confirm = ($type == "g" ? $this->loc->s("confirm-remove-groupright") : $this->loc->s("confirm-remove-userright"));
			if ($type2 == "subnet") {
				$output = "<span id=\"".FS::$iMgr->formatHTMLId($type.$id.$right.$value2)."\">".$name." ".
					FS::$iMgr->removeIcon(2,$type."id=".$id."&".$type2."=".$value2."&right=".$right,
						array("js" => true,
						"confirmtext" => $confirm,
						"confirmval" => $name
					))."<br /></span>";
			}
			else {
				$output = "<span id=\"".$type.$id.$right.$type2."\">".$name." ".
					FS::$iMgr->removeIcon(2,$type."id=".$id."&".$type2."=".$value2."&right=".$right,
						array("js" => true,
							"confirmtext" => $confirm,
							"confirmval" => $name
						))."<br /></span>";
			}

			return $output;
		}

		private function userSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$users = $this->getUsers();
			$found = false;
			foreach ($users as $uid => $username) {
				if (!in_array($uid,$values)) {
					if (!$found) $found = true;
					$output .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
			if (!$found) return "";
			else return $output;
		}

		private function groupSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$groups = $this->getUserGroups();
			$found = false;
			foreach ($groups as $gid => $gname) {
				if (!in_array($gid,$values)) {
					if (!$found) $found = true;
					$output .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
			if (!$found) return "";
			else return $output;
		}

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function getUsers() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$users[$data["uid"]] = $data["username"];
			}
			return $users;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if (!FS::isAjaxCall()) {
				$backupfound = FS::$secMgr->checkAndSecuriseGetData("bck");
				$typefound = FS::$secMgr->checkAndSecuriseGetData("type");
				if ($backupfound && $typefound)
					$output .= $this->addOrEditBackupServer();
				else {
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
					$output = FS::$iMgr->h1("title-ipmrightsmgmt");
					$panElmts = array(
						array(1,"mod=".$this->mid,$this->loc->s("title-globalrights"))
					);
					if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."dhcp_subnet_v4_declared","netid")) {
						$panElmts[] = array(2,"mod=".$this->mid,$this->loc->s("title-bysubnet"));
					}
					// Show only if there is devices
					$output .= FS::$iMgr->tabPan($panElmts,$sh);
				}
			}
			else if ($sh == 1) {
				$output .= $this->showGlobalRights();
			}
			else if ($sh == 2) {
				$output .= $this->showSubnetForm();
			}

			return $output;
		}

		private function getRightForKey($key) {
			switch($key) {
				case "read": return $this->loc->s("right-read");
				case "servermgmt": return $this->loc->s("right-servermgmt");
				case "advancedtools": return $this->loc->s("right-advancedtools");
				case "optionsmgmt": return $this->loc->s("right-optionsmgmt");
				case "optionsgrpmgmt": return $this->loc->s("right-optionsgrpmgmt");
				case "subnetmgmt": return $this->loc->s("right-subnetmgmt");
				case "rangemgmt": return $this->loc->s("right-rangemgmt");
				case "ipmgmt": return $this->loc->s("right-ipmgmt");
				case "history": return $this->loc->s("right-history");
				default: return FS::$iMgr->printError("err-not-found");
			}
		}

		/*
		* $type snmp/ip
		* $id uid/gid
		*/
		private function jsUserGroupSelect($right,$type,$id,$value="") {
			$rules = NULL;

			if ($type == "glbl") {
				$rules = $this->initRules($right);
				if (preg_match("#^uid#",$id)) {
					$rules = $this->loadGlobalRules($rules,2,$right);
				}
				else if (preg_match("#^gid#",$id)) {
					$rules = $this->loadGlobalRules($rules,1,$right);
				}
				else {
					return "";
				}
			}
			else if ($type == "subnet") {
				$rules = $this->initSubnetRules($right);
				if (preg_match("#^uid#",$id)) {
					$rules = $this->loadSubnetRules($rules,2,$value,$right);
				}
				else if (preg_match("#^gid#",$id)) {
					$rules = $this->loadSubnetRules($rules,1,$value,$right);
				}
				else {
					return "";
				}
			}
			else {
				return "";
			}

			$js = "";
			foreach ($rules as $key => $values) {
				if ($type == "glbl") {
					if (preg_match("#^uid#",$id)) {
						$js = sprintf("%s$('#lu%s%s').html('%s'); $('#%s').select2();",
							$js, $right, $type, $this->userSelect($id,$values), $id);
					}
					else if (preg_match("#^gid#",$id)) {
						$js = sprintf("%s$('#lg%s%s').html('%s'); $('#%s').select2();",
							$js, $right, $type, $this->groupSelect($id,$values), $id);
					}
				}
				else if ($type == "subnet") {
					if (preg_match("#^uid#",$id)) {
						$js = sprintf("%s$('#lu%s').html('%s'); $('#%s').select2();",
							$js, FS::$iMgr->formatHTMLId($right.$value),
							$this->userSelect($id,$values), $id
						);
					}
					else if (preg_match("#^gid#",$id)) {
						$js = sprintf("%s$('#lg%s').html('%s'); $('#%s').select2();",
							$js, FS::$iMgr->formatHTMLId($right.$value),
							$this->groupSelect($id,$values), $id
						);
					}

				}
			}
			return $js;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				// Add/Edit global user/group right
				case 1:
					if (!FS::$sessMgr->hasRight("read")) {
						FS::$iMgr->echoNoRights("modify global rights");
						return;
					}

					$global = FS::$secMgr->checkAndSecurisePostData("global");
					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");
					$right = FS::$secMgr->checkAndSecurisePostData("right");

					// gid and uid fields are dynamic

					$idsfx = FS::$iMgr->formatHTMLId($right.($global == 1 ? "glbl" : $subnet));

					$gid = FS::$secMgr->checkAndSecurisePostData("gid".$idsfx);
					$uid = FS::$secMgr->checkAndSecurisePostData("uid".$idsfx);

					if ((!$gid && !$uid) || (!$global && !$subnet) || !$right) {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						$this->log(2,"Bad datas when add global/subnet rights");
						return;
					}

					$js = "";

					if ($global) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Add/edit global group right: Group gid '".$gid."' not found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								$this->log(1,"Add/edit global group right: the rule for right '".$right."' already exists");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_ipmmgmt_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_ipmmgmt_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","global",$gname,$gid,$right,"1");
							$js .= $this->jsUserGroupSelect($right,"glbl","gid".$idsfx).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchgrpr_".
								FS::$iMgr->formatHTMLId("gglbl-".$right)."');";
							$this->log(0,"global right '".$right."' for group '".$gid."' added/edited");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Add/edit global user right: user with uid '".$uid."' doesn't exists");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								$this->log(1,"Add/edit global user right: the rule for right '".$right."' already exists");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_ipmmgmt_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_ipmmgmt_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","global",$username,$uid,$right,"1");
							$js .= $this->jsUserGroupSelect($right,"glbl","uid".$idsfx).
								"$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchusrr_".
								FS::$iMgr->formatHTMLId("uglbl-".$right)."');";
							$this->log(0,"global right '".$right."' for user '".$uid."' added/edited");
						}
					}
					else if ($subnet) {
						$subnets = preg_replace("#[.]#","_",$subnet);
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Add/edit subnet group right: Group gid '".$gid."' not found");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".
								$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								$this->log(1,"Add/edit subnet group right: the rule for right '".$right."' already exists");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval",
								"'".$gid."','mrule_ipmmgmt_".$subnets."_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","subnet",$gname,$gid,$right,$subnet);
							$js .= $this->jsUserGroupSelect($right,"subnet","gid".$idsfx,$subnet);
							$js .= "$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchgrpr_".
								FS::$iMgr->formatHTMLId("g".FS::$iMgr->FormatHTMLId($subnet)."-".$right)."');";
							$this->log(0,"subnet right '".$right."' for group '".$gid."' and subnet '".$subnet."' added/edited");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Add/edit subnet user right: user with uid '".$uid."' doesn't exists");
								return;
							}
							if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".
								$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEchoError("err-already-exist");
								$this->log(1,"Add/edit subnet user right: the rule for right '".$right."' already exists");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval",
								"'".$uid."','mrule_ipmmgmt_".$subnets."_".$right."','on'");
							FS::$dbMgr->CommitTr();

							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","subnet",$username,$uid,$right,$subnet);
							$js .= $this->jsUserGroupSelect($right,"subnet","uid".$idsfx,$subnet);
							$js .= "$('".FS::$secMgr->cleanForJS($jscontent)."').insertBefore('#anchusrr_".
								FS::$iMgr->formatHTMLId("u".FS::$iMgr->formatHTMLId($subnet)."l-".$right)."');";
							$this->log(0,"subnet right '".$right."' for user '".$uid."' and subnet '".$subnet."' added/edited");
						}
					}

					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				// Remove group/user for global/subnet rights
				case 2:
					if (!FS::$sessMgr->hasRight("read")) {
						FS::$iMgr->echoNoRights("remove global rights");
						return;
					}

					$global = FS::$secMgr->checkAndSecuriseGetData("global");
					$subnet = FS::$secMgr->checkAndSecuriseGetData("subnet");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");

					// ID are dynamic (due to JS)
					$idsfx = FS::$iMgr->formatHTMLId($right.($global == 1 ? "glbl" : $subnet));


					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");

					if ((!$uid && !$gid) || (!$global && !$subnet) || !$right) {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						$this->log(2,"Bad datas when remove global right");
						return;
					}

					if ($global) {
						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Unable to remove global right '".$right."' for gid '".$gid."': right doesn't exist");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_ipmmgmt_".$right."' AND gid = '".$gid."'");
							$this->log(0,"global right '".$right."' for gid '".$gid."' removed");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Unable to remove global right '".$right."' for uid '".$uid."': right doesn't exist");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_ipmmgmt_".$right."' AND uid = '".$uid."'");
							$this->log(0,"global right '".$right."' for uid '".$uid."' removed");
						}
					}
					else if ($subnet) {
						$subnets = preg_replace("#[.]#","_",$subnet);

						if ($gid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$subnets."_".$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Unable to remove subnet right '".$right."' for gid '".$gid."': right doesn't exist");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".$right."' AND gid = '".$gid."'");
							$this->log(0,"subnet right '".$right."' for gid '".$gid."' and subnet .'".$subnet."' removed");
						}
						else if ($uid) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$subnets."_".$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEchoError("err-not-found");
								$this->log(2,"Unable to remove subnet right '".$right."' for uid '".$uid."': right doesn't exist");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules",
								"rulename = 'mrule_ipmmgmt_".$subnets."_".$right."' AND uid = '".$uid."'");
							$this->log(0,"subnet right '".$right."' for uid '".$uid."' AND subnet '".$subnet."' removed");
						}
					}
					if ($gid) {
						if ($global) {
							$js = sprintf("hideAndRemove('#g%s%sglobal');%s",
								$gid,$right,
								$this->jsUserGroupSelect($right,"glbl","gid".$idsfx)
							);
							FS::$iMgr->ajaxEchoOK("Done",$js);
						}
						else if ($subnet) {
							$js = sprintf("hideAndRemove('#g%s');%s",
								FS::$iMgr->formatHTMLId($gid.$right.$subnet),
								$this->jsUserGroupSelect($right,"subnet","gid".$idsfx,$subnet)
							);
							FS::$iMgr->ajaxEchoOK("Done",$js);
						}
					}
					else if ($uid) {
						if ($global) {
							$js = sprintf("hideAndRemove('#u%s%sglobal');%s",
								$uid,$right,
								$this->jsUserGroupSelect($right,"glbl","uid".$idsfx)
							);
							FS::$iMgr->ajaxEchoOK("Done",$js);
						}
						else if ($subnet) {
							$js = sprintf("hideAndRemove('#u%s');%s",
								FS::$iMgr->formatHTMLId($uid.$right.$subnet),
								$this->jsUserGroupSelect($right,"subnet","uid".$idsfx,$subnet)
							);
							FS::$iMgr->ajaxEchoOK("Done",$js);
						}
					}
					return;
				// Filtering
				case 3:
					if (!FS::$sessMgr->hasRight("read")) {
						FS::$iMgr->echoNoRights("filter rights");
						return;
					}

					$subnet = FS::$secMgr->checkAndSecurisePostData("subnet");

					if (!$subnet || !FS::$secMgr->isIP($subnet)) {
						FS::$iMgr->ajaxEchoErrorNC("err-bad-datas");
						return;
					}

					$js = "$('#subnetrights').html('".FS::$secMgr->cleanForJS($this->showSubnetRights($subnet))."');";
					FS::$iMgr->ajaxEchoOK("Done",$js);
					return;
				default: break;
			}
		}
	};

	}

	$module = new iIPMRightsMgmt();
?>
