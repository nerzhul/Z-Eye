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
	require_once(dirname(__FILE__)."/../../lib/FSS/LDAP.FS.class.php");

	if(!class_exists("iUserMgmt")) {
		
	final class iUserMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lUserMgmt();
			$this->rulesclass = new rUserMgmt($this->loc);
			$this->menu = $this->loc->s("menu-name");
			$this->modulename = "usermgmt";
		}

		public function Load() {
			FS::$iMgr->setURL("");
			return $this->showMain();
		}

		private function EditUser($user) {
			$uid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$user."'");
			$output = FS::$iMgr->h2("title-user-mod");
			if (!$uid) {
				$output .= FS::$iMgr->printError("title-user-dont-exist");
				return $output;
			}
			$output = FS::$iMgr->cbkForm("2").FS::$iMgr->tip("tip-password").
                "<table><tr><td>".$this->loc->s("User")."</td><td>".$user.FS::$iMgr->hidden("uid",$uid)."</td></tr>";
                
			if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","sha_pwd","username = '".$user."'")) {
				$mail = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","mail","username = '".$user."'");
				$output .= "<tr><td>".$this->loc->s("Password")."</td><td>".FS::$iMgr->password("pwd","")."</td></tr>".
					"<tr><td>".$this->loc->s("Password-repeat")."</td><td>".FS::$iMgr->password("pwd2","")."</td></tr>".
					"<tr><td>".$this->loc->s("Mail")."</td><td>".FS::$iMgr->input("mail",$mail,24,64)."</td></tr>";
			}
			
			$gids = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$uid."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$gids[] = $data["gid"];
			}
			$output .= "<tr><td>".$this->loc->s("Group")."</td><td>".
				FS::$iMgr->select("ugroup",array("multi" => true)).
				FS::$iMgr->selElmtFromDB(PGDbConfig::getDbPrefix()."groups","gid",
					array("labelfield" => "gname", "selected" => $gids,"sqlopts" => array("order" => "gname"))).
				"</select></td></tr>".
				FS::$iMgr->tableSubmit("Save").
				"</ul></table></form>";

			return $output;
		}
		
		private function showMain() {
			
			$output = FS::$iMgr->h1("title-usermgmt");

			if (FS::$sessMgr->hasRight("mrule_usermgmt_ldapuserimport")) {
				$output .= FS::$iMgr->opendiv(1,$this->loc->s("import-user"));
			}
			$tmpoutput = "";
			$found = 0;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username,mail,last_ip,join_date,last_conn,name,subname,sha_pwd");
			while ($data = FS::$dbMgr->Fetch($query)) {
				if (!$found) {
					$found = 1;
					$tmpoutput .= "<table id=\"userList\"><thead id=\"userthead\"><tr><th class=\"headerSortDown\">UID</th><th>".$this->loc->s("User")."</th><th>".$this->loc->s("User-type")."</th><th>".
					$this->loc->s("Groups")."</th><th>".$this->loc->s("Subname")."</th><th>".$this->loc->s("Name")."</th><th>".
					$this->loc->s("Mail")."</th><th>".$this->loc->s("last-ip")."</th><th>".$this->loc->s("last-conn")."</th><th>".$this->loc->s("inscription")."</th><th></th></tr></thead>";
				}
				$tmpoutput .= $this->showUserTr($data["uid"],$data["username"],$data["sha_pwd"] == "",$data["subname"],$data["name"],$data["mail"],$data["last_ip"],$data["last_conn"],$data["join_date"]);
			}

			if ($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("userList");
			}
			if (FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
				$output .= FS::$iMgr->h1("title-directorymgmt");

				FS::$iMgr->setJSBuffer(1);

				$output .= FS::$iMgr->opendiv(2,$this->loc->s("new-directory"));

				$found = 0;
				$tmpoutput = "";
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,dn,rootdn,filter");
				while ($data = FS::$dbMgr->Fetch($query)) {
					if (!$found) {
						$found = 1;
						$tmpoutput .= "<table id=\"ldapList\"><thead><tr><th class=\"headerSortDown\">".$this->loc->s("Server")."</th><th>".$this->loc->s("port").
						"</th><th>".$this->loc->s("base-dn")."</th><th>".$this->loc->s("root-dn")."</th><th>".$this->loc->s("ldap-filter")."</th><th></th></tr></thead>";
					}
					$tmpoutput .= "<tr id=\"d".preg_replace("#[.]#","-",$data["addr"])."tr\"><td>".FS::$iMgr->opendiv(3,$data["addr"],array("lnkadd" => "addr=".$data["addr"])).
						"</td><td>".$data["port"]."</td><td>".$data["dn"]."</td><td>".$data["rootdn"]."</td><td>".$data["filter"]."</td><td>".
						FS::$iMgr->removeIcon("act=5&addr=".$data["addr"],array("js" => true,
							"confirm" => $this->loc->s("confirm-removedirectory")."'".$data["addr"]."' ?"))."</tr>";
				}
			}
			if ($found) {
				$output .= $tmpoutput."</table>";
				FS::$iMgr->jsSortTable("ldapList");
			}
			return $output;
		}

		private function showUserTr($uid, $username = "", $localuser = false, $subname = "", $name = "", $mail = "",$last_ip = "",$last_conn = "", $join_date = "") {
			$output = "<tr id=\"u".$uid."tr\"><td>".$uid.
				"</td><td>".FS::$iMgr->opendiv(4,$username,array("lnkadd" => "user=".$username))."</td><td>".
				($localuser ? $this->loc->s("Extern") : $this->loc->s("Intern"))."</td><td>";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$uid."'");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$gname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$data["gid"]."'");
				$output .= $gname."<br />";
			}

			if (preg_match("#[.]#",$last_conn)) {
				$last_conn = preg_split("#[.]#",$last_conn);
				$last_conn = $last_conn[0];
			}
			if (preg_match("#[.]#",$join_date)) {
				$join_date = preg_split("#[.]#",$join_date);
				$join_date = $join_date[0];
			}
			$output .= "</td><td>".$subname."</td><td>".$name."</td><td>".$mail."</td><td>".$last_ip."</td><td>".$last_conn."</td><td>".$join_date."</td><td>";
			if ($uid != 1) {
				$output .= FS::$iMgr->removeIcon("act=3&uid=".$uid,array("js" => true,
					"confirm" => $this->loc->s("confirm-removeuser")."'".$username."' ?"));
			}
			$output .= "</td></tr>";
			return $output;
		}

		private function showUserImportForm() {
			$output = FS::$iMgr->cbkForm("6");
			$output .= "<table>";
			$output .= FS::$iMgr->idxLine("User","username");

			$countElmt = 0;
			$tmpoutput = "";
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."groups","gid,gname");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$countElmt++;
				$tmpoutput .= FS::$iMgr->selElmt($data["gname"],$data["gid"]);
			}

			if ($countElmt > 0) {
				if ($countElmt > 12)
					$size = round($countElmt/4);
				else
					$size = $countElmt;
				$output .= "<tr><td>".$this->loc->s("Groups")."</td><td>".FS::$iMgr->select("groups",array("multi" => true, "size" => $size));
				$output .= $tmpoutput;
				$output .= "</select></td></tr>";
			}
			$output .= FS::$iMgr->tableSubmit("Import");
			return $output;
		}

		private function showDirectoryForm($addr = "") {
			$output = FS::$iMgr->cbkForm("4");

			$port = 389; $ssl = false; $dn = ""; $rootdn = ""; $ldapname = ""; $ldapsurname = ""; $ldapmail = "";
			$ldapuid = ""; $ldapfilter = "(objectclass=*)";

			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","port,dn,rootdn,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl","addr = '".$addr."'");
			if ($data = FS::$dbMgr->Fetch($query)) {
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

			$output .= "<table><tr><td>".$this->loc->s("Template")."</td><td>".FS::$iMgr->select("ldapmodel",array("js" => "autoCompleteLDAP(this);")).
				FS::$iMgr->selElmt($this->loc->s("None"),0).
				FS::$iMgr->selElmt("Active Directory",1).
				"</select></td></tr>";
			
			$output .= FS::$iMgr->idxLines(array(
				array("ldap-addr",	"addr",	array("value" => $addr, "type" => "idxedit", "length" => 40, "size" => 20, "edit" => $addr != "")),
				array("ldap-port",	"port",		array("value" => $port, "size" => 5, "length" => 5)),
				array($this->loc->s("SSL")." ?",	"ssl",		array("value" => $ssl, "type" => "chk", "rawlabel" => true)),
				array("base-dn",	"dn",		array("value" => $dn, "size" => 20, "length" => 200,"tooltip" => "tooltip-base-dn")),
				array("root-dn",	"rootdn",	array("value" => $rootdn, "size" => 20, "length" => 200,"tooltip" => "tooltip-root-dn")),
				array("root-pwd",	"rootpwd",	array("type" => "pwd")),
				array("attr-name",	"ldapname",	array("value" => $ldapname, "size" => 20, "length" => 40,"tooltip" => "tooltip-attr-name")),
				array("attr-subname",	"ldapsurname",	array("value" => $ldapsurname, "size" => 20, "length" => 40,"tooltip" => "tooltip-attr-subname")),
				array("attr-mail",	"ldapmail",	array("value" => $ldapmail, "size" => 20, "length" => 40,"tooltip" => "tooltip-attr-mail")),
				array("attr-uid",	"ldapuid",	array("value" => $ldapuid, "size" => 20, "length" => 40,"tooltip" => "tooltip-attr-uid")),
				array("ldap-filter",	"ldapfilter",	array("value" => $ldapfilter, "size" => 20, "length" => 200,"tooltip" => "tooltip-ldap-filter"))
				)).
				FS::$iMgr->tableSubmit("Save");

			$js = "function autoCompleteLDAP(obj) {
				if (obj.value == 1) {
					$('#port').val('389');
					$('#ssl').prop('checked',false);
					$('#ldapname').val('sn');
					$('#ldapsurname').val('givenname');
					$('#ldapmail').val('mail');
					$('#ldapuid').val('samaccountname');
					$('#ldapfilter').val('(objectclass=user)');
				}
				else {
					$('#port').val('636');
					$('#ssl').prop('checked',true);
					$('#ldapname').val('');
					$('#ldapsurname').val('');
					$('#ldapmail').val('');
					$('#ldapuid').val('');
					$('#ldapfilter').val('(objectclass=*)');
				}
			}";
			FS::$iMgr->js($js);
			return $output;
		}

		private function SearchAndImportUser($username,$groups = array()) {
			if (!$username) {
				FS::$iMgr->ajaxEcho("err-bad-datas");
				return;
			}
			// If user already exists
			if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'")) {
				$this->log(2,"User '".$username."' already exists");
				FS::$iMgr->ajaxEchoNC("err-user-already-exists");
				return;
			}
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,dn,rootdn,dnpwd,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl");
			while ($data = FS::$dbMgr->Fetch($query)) {
				$ldapMgr = new LDAP();
				$ldapMgr->setServerInfos($data["addr"],$data["port"],($data["ssl"] == 1 ? true : false),$data["dn"],$data["rootdn"],$data["dnpwd"],$data["ldapuid"],$data["filter"]);
				$ldapMgr->RootConnect();
				$result = $ldapMgr->GetOneEntry($data["ldapuid"]."=".$username);
				if ($result) {
					$grpcount = 0;
					if ($groups && is_array($groups)) {
						$grpcount = count($groups);
						for ($i=0;$i<$grpcount;$i++) {
							if (!FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gid = '".$groups[$i]."'")) {
								$this->log(2,"Group '".$groups[$i]."' doesn't exists");
								FS::$iMgr->ajaxEchoNC("err-group-not-exists");
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
					$user->setMail($mail);
					$user->Create();

					$uid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'");
					for ($i=0;$i<$grpcount;$i++)
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					$jscontent = "";
					$js = "$('".$jscontent."').addAfter('#userthead');";

					FS::$iMgr->ajaxEcho("Done",$js);
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				}
			}
			FS::$iMgr->ajaxEcho("err-user-not-found");
		}

		public function getIfaceElmt() {
			$el = FS::$secMgr->checkAndSecuriseGetData("el");
			switch($el) {
				case 1: return $this->showUserImportForm();
				case 2: return $this->showDirectoryForm();
				case 3: 
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if (!$addr) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->showDirectoryForm($addr);
				case 4:
					$user = FS::$secMgr->checkAndSecuriseGetData("user");
					if (!$user) {
						return $this->loc->s("err-bad-datas");
					}
					return $this->EditUser($user);
				default: return;
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1: // add user
					if (!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
						$this->log(2,"User tries to add user but don't have rights");
						return;
					}
					break;
				case 2: // edit user
					if (!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
						$this->log(2,"User tries to edit user but don't have rights !");
						FS::$iMgr->ajaxEcho("err-no-rights");
						return;
					}
					$uid = FS::$secMgr->checkAndSecurisePostData("uid");
					if (!$uid || !FS::$secMgr->isNumeric($uid)) {
						$this->log(2,"Some fields are missing for user management (user edit)");
						FS::$iMgr->ajaxEchoNC("err-invalid-bad-data");
						return;
					}

					$username = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$uid."'");
					if (!$username) {
						$this->log(2,"User uid '".$uid."' doesn't exists");
						FS::$iMgr->ajaxEchoNC("err-invalid-bad-data");
						return;
					}
					
					FS::$dbMgr->BeginTr();
					
					$pwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$pwd2 = FS::$secMgr->checkAndSecurisePostData("pwd2");
					if ($pwd || $pwd2) {
						if ($pwd != $pwd2) {
							$this->log(1,"Try to modify password for user ".$uid." but passwords didn't match");
							FS::$iMgr->ajaxEchoNC("err-pwd-match");
							return;
						}
						$user = new User();
						$user->LoadFromDB($uid);
						switch($user->changePassword($pwd)) {
							case 0: break; // ok
							case 1: // too short
								FS::$iMgr->ajaxEchoNC("err-pwd-short");
								return;
							case 2: // complexity
								FS::$iMgr->ajaxEchoNC("err-pwd-complex");
                                return;
							default: // unk
								FS::$iMgr->ajaxEchoNC("err-pwd-unk");
                                return;
						}
					}

					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					if ($mail) {
						if (!FS::$secMgr->isMail($mail)) {
							FS::$iMgr->ajaxEchoNC("err-mail");
							return;
						}
						FS::$dbMgr->Update(PGDbConfig::getDbPrefix()."users","mail = '".$mail."'","uid = '".$uid."'");
					}

					$groups = FS::$secMgr->checkAndSecurisePostData("ugroup");
					$count = count($groups);
					for ($i=0;$i<$count;$i++) {
						if (!isset($groups[$i]) || $groups[$i] == "") {
							continue;
						}
						
						$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gname","gid = '".$groups[$i]."'");
						if (!$exist) {
							$this->log(1,"Try to add user ".$uid." to inexistant group '".$groups[$i]."'");
							FS::$iMgr->ajaxEchoNC("err-invalid-bad-data");
							return;
						}
					}
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","uid = '".$uid."'");
					$groups = array_unique($groups);
					$count = count($groups);
					for ($i=0;$i<$count;$i++) {
						FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."user_group","uid,gid","'".$uid."','".$groups[$i]."'");
					}
					FS::$dbMgr->CommitTr();
					
					$this->log(0,"User ".$uid." edited");
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				case 3: // del user
					if (!FS::$sessMgr->hasRight("mrule_usermgmt_write")) {
                        $this->log(2,"User tries to delete user but don't have rights");
						FS::$iMgr->ajaxEcho("err-no-right");
                        return;
                    }
					$uid = FS::$secMgr->checkAndSecuriseGetData("uid");
					if (!$uid || !FS::$secMgr->isNumeric($uid)) {
						$this->log(2,"Some fields are wrong or missing for user management (User delete)");
						FS::$iMgr->ajaxEchoNC("err-invalid-bad-data");
						return;
					}
					$exist = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","last_conn","uid = '".$uid."'");
					if (!$exist) {
						$this->log(1,"Unable to remove user '".$uid."', doesn't exist");
						FS::$iMgr->ajaxEchoNC("err-invalid-user");
						return;
					}
					FS::$dbMgr->BeginTr();
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."users","uid = '".$uid."'");
					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."user_group","uid = '".$uid."'");
					FS::$dbMgr->CommitTr();
					$this->log(0,"User '".$uid."' removed");
					FS::$iMgr->ajaxEcho("Done","hideAndRemove('#u".$uid."tr');");
					return;
				case 4: // add ldap
					if (!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
						$this->log(2,"User tries to add ldap but don't have rights");
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

					if (!$addr || !$port || !FS::$secMgr->isNumeric($port) || !$basedn || !$rootdn || !$rootpwd || !$ldapname || !$ldapsurname || !$ldapmail || !$ldapuid || !$ldapfilter) {
						$this->log(2,"Some fields are missing/wrong for user management (LDAP add)");
						FS::$iMgr->ajaxEchoNC("err-invalid-bad-data");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if ($edit) {
						if (!$serv) {
							$this->log(1,"Unable to edit LDAP ".$addr.":".$port.", not exists");
							FS::$iMgr->ajaxEcho("err-ldap-not-exist");
							return;
						}
					}
					else {
						if ($serv) {
							$this->log(1,"Unable to add LDAP ".$addr.":".$port.", already exists");
							FS::$iMgr->ajaxEchoNC("err-ldap-exist");
							return;
						}
					}

					$ldapMgr = new LDAP();
					$ldapMgr->setServerInfos($addr,$port,$ssl == "on" ? true : false,$basedn,$rootdn,$rootpwd,$ldapuid,$ldapfilter);
					if (!$ldapMgr->RootConnect()) {
						$this->log(1,"Unable to add LDAP ".$addr.":".$port.", connection fail");
						FS::$iMgr->ajaxEchoNC("err-ldap-bad-data");
						return;
					}

					if ($edit) FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr ='".$addr."'");
					FS::$dbMgr->Insert(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,ssl,dn,rootdn,dnpwd,filter,ldapuid,ldapmail,ldapname,ldapsurname",
						"'".$addr."','".$port."','".($ssl == "on" ? 1 : 0)."','".$basedn."','".$rootdn."','".$rootpwd."','".$ldapfilter."','".$ldapuid."','".$ldapmail."','".$ldapname."','".$ldapsurname."'");
					$this->log(0,"New LDAP: ".$addr.":".$port." basedn: ".$basedn);
					FS::$iMgr->redir("mod=".$this->mid,true);
					return;
				case 5: // LDAP remove
					if (!FS::$sessMgr->hasRight("mrule_usermgmt_ldapwrite")) {
                                                $this->log(2,"User tries to remove ldap but don't have rights");
						FS::$iMgr->ajaxEcho("err-no-right");
                                                return;
                                        }
					$addr = FS::$secMgr->checkAndSecuriseGetData("addr");
					if (!$addr) {
						$this->log(2,"Some fields are missing for user management (LDAP remove)");
						FS::$iMgr->ajaxEcho("err-invalid-bad-data");
						return;
					}

					$serv = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr","addr = '".$addr."'");
					if (!$serv) {
						$this->log(1,"Unable to remove LDAP ".$addr.":".$port.", not exists");
						FS::$iMgr->ajaxEcho("err-ldap-exist");
						return;
					}

					FS::$dbMgr->Delete(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr ='".$addr."'");
					$this->log(0,"LDAP '".$addr."' removed");
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
	
	}
	
	$module = new iUserMgmt();
?>
