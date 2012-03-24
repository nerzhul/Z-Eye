<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
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
			$output .= FS::$iMgr->addForm($this->mid."&act=1");
			$output .= FS::$iMgr->addInput("uname","login");
			$output .= "<br />";
			$output .= FS::$iMgr->addPasswdField("upwd","password");
			$output .= "<br />";
			$output .= FS::$iMgr->addSubmit("connect","Connexion");
			$output .= "</form></div>";
			return $output;
		}
		
		public function TryConnect($username,$password) {
			$query = FS::$dbMgr->Select("fss_users","uid,username,sha_pwd,ulevel","username = '".$username."'");
			if($data = mysql_fetch_array($query)) {
				$encryptPwd = FS::$secMgr->EncryptPassword($password,$username,$data["uid"]);
				if($data["sha_pwd"] != $encryptPwd) {
					header("Location: index.php?mod=".$this->mid."&err=1");
					return;
				}					
				$_SESSION["uid"] = $data["uid"];
				$_SESSION["ulevel"] = $data["ulevel"];
				FS::$dbMgr->Update("fss_users","last_conn = NOW(), last_ip = '".FS::$sessMgr->getOnlineIP()."'","uid = '".$data["uid"]."'");
				header("Location: index.php");
			}
			else {
				header("Location: index.php?mod=".$this->mid."&err=15");
			}
		}
		
		public function handlePostDatas($act) {
			$user = FS::$secMgr->checkAndSecurisePostData("uname");
			$pwd = FS::$secMgr->checkAndSecurisePostData("upwd");
			$this->TryConnect($user,$pwd);
		}
	};
?>