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
			$this->showRetMenu = false;
		}

		public function content() {
			$output = "<div id=\"pop\" style=\"display:none;\"><div id=\"subpop\"></div></div>";
			$output .= "<div draggable=\"true\" id=\"trash\">".FS::$iMgr->img("styles/trash.png",64,64)."</div>";
			$output .= "<div draggable=\"true\" id=\"editf\">".FS::$iMgr->img("styles/edit.png",64,64)."</div>";
			$tmpoutput = "";
			if(FS::$sessMgr->isConnected())
				$tmpoutput .= $this->showSearchForm();

			$tmpoutput .= "<div id=\"main\">";
			$tmpoutput .= $this->showModule();
			$tmpoutput .= "</div>";
			// must be after because of return button
			$output .= $this->showConnForm().$tmpoutput;
			$output .= "<div id=\"notification\"><div id=\"subnotification\"></div></div>";
			$output .= "<div id=\"footer\"><center>Designed and Coded by Loïc BLOT, CNRS";
			$output .= " - Copyright 2010-".date('Y').", All rights Reserved</center></div>";
			return $output;
		}

		public function showNotification($text,$timeout = 5000) {
			return "<script type=\"text/javascript\">
				$('#subnotification').html('".addslashes($text)."');
				$('#notification').slideDown();
				setTimeout(function() {
					$('#notification').slideUp();
				},".$timeout.");
			</script>";
		}
		public function callbackNotification($link,$id,$options = array()) {
			$output = "<script type=\"text/javascript\">$('#".$id."').submit(function(event) {";
			// Locking screen if needed
			if(isset($options["lock"]) && $options["lock"] == true) {
				$output .= "$('#subpop').html('".FS::$iMgr->img("styles/images/loader.gif",32,32)."'); $('#pop').show();";
			}
			// Starting notification
			if(isset($options["snotif"]) && strlen($options["snotif"]) > 0) {
				$output .= "$('#subnotification').html('".addslashes($options["snotif"])."');
				$('#notification').slideDown();
				setTimeout(function() {
						$('#notification').slideUp();
				},".(isset($options["stimeout"]) && $options["stimeout"] > 1000 ? $options["stimeout"] : 5000).");";
			}
			$output .= "event.preventDefault();
				$.post('".$link."&at=3', $('#".$id."').serialize(), function(data) {
				$('#subnotification').html(data); $('#notification').slideDown();
				setTimeout(function() {
					$('#notification').slideUp();
				},".(isset($options["timeout"]) && $options["timeout"] > 1000 ? $options["timeout"] : 5000).");";
				if(isset($options["lock"]) && $options["lock"] == true) {
					$output .= "$('#pop').hide();";
				}
			$output .= "}); });</script>";
			return $output;
		}

		protected function showConnForm() {
			$output = "<div id=\"logform\"><div id=\"menupanel\">";
			if($this->showRetMenu) {
				$output .= "<div id=\"menuStack\"><div id=\"menuElmt\" onclick=\"javascript:history.back()\">Retour</div></div>";
			}
			$menulist = array(1,7,6,3,2);
                        $output .= $this->loadMenus($menulist);
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
					$output .= $this->label("uname","Utilisateur");
					$output .= $this->input("uname","");
					$output .= $this->label("upwd","Mot de passe");
					$output .= $this->password("upwd","");
					$output .= "<div class=\"clearlogform\"></div>";
					$output .= $this->submit("conn","Connexion");
					$output .= "</form>";
			} else {
				$output .= "<h4>Déconnexion</h4><form class=\"clearfixlogform\" action=\"index.php?mod=".FS::$iMgr->getModuleIdByPath("disconnect")."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
				$output .= FS::$iMgr->submit("disconnect","Confirmer");
				$output .= "</form>";
			}

			$output .= "</div></div></div></div></div>";

			return $output;
		}

		protected function showSearchForm() {
			$output = "<div id=\"searchform\">
				<div id=\"searchpanel\">
					<div class=\"contentlog clearfixlogform\">";
			$output .= $this->form("index.php?mod=".$this->getModuleIdByPath("search"),array("get" => 1));
                        $output .= $this->addHidden("mod",$this->getModuleIdByPath("search"));
			$output .= $this->input("s","",30,60)." <button class=\"searchButton\" type=\"submit\"><img src=\"styles/images/search.png\" width=\"15px\" height=\"15px\" /></button></form>";
			$output .= "</div></div>";

			$output .= "<div class=\"tabsearchform\"><a id=\"searchopen\" class=\"open\" href=\"#\"><img src=\"styles/images/search.png\" width=\"30px\" height=\"30px\" style=\"margin-top: 10px\"/></a>
				<a id=\"searchclose\" style=\"display: none;\" class=\"close\" href=\"#\"><img src=\"styles/images/search.png\" width=\"30px\" height=\"30px\" style=\"margin-top: 10px\"/></a></div>
			</div>";
		
			return $output;
		}

		public function showModule() {
			$output = "";
			$module = FS::$secMgr->checkAndSecuriseGetData("mod");
			if(!$module) $module = 0;

			if($module && !FS::$secMgr->isNumeric($module))
				$output .= $this->printError("Module inconnu !");
			else {
				FS::$secMgr->SecuriseStringForDB($module);
				if($module > 0)
					$output .= $this->loadModule($module);
				else if($module == 0)
					$output .= $this->loadModule($this->getModuleIdByPath("default"));
				else
					$output .= $this->printError("Module inconnu !");
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
				require(dirname(__FILE__)."/user_modules/".$path."/main.php");

				if($module->getRulesClass()->canAccessToModule()) {
						$module->getModuleClass()->setModuleId($id);
						$output .= $module->getModuleClass()->Load();
					}
				else
					$output .= $this->printError("Vous n'êtes pas accrédité pour l'accès à ce contenu.");
			}
			else
				$output .= $this->printError("Module inconnu !");

			return $output."<script type=\"text/javascript\">$('textarea, input:checkbox, input:radio, input:file').uniform();</script>";
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

		public function showReturnMenu($show) { $this->showRetMenu = $show;}
		private $showRetMenu;
	};
?>
