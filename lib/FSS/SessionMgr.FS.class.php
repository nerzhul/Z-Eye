<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
	
	class FSSessionMgr {
		function FSSessionMgr($DBMgr) {
			$this->dbMgr = $DBMgr;
		}
		
		public function InitSessionIfNot() {
			
			if(!isset($_SESSION["uid"]))
				$_SESSION["uid"] = 0;
			
			if(!isset($_SESSION["ulevel"]))
				$_SESSION["ulevel"] = 0;
		}
		
		public function isConnected() {
			if(isset($_SESSION["uid"]) && $_SESSION["uid"] > 0)
				return true;	
		}
		
		public function getOnlineIP() {
			$IP = "0.0.0.0";
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				$IP = $_SERVER['HTTP_X_FORWARDED_FOR'] ;
			else if(isset($_SERVER['HTTP_CLIENT_IP']))
				$IP = $_SERVER['HTTP_CLIENT_IP'] ;
			else
				$IP = $_SERVER['REMOTE_ADDR'] ;
			return $IP;
		}
		
		public function getUserAgent() {
			return $_SERVER['HTTP_USER_AGENT'];
		}
		
		public function getURI() {
			return $_SERVER['REQUEST_URI'];
		}
		
		public function getLang() {
			$lang = "fr";
			$tmp = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$lang = strtolower(substr(chop($tmp[0]),0,2));
		}
		
		public function getUserLevel() { return (isset($_SESSION["ulevel"]) ? $_SESSION["ulevel"] : 0); }
		public function getUid() { return $_SESSION["uid"]; }
		
		public function Close() {
			session_destroy();
		}
		
		private $dbMgr;
	};
?>
