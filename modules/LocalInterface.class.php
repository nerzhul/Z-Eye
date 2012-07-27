<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	
	require_once(dirname(__FILE__)."/../lib/FSS/User.FS.class.php");
	require_once(dirname(__FILE__)."/../lib/FSS/HTTPLink.FS.class.php");
	
	class LocalInterface extends FSInterfaceMgr {
		function LocalInterface($DBMgr) {
			parent::FSInterfaceMgr($DBMgr);
		}
		
		public function showContent() {
			$output = "<div id=\"pop\" style=\"display:none;\"><div id=\"subpop\"></div></div>";
			$output .= $this->showConnForm();
			if(FS::$sessMgr->isConnected())
				$output .= $this->showSearchForm();

			$output .= "<div id=\"main\">";
			// header for enterprise
			$output .= $this->showModule();
			$output .= "</div>";
			$output .= "<div id=\"footer\"><center>Designed and Coded by Loïc BLOT, CNRS";
			$output .= " - Copyright 2010-".date('Y').", All rights Reserved</center></div>";
			return $output;
		}

		protected function showConnForm() {
			$output = "<div id=\"logform\"><div id=\"menupanel\">";
			$menulist = array(1,7,6,3,2);
                        $output .= $this->newShowMenu($menulist);
			$output .= "<div id=\"menuStack\"><div id=\"menuElmt\"><ul class=\"login\">";

                        $output .= "<li id=\"logintoggle\">";
                        if(!FS::$sessMgr->isConnected())
                                $output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Connexion</a>";
                        else
                                $output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Déconnexion</a>";
                        $output .= "<a id=\"loginclose\" style=\"display: none;\" class=\"close\" href=\"#\">Fermer</a>
                        </li></ul></div></div>";
			$output .= "<div id=\"logpanel\"><div class=\"contentlog clearfixlogform\"><div class=\"left\">";
			$output .= "<h1>Bienvenue sur Z-Eye</h1>";

                        $output .= "<p class=\"grey\">Cette interface permet de monitorer et d'administrer les services</p>";

			$output .= "</div><div class=\"left\">";
			
			if(!FS::$sessMgr->isConnected()) {
				$output .= "<form class=\"clearfixlogform\" action=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("connect")."&act=1\" method=\"post\">";
					$output .= "<h1>Identification</h1>";
					$output .= $this->addLabel("uname","Utilisateur");
					$output .= $this->addInput("uname","");
					$output .= $this->addLabel("upwd","Mot de passe");
					$output .= $this->addPasswdField("upwd","");
					$output .= "<div class=\"clearlogform\"></div>";
					$output .= $this->addSubmit("conn","Connexion");
					$output .= "</form>";
			} else {
				$output .= "<h4>Déconnexion</h4><form class=\"clearfixlogform\" action=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("disconnect")."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
				$output .= FS::$iMgr->addSubmit("disconnect","Confirmer");
				$output .= "</form>";
			}
			
			$output .= "</div></div></div></div></div>";
		
			return $output;	
		}

		protected function showSearchForm() {
			$output = "<div id=\"searchform\">
				<div id=\"searchpanel\">
					<div class=\"contentlog clearfixlogform\">";
			$output .= $this->addForm("index.php?mod=".$this->getModuleIdByPath("search"),"",false);
                        $output .= $this->addHidden("mod",$this->getModuleIdByPath("search"));
			$output .= $this->addInput("s","",30,60)." <button class=\"searchButton\" type=\"submit\"><img src=\"styles/images/search.png\" width=\"15px\" height=\"15px\" /></button></form>";
			$output .= "</div></div>";

			$output .= "<div class=\"tabsearchform\"><a id=\"searchopen\" class=\"open\" href=\"#\"><img src=\"styles/images/search.png\" width=\"30px\" height=\"30px\" style=\"margin-top: 10px\"/></a>
				<a id=\"searchclose\" style=\"display: none;\" class=\"close\" href=\"#\"><img src=\"styles/images/search.png\" width=\"30px\" height=\"30px\" style=\"margin-top: 10px\"/></a></div>
			</div>";
		
			return $output;
		}

		private function showUserMenu() {
			$output = "<div id=\"rightmenu\">";
			$output .= $this->loadMenu(2);
			$output .= "</div>";
			return $output;
		}

		private function newShowMenu($mlist) {
			$output = "";
			for($i=0;$i<count($mlist);$i++) {
				$query = $this->dbMgr->Select("fss_menus","name,ulevel,isconnected","id = '".$mlist[$i]."'");
				if($data = mysql_fetch_array($query)) {
					$conn = FS::$sessMgr->isConnected();
					if((!$conn && $data["isconnected"] == -1 || $conn && $data["isconnected"] == 1 || $data["isconnected"] == 0) &&
					(FS::$sessMgr->getUserLevel() >= $data["ulevel"])) {
						$output .= "<div id=\"menuStack\"><div id=\"menuTitle\">".$data["name"]."</div><div class=\"menupopup\">";
						$query2 = $this->dbMgr->Select("fss_menu_link","id_menu_item","id_menu = '".$mlist[$i]."'","`order`");
						while($data2 = mysql_fetch_array($query2)) {
							$query3 = $this->dbMgr->Select("fss_menu_items","title,link,isconnected,ulevel","id = '".$data2["id_menu_item"]."'");
							while($data3 = mysql_fetch_array($query3))
								if((!$conn && $data3["isconnected"] == -1 || $conn && $data3["isconnected"] == 1 || $data3["isconnected"] == 0) &&
									(FS::$sessMgr->getUserLevel() >= $data3["ulevel"])) {
									$link = new HTTPLink($data3["link"]);
									$output .= "<div class=\"menuItem\"><a href=\"".$link->getIt()."\">".$data3["title"]."</a></div>";
								}
						}
						$output .= "</div></div>";
					}
				}
			}
			return $output;
		}

		public function getRealNameById($id) {
			$user = new User();
			$user->LoadFromDB($id);
			return $user->getSubName()." ".$user->getName();
		}

		public function showModule() {
			$output = "";
			$module = FS::$secMgr->checkGetData("mod");
			if(!$module) $module = 0;
			
			if($module && !FS::$secMgr->isNumeric($module))
				$output .= $this->printError("Module inconnu !");
			else {
				FS::$secMgr->SecuriseStringForDB($module);
				if($module)
					$output .= $this->loadModule($module);
			}
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
	};
?>
