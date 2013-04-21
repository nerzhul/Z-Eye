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
	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	class iUserMgmt extends FSModule{
		function iUserMgmt() { parent::FSModule(); $this->loc = new lUserMgmt(); }

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
				case 10: $output .= FS::$iMgr->printError($this->loc->s("err-ldap-not-exist")); break;
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
			$uid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$user."'");
			$output = FS::$iMgr->h2("title-user-mod");
			if(!$uid) {
				$output .= FS::$iMgr->printError($this->loc->s("title-user-dont-exist"));
				return $output;
			}
			$output = FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
                        $output .= "<ul class=\"ulform\"><li><b>".$this->loc->s("User").":</b> ".$user.FS::$iMgr->hidden("uid",$uid)."</li>";
			if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","sha_pwd","username = '".$user."'")) {
				$mail = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","mail","username = '".$user."'");
				$output .= "<li><i>".$this->loc->s("tip-password")."</i></li>
					<li>".$this->loc->s("Password").": ".FS::$iMgr->password("pwd","")."</li>
					<li>".$this->loc->s("Password-repeat").": ".FS::$iMgr->password("pwd2","")."</li>
					<li>".$this->loc->s("Mail").": ".FS::$iMgr->input("mail",$mail,24,64)."</li>";
			}
			$grpidx = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$uid."'");
			while($data = FS::$dbMgr->Fetch($query)) {
				$output .= "<li class=\"ugroupli".$grpidx."\">".FS::$iMgr->select("ugroup".$grpidx,"",$this->loc->s("Group")).$this->addGroupList($data["gid"])."</select>";
				$output .= " <a onclick=\"javascript:delGrpElmt(".$grpidx.");\">X</a></li>";
				$grpidx++;
			}
                        $output .= "<li id=\"formactions\">".FS::$iMgr->button("newgrp","Nouveau Groupe","addGrpForm()").FS::$iMgr->submit("",$this->loc->s("Modify"))."</li>";
                        $output .= "</ul></form>";

			$output .= FS::$iMgr->js("grpidx = ".$grpidx."; function addGrpForm() {
                                $('<li class=\"ugroupli'+grpidx+'\">".FS::$iMgr->select("ugroup'+grpidx+'","","Groupe").$this->addGroupList()."</select>
                        	<a onclick=\"javascript:delGrpElmt('+grpidx+');\">X</a></li>').insertBefore('#formactions');
                                        grpidx++;
                                }
                                function delGrpElmt(grpidx) {
                                        $('.ugroupli'+grpidx).remove();
                                }");
			return $output;
		}

		private function EditServer($addr) {
			if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) 
				return FS::$iMgr->printError($this->loc->s("err-rights"));

			$output = FS::$iMgr->h2("title-directory");
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","port,dn,rootdn,dnpwd,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl","addr = '".$addr."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=4");
				$output .= "<ul class=\"ulform\">".FS::$iMgr->hidden("addr",$addr).FS::$iMgr->hidden("edit","1")."<li><b>".$this->loc->s("Directory").": </b>".$addr."</li><li>";
				$output .= FS::$iMgr->numInput("port",$data["port"],array("size" => 5, "length" => 5, "label" => $this->loc->s("ldap-port")))."</li><li>";
				$output .= FS::$iMgr->check("ssl",array("check" => ($data["ssl"] == 1 ? true : false),"label" => "SSL ?"))."</li><li>";
				$output .= FS::$iMgr->input("dn",$data["dn"],20,200,$this->loc->s("base-dn"),"tooltip-base-dn")."</li><li>";
				$output .= FS::$iMgr->input("rootdn",$data["rootdn"],20,200,$this->loc->s("root-dn"),"tooltip-root-dn")."</li><li>";
				$output .= FS::$iMgr->password("rootpwd",$data["dnpwd"],$this->loc->s("root-pwd"))."</li><li>";
				$output .= FS::$iMgr->input("ldapname",$data["ldapname"],20,40,$this->loc->s("attr-name"),"tooltip-attr-name")."</li><li>";
				$output .= FS::$iMgr->input("ldapsurname",$data["ldapsurname"],20,40,$this->loc->s("attr-subname"),"tooltip-attr-subname")."</li><li>";
				$output .= FS::$iMgr->input("ldapmail",$data["ldapmail"],20,40,$this->loc->s("attr-mail"),"tooltip-attr-mail")."</li><li>";
				$output .= FS::$iMgr->input("ldapuid",$data["ldapuid"],20,40,$this->loc->s("attr-uid"),"tooltip-attr-uid")."</li><li>";
				$output .= FS::$iMgr->input("ldapfilter",$data["filter"],20,200,$this->loc->s("ldap-filter"),"tooltip-ldap-filter")."</li><li>";
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
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query))
				$output .= FS::$iMgr->selElmt($data["gname"],$data["gid"],$gid == $data["gid"] ? true : false);
			return $output;
		}

		private function showMain() {
			$output = FS::$iMgr->h1("title-usermgmt");

			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username,mail,last_ip,join_date,last_conn,name,subname,sha_pwd");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"userList\"><thead><tr><th class=\"headerSortDown\">UID</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("User-type")."</th><th>".
					$this->loc->s("Groups")."</th><th>".$this->loc->s("Subname")."</th><th>".$this->loc->s("Name")."</th><th>".
					$this->loc->s("Mail")."</th><th>".$this->loc->s("last-ip")."</th><th>".$this->loc->s("last-conn")."</th><th>".$this->loc->s("inscription")."</th><th></th></tr></thead>";
				}
				$tmpoutput .= "<tr id=\"u".$data["uid"]."tr\"><td>".$data["uid"]."</td><td><a href=\"index.php?mod=".$this->mid."&user=".$data["username"]."\">".$data["username"]."</a></td><td>".
					($data["sha_pwd"] == "" ? $this->loc->s("Extern") : $this->loc->s("Intern"))."</td><td>";
				$query2 = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$data["uid"]."'");
				while($data2 = FS::$dbMgr->Fetch($query2)) {
					$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$data2["gid"]."'");
					$tmpoutput .= $gname."<br />";
				}
				$tmpoutput .= "</td><td>".$data["subname"]."</td><td>".$data["name"]."</td><td>".$data["mail"]."</td><td>".$data["last_ip"]."</td><td>".
					$data["last_conn"]."</td><td>".$data["join_date"]."</td><td>";
				if($data["uid"] != 1) {
					$tmpoutput .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=3&uid=".$data["uid"],array("js" => true,
						"confirm" => array($this->loc->s("confirm-removeuser")."'".$data["username"]."' ?","Confirm","Cancel")));
				}
				$tmpoutput .= "</td></tr>";
			}

			if($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("userList");
			}
			if(FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
				$output .= FS::$iMgr->h1("title-directorymgmt");

				FS::$iMgr->setJSBuffer(1);

				$output .= FS::$iMgr->opendiv($this->showDirectoryForm(),$this->loc->s("new-directory"),array("width" => 470));

				$found = 0;
				$tmpoutput = "";
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,dn,rootdn,filter");
				while($data = FS::$dbMgr->Fetch($query)) {
					if(!$found) {
						$found = 1;
						$tmpoutput .= "<table id=\"ldapList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Server")."</th><th>".$this->loc->s("port").
						"</th><th>".$this->loc->s("base-dn")."</th><th>".$this->loc->s("root-dn")."</th><th>".$this->loc->s("ldap-filter")."</th><th></th></tr></thead>";
					}
					$tmpoutput .= "<tr id=\"d".preg_replace("#[.]#","-",$data["addr"])."tr\"><td><a href=\"index.php?mod=".$this->mid."&addr=".$data["addr"]."\">".
						$data["addr"]."</a></td><td>".$data["port"]."</td><td>".$data["dn"]."</td><td>".$data["rootdn"]."</td><td>".$data["filter"]."</td><td>".
						FS::$iMgr->removeIcon("mod=".$this->mid."&act=5&addr=".$data["addr"],array("js" => true,
							"confirm" => array($this->loc->s("confirm-removedirectory")."'".$data["addr"]."' ?","Confirm","Cancel")))."</tr>";
				}
			}
			if($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("ldapList");
			}
			return $output;
		}

		private function showDirectoryForm() {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=4");
			$output .= "<table>";
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-addr"),"addr","",array("length" => 40, "size" => 20));
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-port"),"port","389",array("size" => 5, "length" => 5));
			$output .= FS::$iMgr->idxLine($this->loc->s("SSL")." ?","ssl",false,array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("base-dn"),"dn","",array("size" => 20, "length" => 200,"tooltip" => "tooltip-base-dn"));
			$output .= FS::$iMgr->idxLine($this->loc->s("root-dn"),"rootdn","",array("size" => 20, "length" => 200,"tooltip" => "tooltip-root-dn"));
			$output .= FS::$iMgr->idxLine($this->loc->s("root-pwd"),"rootpwd","",array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-name"),"ldapname","",array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-name"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-subname"),"ldapsurname","",array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-subname"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-mail"),"ldapmail","",array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-mail"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-uid"),"ldapuid","",array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-uid"));
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-filter"),"ldapfilter","(objectclass=*)",array("size" => 20, "length" => 200,"tooltip" => "tooltip-ldap-filter"));
			$output .= FS::$iMgr->tableSubmit($this->loc->s("Save"));
			$output .= "</table></form>";
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
						FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}

					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
					if(!$username) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User uid '".$uid."' doesn't exists");
						FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}

					$pwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$pwd2 = FS::$secMgr->checkAndSecurisePostData("pwd2");
					if($pwd || $pwd2) {
						if($pwd != $pwd2) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Try to modify password for user ".$uid." but passwords didn't match");
							FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=5");
							return;
						}
						$user = new User();
						$user->LoadFromDB($uid);
						switch($user->changePassword($pwd)) {
							case 0: break; // ok
							case 1: // too short
								FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=6");
								return;
							case 2: // complexity
								FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=7");
                                                                return;
							default: // unk
								FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=8");
                                                                return;
						}
					}

					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					if($mail) {
						if(!FS::$secMgr->isMail($mail)) {
							FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=9");
							return;
						}
						FS::$dbMgr->Update(PGDbConfig::getDbPrefix()."users","mail = '".$mail."'","uid = '".$uid."'");
					}

					$groups = array();
					foreach($_POST as $key => $value) {
						   if(preg_match("#^ugroup#",$key)) {
								$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$value."'");
								if(!$exist) {
									FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Try to add user ".$uid." to inexistant group '".$value."'");
									FS::$iMgr->redir("mod=".$this->mid."&user=".$username."&err=2");
									return;
								}
								array_push($groups,$value);
						   }
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","uid = '".$uid."'");
					$groups = array_unique($groups);
					$count = count($groups);
					for($i=0;$i<$count;$i++)
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"User ".$uid." edited");
					FS::$iMgr->redir("mod=".$this->mid);
					return;
				case 3: // del user
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to delete user but don't have rights");
						FS::$iMgr->ajaxEcho("err-no-right");
                                                return;
                                        }
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					if(!$uid || !FS::$secMgr->isNumeric($uid)) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are wrong or missing for user management (User delete)");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","last_conn","uid = '".$uid."'");
					if(!$exist) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to remove user '".$uid."', doesn't exist");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-invalid-user");
						else
						FS::$iMgr->redir("mod=".$this->mid."&err=1");
						return;
					}
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."users","uid = '".$uid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","uid = '".$uid."'");
					FS::$dbMgr->CommitTr();
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"User '".$uid."' removed");
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#u".$uid."tr');");
					else
						FS::$iMgr->redir("mod=".$this->mid);
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
					$edit = FS::$secMgr->checkAndSecurisePostData("edit");

					if(!$addr || !$port || !FS::$secMgr->isNumeric($port) || !$basedn || !$rootdn || !$rootpwd || !$ldapname || !$ldapsurname || !$ldapmail || !$ldapuid || !$ldapfilter) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing/wrong for user management (LDAP add)");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if($edit) {
						if(!$serv) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to edit LDAP ".$addr.":".$port.", not exists");
							if(FS::isAjaxCall())
								FS::$iMgr->ajaxEcho("err-ldap-not-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&err=10");
							return;
						}
					}
					else {
						if($serv) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", already exists");
							if(FS::isAjaxCall())
								FS::$iMgr->ajaxEcho("err-ldap-exist");
							else
								FS::$iMgr->redir("mod=".$this->mid."&err=4");
							return;
						}
					}

					$ldapMgr = new LDAP();
					$ldapMgr->setServerInfos($addr,$port,$ssl == "on" ? true : false,$basedn,$rootdn,$rootpwd,$ldapuid,$ldapfilter);
					if(!$ldapMgr->RootConnect()) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", connection fail");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-ldap-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=3");
						return;
					}

					if($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr ='".$addr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,ssl,dn,rootdn,dnpwd,filter,ldapuid,ldapmail,ldapname,ldapsurname",
						"'".$addr."','".$port."','".($ssl == "on" ? 1 : 0)."','".$basedn."','".$rootdn."','".$rootpwd."','".$ldapfilter."','".$ldapuid."','".$ldapmail."','".$ldapname."','".$ldapsurname."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"New LDAP: ".$addr.":".$port." basedn: ".$basedn);
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				case 5: // LDAP remove
					if(!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
                                                FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User tries to remove ldap but don't have rights");
						FS::$iMgr->ajaxEcho("err-no-right");
                                                return;
                                        }
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if(!$addr) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Some fields are missing for user management (LDAP remove)");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=2");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if(!$serv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to remove LDAP ".$addr.":".$port.", not exists");
						if(FS::isAjaxCall())
							FS::$iMgr->ajaxEcho("err-ldap-exist");
						else
							FS::$iMgr->redir("mod=".$this->mid."&err=4");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr ='".$addr."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"LDAP '".$addr."' removed");
					if(FS::isAjaxCall())
						FS::$iMgr->ajaxEcho("Done","hideAndRemove('#d".preg_replace("#[.]#","-",$addr)."tr');");
					else
						FS::$iMgr->redir("mod=".$this->mid);
					return;
				default: break;
			}
		}
	};
?>
