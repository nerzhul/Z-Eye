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
	class iConnect extends genModule{
		function iConnect() { parent::genModule(); }
		public function Load() {
			$output = "";
			$err = FS::$secMgr->checkGetData("err");
			if($err) {
				FS::$secMgr->SecuriseStringForDB($err);
				switch($err) {
					case 1: $output .= FS::$iMgr->printError("Nom d'utilisateur et/ou mot de passe inconnu"); break;
					default: $output .= FS::$iMgr->printError("Erreur inconnue.");	break;
				}
			}
			$output .= "<div id=\"module_connect\"><h4>Connexion à votre espace personnel</h4>";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this->mid."&act=1");
			$output .= FS::$iMgr->addInput("uname","login");
			$output .= "<br />";
			$output .= FS::$iMgr->addPasswdField("upwd","password");
			$output .= "<br />";
			$output .= FS::$iMgr->addSubmit("connect","Connexion");
			$output .= "</form></div>";
			return $output;
		}
		
		public function TryConnect($username,$password) {
			$output = "";
			$errorldap = 0;
			$ldapMgr = new LDAP();
			if(!$ldapMgr->Authenticate($username, $password)) {
                               $errorldap = 1;
                        }

			$url = FS::$secMgr->checkAndSecurisePostData("rdr");
                        if($url == NULL || $url == "index.php") $url = "m-0.html";

			echo $url;
			if($errorldap == 0) {
	                        $ldapMgr->RootConnect();
        	                $result = $ldapMgr->GetOneEntry(LDAPConfig::$LDAPname."=".$username);
                	        if(!$result) {
        	               	    header("Location: index.php?mod=".$this->mid."&err=1");
	                            return;
	                        }

        	                $mail = is_array($result["supannautremail"]) ? $result["supannautremail"][0] : $result["supannautremail"];
                	        $prenom = $result["givenname"];
                        	$nom = $result["sn"];
	                        $user = new User();
        	                $user->setUsername($username);
                	        $user->setSubName($prenom);
                        	$user->setName($nom);
				$user->setUserLevel(4);
	                        $user->setMail($mail);

				$query = FS::$pgdbMgr->Select("z_eye_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
				if($data = pg_fetch_array($query))
				{
					$_SESSION["uid"] = $data["uid"];
                                        $_SESSION["ulevel"] = $data["ulevel"];
                                        FS::$pgdbMgr->Update("z_eye_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
					header("Location: ".$url);
					return;
				}
				else {
	        	                $user->Create();
					$query = FS::$pgdbMgr->Select("z_eye_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
	                                if($data = pg_fetch_array($query))
                                	{
                        	                $_SESSION["uid"] = $data["uid"];
                	                        $_SESSION["ulevel"] = $data["ulevel"];
        	                                FS::$pgdbMgr->Update("z_eye_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
	                                        header("Location: ".$url);
                                        	return;
                                	}
				}
			} else {
				$query = FS::$pgdbMgr->Select("z_eye_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
				if($data = pg_fetch_array($query)) {
					$encryptPwd = FS::$secMgr->EncryptPassword($password,$username,$data["uid"]);
					if($data["sha_pwd"] != $encryptPwd) {
						$link = new HTTPLink(15);
						header("Location: index.php?mod=".$this->mid."&err=1");
						return;
					}
					$_SESSION["uid"] = $data["uid"];
					$_SESSION["ulevel"] = $data["ulevel"];
					FS::$pgdbMgr->Update("z_eye_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
					header("Location: ".$url);
					return;
				}
			}
			header("Location: index.php?mod=".$this->mid."&err=1");
		}

		public function handlePostDatas($act) {
			$user = FS::$secMgr->checkAndSecurisePostData("uname");
			$pwd = FS::$secMgr->checkAndSecurisePostData("upwd");
			$this->TryConnect($user,$pwd);
		}
	};
?>
