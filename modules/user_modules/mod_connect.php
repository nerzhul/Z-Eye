<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	class iConnect extends genModule{
		function iConnect($iMgr) { parent::genModule($iMgr); }
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
			$link = new HTTPLink(14);
			$output .= "<div id=\"module_connect\"><h4>Connexion à votre espace personnel</h4>";
			$output .= $this->iMgr->addForm($link->getIt());
			$output .= $this->iMgr->addInput("uname","login");
			$output .= "<br />";
			$output .= $this->iMgr->addPasswdField("upwd","password");
			$output .= "<br />";
			$output .= $this->iMgr->addSubmit("connect","Connexion");
			$output .= "</form></div>";
			return $output;
		}
		
		public function TryConnect($username,$password) {
			$output = "";
			$errorldap = 0;
			if(!FS::$ldapMgr->Authenticate($username, $password)) {
                               $errorldap = 1;
                        }

			$url = FS::$secMgr->checkAndSecurisePostData("rdr");
                        if($url == NULL || $url == "http://demeter.srv.iogs/index.php") $url = "m-32.html";

			echo $url;
			if($errorldap == 0) {
	                        FS::$ldapMgr->RootConnect();
        	                $result = FS::$ldapMgr->GetOneEntry(LDAPConfig::$LDAPname."=".$username);
                	        if(!$result) {
                        	    $link = new HTTPLink(15);
        	               	    header("Location: ".$link->getIt());
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

				$query = FS::$dbMgr->Select("fss_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
				if($data = mysql_fetch_array($query))
				{
					$_SESSION["uid"] = $data["uid"];
                                        $_SESSION["ulevel"] = $data["ulevel"];
                                        FS::$dbMgr->Update("fss_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
					header("Location: ".$url);
					return;
				}
				else {
	        	                $user->Create();
					$query = FS::$dbMgr->Select("fss_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
	                                if($data = mysql_fetch_array($query))
                                	{
                        	                $_SESSION["uid"] = $data["uid"];
                	                        $_SESSION["ulevel"] = $data["ulevel"];
        	                                FS::$dbMgr->Update("fss_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
	                                        header("Location: ".$url);
                                        	return;
                                	}
				}
			} else {
				$query = FS::$dbMgr->Select("fss_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
				if($data = mysql_fetch_array($query)) {
					$encryptPwd = FS::$secMgr->EncryptPassword($password,$username,$data["uid"]);
					if($data["sha_pwd"] != $encryptPwd) {
						$link = new HTTPLink(15);
						header("Location: ".$link->getIt());
						return;
					}
					$_SESSION["uid"] = $data["uid"];
					$_SESSION["ulevel"] = $data["ulevel"];
					FS::$dbMgr->Update("fss_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
					header("Location: ".$url);
					return;
				}
			}

			$link = new HTTPLink(15);
                        header("Location: ".$link->getIt());
		}
	};
?>
