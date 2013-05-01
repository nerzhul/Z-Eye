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

	class iUserMgmt extends FSModule {
		function iUserMgmt($locales) { parent::FSModule($locales); }

		public function Load() {
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			$output = "";
			switch($err) {
				case 1: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-user")); break;
				case 2: $output .= FS::$iMgr->printError($this->loc->s("err-invalid-bad-data")); break;
				case 5: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-match")); break;
				case 6: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-short")); break;
				case 7: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-complex")); break;
				case 8: $output .= FS::$iMgr->printError($this->loc->s("err-pwd-unk")); break;
				case 9: $output .= FS::$iMgr->printError($this->loc->s("err-mail")); break;
				case 10: $output .= FS::$iMgr->printError($this->loc->s("err-ldap-not-exist")); break;
			}

			$user = FS::$secMgr->checkAndSecuriseGetData("user");
			if($user)
				$output .= $this->EditUser($user);
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

		private function addGrouplist($gid=-1) {
			$output = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query))
				$output .= FS::$iMgr->selElmt($data["gname"],$data["gid"],$gid == $data["gid"] ? true : false);
			return $output;
		}

		private function showMain() {
			$output = FS::$iMgr->h1("title-usermgmt");

			if(FS::$sessMgr->hasRight("mrule_usermgmt_ldapuserimport")) {
				$output .= FS::$iMgr->opendiv($this->showUserImportForm(),$this->loc->s("import-user"),array("width" => 400));
			}
			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username,mail,last_ip,join_date,last_conn,name,subname,sha_pwd");
			while($data = FS::$dbMgr->Fetch($query)) {
				if(!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"userList\"><thead id=\"userthead\"><tr><th class=\"headerSortDown\">UID</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("User-type")."</th><th>".
					$this->loc->s("Groups")."</th><th>".$this->loc->s("Subname")."</th><th>".$this->loc->s("Name")."</th><th>".
					$this->loc->s("Mail")."</th><th>".$this->loc->s("last-ip")."</th><th>".$this->loc->s("last-conn")."</th><th>".$this->loc->s("inscription")."</th><th></th></tr></thead>";
				}
				$tmpoutput .= $this->showUserTr($data["uid"],$data["username"],$data["sha_pwd"] == "",$data["subname"],$data["name"],$data["mail"],$data["last_ip"],$data["last_conn"],$data["join_date"]);
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
					$tmpoutput .= "<tr id=\"d".preg_replace("#[.]#","-",$data["addr"])."tr\"><td>".FS::$iMgr->opendiv($this->showDirectoryForm($data["addr"]),$data["addr"],array("width" => 470)).
						"</td><td>".$data["port"]."</td><td>".$data["dn"]."</td><td>".$data["rootdn"]."</td><td>".$data["filter"]."</td><td>".
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

		private function showUserTr($uid, $username = "", $localuser = false, $subname = "", $name = "", $mail = "",$last_ip = "",$last_conn = "", $join_date = "") {
			$output = "<tr id=\"u".$uid."tr\"><td>".$uid."</td><td><a href=\"index.php?mod=".$this->mid."&user=".$username."\">".$username."</a></td><td>".
				($localuser ? $this->loc->s("Extern") : $this->loc->s("Intern"))."</td><td>";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$uid."'");
			while($data = FS::$dbMgr->Fetch($query)) {
				$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$data["gid"]."'");
				$output .= $gname."<br />";
			}

			if(preg_match("#[.]#",$last_conn)) {
				$last_conn = preg_split("#[.]#",$last_conn);
				$last_conn = $last_conn[0];
			}
			if(preg_match("#[.]#",$join_date)) {
				$join_date = preg_split("#[.]#",$join_date);
				$join_date = $join_date[0];
			}
			$output .= "</td><td>".$subname."</td><td>".$name."</td><td>".$mail."</td><td>".$last_ip."</td><td>".$last_conn."</td><td>".$join_date."</td><td>";
			if($uid != 1) {
				$output .= FS::$iMgr->removeIcon("mod=".$this->mid."&act=3&uid=".$uid,array("js" => true,
					"confirm" => array($this->loc->s("confirm-removeuser")."'".$username."' ?","Confirm","Cancel")));
			}
			$output .= "</td></tr>";
			return $output;
		}

		private function showUserImportForm() {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=6");
			$output .= "<table>";
			$output .= FS::$iMgr->idxLine($this->loc->s("User"),"username","");

			$countElmt = 0;
			$tmpoutput = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$tmpoutput .= FS::$iMgr->selElmt($data["gname"],$data["gid"]);
			}

			if($countElmt > 0) {
				if($countElmt > 12)
					$size = round($countElmt/4);
				else
					$size = $countElmt;
				$output .= "<tr><td>".$this->loc->s("Groups")."</td><td>".FS::$iMgr->select("groups[]","",NULL,true,array("size" => $size));
				$output .= $tmpoutput;
				$output .= "</select></td></tr>";
			}
			$output .= FS::$iMgr->tableSubmit("Import");
			return $output;
		}

		private function showDirectoryForm($addr = "") {
			$output = FS::$iMgr->cbkForm("index.php?mod=".$this->mid."&act=4");

			$port = 389; $ssl = false; $dn = ""; $rootdn = ""; $ldapname = ""; $ldapsurname = ""; $ldapmail = "";
			$ldapuid = ""; $ldapfilter = "(objectclass=*)";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","port,dn,rootdn,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl","addr = '".$addr."'");
			if($data = FS::$dbMgr->Fetch($query)) {
				$port = $data["port"];
				$dn = $data["dn"];
				$rootdn = $data["rootdn"];
				$ldapuid = $data["ldapuid"];
				$ldapfilter = $data["filter"];
				$ldapmail = $data["ldapmail"];
				$ldapname = $data["ldapname"];
				$ldapsurname = $data["ldapsurname"];
				$ssl = ($data["ssl"] == 't');
			}

			$output .= "<table>";
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-addr"),	"addr",		$addr,		array("type" => "idxedit", "length" => 40, "size" => 20, "edit" => $addr != ""));
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-port"),	"port",		$port,		array("size" => 5, "length" => 5));
			$output .= FS::$iMgr->idxLine($this->loc->s("SSL")." ?",	"ssl",		$ssl,		array("type" => "chk"));
			$output .= FS::$iMgr->idxLine($this->loc->s("base-dn"),		"dn",		$dn,		array("size" => 20, "length" => 200,"tooltip" => "tooltip-base-dn"));
			$output .= FS::$iMgr->idxLine($this->loc->s("root-dn"),		"rootdn",	$rootdn,	array("size" => 20, "length" => 200,"tooltip" => "tooltip-root-dn"));
			$output .= FS::$iMgr->idxLine($this->loc->s("root-pwd"),	"rootpwd",	"",		array("type" => "pwd"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-name"),	"ldapname",	$ldapname,	array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-name"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-subname"),	"ldapsurname",	$ldapsurname,	array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-subname"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-mail"),	"ldapmail",	$ldapmail,	array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-mail"));
			$output .= FS::$iMgr->idxLine($this->loc->s("attr-uid"),	"ldapuid",	$ldapuid,	array("size" => 20, "length" => 40,"tooltip" => "tooltip-attr-uid"));
			$output .= FS::$iMgr->idxLine($this->loc->s("ldap-filter"),	"ldapfilter",	$ldapfilter,	array("size" => 20, "length" => 200,"tooltip" => "tooltip-ldap-filter"));
			$output .= FS::$iMgr->tableSubmit("Save");
			return $output;
		}

		private function SearchAndImportUser($username,$groups = array()) {
			if(!$username) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
			// If user already exists
			if(FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'")) {
				FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",2,"User '".$username."' already exists");
				FS::$iMgr->ajaxEcho("err-user-already-exists");
				return;
			}
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,dn,rootdn,dnpwd,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl");
			while($data = FS::$dbMgr->Fetch($query)) {
				$ldapMgr = new LDAP();
				$ldapMgr->setServerInfos($data["addr"],$data["port"],($data["ssl"] == 1 ? true : false),$data["dn"],$data["rootdn"],$data["dnpwd"],$data["ldapuid"],$data["filter"]);
				$ldapMgr->RootConnect();
				$result = $ldapMgr->GetOneEntry($data["ldapuid"]."=".$username);
				if($result) {
					$grpcount = 0;
					if($groups && is_array($groups)) {
						$grpcount = count($groups);
						for($i=0;$i<$grpcount;$i++) {
							if(!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gid = '".$groups[$i]."'")) {
								FS::$ilog->i(FS::$sessMgr->getUserName(),"usermgmt",2,"Group '".$groups[$i]."' doesn't exists");
								FS::$iMgr->ajaxEcho("err-group-not-exists");
								return;
							}
						}
					}

					$mail = is_array($result[$data["ldapmail"]]) ? $result[$data["ldapmail"]][0] : $result[$data["ldapmail"]];
					$surname = $result[$data["ldapsurname"]];
					$name = $result[$data["ldapname"]];

					$user = new User();
					$user->setUsername($username);
					$user->setSubName($surname);
					$user->setName($name);
					$user->setUserLevel(4);
					$user->setMail($mail);
					$user->Create();

					$uid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'");
					for($i=0;$i<$grpcount;$i++)
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					$jscontent = "";
					$js = "$('".$jscontent."').addAfter('#userthead');";

					FS::$iMgr->ajaxEcho("Done",$js);
					FS::$iMgr->redir("m-".$this->mid.".html",true);
					return;
				}
			}
			FS::$iMgr->ajaxEcho("err-user-not-found");
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
						FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if($edit) {
						if(!$serv) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to edit LDAP ".$addr.":".$port.", not exists");
							FS::$iMgr->ajaxEcho("err-ldap-not-exist");
							return;
						}
					}
					else {
						if($serv) {
							FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", already exists");
							FS::$iMgr->ajaxEcho("err-ldap-exist");
							return;
						}
					}

					$ldapMgr = new LDAP();
					$ldapMgr->setServerInfos($addr,$port,$ssl == "on" ? true : false,$basedn,$rootdn,$rootpwd,$ldapuid,$ldapfilter);
					if(!$ldapMgr->RootConnect()) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to add LDAP ".$addr.":".$port.", connection fail");
						FS::$iMgr->ajaxEcho("err-ldap-bad-data");
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
						FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if(!$serv) {
						FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",1,"Unable to remove LDAP ".$addr.":".$port.", not exists");
						FS::$iMgr->ajaxEcho("err-ldap-exist");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr ='".$addr."'");
					FS::$log->i(FS::$sessMgr->getUserName(),"usermgmt",0,"LDAP '".$addr."' removed");
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#d".preg_replace("#[.]#","-",$addr)."tr');");
					return;
				case 6: // LDAP Import
					$username = FS::$secMgr->checkAndSecurisePostData("username");
					$groups = FS::$secMgr->checkAndSecurisePostData("groups");
					$this->SearchAndImportUser($username,$groups);
					return;
				default: break;
			}
		}
	};
?>
