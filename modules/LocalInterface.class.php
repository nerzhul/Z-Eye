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

	require_once(dirname(__FILE__)."/../lib/FSS/User.FS.class.php");
	require_once(dirname(__FILE__)."/../lib/FSS/HTTPLink.FS.class.php");

	class LocalInterface extends FSInterfaceMgr {
		function LocalInterface() {
			parent::FSInterfaceMgr();
			$this->showRetMenu = false;
		}

		public function content() {
			$lockinstall = file_get_contents(dirname(__FILE__)."/../config/LOCK");

			$output = "<div id=\"lock\" style=\"display:none;\"><div id=\"subpop\"></div></div>";
			if($lockinstall) {
				$output .= "<div draggable=\"true\" id=\"trash\">".FS::$iMgr->img("styles/trash.png",64,64)."</div>";
				$output .= "<div draggable=\"true\" id=\"editf\">".FS::$iMgr->img("styles/edit.png",64,64)."</div>";
			}
			$output .= "<div id=\"tooltip\"></div>";
			

			$tmpoutput = "<div id=\"main\">";
			$tmpoutput .= $this->showModule();
			$tmpoutput .= "</div>";
			// must be after because of return button
			if($lockinstall)
				$output .= $this->showConnForm();
			$output .= $tmpoutput.
				"<div id=\"notification\"><div id=\"subnotification\"></div></div>
				<div id=\"footer\"><center>Designed and Coded by Loïc BLOT, CNRS - Copyright 2010-".date('Y').", All rights Reserved</center></div>";

			// Add header
			$output = $this->header().$output;
			return $output;
		}

		public function showNotification($text,$timeout = 5000, $jsbraces = true) {
			$output = "";
			if($jsbraces) $output .= "<script type=\"text/javascript\">";
			$output .= "$('#subnotification').html('".addslashes($text)."');
				$('#notification').slideDown();
				setTimeout(function() {
					$('#notification').slideUp();
				},".$timeout.");";
			if($jsbraces) $output .= "</script>";
			return $output;
		}

		protected function showConnForm() {
			$output = "<div id=\"logform\"><div id=\"menupanel\">";
			if($this->showRetMenu) {
				$output .= "<div id=\"menuStack\"><div id=\"menuTitle\" onclick=\"javascript:history.back()\">Retour</div></div>";
			}
			$menulist = array(1,7,6,3,2);
                        $output .= $this->loadMenus($menulist);
			$output .= "<div id=\"menuStack\"><div id=\"menuTitle\"><ul class=\"login\">";

			$output .= "<li id=\"logintoggle\">";
			if(!FS::$sessMgr->isConnected())
					$output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Connexion</a>";
			else
					$output .= "<a id=\"loginopen\" class=\"open\" href=\"#\">Déconnexion</a>";
			$output .= "<a id=\"loginclose\" style=\"display: none;\" class=\"close\" href=\"#\">Fermer</a>
			</li></ul></div></div>";

			if(FS::$sessMgr->isConnected())
				$output .= $this->showSearchForm();

			$output .= "<div id=\"logpanel\"><div class=\"contentlog clearfixlogform\"><div class=\"left\">";
			$output .= $this->h4("Bienvenue sur Z-Eye",true);

			$output .= "<p class=\"grey\">Cette interface permet de gérer et monitorer les services et équipements réseau</p>";

			$output .= "</div><div class=\"left\">";

			if(!FS::$sessMgr->isConnected()) {
				$output .= "<form class=\"clearfixlogform\" action=\"index.php?mod=".$this->getModuleIdByPath("connect")."&act=1\" method=\"post\">";
					$output .= $this->h4("Identification",true);
					$output .= $this->label("uname","Utilisateur");
					$output .= $this->input("uname","");
					$output .= $this->label("upwd","Mot de passe");
					$output .= $this->password("upwd","");
					$output .= $this->hidden("redir",$_SERVER["REQUEST_URI"]);
					$output .= "<div class=\"clearlogform\"></div>";
					$output .= $this->submit("conn","Connexion");
					$output .= "</form>";
			} else {
				$output .= FS::$iMgr->h4("Déconnexion",true)."<form class=\"clearfixlogform\" action=\"index.php?mod=".$this->getModuleIdByPath("disconnect")."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
				$output .= FS::$iMgr->submit("disconnect","Confirmer");
				$output .= "</form>";
			}

			$output .= "</div></div></div>";
			$output .= "</div></div>";

			return $output;
		}

		protected function showSearchForm() {
			$output = "<div id=\"menuStack\">
				<div id=\"menuItem\"><div id=\"search\">";
			$output .= $this->form("index.php?mod=".$this->getModuleIdByPath("search"),array("get" => 1));
                        $output .= $this->hidden("mod",$this->getModuleIdByPath("search"));
			$output .= $this->autoComplete("s",30,60)." <button class=\"searchButton\" type=\"submit\"><img src=\"styles/images/search.png\" width=\"15px\" height=\"15px\" /></button></form>";
			$output .= "</div></div></div>";

			return $output;
		}

		public function showModule() {
			$output = "";
			$lockinstall = file_get_contents(dirname(__FILE__)."/../config/LOCK");
			if(!$lockinstall)
				return $this->loadModule($this->getModuleIdByPath("install"));
				
			$module = FS::$secMgr->checkAndSecuriseGetData("mod");
			if(!$module) $module = 0;

			if($module && !FS::$secMgr->isNumeric($module))
				return $this->printError("Module inconnu !");

			if($module > 0)
				return $this->loadModule($module);
			else if($module == 0)
				return $this->loadModule($this->getModuleIdByPath("default"));
			else
				return $this->printError("Module inconnu !");
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
				require(dirname(__FILE__)."/user_modules/".$path."/main.php");

				if($module->getRulesClass()->canAccessToModule()) {
					$this->setCurrentModule($module->getModuleClass());
					$module->getModuleClass()->setModuleId($id);
					$output .= $module->getModuleClass()->Load();
				}
				else
					$output .= $this->printError("Vous n'êtes pas accrédité pour l'accès à ce contenu.");
			}
			else
				$output .= $this->printError("Module inconnu !");

			return $output;
		}

		public function linkIcon($link,$iconname,$options=array()) {
			$output = "<a ";
			if(isset($options["js"]) && $options["js"] == true) {
				$jsarr = "{";
				if(isset($options["snotif"])) $jsarr .= "'snotif': '".addslashes($options["snotif"])."'";
				// Confirmation div
				if(isset($options["confirm"]) && is_array($options["confirm"]) && count($options["confirm"]) == 3) {
					$jsarr .= ($jsarr != "{" ? "," : "")."'lock': true";
					$output .= "onclick=\"confirmPopup('".addslashes($options["confirm"][0])."','".
						addslashes($this->cur_module->getLoc()->s($options["confirm"][1]))."','".
						addslashes($this->cur_module->getLoc()->s($options["confirm"][2]))."','index.php?".
						addslashes($link)."'";
				}
				else
					$output .= "onclick=\"callbackLink('index.php?".addslashes($link)."'";
				$jsarr .= "}";
				if($jsarr != "{}")
					$output .= ",".$jsarr;
				$output .= ");\" ";
			}
			else
				$output .= "href=\"index.php?".$link."\"";
			$output .= ">".FS::$iMgr->img("styles/images/".$iconname.".png",15,15)."</a>";
			return $output;
		}

		public function removeIcon($link,$options=array()) { return $this->linkIcon($link,"cross",$options); }

		public function autoComplete($name, $size = 20, $length = 40, $label=NULL, $tooltip=NULL) {
			$output = $this->input($name,"",$size,$length,$label,$tooltip);
			$output .= $this->js("$('#".$name."').autocomplete({source: 'index.php?mod=".$this->getModuleIdByPath("search")."&at=2',
				minLength: 3});");
			return $output;
		}

		public function showReturnMenu($show) { $this->showRetMenu = $show;}
		private $showRetMenu;
	};
?>
