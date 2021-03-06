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

	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	if(!class_exists("iGroupMgmt")) {
		
	final class iGroupMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->modulename = "groupmgmt";
			$this->rulesclass = new rGroupMgmt();
			
			$this->menu = _("Users and rights");
			$this->menutitle = _("Z-Eye groups management");
		}

		public function Load() {
			FS::$iMgr->setURL("");
			FS::$iMgr->setTitle(_("title-mgmt"));
			return FS::$iMgr->h1("title-mgmt").$this->showMain();
		}

		private function editGroup($gname) {
			$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
			if (!$gid) {
				return FS::$iMgr->printError("err-not-exist");
			}

			$output = "<b>"._("title-edit")." '".$gname."'</b>".
				FS::$iMgr->cbkForm("3");
			$rules = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","rulename","gid = '".$gid."' AND ruleval = 'on'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$rules[] = $data["rulename"];
			}
			return $output.$this->loadModuleRuleSets($rules).
				FS::$iMgr->hidden("gid",$gid).
				FS::$iMgr->submit("",_("Save"))."</form>";
		}

		private function showMain() {
			$output = FS::$iMgr->opendiv(1,_("New-group"));
			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname,description");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"groupList\"><thead><tr><th class=\"headerSortDown\">GID</th><th>".
						_("Groupname")."</th><th>"._("User-nb")."</th><th></th></tr></thead>";
				}
				$tmpoutput .= "<tr id=\"gr".$data["gid"]."tr\"><td>".$data["gid"]."</td><td>".
					FS::$iMgr->opendiv(2,$data["gname"],array("lnkadd" => "g=".$data["gname"]))."</td><td>".
					FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."user_group","gid","gid = '".$data["gid"]."'")."</td><td>".
					FS::$iMgr->removeIcon(2,"gname=".$data["gname"],
						array("js" => true, 
							"confirmtext" => "confirm-removegrp",
							"confirmval" => $data["gname"]
						))."</td></tr>";
			}
			if ($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("groupList");
			}
			return $output;
		}

		private function loadModuleRuleSets($activerules = array()) {
			$output = "";
			$dir = opendir(dirname(__FILE__)."/../");
			$found = 0;
			while ($elem = readdir($dir)) {
				$dirpath = dirname(__FILE__)."/../".$elem;
				if (is_dir($dirpath) && $elem != ".." && $elem != ".") {
					$dir2 = opendir($dirpath);
					while ($elem2 = readdir($dir2)) {
						if (is_file($dirpath."/".$elem2) && $elem2 == "rules.php") {
							require($dirpath."/module.php");

							$tmpoutput = $module->getRulesClass()->showMgmtInterface($activerules);
							if (strlen($tmpoutput) > 0) {
								if ($found == 0) {
									$found = 1;
									$output .= "<table id=\"ruleList\"><thead><tr><th>Module</th><th>"._("Rule")."</th></tr></thead>";
								}
								$output .= $tmpoutput;
							}
						}
					}
				}
			}
			if ($found) {
				$output .= "</table>";
			}
			return $output;
		}

		private function getGroupForm() {
			$output = FS::$iMgr->cbkForm("1").
				"<ul class=\"ulform\"><li>".FS::$iMgr->input("gname","",20,40,"Groupname").
				FS::$iMgr->h2("title-opts").
				$this->loadModuleRuleSets().
				"</li><li>".FS::$iMgr->submit("reggrp",_("Add"))."</li>".
				"</ul></form>";
			return $output;
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->getGroupForm();
				case 2:
					$gname = FS::$secMgr->checkAndSecuriseGetData("g");
					if (!$gname) {
						return _("err-bad-datas");
					}
					return $this->editGroup($gname);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					// @TODO description field
					$gname = FS::$secMgr->checkAndSecurisePostData("gname");
					if (!$gname) {
						$this->log(2,"Some datas are missing when try to create group");
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if ($exist) {
						FS::$iMgr->ajaxEchoError("err-already-exist");
						$this->log(1,"The group ".$gname." already exists");
						return;
					}
					
					FS::$dbMgr->BeginTr();
					
					$gid = FS::$dbMgr->GetMax(PGDbConfig::getDbPrefix()."groups","gid")+1;
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."groups","gid,gname","'".$gid."','".$gname."'");
					$rules = array();
					foreach ($_POST as $key => $value) {
						   if (preg_match("#^mrule_#",$key)) {
									$rules[$key] = $value;
						   }
					}
					foreach ($rules as $key => $value) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
					}
					
					FS::$dbMgr->CommitTr();
					
					$this->log(0,"New group '".$gname."' added");
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				// Remove group
				case 2:
					$gname = FS::$secMgr->checkAndSecuriseGetData("gname");
					if (!$gname) {
						FS::$iMgr->ajaxEchoError("err-bad-datas");
						$this->log(2,"Some datas are missing when try to remove group");
						return;
					}
					$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if (!$gid) {
						FS::$iMgr->ajaxEchoError("err-not-exist");
						$this->log(1,"Unable to remove group '".$gname."', group doesn't exists");
						return;
					}
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."groups","gname = '".$gname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","gid = '".$gid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","gid = '".$gid."'");
					FS::$dbMgr->CommitTr();
					
					$this->log(0,"Group '".$gname."' removed");
					FS::$iMgr->ajaxEchoOK("Done","hideAndRemove('#gr".$gid."tr');");
                    return;
				case 3:
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					if (!$gid) {
						FS::$iMgr->ajaxEchoError("err-not-exist");
						$this->log(2,"Some datas are missing when try to edit group");
						return;
					}
					$rules = array();
					foreach ($_POST as $key => $value) {
						   if (preg_match("#^mrule_#",$key)) {
							$rules[$key] = $value;
						   }
					}
					
					FS::$dbMgr->BeginTr();
					
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","gid = '".$gid."'");
					foreach ($rules as $key => $value) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
					}
					
					FS::$dbMgr->CommitTr();
					
					$this->log(0,"Group Id '".$gid."' edited");
					FS::$iMgr->ajaxEchoOK("Done");

				default: break;
			}
		}
	};
	
	}
	
	$module = new iGroupMgmt();
?>
