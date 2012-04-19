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
				$output .= "<form class=\"clearfixlogform\" action=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("connect")."&act=1\" method=\"post\">";
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
				$output .= "<h4>Déconnexion</h4><form class=\"clearfixlogform\" action=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("disconnect")."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
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
			
			if(FS::$sessMgr->isConnected())
				$output .= $this->showSearch();
			if($module && !FS::$secMgr->isNumeric($module))
				$output .= $this->printError("Module inconnu !");
			else {
				FS::$secMgr->SecuriseStringForDB($module);
				if($module)
					$output .= "<hr>".$this->loadModule($module);
			}
			$output .= "</div>";
			return $output;
		}

		public function loadModule($id) {
			$output = "";
					
			$dir = opendir(dirname(__FILE__)."/user_modules");
			$found = false;
			$moduleid = 0;
			while(($elem = readdir($dir)) && $found == false) {
				$dirpath = dirname(__FILE__)."/user_modules/".$elem;
				if(is_dir($dirpath)) $moduleid++;
				if(is_dir($dirpath) && $moduleid == $id) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2)) && $found == false) {
						if(is_file($dirpath."/".$elem2) && $elem2 == "main.php")
							$found = true;
							$path = $elem;
					}
				}
			}
			if($found == true) {
				
				require_once(dirname(__FILE__)."/user_modules/".$path."/main.php");
				$module = new iModule();
				
				if($module->getConfig()->connected == 1 && FS::$sessMgr->isConnected() || 
					$module->getConfig()->connected == 0 && !FS::$sessMgr->isConnected() || $module->getConfig()->connected == 2) {
					
					if($module->getConfig()->seclevel <= FS::$sessMgr->getUserLevel()) {
						
						$module->getModuleClass()->setModuleId($id);
						$output .= $module->getModuleClass()->Load();
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
		
		public function getModuleIdByPath($path) {
			$dir = opendir(dirname(__FILE__)."/user_modules");
			$moduleid = 0;
			$found = false;
			while(($elem = readdir($dir)) && $found == false) {
				$dirpath = dirname(__FILE__)."/user_modules/".$elem;
				if(is_dir($dirpath)) $moduleid++;
				if(is_dir($dirpath) && $elem == $path) {
					$dir2 = opendir($dirpath);
					while(($elem2 = readdir($dir2)) && $found == false) {
						if(is_file($dirpath."/".$elem2) && $elem2 == "main.php")
							return $moduleid;
					}
				}
			}
			
			return 0;
		}
		
		private function showSearch() {
			$output = "<div id=\"monoComponent\"><center><h2>Z-Eye</h2>";
			$output .= $this->addForm("index.php?mod=".$this->getModuleIdByPath("search"));
			$output .= $this->addInput("s","",60,60)."<br /><input class=\"bigButtonStyle\" type=\"submit\" name=\"Rechercher\" value=\"Rechercher\" /></form>";
			$output .= "</div>";
			return $output;
		}
	};
?>
