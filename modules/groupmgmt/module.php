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

	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	final class iGroupMgmt extends FSModule {
		function __construct($locales) {
			parent::__construct($locales);
			$this->modulename = "groupmgmt";
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-mgmt"));
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-already-exist")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-bad-data")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-not-exist")); break;
			}
			if(!FS::isAjaxCall()) {
				$gname = FS::$secMgr->checkAndSecuriseGetData("g");
				$output = FS::$iMgr->h1("title-mgmt");
				if($gname)
					$output .= $this->editGroup($gname);
				else
					$output .= $this->showMain();
			}
			return $output;
		}

		private function editGroup($gname) {
			$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
			if(!$gid) {
				return FS::$iMgr->printError($this->loc->s("err-not-exist"));
			}

			FS::$iMgr->showReturnMenu(true);
			$output = FS::$iMgr->h2($this->loc->s("title-edit")." '".$gname."'",true);
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=3");
			$rules = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","rulename","gid = '".$gid."' AND ruleval = 'on'");
			while($data = FS::$dbMgr->Fetch($query))
				$rules[] = $data["rulename"];
			$output .= $this->loadModuleRuleSets($rules);
			$output .= FS::$iMgr->hidden("gid",$gid);
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</form>";
			return $output;
		}

		private function showMain() {
			$output = FS::$iMgr->opendiv(1,$this->loc->s("New-group"));
			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname,description");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"groupList\"><thead><tr><th class=\"headerSortDown\">GID</th><th>".$this->loc->s("Groupname")."</th><th>".$this->loc->s("User-nb")."</th><th></th></tr></thead>";
				}
				$tmpoutput .= "<tr id=\"gr".$data["gid"]."tr\"><td>".$data["gid"]."</td><td><a href=\"index.php?mod=".$this->mid."&g=".$data["gname"]."\">".$data["gname"]."</a></td><td>".
					FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."user_group","gid","gid = '".$data["gid"]."'")."</td><td>".
					FS::$iMgr->removeIcon("mod=".$this->mid."&act=2&gname=".$data["gname"],array("js" => true, 
						"confirm" => array($this->loc->s("confirm-removegrp")."'".$data["gname"]."' ?","Confirm","Cancel")))."</td></tr>";
			}
			if($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("groupList");
			}
			return $output;
		}

		private function loadModuleRuleSets($activerules = array()) {
			$output = "";
			$dir = opendir(dirname(__FILE__)."/../");
			$found = 0;
			while($elem = readdir($dir)) {
				$dirpath = dirname(__FILE__)."/../".$elem;
				if(is_dir($dirpath) && $elem != ".." && $elem != ".") {
					$dir2 = opendir($dirpath);
					while($elem2 = readdir($dir2)) {
						if(is_file($dirpath."/".$elem2) && $elem2 == "rules.php") {
							require($dirpath."/main.php");

							$tmpoutput = $module->getRulesClass()->showMgmtInterface($activerules);
							if(strlen($tmpoutput) > 0) {
								if($found == 0) {
									$found = 1;
									$output .= "<table id=\"ruleList\"><thead><tr><th>Module</th><th>".$this->loc->s("Rule")."</th></tr></thead>";
								}
								$output .= $tmpoutput;
							}
						}
					}
				}
			}
			if($found) {
				$output .= "</table>";
			}
			return $output;
		}

		private function getGroupForm() {
			$output = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$output .= "<ul class=\"ulform\"><li>".FS::$iMgr->input("gname","",20,40,$this->loc->s("Groupname"));
			$output .= FS::$iMgr->h2("title-opts");
			$output .= $this->loadModuleRuleSets();
                        $output .= "</li><li>".FS::$iMgr->submit("reggrp",$this->loc->s("Add"))."</li>";
			$output .= "</ul></form>";
			return $output;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->getGroupForm();
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					// @TODO description field
					$gname = FS::$secMgr->checkAndSecurisePostData("gname");
					if(!$gname) {
						$this->log(2,"Some datas are missing when try to create group");
						FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if($exist) {
						FS::$iMgr->redir("mod=".$this->mid."&err=1");
						$this->log(1,"The group ".$gname." already exists");
						return;
					}
					$gid = FS::$dbMgr->GetMax(PGDbConfig::getDbPrefix()."groups","gid")+1;
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."groups","gid,gname","'".$gid."','".$gname."'");
					$rules = array();
					foreach($_POST as $key => $value) {
						   if(preg_match("#^mrule_#",$key)) {
									$rules[$key] = $value;
						   }
					}
					foreach($rules as $key => $value) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
					}
					$this->log(0,"New group '".$gname."' added");
					FS::$iMgr->redir("mod=".$this->mid);
					return;
				// Remove group
				case 2:
					$gname = FS::$secMgr->checkAndSecuriseGetData("gname");
					if(!$gname) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
						$this->log(2,"Some datas are missing when try to remove group");
						return;
					}
					$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if(!$gid) {
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-not-exist");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=1");
						$this->log(1,"Unable to remove group '".$gname."', group doesn't exists");
						return;
					}
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."groups","gname = '".$gname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","gid = '".$gid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","gid = '".$gid."'");
					FS::$dbMgr->CommitTr();
					$this->log(0,"Group '".$gname."' removed");
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#gr".$gid."tr');");
					else
						FS::$iMgr->redir("mod=".$this->mid);
                                        return;
				case 3:
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					if(!$gid) {
						FS::$iMgr->redir("mod=".$this->mid."&err=3");
						$this->log(2,"Some datas are missing when try to edit group");
						return;
					}
					$rules = array();
					foreach($_POST as $key => $value) {
						   if(preg_match("#^mrule_#",$key)) {
							$rules[$key] = $value;
						   }
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","gid = '".$gid."'");
					foreach($rules as $key => $value) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
					}
					$this->log(0,"Group Id '".$gid."' edited");
					FS::$iMgr->redir("mod=".$this->mid);

				default: break;
			}
		}
	};
?>
