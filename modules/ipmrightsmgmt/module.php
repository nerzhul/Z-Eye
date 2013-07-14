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
	
	final class iIPMRightsMgmt extends FSModule {
		function __construct($locales) { parent::__construct($locales); }
		
		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-ipmrightsmgmt"));

			$output = $this->showMain();
			return $output;
		}


		private function showGlobalRights() {
			// IP for ajax filtering
			$output = "";	
			$found = false;

			$grpoutput = FS::$iMgr->h1("group-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Groups")."</th></tr>";
			$usroutput = FS::$iMgr->h1("user-rights")."<table><tr><th>".$this->loc->s("Right")."</th><th>".
				$this->loc->s("Users")."</th></tr>";

			$grprules = $this->initGlobalRules();
			$usrrules = $this->initGlobalRules();
			// Groups
			$first = true;
			$grprules = $this->loadGlobalRules($grprules,1);
			foreach($grprules as $key => $values) {
				if($first) $first = false;
				$grpoutput .= "<tr><td>".$this->getRightForKey($key);
				$grpoutput .= "</td><td>";
				$grpoutput .= $this->showGlobalGroups($key,$values);
				$grpoutput .= "</td></tr>";
			}
			$output .= $grpoutput."</table>";

			// Users			
			$usrrules = $this->loadGlobalRules($usrrules,2);
			$first = true;
			foreach($usrrules as $key => $values) {
				if($first) $first = false;
				$usroutput .= "<tr><td>".$this->getRightForKey($key);
				$usroutput .= "</td><td>";
				$usroutput .= $this->showGlobalUsers($key,$values);
				$usroutput .= "</td></tr>";
			}
			$output .= $usroutput."</table>";
			return $output;
		}

		private function initGlobalRules($rulefilter = "") {
			$rules = array();
			$rulelist = array("read","servermgmt","advancedtools","optionsmgmt", 
				"optionsgrpmgmt","subnetmgmt","rangemgmt","ipmgmt");
			for($i=0;$i<count($rulelist);$i++) {
				if(strlen($rulefilter) == 0 || strlen($rulefilter) > 0 && $rulelist[$i] == $rulefilter)
					$rules[$rulelist[$i]] = array();
			}
			return $rules;
		}

		private function loadGlobalRules($rules,$type,$rulefilter="") {
			$idx = "";
			if($type == 1) {
				$idx = "gid";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					($rulefilter ? $rulefilter : "%")."'");
			}
			else if($type == 2) {
				$idx = "uid";	
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","rulename ILIKE 'mrule_ipmmgmt_".
					($rulefilter ? $rulefilter : "%")."'");
			}
			else
				return NULL;
			while($data2 = FS::$dbMgr->Fetch($query2)) {
				$ruleidx = preg_replace("#mrule_ipmmgmt_#","",$data2["rulename"]);
				switch($ruleidx) {
					case "read": case "servermgmt": case "advancedtools": case "optionsmgmt":
					case "optionsgrpmgmt": case "subnetmgmt": case "rangemgmt": case "ipmgmt":
						array_push($rules[$ruleidx],$data2[$idx]);
						break;
				}
			}
			return $rules;
		}

		private function showGlobalUsers($right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("u","global",$username,$values[$i],$right,"1");
				}
			}
			$output .= "<span id=\"anchusrr_".FS::$iMgr->formatHTMLId("uglbl-".$right)."\" style=\"display:none;\"></span>";
			$tmpoutput = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("global",1).FS::$iMgr->hidden("right",$right)."<span id=\"lu".$right."glbl\">";
			$output .= $tmpoutput.$this->userSelect("uid",$values)."</span></form>";
			return $output;
		}

		private function showGlobalGroups($right,$values) { 
			$output = "";

			$count = count($values);
			if($count) {
				for($i=0;$i<count($values);$i++) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$values[$i]."'");
					$output .= $this->showRemoveSpan("g","global",$gname,$values[$i],$right,"1");
				}
			}
			$output .= "<span id=\"anchgrpr_".FS::$iMgr->formatHTMLId("gglbl-".$right)."\" style=\"display:none;\"></span>";
			$tmpoutput = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=1");
			$tmpoutput .= FS::$iMgr->hidden("global","1").FS::$iMgr->hidden("right",$right)."<span id=\"lg".$right."glbl\">";
			$output .= $tmpoutput.$this->groupSelect("gid",$values)."</span></form>";
			return $output;
		}

		/*
		* $type: g (group) u (user)
		* $type2: glbl
		*/
		private function showRemoveSpan($type,$type2,$name,$id,$right,$value2) {
			$confirm = ($type == "g" ? $this->loc->s("confirm-remove-groupright") : $this->loc->s("confirm-remove-userright"));
			$output = "<span id=\"".$type.$id.$right.$type2."\">".$name." ".
				FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&".$type."id=".$id."&".$type2."=".$value2."&right=".$right,
					array("js" => true, "confirm" => array($confirm."'".$name."' ?","Confirm","Cancel")))."<br /></span>";

			return $output;
		}

		private function userSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$users = $this->getUsers();
			$found = false;
			foreach($users as $uid => $username) {
				if(!in_array($uid,$values)) {
					if(!$found) $found = true;
					$output .= FS::$iMgr->selElmt($username,$uid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
			if(!$found) return "";
			else return $output;
		}

		private function groupSelect($sname,$values) {
			$output = FS::$iMgr->select($sname);
			$groups = $this->getUserGroups();
			$found = false;
			foreach($groups as $gid => $gname) {
				if(!in_array($gid,$values)) {
					if(!$found) $found = true;
					$output .= FS::$iMgr->selElmt($gname,$gid);
				}
			}
			$output .= "</select>".FS::$iMgr->submit("",$this->loc->s("Add"));
			if(!$found) return "";
			else return $output;
		}

		private function getUserGroups() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query)) {
				$groups[$data["gid"]] = $data["gname"];
			}
			return $groups;
		}

		private function getUsers() {
			$groups = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username");
			while($data = FS::$dbMgr->Fetch($query)) {
				$users[$data["uid"]] = $data["username"];
			}
			return $users;
		}

		private function showMain() {
			$output = "";
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			if(!FS::isAjaxCall()) {
				$backupfound = FS::$secMgr->checkAndSecuriseGetData("bck");
				$typefound = FS::$secMgr->checkAndSecuriseGetData("type");
				if($backupfound && $typefound)
					$output .= $this->addOrEditBackupServer();
				else {
					$filter = FS::$secMgr->checkAndSecuriseGetData("filter");
					$output = FS::$iMgr->h1("title-ipmrightsmgmt");
					$panElmts = array(array(1,"mod=".$this->mid,$this->loc->s("title-globalrights")));
					// Show only if there is devices
					$output .= FS::$iMgr->tabPan($panElmts,$sh);
				}
			}
			else if($sh == 1)
				$output .= $this->showGlobalRights();
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
				default: return FS::$iMgr->printError($this->loc->s("err-not-found"));
			}
		}

		/*
		* $type snmp/ip
		* $id uid/gid
		*/
		private function jsUserGroupSelect($right,$type,$id) {
			$rules = $this->initGlobalRules($right);
			if($id == "uid") {
				$rules = $this->loadGlobalRules($rules,2,$right);
			}
			else if($id == "gid") {
				$rules = $this->loadGlobalRules($rules,1,$right);
			}
			else 
				return "";
			$js = "";
			foreach($rules as $key => $values) {
				if($id == "uid")
					$js .= "$('#lu".$right.$type."').html('".$this->userSelect("uid",$values)."');";
				else if($id == "gid")
					$js .= "$('#lg".$right.$type."').html('".$this->groupSelect("gid",$values)."');";
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
				case 1: // Add group right for SNMP/IP community 
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					$global = FS::$secMgr->checkAndSecurisePostData("global");
					$right = FS::$secMgr->checkAndSecurisePostData("right");

					if((!$gid && !$uid) || (!$global) || !$right) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					$js = "";

					if($global) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND gid = '".$gid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_ipmmgmt_".$right."' AND gid = '".$gid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','mrule_ipmmgmt_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$gid."'");
							$jscontent = $this->showRemoveSpan("g","global",$gname,$gid,$right,"1");
							$js .= $this->jsUserGroupSelect($right,"glbl","gid");
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchgrpr_".FS::$iMgr->formatHTMLId("gglbl-".$right)."');";
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_ipmmgmt_ip_".
								$right."' AND uid = '".$uid."' AND ruleval = 'on'")) {
								FS::$iMgr->ajaxEcho("err-already-exist");
								return;
							}
							FS::$dbMgr->BeginTr();
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_ipmmgmt_".$right."' AND uid = '".$uid."'");
							FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_rules","uid,rulename,ruleval","'".$uid."','mrule_ipmmgmt_".$right."','on'");
							FS::$dbMgr->CommitTr();
							$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
							$jscontent = $this->showRemoveSpan("u","global",$username,$uid,$right,"1");
							$js .= $this->jsUserGroupSelect($right,"glbl","uid");
							$js .= "$('".addslashes($jscontent)."').insertBefore('#anchusrr_".FS::$iMgr->formatHTMLId("uglbl-".$right)."');";
						}
					}

					FS::$iMgr->ajaxEcho("Done",$js);
					return;
				// Remove group/user for global rights
				case 2:
					$gid = FS::$secMgr->checkAndSecuriseGetData("gid");
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					$global = FS::$secMgr->checkAndSecuriseGetData("global");
					$right = FS::$secMgr->checkAndSecuriseGetData("right");

					if((!$uid && !$gid) || (!$global) || !$right) {
						FS::$iMgr->ajaxEcho("err-bad-datas");
						return;
					}

					if($global) {
						if($gid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND gid = '".$gid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","rulename = 'mrule_ipmmgmt_".$right."' AND gid = '".$gid."'");
						}
						else if($uid) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = 'mrule_ipmmgmt_".
								$right."' AND uid = '".$uid."'")) {
								FS::$iMgr->ajaxEcho("err-not-found");
								return;
							}
							FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_rules","rulename = 'mrule_ipmmgmt_".$right."' AND uid = '".$uid."'");
						}
					}
					if($gid) {
						if($global) {
							$js = $this->jsUserGroupSelect($right,"glbl","gid");
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."g".$gid.$right."global');".$js);
						}
					}
					else if($uid) {
						if($global) {
							$js = $this->jsUserGroupSelect($right,"glbl","uid");
							FS::$iMgr->ajaxEcho("Done","hideAndRemove('#"."u".$uid.$right."global');".$js); 
						}
					}
					return;
				default: break;
			}
		}
	};
?>
