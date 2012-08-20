<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
	require_once(dirname(__FILE__)."/../../../lib/FSS/LDAP.FS.class.php");
	class iGroupMgmt extends genModule{
		function iGroupMgmt() { parent::genModule(); }
		public function Load() {
			$output = "";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("Ce groupe existe déjà !"); break;
				case 2: $output .= FS::$iMgr->printError("Données invalides."); break;
				case 3: $output .= FS::$iMgr->printError("Ce groupe n'existe pas !"); break;
			}
			if(!FS::isAjaxCall()) {
				$gname = FS::$secMgr->checkAndSecuriseGetData("g");
				if($gname)
					$output .= $this->editGroup($gname);
				else
					$output .= $this->showMain();
			}
			return $output;
		}

		private function editGroup($gname) {
			$gid = FS::$pgdbMgr->GetOneData("z_eye_groups","gid","gname = '".$gname."'");
			if(!$gid) {
				return FS::$iMgr->printError("Ce groupe n'existe pas !");
			}

			FS::$iMgr->showReturnMenu(true);
			$output = "<h3>Edition du groupe '".$gname."'</h3>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=3");
			$rules = array();
			$query = FS::$pgdbMgr->Select("z_eye_group_rules","rulename","gid = '".$gid."' AND ruleval = 'on'");
			while($data = pg_fetch_array($query))
				array_push($rules,$data["rulename"]);
			$output .= $this->loadModuleRuleSets($rules);
			$output .= FS::$iMgr->addHidden("gid",$gid);
			$output .= FS::$iMgr->addSubmit("","Enregistrer")."</form>";
			return $output;
		}

		private function showMain() {
			$output = "<h3>Gestion des groupes</h3>";
			$formoutput = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$formoutput .= "<ul class=\"ulform\"><li>".FS::$iMgr->addInput("gname","",20,40,"Nom du groupe");
			$formoutput .= "<h3>Options des modules</h3>";
			$formoutput .= $this->loadModuleRuleSets();
                        $formoutput .= "</li><li>".FS::$iMgr->addSubmit("reggrp","Ajouter")."</li>";
			$formoutput .= "</ul></form>";
			$output .= FS::$iMgr->addOpenableDiv($formoutput,"Nouveau Groupe");
			$tmpoutput = "";
			$found = 0;
			$query = FS::$pgdbMgr->Select("z_eye_groups","gid,gname,description");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"grpt\"><tr><th>GID</th><th>Nom du groupe</th><th>Nombre d'utilisateurs</th></tr>";
				}
				$tmpoutput .= "<tr><td>".$data["gid"]."</td><td id=\"dragtd\" draggable=\"true\">".$data["gname"]."</td><td>".FS::$pgdbMgr->Count("z_eye_user_group","gid","gid = '".$data["gid"]."'")."</td></tr>";
			}
			if($found) {
				$output .= $tmpoutput."</table>";
				$output .= "<script type=\"text/javascript\">
                                $.event.props.push('dataTransfer');
                                $('#grpt #dragtd').on({
                                        mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
                                        mouseleave: function(e) { $('#trash').hide(); $('#editf').hide(); },
                                        dragstart: function(e) { $('#trash').show(); e.dataTransfer.setData('text/html', $(this).text()); },
                                        dragenter: function(e) { e.preventDefault();},
                                        dragover: function(e) { e.preventDefault(); },
                                        dragleave: function(e) { },
                                        drop: function(e) {},
                                        dragend: function() { $('#trash').hide(); $('#editf').hide(); }
                                });
                                $('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer le groupe \''+e.dataTransfer.getData('text/html')+'\' ?".
                                              FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2").
  	                                      FS::$iMgr->addHidden("gname","'+e.dataTransfer.getData('text/html')+'").
                                              FS::$iMgr->addSubmit("","Supprimer").
                                              FS::$iMgr->addButton("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                              $('#pop').show();
                                	}
                                });
				$('#editf').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&g='+e.dataTransfer.getData('text/html')); }
                                });</script>";
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
									$output .= "<table><tr><th>Module</th><th>Règle</th></tr>";
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
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$pgdbMgr->GetOneData("z_eye_groups","gid","gname = '".$gname."'");
					if($exist) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$gid = FS::$pgdbMgr->GetMax("z_eye_groups","gid")+1;
					FS::$pgdbMgr->Insert("z_eye_groups","gid,gname","'".$gid."','".$gname."'");
					$rules = array();
                                        foreach($_POST as $key => $value) {
                                               if(preg_match("#^mrule_#",$key)) {
                                                        $rules[$key] = $value;
                                               }
                                        }
					foreach($rules as $key => $value) {
						FS::$pgdbMgr->Insert("z_eye_group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
					}
					header("Location: index.php?mod=".$this->mid);
					return;
				case 2:
					$gname = FS::$secMgr->checkAndSecurisePostData("gname");
                                        if(!$gname) {
                                                header("Location: index.php?mod=".$this->mid."&err=2");
                                                return;
                                        }
					$gid = FS::$pgdbMgr->GetOneData("z_eye_groups","gid","gname = '".$gname."'");
                                        if(!$gid) {
                                                header("Location: index.php?mod=".$this->mid."&err=1");
                                                return;
                                        }
					FS::$pgdbMgr->Delete("z_eye_groups","gname = '".$gname."'");
					FS::$pgdbMgr->Delete("z_eye_group_rules","gid = '".$gid."'");
					FS::$pgdbMgr->Delete("z_eye_user_group","gid = '".$gid."'");
					header("Location: index.php?mod=".$this->mid);
                                        return;
				case 3:
					$gid = FS::$secMgr->checkAndSecurisePostData("gid");
                                        if(!$gid) {
                                                header("Location: index.php?mod=".$this->mid."&err=3");
                                                return;
                                        }
                                        $rules = array();
                                        foreach($_POST as $key => $value) {
                                               if(preg_match("#^mrule_#",$key)) {
                                                        $rules[$key] = $value;
                                               }
                                        }
					FS::$pgdbMgr->Delete("z_eye_group_rules","gid = '".$gid."'");
                                        foreach($rules as $key => $value) {
                                                FS::$pgdbMgr->Insert("z_eye_group_rules","gid,rulename,ruleval","'".$gid."','".$key."','".$value."'");
                                        }
                                        header("Location: index.php?mod=".$this->mid);

				default: break;
			}
		}
	};
?>
