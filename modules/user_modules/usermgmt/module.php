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
	class iUserMgmt extends genModule{
		function iUserMgmt() { parent::genModule(); }
		public function Load() {
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "";
			switch($err) {
				case 1: $output .= FS::$iMgr->printError("Utilisateur invalide"); break;
				case 2: $output .= FS::$iMgr->printError("Informations invalides"); break;
			}

			$user = FS::$secMgr->checkAndSecuriseGetData("user");
			if(!$user)
				$output = $this->showMain();
			else
				$output = $this->EditUser($user);
			return $output;
		}

		private function EditUser($user) {
			$uid = FS::$pgdbMgr->GetOneData("z_eye_users","uid","username = '".$user."'");
			$output = "<h3>Modification de l'utilisateur</h3>";
			if(!$uid) {
				$output .= FS::$iMgr->printError("L'utilisateur demandé n'existe pas !");
				return $output;
			}
			$output = FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=2");
                        $output .= "<ul class=\"ulform\"><li><b>Nom d'utilisateur:</b> ".$user.FS::$iMgr->addHidden("uid",$uid)."</li>";
			$grpidx = 0;
			$query = FS::$pgdbMgr->Select("z_eye_user_group","gid","uid = '".$uid."'");
			while($data = pg_fetch_array($query)) {
				$output .= "<li class=\"ugroupli".$grpidx."\">".FS::$iMgr->addList("ugroup".$grpidx,"","Groupe").$this->addGroupList($data["gid"])."</select>";
				$output .= " <a onclick=\"javascript:delGrpElmt(".$grpidx.");\">X</a></li>";
				$grpidx++;
			}
                        $output .= "<li id=\"formactions\">".FS::$iMgr->addButton("newgrp","Nouveau Groupe","addGrpForm()").FS::$iMgr->addSubmit("","Ajouter")."</li>";
                        $output .= "</ul></form>";

			$output .= "<script type=\"text/javascript\">grpidx = ".$grpidx."; function addGrpForm() {
                                $('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->addList("ugroup'+grpidx+'","","Groupe").$this->addGroupList()."</select>";
                        $output .= " <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
                                        grpidx++;
                                }
                                function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }</script>";

			return $output;
		}

		private function addGrouplist($gid) {
			$output = "";
			$query = FS::$pgdbMgr->Select("z_eye_groups","gid,gname");
			while($data = pg_fetch_array($query))
				$output .= FS::$iMgr->addElementToList($data["gname"],$data["gid"],$gid == $data["gid"] ? true : false);
			return $output;
		}

		private function showMain() {
			$output = "<h3>Gestion des utilisateurs</h3>";

			$tmpoutput = "";
			$found = 0;
			$query = FS::$pgdbMgr->Select("z_eye_users","uid,username,mail,last_ip,join_date,last_conn,name,subname,sha_pwd");
			while($data = pg_fetch_array($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"utb\"><tr><th>UID</th><th>Utilisateur</th><th>Type d'utilisateur</th><th>Groupes</th><th>Prénom</th><th>Nom</th><th>Mail</th><th>Dernière IP</th><th>Dernière connexion</th><th>Inscription</th></tr>";
				}
				$tmpoutput .= "<tr><td>".$data["uid"]."</td><td id=\"dragtd\" draggable=\"true\">".$data["username"]."</td><td>".($data["sha_pwd"] == "" ? "Externe" : "Interne")."</td><td>";
				$query2 = FS::$pgdbMgr->Select("z_eye_user_group","gid","uid = '".$data["uid"]."'");
				while($data2 = pg_fetch_array($query2)) {
					$gname = FS::$pgdbMgr->GetOneData("z_eye_groups","gname","gid = '".$data2["gid"]."'");
					$tmpoutput .= $gname."<br />";
				}
				$tmpoutput .= "</td><td>".$data["subname"]."</td><td>".$data["name"]."</td><td>".$data["mail"]."</td><td>".$data["last_ip"]."</td><td>".
					$data["last_conn"]."</td><td>".$data["join_date"]."</td></tr>";
			}

			if($found) {
				$output .= $tmpoutput."</table>";
				$output .= "<script type=\"text/javascript\">
                                $.event.props.push('dataTransfer');
                                $('#utb #dragtd').on({
                                        mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
                                        mouseleave: function(e) { $('#trash').hide(); $('#editf').hide();},
                                        dragstart: function(e) { $('#trash').show(); $('#editf').show(); e.dataTransfer.setData('text/html', $(this).text()); },
                                        dragenter: function(e) { e.preventDefault();},
                                        dragover: function(e) { e.preventDefault(); },
                                        dragleave: function(e) { },
                                        drop: function(e) {},
                                        dragend: function() { $('#trash').hide(); $('#editf').hide();}
                                });
				$('#editf').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $(location).attr('href','index.php?mod=".$this->mid."&user='+e.dataTransfer.getData('text/html')); }
                                });
                                $('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { $('#subpop').html('Êtes vous sûr de vouloir supprimer l\'utilisateur \''+e.dataTransfer.getData('text/html')+'\' ?".
                                              FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=3").
                                              FS::$iMgr->addHidden("username","'+e.dataTransfer.getData('text/html')+'").
                                              FS::$iMgr->addSubmit("","Supprimer").
                                              FS::$iMgr->addButton("popcancel","Annuler","$(\'#pop\').hide()")."</form>');
                                              $('#pop').show();
                                }
                                });</script>";
			}
			return $output;
		}
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // add user
					break;
				case 2: // edit user
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					if(!$uid || !FS::$secMgr->isNumeric($uid)) {
						header("Location: index.php?mod=".$this->mid."&err=2");
                                                return;
                                        }

					$groups = array();
					foreach($_POST as $key => $value) {
                                               if(preg_match("#^ugroup#",$key)) {
							$exist = FS::$pgdbMgr->GetOneData("z_eye_groups","gname","gid = '".$value."'");
							if(!$exist) {
								header("Location: index.php?mod=".$this->mid."&err=2");
								return;
							}
							array_push($groups,$value);
                                               }
                                        }
					FS::$pgdbMgr->Delete("z_eye_user_group","uid = '".$uid."'");
					$groups = array_unique($groups);
					for($i=0;$i<count($groups);$i++)
						FS::$pgdbMgr->Insert("z_eye_user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					header("Location: index.php?mod=".$this->mid);
					return;
				case 3: // del user
					$username = FS::$secMgr->checkAndSecurisePostData("username");
					if(!$username) {
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$pgdbMgr->GetOneData("z_eye_users","uid","username = '".$username."'");
					if(!$exist) {
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					FS::$pgdbMgr->Delete("z_eye_users","username = '".$username."'");
					header("Location: index.php?mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
