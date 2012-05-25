<?php
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
