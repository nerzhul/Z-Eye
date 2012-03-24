<?php
	require_once(dirname(__FILE__)."/Category.class.php");
	require_once(dirname(__FILE__)."/../lib/FSS/User.FS.class.php");
	require_once(dirname(__FILE__)."/../lib/FSS/HTTPLink.FS.class.php");
	
	class LocalInterface extends FSInterfaceMgr {
		function LocalInterface($DBMgr) {
			parent::FSInterfaceMgr($DBMgr);
		}
		
		public function showContent() {
			$output = "<div id=\"pop\" style=\"display:none;\"><div id=\"subpop\"></div></div>";
			$output .= $this->showConnForm();
			$output .= "<div id=\"fakemain\">";
			$output .= "<div id=\"menus\">";
                        $output .= $this->showRightMenu();
			$output .= "<div id=\"rightmenu\">";
			$output .= $this->loadMenu(6);
			$output .= "</div>";
                        $output .= $this->showAdminMenu();
                        $output .= "</div>";

			$output .= "<div id=\"main\">";
			// header for enterprise
			$output .= $this->showModule();
			$output .= "</div>";
			$output .= "<div id=\"footer\"><center>Designed and Coded by Loïc BLOT, CNRS";
			$output .= " - Copyright 2010-".date('Y').", All rights Reserved</center></div></div>";
			return $output;
		}

		protected function showConnForm() {
			$output = "<div id=\"logform\"><div id=\"logpanel\"><div class=\"contentlog clearfixlogform\"><div class=\"left\">";
			$output .= "<h1>Bienvenue sur Demeter</h1>";

			$output .= "<p class=\"grey\">Cette interface permet de monitorer et administrer les services</p>";
			$output .= "</div><div class=\"left\">";

			if(!FS::$sessMgr->isConnected()) {
				$link = new HTTPLink(14);
				$output .= "<form class=\"clearfixlogform\" action=\"".$link->getIt()."\" method=\"post\">";
					$output .= "<h1>Identification</h1>";
					$output .= $this->addLabel("uname","Utilisateur");
					$output .= $this->addInput("uname","");
					$output .= $this->addLabel("upwd","Mot de passe");
					$output .= $this->addPasswdField("upwd","");
					$output .= $this->addHidden("rdr","http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
					$output .= "<div class=\"clearlogform\"></div>";
					$output .= $this->addSubmit("conn","Connexion");
					$output .= "</form>";
			} else {
				$link = new HTTPLink(16);
				$output .= "<h4>Déconnexion</h4><form class=\"clearfixlogform\" action=\"".$link->getIt()."\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
				$output .= FS::$iMgr->addSubmit("disconnect","Confirmer");
				$output .= "</form>";
			}
			
			
			$output .= "</div></div></div>";

			$output .= "<div class=\"tablogform\"><ul class=\"login\">
			<li class=\"left\">&nbsp;</li><li>Bonjour ";
			
			if(!FS::$sessMgr->isConnected())
				$output .= "invit&eacute;";
			else {
				$user = new User();
				$user->LoadFromDB($_SESSION["uid"]);
				$output .= $user->getSubName();
			}
			
			$output .= "</li><li class=\"sep\">|</li>
			<li id=\"logintoggle\">";
			if(!FS::$sessMgr->isConnected())
				$output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Connexion</a>";
			else
				$output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Déconnexion</a>";
			$output .= "<a id=\"loginclose\" style=\"display: none;\" class=\"close\" href=\"#\">Fermer</a>
			</li>
			<li class=\"right\">&nbsp;</li></ul></div></div>";
		
			return $output;
		}

		private function showRightMenu() {
			$output = "<div id=\"rightmenu\">";
			$output .= $this->loadMenu(1);
			$output .= "</div>";
			return $output;
		}
		
		private function showUserMenu() {
			$output = "<div id=\"rightmenu\">";
			$output .= $this->loadMenu(2);
			$output .= "</div>";
			return $output;
		}

		private function showAdminMenu() {
			$output = "<div id=\"rightmenu\">";
			$output .= $this->loadMenu(3);
			$output .= "</div>";
			return $output;
		}

		public function getRealNameById($id) {
			$user = new User();
			$user->LoadFromDB($id);
			return $user->getSubName()." ".$user->getName();
		}

		public function showModule() {
			$output = "<div id=\"mainContent\">";
			$module = FS::$secMgr->checkGetData("mod");
			if(!$module) $module = 0;
			
			if(!FS::$secMgr->isNumeric($module))
				$output .= $this->printError("Module inconnu !");
			else {
				FS::$secMgr->SecuriseStringForDB($module);
				switch($module) {
					case 0:	$output .= $this->showHome(); break;
					default: $output .= $this->loadModule($module); break;
				}
			}
			$output .= "</div>";
			return $output;
		}

		private function loadModule($id) {
			$output = "";
			$query = $this->dbMgr->Select("fss_modules","name,path,ulevel,isconnected","id = '".$id."'");
			if($data = mysql_fetch_array($query)) {
				if($data["isconnected"] > 0 && FS::$sessMgr->isConnected() || 
				$data["isconnected"] == -1 && !FS::$sessMgr->isConnected() || $data["isconnected"] == 0) {
					if($data["ulevel"] <= FS::$sessMgr->getUserLevel()) {
						require_once(dirname(__FILE__)."/user_modules/mod_".$data["path"].".php");
						$module = $this->getObjectByName($data["name"]);
						$output .= $module->Load();
					}
					else
						$output .= $this->printError("Vous n'êtes pas accrédité pour l'accès à ce contenu.");
				}
				else
					$output .= $this->printError("Vous devez être authentifié pour accéder à ce contenu.");
			}
			else
				$output .= $this->printError("Module inconnu !");
			return $output;
		}
		
		private function showHome() {
			$output = "";
			$output .= "<div id=\"pagination\"><br /><br /><br /><br />";
			$output .= "</div>";
			return $output;
		}
		
		private function showArticle($article) {
			$cat = new Category();
			$cat->setId($article->getCategory());
			$cat->Load();
			$output = "<div id=\"article\">
			<h4><span>".$cat->getName()." &gt; </span>".$article->getTitle()." <span>(Publi&eacute; le ".$article->getPostDate().")</span></h4>
			".$article->getContent()."<br />";
			if(strlen($article->getSource()) > 0)
				$output .= "<span>Source : <a href=\"".$article->getSource()."\">".$article->getSource()."</a></span><br />";	
			$output .= "<span>&Eacute;crit par ".$this->getRealNameById($article->getAuthor())."</span><br />";
			if($article->getUpdateDate() != $article->getPostDate())
				$output .= "<span>Mis à jour le ".$article->getUpdateDate()."</span><br />";
					
			$output .= "<hr></div>";
			return $output;
		}
		
		public function getObjectByName($name) {
			switch($name) {
				case "iConnect": return new iConnect($this);
				case "iDisconnect": return new iDisconnect($this);
				case "iLinkMgmt": return new iLinkMgmt($this);
				case "iModuleMgmt": return new iModuleMgmt($this);
				case "iMenuMgmt": return new iMenuMgmt($this);
				case "iInscription": return new iInscription($this);
				case "iSandbox": return new iSandBox($this);
				case "iDHCP": return new iDHCPConfig($this);
				case "iStats": return new iStats($this);
				case "iNagios": return new iNagios($this);
				case "iSwitchMgmt": return new iSwitchMgmt($this);
				case "iPriseMgmt": return new iPriseMgmt($this);
				case "iNetdisco": return new iNetdisco($this);
				case "":
				default: 
					require_once("user_modules/generic_module.php");
					return new genModule($this);
			}
		}
	};
?>
