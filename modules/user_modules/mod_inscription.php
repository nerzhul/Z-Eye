<?php
	require_once(dirname(__FILE__)."/generic_module.php");
	class iInscription extends genModule{
		function iInscription($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<div id=\"monoComponent\">
			<h3>Inscription</h3>";
			if($err = FS::$secMgr->checkGetData("err")) {
				$err_str = "Erreur inconnue";
				switch($err) {
					case 1: $err_str = "Un des champs est vide !"; break;
					case 2: $err_str = "Le nom d'utilisateur entré est trop court !"; break;
					case 3: $err_str = "Le nom entré est trop court"; break;
					case 4: $err_str = "Le mot de passe entré est trop court (7 caractères minimum)"; break;
					case 5: $err_str = "Le mail entré est invalide !"; break;
					case 6: $err_str = "Le nom d'utilisateur entré est invalide"; break;
					case 7: $err_str = "Le nom entré est invalide"; break;
					case 8: $err_str = "Le prénom entré est trop court"; break;
					case 9: $err_str = "Le prénom entré est invalide"; break;
					case 10: $err_str = "Les adresses mail entrées en concordent pas"; break;
					case 11: $err_str = "Les mots de passe entrés ne concordent pas"; break;
					case 12: $err_str = "L'utilisateur existe déjà !"; break;
					case 13: $err_str = "L'adresse mail entrée est déjà utilisée"; break;
				}
				if($err == -1) {
					$output .= FS::$iMgr->printDebug("Inscription Réussie !");
					$output .= "<p>Votre inscription a été effectuée. Vous recevrez le récapitulatif de vos informations par mail d'ici quelques instants. Vous pouvez d'ores et déjà vous connecter avec vos identifiants et consulter les parties privées du site.<br /><br />Merci de votre confiance<br /><br /><i>L'équipe Frost Sapphire Studios</i></p></div>";
					return $output;	
				}
				else
					$output .= FS::$iMgr->printError($err_str);
			}
			$link = new HTTPLink(64);
			$output .= $this->iMgr->addForm($link->getIt());
			$output .= "<table>";
			$output .= $this->iMgr->addIndexedLine("Identifiant de connexion","idtf");
			$output .= $this->iMgr->addIndexedLine("Prénom","sbnm");
			$output .= $this->iMgr->addIndexedLine("Nom","name");
			$output .= $this->iMgr->addIndexedLine("E-mail","mail");
			$output .= $this->iMgr->addIndexedLine("E-mail (confirmez)","mail2");
			$output .= $this->iMgr->addIndexedLine("Mot de Passe","pwd","",true);
			$output .= $this->iMgr->addIndexedLine("Mot de Passe (confirmez)","pwdr","",true);
			$output .= "<tr><td colspan=\"2\"><center>";
			$output .= $this->iMgr->addSubmit("inscr","S'inscrire");
			$output .= "</center></td></tr></table></form></div>";
			return $output;
		}
		
		public function RegisterUser() {
			$uname = FS::$secMgr->checkPostData("idtf");
			$subname = FS::$secMgr->checkPostData("sbnm");
			$name = FS::$secMgr->checkPostData("name");
			$mail = FS::$secMgr->checkPostData("mail");
			$mail2 = FS::$secMgr->checkPostData("mail2");
			$pwd = FS::$secMgr->checkPostData("pwd");
			$pwd2 = FS::$secMgr->checkPostData("pwdr");
			
			if(strlen($uname) == 0 || strlen($subname) == 0 || strlen($name) == 0 || strlen($mail) == 0 || strlen($pwd) == 0)
				return 1;

			if(strlen($uname) < 6) return 2;
			if(strlen($subname) < 2) return 8;
			if(strlen($name) < 2) return 3;
			if(strlen($pwd) < 7) return 4;
			if(!FS::$secMgr->isMail($mail)) return 5;
			if(!FS::$secMgr->isAlphaNumeric($uname)) return 6;
			if(!FS::$secMgr->isAlphabetic($name)) return 7;
			if(!FS::$secMgr->isAlphabetic($subname)) return 9;
			
			if($mail != $mail2) return 10;
			if($pwd != $pwd2) return 11;
			
			if(FS::$dbMgr->getOneData("fss_users","username","username = '".$uname."'")) return 12;
			if(FS::$dbMgr->getOneData("fss_users","mail","mail = '".$mail."'")) return 13;
			
			$user = new User();
			$user->setUsername($uname);
			$user->setSubName($subname);
			$user->setName($name);
			$user->setMail($mail);
			$user->Create();
			$uid = FS::$dbMgr->getOneData("fss_users","uid","username = '".$uname."'");
			
			$encPwd = FS::$secMgr->EncryptPassword($pwd,$user->getUserName(),$uid);
			FS::$dbMgr->Update("fss_users","sha_pwd = '".$encPwd."'","uid = '".$uid."'");
			
			FS::$mailMgr->Reinit();
			FS::$mailMgr->setSender("Frost Sapphire Studios","noreply@frostsapphirestudios.com");
			FS::$mailMgr->setReply("nobody");
			FS::$mailMgr->setDest($mail);
			FS::$mailMgr->setSubject("Inscription sur le site Frost Sapphire Studios");
			FS::$mailMgr->setMsg("Ceci est un mail automatique suite à votre inscription sur Frost Sapphire Studios.<br />
				Merci de ne pas répondre, nous ne pourrions pas traiter votre demande<br />
				<b>Rappel de vos coordonnées</b><br />
				Nom : ".$name."<br />
				Prénom : ".$subname."<br />
				Nom d'utilisateur : ".$uname."<br />
				Mail associé : ".$mail."<br />
				Mot de passe : ".$pwd."<br /><br />
				Nous vous remercions de votre confiance.<br />
				A bientôt sur www.frostsapphirestudios.com<br />
				<i>L'équipe Frost Sapphire Studios</i>");
			FS::$mailMgr->Send();
			return -1;
		}
	};
?>
