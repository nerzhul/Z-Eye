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

	final class iConnect extends FSModule{
		function __construct($locales) {
			parent::__construct($locales);
			$this->modulename = "connect";
		}

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-conn"));
			return "";
		}

		public function TryConnect($username,$password) {
			$username = strtolower($username);
			$output = "";
			$ldapok = false;
			$ldapident = "";
			$ldapMgr = new LDAP();
			$ldapsurname = "";
			$ldapname = "";
			$ldapmail = "";
			$found = false;
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."ldap_auth_servers","addr,port,dn,rootdn,dnpwd,ldapuid,filter,ldapmail,ldapname,ldapsurname,ssl");
			while (!$found && ($data = FS::$dbMgr->Fetch($query))) {
				$tmpldapMgr = new LDAP();
				$tmpldapMgr->setServerInfos($data["addr"],$data["port"],($data["ssl"] == 1 ? true : false),$data["dn"],$data["rootdn"],$data["dnpwd"],$data["ldapuid"],$data["filter"]);
				$tmpldapMgr->RootConnect();
				if ($tmpldapMgr->GetOneEntry($data["ldapuid"]."=".$username)) {
					if ($tmpldapMgr->Authenticate($username, $password)) {
						$ldapok = true;
						$ldapident = $data["ldapuid"];
						$ldapsurname = $data["ldapsurname"];
						$ldapname = $data["ldapname"];
						$ldapmail = $data["ldapmail"];
						$ldapMgr->setServerInfos($data["addr"],$data["port"],($data["ssl"] == 1 ? true : false),$data["dn"],$data["rootdn"],$data["dnpwd"],$data["ldapuid"],$data["filter"]);
						$found = true;
					}
					else {
						FS::$dbMgr->Update(PgDbConfig::getDbPrefix()."users","failauthnb = failauthnb + 1","username = '".$username."'");
					}
				}
			}

			$url = FS::$secMgr->checkAndSecurisePostData("redir");
			if ($url == NULL || $url == "index.php") $url = "m-0.html";
			$url = preg_replace("#^/index\.php\?#","",$url);

			if ($ldapok) {
				$ldapMgr->RootConnect();
				$result = $ldapMgr->GetOneEntry($ldapident."=".$username);
				if (!$result) {
					FS::$iMgr->ajaxEcho("err-bad-user");
					$this->log(1,"Login failed for user '".$username."' (Unknown user)","None");
					return;
				}

				$mail = is_array($result[$ldapmail]) ? $result[$ldapmail][0] : $result[$ldapmail];
				$prenom = $result[$ldapsurname];
				$nom = $result[$ldapname];
				$user = new User();
				$user->setUsername($username);
				$user->setSubName($prenom);
				$user->setName($nom);
				$user->setMail($mail);
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$this->genAPIKeyIfNot($username);
					$this->connectUser($data["uid"]);
					$this->log(0,"Login success for user '".$username."'","None");
					$this->reloadInterface($url);
					return;
				}
				else {
					$user->Create();
					$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid","username = '".$username."'");
					if ($data = FS::$dbMgr->Fetch($query)) { 
						$this->genAPIKeyIfNot($username);
						$this->connectUser($data["uid"]);
						$this->log(0,"Login success for user '".$username."'","None");
						$this->reloadInterface($url);
						return;
					}
				}
			} else {
				$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid,username,sha_pwd","username = '".$username."'");
				if ($data = FS::$dbMgr->Fetch($query)) {
					$encryptPwd = FS::$secMgr->EncryptPassword($password,$username,$data["uid"]);
					if ($data["sha_pwd"] != $encryptPwd) {
						$this->log(1,"Login failed for user '".$username."' (Bad password)","None");
						FS::$dbMgr->Update(PgDbConfig::getDbPrefix()."users","failauthnb = failauthnb + 1","username = '".$username."'");
						FS::$iMgr->ajaxEcho("err-bad-user");
						return;
					}
					$this->genAPIKeyIfNot($username);
					$this->connectUser($data["uid"]);
					$this->log(0,"Login success for user '".$username."'","None");
					$this->reloadInterface($url);
					return;
				}
			}
			$this->log(1,"Login failed for user '".$username."' (Unknown user)","None");
			FS::$iMgr->ajaxEcho("err-bad-user");
		}

		private function genAPIKeyIfNot($username) {
			if (FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","android_api_key","username = '".$username."'")) {
				return;
			}
			$dict = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

			$valid = false;
			do {
				$bigkey = "";
				for ($i=0;$i<rand(10,40);$i++) {
					$bigkey .= substr(str_shuffle($dict),0,rand(8,15));
				}
				$apikey = hash("sha256",$bigkey);
				if (!FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","android_api_key","android_api_key = '".$apikey."'")) {
					FS::$dbMgr->Update(PgDbConfig::getDbPrefix()."users","android_api_key = '".$apikey."'","username = '".$username."'");
					$valid = true;
				}
			}
			while ($valid == false);
		}
		
		private function connectUser($uid) {
			$langs = preg_split("#[;]#",$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			if (count($langs) > 0)
				$_SESSION["lang"] = $langs[0];
			$_SESSION["uid"] = $uid;
			$_SESSION["prevfailedauth"] = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","failauthnb","uid = '".$uid."'");
			FS::$dbMgr->Update(PGDbConfig::getDbPrefix()."users","failauthnb = '0', last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$uid."'");
		}

		public function LoadForAndroid() {
			if (!$this->AuthenticateAndroid()) {
				return 1;
			}

			return 0;
		}

		public function AuthenticateAndroid() {
			$apikey = FS::$secMgr->checkAndSecurisePostData("apikey");
			$user = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."users","username","android_api_key = '".$apikey."' AND android_api_key != ''");
			if (!$user) {
				return false;
			}
				
			return true;
		}

		public function reloadInterface($url) {
			if ($url) {
				$url = "&".$url;
			}
			$js = "loadWindowHead();loadMainContainer('".$url."');";
			FS::$iMgr->ajaxEcho("Done",$js);
		}

		public function Disconnect() {
			if (FS::$sessMgr->getUid()) {
				$this->log(0,"User disconnected");
				FS::$sessMgr->Close(); 

				$js = "loadWindowHead();loadMainContainer('');unlockScreen(true);";
				FS::$iMgr->ajaxEcho("Done",$js);
			}
		}

		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$user = FS::$secMgr->checkAndSecurisePostData("loginuname");
					$pwd = FS::$secMgr->checkAndSecurisePostData("loginupwd");
					$this->TryConnect($user,$pwd);
					return;
				case 2:
					$this->Disconnect();
					return;
			}
		}
	};
?>
