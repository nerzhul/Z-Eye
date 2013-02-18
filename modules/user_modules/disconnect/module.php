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
	
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	
	class iDisconnect extends genModule{
		function iDisconnect() { parent::genModule(); $this->loc = new lDisconnect(); }

		public function Load() {
			FS::$iMgr->setCurrentModule($this);
			$output = "<div id=\"module_connect\"><h4>".$this->loc->s("Disconnect")."</h4><form action=\"index.php?mod=".$this->mid."&act=1\" method=\"post\">".$this->loc->s("confirm-disconnect")."<br /><br />";
			$output .= FS::$iMgr->submit("",$this->loc->s("Confirm"));
			$output .= "</form></div>";
			return $output;
		}

		public function Disconnect() {
			$act = FS::$secMgr->checkAndSecuriseGetData("act");
			switch($act) {
				case 1: if(FS::$sessMgr->getUid()) {
						FS::$log->i(FS::$sessMgr->getUserName(),"disconnect",1,"User disconnected");
						FS::$sessMgr->Close(); 
					}
					break;
				default: break;
			}
			FS::$iMgr->redir("mod=0");	
		}
		
		public function handlePostDatas($act) {
			$this->Disconnect();
		}
	};
?>
