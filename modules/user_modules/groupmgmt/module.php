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

	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");

	class iGroupMgmt extends genModule{
		function iGroupMgmt() { parent::genModule(); $this->loc = new lGroupMgmt(); }
		public function Load() {
			FS::$iMgr->setCurrentModule($this);
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
			$output = "<h2>".$this->loc->s("title-edit")." '".$gname."'</h2>";
			$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=3");
			$rules = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."group_rules","rulename","gid = '".$gid."' AND ruleval = 'on'");
			while($data = FS::$dbMgr->Fetch($query))
				array_push($rules,$data["rulename"]);
			$output .= $this->loadModuleRuleSets($rules);
			$output .= FS::$iMgr->hidden("gid",$gid);
			$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</form>";
			return $output;
		}

		private function showMain() {
			$output = "";
			$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
			$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->input("gname","",20,40,$this->loc->s("Groupname"));
			$formoutput .= "<h2>".$this->loc->s("title-opts")."</h2>";
			$formoutput .= $this->loadModuleRuleSets();
                        $formoutput .= "</li><li>".FS::$iMgr->submit("reggrp",$this->loc->s("Add"))."</li>";
			$formoutput .= "</ul></form>";
			$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("New-group"));
			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname,description");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table><tr><th>GID</th><th>".$this->loc->s("Groupname")."</th><th>".$this->loc->s("User-nb")."</th><th></th></tr>";
				}
				$tmpoutput .= "<tr><td>".$data["gid"]."</td><td><a href=\"index.php?mod=".$this->mid."&g=".$data["gname"]."\">".$data["gname"]."</a></td><td>".
					FS::$dbMgr->Count(PGDbConfig::getDbPrefix()."user_group","gid","gid = '".$data["gid"]."'")."</td><td>".FS::$iMgr->removeIcon("index.php?mod=".$this->mid."&act=2&gname=".$data["gname"])."</td></tr>";
			}
			if($found) {
				$output .= $tmpoutput."</table>";
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
									$output .= "<table><tr><th>Module</th><th>".$this->loc->s("Rule")."</th></tr>";
								}
								$output .= $tmpoutput;
							}
						}
					}
				}
			}
			if($found) $output .= "</table>";
			return $output;
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					// @TODO description field
					$gname = FS::$secMgr->checkAndSecurisePostData("gname");
					if(!$gname) {
						FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",2,"Some datas are missing when try to create group");
						FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if($exist) {
						FS::$iMgr->redir("mod=".$this->mid."&err=1");
						FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",1,"The group ".$gname." already exists");
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
					FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",0,"New group '".$gname."' added");
					FS::$iMgr->redir("mod=".$this->mid);
					return;
				case 2:
					$gname = FS::$secMgr->checkAndSecuriseGetData("gname");
					if(!$gname) {
						FS::$iMgr->redir("mod=".$this->mid."&err=2");
						FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",2,"Some datas are missing when try to remove group");
						return;
					}
					$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
					if(!$gid) {
						FS::$iMgr->redir("mod=".$this->mid."&err=1");
						FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",1,"Unable to remove group '".$gname."', group doesn't exists");
						return;
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."groups","gname = '".$gname."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."group_rules","gid = '".$gid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","gid = '".$gid."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",0,"Group '".$gname."' removed");
					FS::$iMgr->redir("mod=".$this->mid);
                                        return;
				case 3:
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
					if(!$gid) {
						FS::$iMgr->redir("mod=".$this->mid."&err=3");
						FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",2,"Some datas are missing when try to edit group");
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
					FS::$log->i(FS::$sessMgr->getUserName(),"groupmgmt",0,"Group Id '".$gid."' edited");
					FS::$iMgr->redir("mod=".$this->mid);

				default: break;
			}
		}
	};
?>
