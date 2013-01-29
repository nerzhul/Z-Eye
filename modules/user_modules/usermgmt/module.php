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

	class iUserMgmt extends genModule{
		function iUserMgmt() { parent::genModule(); $this->loc = new lUserMgmt(); }
		public function Load() {
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "";
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-user")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-bad-data")); break;
				case 3: $output .= FS::$iMgr->printError($this->loc->s("err-ldap-bad-data")); break;
				case 4: $output .= FS::$iMgr->printError($this->loc->s("err-ldap-exist")); break;
				case 5: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-match")); break;
				case 6: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-short")); break;
				case 7: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-complex")); break;
				case 8: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-unk")); break;
				case 9: $output .= FS::$iMgr->printError($this->loc->s("err-mail")); break;
			}

			$user = FS::$secMgr->checkAndSecuriseGetData("user");
			$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
			if($user)
				$output .= $this->EditUser($user);
			else if($addr)
				$output .= $this->EditServer($addr);
			else
				$output .= $this->showMain();
			return $output;
		}

		private function EditUser($user) {
			$uid = FS::$dbMgr->GetOneData("z_eye_users","uid","username = '".$user."'");
			$output = "<h2>".$this->loc->s("title-user-mod")."</h2>";
			if(!$uid) {
				$output .= FS::$iMgr->printError($this->loc->s("title-user-dont-exist"));
				return $output;
			}
			$output = FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
                        $output .= "<ul class=\"ulform\"><li><b>".$this->loc->s("User").":</b> ".$user.FS::$iMgr->hidden("uid",$uid)."</li>";
			if(FS::$dbMgr->GetOneData("z_eye_users","sha_pwd","username = '".$user."'")) {
				$mail = FS::$dbMgr->GetOneData("z_eye_users","mail","username = '".$user."'");
				$output .= "<li><i>".$this->loc->s("tip-password")."</i></li>
					<li>".$this->loc->s("Password").": ".FS::$iMgr->password("pwd","")."</li>
					<li>".$this->loc->s("Password-repeat").": ".FS::$iMgr->password("pwd2","")."</li>
					<li>".$this->loc->s("Mail").": ".FS::$iMgr->input("mail",$mail,24,64)."</li>";
			}
			$grpidx = 0;
			$query = FS::$dbMgr->Select("z_eye_user_group","gid","uid = '".$uid."'");
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= "<li class=\"ugroupli".$grpidx."\">".FS::$iMgr->select("ugroup".$grpidx,"",$this->loc->s("Group")).$this->addGroupList($data["gid"])."</select>";
				$output .= " <a onclick=\"javascript:delGrpElmt(".$grpidx.");\">X</a></li>";
				$grpidx++;
			}
                        $output .= "<li id=\"formactions\">".FS::$iMgr->button("newgrp","Nouveau Groupe","addGrpForm()").FS::$iMgr->submit("",$this->loc->s("Modify"))."</li>";
                        $output .= "</ul></form>";

			$output .= "<script type=\"text/javascript\">grpidx = ".$grpidx."; function addGrpForm() {
                                $('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->select("ugroup'+grpidx+'","","Groupe").$this->addGroupList()."</select>";
                        $output .= " <a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
                                        grpidx++;
                                }
                                function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }</script>";

			return $output;
		}

		private function EditServer($addr) {
			if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) 
				return FS::$iMgr->printError($this->loc->s("err-rights"));

			$output = "<h2>".$this->loc->s("title-directory")."</h2>";
			$query = FS::$dbMgr->Select("z_eye_ldap_auth_servers","port,dn,rootdn,dnpwd,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl","addr = '".$addr."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=6");
				$output .= "<ul class=\"ulform\">".FS::$iMgr->hidden("addr",$addr)."<li><b>".$this->loc->s("Directory").": </b>".$addr."</li><li>";
				$output .= FS::$iMgr->numInput("port",$data["port"],array("size" => 5, "length" => 5, "label" => $this->loc->s("ldap-port")))."</li><li>";
				$output .= FS::$iMgr->check("ssl",array("check" => ($data["ssl"] == 1 ? true : false),"label" => "SSL ?"))."</li><li>";
				$output .= FS::$iMgr->input("dn",$data["dn"],20,200,$this->loc->s("base-dn"))."</li><li>";
				$output .= FS::$iMgr->input("rootdn",$data["rootdn"],20,200,$this->loc->s("root-dn"))."</li><li>";
				$output .= FS::$iMgr->password("rootpwd",$data["dnpwd"],$this->loc->s("root-pwd"))."</li><li>";
				$output .= FS::$iMgr->input("ldapname",$data["ldapname"],20,40,$this->loc->s("attr-name"))."</li><li>";
				$output .= FS::$iMgr->input("ldapsurname",$data["ldapsurname"],20,40,$this->loc->s("attr-subname"))."</li><li>";
				$output .= FS::$iMgr->input("ldapmail",$data["ldapmail"],20,40,$this->loc->s("attr-mail"))."</li><li>";
				$output .= FS::$iMgr->input("ldapuid",$data["ldapuid"],20,40,$this->loc->s("attr-uid"))."</li><li>";
				$output .= FS::$iMgr->input("ldapfilter",$data["filter"],20,200,$this->loc->s("ldap-filter"))."</li><li>";
				$output .= FS::$iMgr->submit("",$this->loc->s("Save"))."</li>";
				$output .= "</ul></form>";
			}
			else {
				$output .= FS::$iMgr->printError($this->loc->s("err-ldap-not-exist"));
					return $output;
			}

			return $output;
		}

		private function addGrouplist($gid=-1) {
			$output = "";
			$query = FS::$dbMgr->Select("z_eye_groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query))
				$output .= FS::$iMgr->selElmt($data["gname"],$data["gid"],$gid == $data["gid"] ? true : false);
			return $output;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("title-usermgmt")."</h1>";

			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select("z_eye_users","uid,username,mail,last_ip,join_date,last_conn,name,subname,sha_pwd");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table><tr><th>UID</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("User-type")."</th><th>".
					$this->loc->s("Groups")."</th><th>".$this->loc->s("Subname")."</th><th>".$this->loc->s("Name")."</th><th>".
					$this->loc->s("Mail")."</th><th>".$this->loc->s("last-ip")."</th><th>".$this->loc->s("last-conn")."</th><th>".$this->loc->s("inscription")."</th><th></th></tr>";
				}
				$tmpoutput .= "<tr><td>".$data["uid"]."</td><td><a href=\"index.php?mod=".$this->mid."&user=".$data["username"]."\">".$data["username"]."</a></td><td>".
					($data["sha_pwd"] == "" ? $this->loc->s("Extern") : $this->loc->s("Intern"))."</td><td>";
				$query2 = FS::$dbMgr->Select("z_eye_user_group","gid","uid = '".$data["uid"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					$gname = FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$data2["gid"]."'");
					$tmpoutput .= $gname."<br />";
				}
				$tmpoutput .= "</td><td>".$data["subname"]."</td><td>".$data["name"]."</td><td>".$data["mail"]."</td><td>".$data["last_ip"]."</td><td>".
					$data["last_conn"]."</td><td>".$data["join_date"]."</td><td><a href=\"index.php?mod=".$this->mid."&act=3&uid=".$data["uid"]."\">".FS::$iMgr->removeIcon()."</a></td></tr>";
			}

			if($found) {
				$output .= $tmpoutput."</table>";
			}
			if(FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
				$output .= "<h1>".$this->loc->s("title-directorymgmt")."</h1>";
				$formoutput = FS::$iMgr->form("index.php?mod=".$this->mid."&act=4");
				$formoutput .= "<ul class=\"ulform\"><li>";
				$formoutput .= FS::$iMgr->input("addr","",20,40,$this->loc->s("ldap-addr"))."</li><li>";
				$formoutput .= FS::$iMgr->numInput("port","389",array("size" => 5, "length" => 5,"label" => $this->loc->s("ldap-port")))."</li><li>";
				$formoutput .= FS::$iMgr->check("ssl",array("label" => $this->loc->s("SSL")." ?"))."</li><li>";
				$formoutput .= FS::$iMgr->input("dn","",20,200,$this->loc->s("base-dn"))."</li><li>";
				$formoutput .= FS::$iMgr->input("rootdn","",20,200,$this->loc->s("root-dn"))."</li><li>";
				$formoutput .= FS::$iMgr->password("rootpwd","",$this->loc->s("root-pwd"))."</li><li>";
				$formoutput .= FS::$iMgr->input("ldapname","",20,40,$this->loc->s("attr-name"))."</li><li>";
				$formoutput .= FS::$iMgr->input("ldapsurname","",20,40,$this->loc->s("attr-subname"))."</li><li>";
				$formoutput .= FS::$iMgr->input("ldapmail","",20,40,$this->loc->s("attr-mail"))."</li><li>";
				$formoutput .= FS::$iMgr->input("ldapuid","",20,40,$this->loc->s("attr-uid"))."</li><li>";
				$formoutput .= FS::$iMgr->input("ldapfilter","",20,200,$this->loc->s("ldap-filter"))."</li><li>";
				$formoutput .= FS::$iMgr->submit("",$this->loc->s("Save"))."</li>";
				$formoutput .= "</ul></form>";

				$output .= FS::$iMgr->opendiv($formoutput,$this->loc->s("new-directory"));

				$found = 0;
				$tmpoutput = "";
				$query = FS::$dbMgr->Select("z_eye_ldap_auth_servers","addr,port,dn,rootdn,filter");
				while($data = FS::$dbMgr->Fetch($query)) {
					if(!$found) {
						$found = 1;
						$tmpoutput .= "<table id=\"ldaptb\"><tr><th>".$this->loc->s("Server")."</th><th>".$this->loc->s("port").
						"</th><th>".$this->loc->s("base-dn")."</th><th>".$this->loc->s("root-dn")."</th><th>".$this->loc->s("ldap-filter")."</th></tr>";
					}
					$tmpoutput .= "<tr><td id=\"dragtd\" draggable=\"true\">".$data["addr"]."</td><td>".$data["port"]."</td><td>".$data["dn"]."</td><td>".$data["rootdn"]."</td><td>".$data["filter"]."</td></tr>";
				}
			}
			if($found) {
				$output .= $tmpoutput."</table>";
				$output .= "<script type=\"text/javascript\">var datatype = 0;
        	                       $.event.props.push('dataTransfer');
                	               $('#ldaptb #dragtd').on({
                                       mouseover: function(e) { $('#trash').show(); $('#editf').show(); },
                               	       mouseleave: function(e) { $('#trash').hide(); $('#editf').hide();},
                                       dragstart: function(e) { $('#trash').show(); $('#editf').show(); datatype=2; e.dataTransfer.setData('text/html', $(this).text()); },
                                       dragenter: function(e) { e.preventDefault();},
    	                               dragover: function(e) { e.preventDefault(); },
               	                       dragleave: function(e) { },
                                       drop: function(e) {},
                          	       dragend: function() { $('#trash').hide(); $('#editf').hide(); }
	                        });</script>";
			}
			$output .= "<script type=\"text/javascript\">
				$('#editf').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) {
					if(datatype == 2) { $(location).attr('href','index.php?mod=".$this->mid."&addr='+e.dataTransfer.getData('text/html')); } 
				}
        	                });
                                $('#trash').on({
                                        dragover: function(e) { e.preventDefault(); },
                                        drop: function(e) { 
					if(datatype == 2) {
						$('#subpop').html('".$this->loc->s("sure-remove-directory")." \''+e.dataTransfer.getData('text/html')+'\' ?".
                                              FS::$iMgr->form("index.php?mod=".$this->mid."&act=5").
                                              FS::$iMgr->hidden("addr","'+e.dataTransfer.getData('text/html')+'").
                                              FS::$iMgr->submit("",$this->loc->s("Remove")).
                                              FS::$iMgr->button("popcancel",$this->loc->s("Cancel"),"$(\'#pop\').hide()")."</form>');
                                              $('#pop').show();
					}
					datatype = 0;
                                }
                        });</script>";
			return $output;
		}
		public function handlePostDatas($act) {
			switch($act) {
				case 1: // add user
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to add user but don't have rights");
						return;
					}
					break;
				case 2: // edit user
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to edit user but don't have rights !");
						return;
					}
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					if(!$uid || !FS::$secMgr->isNumeric($uid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing for user management (user edit)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}

					$username = FS::$dbMgr->GetOneData("z_eye_users","username","uid = '".$uid."'");
					if(!$username) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User uid '".$uid."' doesn't exists");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}

					$pwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$pwd2 = FS::$secMgr->checkAndSecurisePostData("pwd2");
					if($pwd || $pwd2) {
						if($pwd != $pwd2) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Try to modify password for user ".$uid." but passwords didn't match");
							header("Location: index.php?mod=".$this->mid."&user=".$username."&err=5");
							return;
						}
						$user = new User();
						$user->LoadFromDB($uid);
						switch($user->changePassword($pwd)) {
							case 0: break; // ok
							case 1: // too short
								header("Location: index.php?mod=".$this->mid."&user=".$username."&err=6");
								return;
							case 2: // complexity
								header("Location: index.php?mod=".$this->mid."&user=".$username."&err=7");
                                                                return;
							default: // unk
								header("Location: index.php?mod=".$this->mid."&user=".$username."&err=8");
                                                                return;
						}
					}

					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					if($mail) {
						if(!FS::$secMgr->isMail($mail)) {
							header("Location: index.php?mod=".$this->mid."&user=".$username."&err=9");
							return;
						}
						FS::$dbMgr->Update("z_eye_users","mail = '".$mail."'","uid = '".$uid."'");
					}

					$groups = array();
					foreach($_POST as $key => $value) {
						   if(preg_match("#^ugroup#",$key)) {
								$exist = FS::$dbMgr->GetOneData("z_eye_groups","gname","gid = '".$value."'");
								if(!$exist) {
									FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Try to add user ".$uid." to inexistant group '".$value."'");
									header("Location: index.php?mod=".$this->mid."&user=".$username."&err=2");
									return;
								}
								array_push($groups,$value);
						   }
					}
					FS::$dbMgr->Delete("z_eye_user_group","uid = '".$uid."'");
					$groups = array_unique($groups);
					$count = count($groups);
					for($i=0;$i<$count;$i++)
						FS::$dbMgr->Insert("z_eye_user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"User ".$uid." edited");
					header("Location: index.php?mod=".$this->mid);
					return;
				case 3: // del user
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to delete user but don't have rights");
                                                return;
                                        }
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					if(!$uid || !FS::$secMgr->isNumeric($uid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are wrong or missing for user management (User delete)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$dbMgr->GetOneData("z_eye_users","last_conn","uid = '".$uid."'");
					if(!$exist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to remove user '".$uid."', doesn't exist");
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					FS::$dbMgr->Delete("z_eye_users","uid = '".$uid."'");
					FS::$dbMgr->Delete("z_eye_user_group","uid = '".$uid."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"User '".$uid."' removed");
					header("Location: index.php?mod=".$this->mid);
					return;
				case 4: // add ldap
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to add ldap but don't have rights");
                                                return;
                                        }
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$ssl = FS::$secMgr->checkAndSecurisePostData("ssl");
					$basedn = FS::$secMgr->checkAndSecurisePostData("dn");
					$rootdn = FS::$secMgr->checkAndSecurisePostData("rootdn");
					$rootpwd = FS::$secMgr->checkAndSecurisePostData("rootpwd");
					$ldapname = FS::$secMgr->checkAndSecurisePostData("ldapname");
					$ldapsurname = FS::$secMgr->checkAndSecurisePostData("ldapsurname");
					$ldapmail = FS::$secMgr->checkAndSecurisePostData("ldapmail");
					$ldapuid = FS::$secMgr->checkAndSecurisePostData("ldapuid");
					$ldapfilter = FS::$secMgr->checkAndSecurisePostData("ldapfilter");

					if(!$addr || !$port || !FS::$secMgr->isNumeric($port) || !$basedn || !$rootdn || !$rootpwd || !$ldapname || !$ldapsurname || !$ldapmail || !$ldapuid || !$ldapfilter) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing/wrong for user management (LDAP add)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}

					$serv = FS::$dbMgr->GetOneData("z_eye_ldap_auth_servers","addr","addr = '".$addr."'");
					if($serv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", already exists");
						header("Location: index.php?mod=".$this->mid."&err=4");
						return;
					}

					$ldapMgr = new LDAP();
					$ldapMgr->setServerInfos($addr,$port,$ssl == "on" ? true : false,$basedn,$rootdn,$rootpwd,$ldapuid,$ldapfilter);
					if(!$ldapMgr->RootConnect()) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", connection fail");
						header("Location: index.php?mod=".$this->mid."&err=3");
						return;
					}

					FS::$dbMgr->Insert("z_eye_ldap_auth_servers","addr,port,ssl,dn,rootdn,dnpwd,filter,ldapuid,ldapmail,ldapname,ldapsurname",
						"'".$addr."','".$port."','".($ssl == "on" ? 1 : 0)."','".$basedn."','".$rootdn."','".$rootpwd."','".$ldapfilter."','".$ldapuid."','".$ldapmail."','".$ldapname."','".$ldapsurname."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"New LDAP: ".$addr.":".$port." basedn: ".$basedn);
					header("Location: index.php?mod=".$this->mid);
					return;
				case 5: // LDAP remove
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to remove ldap but don't have rights");
                                                return;
                                        }
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					if(!$addr) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing for user management (LDAP remove)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}

					$serv = FS::$dbMgr->GetOneData("z_eye_ldap_auth_servers","addr","addr = '".$addr."'");
					if(!$serv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to remove LDAP ".$addr.":".$port.", not exists");
						header("Location: index.php?mod=".$this->mid."&err=4");
						return;
					}

					FS::$dbMgr->Delete("z_eye_ldap_auth_servers","addr ='".$addr."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"LDAP '".$addr."' removed");
					header("Location: index.php?mod=".$this->mid);
					return;
				case 6: // LDAP edition
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to modify ldap but don't have rights");
                                                return;
                                        }
					$addr = FS::$secMgr->checkAndSecurisePostData("addr");
					if(!$addr) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing for user management (LDAP edition)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}

					$serv = FS::$dbMgr->GetOneData("z_eye_ldap_auth_servers","addr","addr = '".$addr."'");
					if(!$serv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to edit LDAP ".$addr.":".$port.", not exists");
						header("Location: index.php?mod=".$this->mid."&err=4");
						return;
					}

					$port = FS::$secMgr->checkAndSecurisePostData("port");
					$ssl = FS::$secMgr->checkAndSecurisePostData("ssl");
					$basedn = FS::$secMgr->checkAndSecurisePostData("dn");
					$rootdn = FS::$secMgr->checkAndSecurisePostData("rootdn");
					$rootpwd = FS::$secMgr->checkAndSecurisePostData("rootpwd");
					$ldapname = FS::$secMgr->checkAndSecurisePostData("ldapname");
					$ldapsurname = FS::$secMgr->checkAndSecurisePostData("ldapsurname");
					$ldapmail = FS::$secMgr->checkAndSecurisePostData("ldapmail");
					$ldapuid = FS::$secMgr->checkAndSecurisePostData("ldapuid");
					$ldapfilter = FS::$secMgr->checkAndSecurisePostData("ldapfilter");

					if(!$port || !FS::$secMgr->isNumeric($port) || !$basedn || !$rootdn || !$rootpwd || !$ldapname || !$ldapsurname || !$ldapmail || !$ldapuid || !$ldapfilter) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing for user management (LDAP edition)");
						header("Location: index.php?mod=".$this->mid."&err=2");
						return;
					}
					FS::$dbMgr->Delete("z_eye_ldap_auth_servers","addr ='".$addr."'");
					$ldapMgr = new LDAP();
					$ldapMgr->setServerInfos($addr,$port,$ssl == "on" ? true : false,$basedn,$rootdn,$rootpwd,$ldapuid,$ldapfilter);
					if(!$ldapMgr->RootConnect()) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to connect to LDAP ".$addr.":".$port.", login failed");
						header("Location: index.php?mod=".$this->mid."&err=3");
						return;
					}

					FS::$dbMgr->Insert("z_eye_ldap_auth_servers","addr,port,ssl,dn,rootdn,dnpwd,filter,ldapuid,ldapmail,ldapname,ldapsurname",
							"'".$addr."','".$port."','".($ssl == "on" ? 1 : 0)."','".$basedn."','".$rootdn."','".$rootpwd."','".$ldapfilter."','".$ldapuid."','".$ldapmail."','".$ldapname."','".$ldapsurname."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"LDAP edition: ".$addr.":".$port." basedn: ".$basedn);
					header("Location: index.php?mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
