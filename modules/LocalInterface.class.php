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
	require_once(dirname(__FILE__)."/../lib/FSS/SSH.FS.class.php");

	class LocalInterface extends FSInterfaceMgr {
		function __construct() {
			parent::__construct();
			$this->showRetMenu = false;
		}

		public function content() {
			$lockinstall = file_get_contents(dirname(__FILE__)."/../config/LOCK");

			$output = $this->header().$this->popupContainer();
			if($lockinstall) {
				$output .= "<div draggable=\"true\" id=\"trash\">".FS::$iMgr->img("styles/trash.png",64,64)."</div>";
				$output .= "<div draggable=\"true\" id=\"editf\">".FS::$iMgr->img("styles/edit.png",64,64)."</div>";
			}
			$output .= "<div id=\"tooltip\"></div>";
			
			$output .= "<div id=\"main\">";
			$output .= $this->showModule();
			$output .= "</div>";
			if($lockinstall)
				$output .= $this->showConnForm();
			$output .= $this->notifContainer().$this->copyrightContainer();
			return $output;
		}

		private function popupContainer() {
			return "<div id=\"lock\" style=\"display:none;\"><div id=\"subpop\"></div></div>";
		}

		private function notifContainer() {
			return "<div id=\"notification\"><div id=\"subnotification\"></div></div>";
		}

		private function copyrightContainer() {
			return "<div id=\"footer\"><center>Designed and Coded by Loïc BLOT, CNRS - Copyright 2010-".date('Y').", All rights Reserved</center></div>";
		}

		protected function showConnForm() {
			$output = "<div id=\"logform\"><div id=\"menupanel\">";
			if($this->showRetMenu) {
				$output .= "<div id=\"menuStack\"><div id=\"menuTitle\" onclick=\"javascript:history.back()\">Retour</div></div>";
			}
                        $output .= $this->loadMenus();

			if(FS::$sessMgr->isConnected()) {
				$output .= $this->showUserForm();
				$output .= $this->showSearchForm();
			}

			if(!FS::$sessMgr->isConnected()) {
				$output .= "<div id=\"menuStack\"><div id=\"menuTitle\"><ul class=\"login\">";

				$output .= "<li id=\"logintoggle\">".
					"<a id=\"loginopen\" class=\"open\" href=\"#\">Connexion</a>".
					"<a id=\"loginclose\" style=\"display: none;\" class=\"close\" href=\"#\">Fermer</a>
					</li></ul></div></div>".
					"<div id=\"logpanel\"><div class=\"contentlog clearfixlogform\"><div class=\"left\">".
					$this->h4("Bienvenue sur Z-Eye",true).
					"<p class=\"grey\">Cette interface permet de gérer et monitorer les services et équipements réseau</p>".
					"</div><div class=\"left\">";

				$output .= FS::$iMgr->cbkForm("index.php?mod=".$this->getModuleIdByPath("connect")."&act=1","Connection");
					$output .= $this->h4("Identification",true);
					$output .= $this->label("uname","Utilisateur");
					$output .= $this->input("uname","");
					$output .= $this->label("upwd","Mot de passe");
					$output .= $this->password("upwd","");
					$output .= $this->hidden("redir",$_SERVER["REQUEST_URI"]);
					$output .= $this->submit("conn",$this->getLocale("Connection"));
					$output .= "</form></div></div></div>";
			}

			$output .= "</div></div>";

			return $output;
		}

		private function showUserForm() {
			return "<div id=\"menuStack\"><div class=\"userMenu\">".FS::$sessMgr->getUserRealName()." (".FS::$sessMgr->getUserName().")</div><div class=\"userpopup\">".
			"<div class=\"menuItem\" onclick=\"confirmPopup('".addslashes($this->getLocale("confirm-disconnect"))."','".$this->getLocale("Confirm")."','".$this->getLocale("Cancel")."',
				'index.php?mod=".$this->getModuleIdByPath("disconnect")."&act=1',{});\">".$this->getLocale("Disconnection")."</div>".

			"</div></div>";
		}

		protected function showSearchForm() {
			return "<div id=\"menuStack\"><div id=\"search\">".
				$this->form("index.php?mod=".$this->getModuleIdByPath("search"),array("get" => 1)).
                        	$this->hidden("mod",$this->getModuleIdByPath("search")).
				$this->autoComplete("s",array("size" => 30,"length" => 60))." <button class=\"searchButton\" type=\"submit\"><img src=\"styles/images/search.png\" width=\"15px\" height=\"15px\" /></button></form>".
				"</div></div>";
		}

		public function showModule() {
			$output = "";
			$lockinstall = file_get_contents(dirname(__FILE__)."/../config/LOCK");
			if(!$lockinstall)
				return $this->loadModule($this->getModuleIdByPath("install"));
				
			$module = FS::$secMgr->checkAndSecuriseGetData("mod");
			if(!$module) $module = 0;

			if($module && !FS::$secMgr->isNumeric($module))
				return $this->printError($this->getLocale("err-unk-module"));

			if($module > 0)
				return $this->loadModule($module);
			else if($module == 0)
				return $this->loadModule($this->getModuleIdByPath("default"));
			else
				return $this->printError($this->getLocale("err-unk-module"));
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
		public function searchIcon($search) { return $this->linkIcon("mod=".$this->getModuleIdByPath("search")."&s=".$search,"search"); }

		public function showReturnMenu($show) { $this->showRetMenu = $show;}
		private $showRetMenu;
	};
?>
