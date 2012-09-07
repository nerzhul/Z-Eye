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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iDisconnect extends genModule{
		function iDisconnect() { parent::genModule(); }
		
		public function Load() {
			$output = "<div id=\"module_connect\"><h4>Déconnexion</h4><form action=\"index.php?mod=".$this->mid."&act=1\" method=\"post\">Êtes vous sûr de vouloir vous déconnecter ?<br /><br />";
			$output .= FS::$iMgr->submit("disconnect","Confirmer");
			$output .= "</form></div>";
			return $output;
		}
		
		public function Disconnect() {
			$act = FS::$secMgr->checkGetData("act");
			switch($act) {
				case 1: FS::$sessMgr->Close(); break;
				default: break;
			}
			header("Location: index.php");
		}
		
		public function handlePostDatas($act) {
			$this->Disconnect();
		}
	};
?>
