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

	require_once(dirname(__FILE__)."/locales.php");
	require_once(dirname(__FILE__)."/rules.php");
	require_once(dirname(__FILE__)."/objects.php");
	
	if(!class_exists("iRouterMgmt")) {
		
	final class iRouterMgmt extends FSModule {
		function __construct() {
			parent::__construct();
			$this->loc = new lRouterMgmt();
			$this->rulesclass = new rRouterMgmt($this->loc);
			$this->menu = $this->loc->s("menu-name");
		}

		public function Load() {
			FS::$iMgr->setTitle("menu-name");
			return $this->showMain();
		}

		private function showMain() {
			$sh = FS::$secMgr->checkAndSecuriseGetData("sh");
			$output = "";

			if (!FS::isAjaxCall()) {
				$output .= FS::$iMgr->h1("menu-name");

				$tabs[] = array(2,"mod=".$this->mid,$this->loc->s("Interface-Management"));
				$tabs[] = array(1,"mod=".$this->mid,$this->loc->s("Declare-Router"));
				$output .= FS::$iMgr->tabPan($tabs,$sh);
			}
			else {
				switch($sh) {
					case 1: $output .= $this->showRouterDeclaration(); break;
					case 2: $output .= $this->showIfaceMgmt(); break;
				}
			}
			return $output;
		}
		
		private function showIfaceMgmt() {
		}
		
		private function showRouterDeclaration() {
			FS::$iMgr->setURL("sh=1");
			FS::$iMgr->setTitle($this->loc->s("menu-name")." > ".$this->loc->s("Declare-Router"));
			$router = new routerObj();
			return $router->renderAll();
		}
	};
	
	}
	
	$module = new iRouterMgmt();
?>
