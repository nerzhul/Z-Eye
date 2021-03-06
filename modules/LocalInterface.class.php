<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	require_once(dirname(__FILE__)."/../lib/FSS/modules/Network.FS.class.php");

	class LocalInterface extends FSInterfaceMgr {
		function __construct() {
			parent::__construct();
			$this->showRetMenu = false;
		}

		public function content() {
			$installlocked = file_get_contents(dirname(__FILE__)."/../config/LOCK");

			$output = sprintf("%s<div id=\"app-dyn-container\"></div>%s%s",
				$this->header(),
				$this->loginContainer(),
				$this->mainContainer()
			);

			if($installlocked) {
				$output = sprintf("%s<div id=\"logform\">%s</div>",
					$output, $this->showWindowHead()
				);
			}
			$output .= $this->bottomContainer();

			if ($installlocked) {
				$this->loadFooterPlugins();
			}

			// Add JS exec here, because we aren't using AJAX
			return sprintf("%s%s<script type=\"text/javascript\">%s</script>",
				$output, FS::$iMgr->footer(), FS::$iMgr->renderJS()
			);
		}

		private function mainContainer() {
			return sprintf("<div id=\"main_wrapper\"><div id=\"main\">%s</div></div>",
				$this->showModule()
			);
		}

		private function bottomContainer() {
			return FS::$iMgr->fileGetContent("http://localhost:8080/templates/footer");
		}

		public function showWindowHead() {
			$output = "<div id=\"menupanel\">";
			if($this->showRetMenu) {
				$output .= "<div id=\"menuStack\"><div id=\"menuTitle\" onclick=\"javascript:history.back()\">Retour</div></div>";
			}

            $output .= $this->loadMenus();

			if (FS::$sessMgr->isConnected()) {
				$output .= $this->showUserForm().
					$this->showSearchForm();
				// Also load inactivityTimer here.
				FS::$iMgr->js("setMaxIdleTimer('".FS::$sessMgr->getIdleTimer()."');");
			}

			if (!FS::$sessMgr->isConnected()) {
				// Connect button which open Login Container
				$output .= "<div id=\"menuStack\" onclick=\"return openLogin();\">".
					"<div id=\"menuTitle\"><a href=\"#\">Connexion</a>".
					"</div></div>";
			}

			$output .= "</div>";

			return $output;
		}

		private function loginContainer() {
			return "<div id=\"login\" style=\"display:none;\"><div id=\"loginF\"><header>".
				FS::$iMgr->img("styles/images/LogoHD.png",446,214).
				"</header><div id=\"loginCbk\"></div><div id=\"loginMsg\">".
				_("Connect-to")." ".Config::getWebsiteName()."</div><div>".
				FS::$iMgr->cbkForm("?mod=".$this->getModuleIdByPath("connect")."&act=1","Connection",true).
				$this->input("loginuname","").
				$this->password("loginupwd","").
				$this->hidden("redir",$_SERVER["REQUEST_URI"]).
				$this->submit("conn",_("Connection")).
				"</form></div></div></div>";
		}

		private function showUserForm() {
			return "<div id=\"menuStack\">".
				"<div class=\"userMenu\">".FS::$sessMgr->getUserRealName().
				" (".FS::$sessMgr->getUserName().")".
				"</div><div class=\"userpopup\">".
				"<div class=\"menuItem\" onclick=\"loadInterface('".FS::$iMgr->getModuleIdByPath("usersettings")."');\">".
				_("Settings")."</div>".
				"<div class=\"menuItem\" onclick=\"confirmPopup('".FS::$secMgr->cleanForJS(_("confirm-disconnect")).
				"','"._("Confirm")."','"._("Cancel")."',".
				"'?mod=".$this->getModuleIdByPath("connect")."&act=2',{});\">".
				_("Disconnection").
				"</div></div></div>";
		}

		protected function showSearchForm() {
			return sprintf("<div id=\"menuStack\"><div id=\"search\">%s%s<button class=\"searchButton\" type=\"submit\">".
				"<img src=\"styles/images/search.png\" width=\"15px\" height=\"15px\" /></button></form>".
				"</div></div>",
				$this->cbkForm("?mod=".$this->getModuleIdByPath("search")."&act=1","Searching...",true),
				$this->autoComplete("s",array("size" => 30,"length" => 60)));
		}

		public function showModule() {
			$output = "";
			$installlocked = file_get_contents(dirname(__FILE__)."/../config/LOCK");
			if(!$installlocked) {
				return $this->loadModule($this->getModuleIdByPath("install"));
			}

			$module = FS::$secMgr->checkAndSecuriseGetData("mod");
			if(!$module) {
				$module = 0;
			}

			if($module && !FS::$secMgr->isNumeric($module)) {
				return $this->printError("err-unk-module");
			}

			if($module > 0) {
				return $this->loadModule($module);
			}
			else if($module == 0) {
				return $this->loadModule($this->getModuleIdByPath("default"));
			}
			else {
				return $this->printError("err-unk-module");
			}
		}

		public function linkIcon($link,$iconname,$options=array()) {
			$output = "<a ";
			if(isset($options["js"]) && $options["js"] == true) {
				$jsarr = "{";
				if(isset($options["snotif"])) {
					$jsarr .= "'snotif': '".FS::$secMgr->cleanForJS($options["snotif"])."'";
				}

				// Confirmation div
				if(isset($options["confirm"]) && is_array($options["confirm"]) && count($options["confirm"]) == 3) {
					$jsarr .= ($jsarr != "{" ? "," : "")."'lock': true";
					$output .= sprintf("onclick=\"confirmPopup('%s','%s','%s','?%s'",
						FS::$secMgr->cleanForJS($options["confirm"][0]),
						FS::$secMgr->cleanForJS(_($options["confirm"][1])),
						FS::$secMgr->cleanForJS(_($options["confirm"][2])),
						FS::$secMgr->cleanForJS($link)
					);
				}
				else {
					$output .= sprintf("onclick=\"callbackLink('?%s'",
						FS::$secMgr->cleanForJS($link)
					);
				}
				$jsarr .= "}";
				if($jsarr != "{}") {
					$output .= ",".$jsarr;
				}
				$output .= ");\" ";
			}
			else {
				$output .= "href=\"/?".$link."\"";
			}
			$output .= ">".FS::$iMgr->img("/styles/images/".$iconname.".png",15,15,"",$options)."</a>";
			return $output;
		}

		public function removeIcon($actid, $link, $options=array()) {
			/*
			 * We override confirm option with an helper.
			 * confirm only contain a text, we add delete button labels
			 * here
			 */
			if (isset($options["confirmtext"]) && isset($options["confirmval"])) {
				$options["confirm"] = array(
					sprintf(_($options["confirmtext"]),
						$options["confirmval"]),
					"Confirm",
					"Cancel"
				);
			}

			$link = sprintf("mod=%s&act=%s&%s",
				$this->cur_module->getModuleId(),
				$actid,
				$link);

			return $this->linkIcon($link,"cross",$options);
		}
		public function searchIcon($search) { return $this->linkIcon("mod=".$this->getModuleIdByPath("search")."&s=".$search,"search"); }

		public function showReturnMenu($show) { $this->showRetMenu = $show;}
		private $showRetMenu;
	};
?>
