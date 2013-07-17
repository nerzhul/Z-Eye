<?php
        /*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */

	class FSSessionMgr {
		function __construct() {
			$this->groupBuf = array();
			$this->secMgr = FS::$secMgr;
			$this->dbMgr = FS::$dbMgr;

			session_set_save_handler(
				array($this, 'shopen'),
				array($this, 'shclose'),
				array($this, 'shread'),
				array($this, 'shwrite'),
				array($this, 'shdestroy'),
				array($this, 'shgc')
			);
		}

		public function shopen() {
			$limit = time() - Config::getSessionExpirationTime();
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."sessions","timestamp < '".$limit."'");
			return true;
		}

		public function shclose() {}

		public function shread($id) {
			FS::$secMgr->SecuriseString($id);
			if ($data = FS::$dbMgr->GetOneData(PgDbConfig::getDbPrefix()."sessions","data","id = '".$id."'"))
				return $data;
			else
				return false;
		}

		public function shwrite($id, $data) {
			$this->secMgr->SecuriseString($id);
			$this->secMgr->SecuriseString($data);
			$this->dbMgr->Delete(PgDbConfig::getDbPrefix()."sessions","id = '".$id."'");
			$this->dbMgr->Insert(PgDbConfig::getDbPrefix()."sessions","id,data,timestamp","'".$id."','".$data."','".time()."'");
			return true;
		}

		public function shdestroy($id) {
			FS::$secMgr->SecuriseString($id);
			FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."sessions","id = '".$id."'");
			return true;
		}

		public function shgc($max) {
			$limit = time() - intval($max);
			return FS::$dbMgr->Delete(PgDbConfig::getDbPrefix()."sessions","timestamp < '".$limit."'");
		}

		public function Start() {
			session_start();
		}

		public function Close() {
			session_destroy();
		}
		
		public function InitSessionIfNot() {

			if (!isset($_SESSION["uid"]))
				$_SESSION["uid"] = 0;

			if (!isset($_SESSION["ulevel"]))
				$_SESSION["ulevel"] = 0;
		}

		public function isConnected() {
			if (isset($_SESSION["uid"]) && $_SESSION["uid"] > 0)
				return true;
		}

		public function getOnlineIP() {
			$IP = "0.0.0.0";
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				$IP = $_SERVER['HTTP_X_FORWARDED_FOR'] ;
			else if (isset($_SERVER['HTTP_CLIENT_IP']))
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

		public function getUserName() {
			if ($this->getUid())
				return FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","username","uid = '".$this->getUid()."'");
			return NULL;
		}

		public function getUserRealName() {
			if ($this->getUid()) {
				$name = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","name","uid = '".$this->getUid()."'");
				$surname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."users","subname","uid = '".$this->getUid()."'");
				return $surname." ".$name;
			}
			return NULL;
		}

		public function getGroups() {
			if (is_array($this->groupBuf) && count($this->groupBuf) > 0)
				return $this->groupBuf;

			$this->groupBuf = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."user_group","gid","uid = '".$this->getUid()."'");
			while($data = pg_fetch_array($query)) {
				$this->groupBuf[] = $data["gid"];
			}
			$this->groupBuf = array_unique($this->groupBuf);
			return $this->groupBuf;
		}

		public function getUsers() {
			$users = array();
			$query = FS::$dbMgr->Select(PGDbConfig::getDbPrefix()."users","uid");
			while($data = pg_fetch_array($query)) {
				$users[] = $data["uid"];
			}
			$users = array_unique($users);
			return $users;
		}

		public function isInGroup($gname) {
			$gid = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."groups","gid","gname = '".$gname."'");
			if (in_array($gid,$this->getGroups()))
				return true;
			return false;
		}

		public function isInGIDGroup($gid) {
			if (in_array($gid,$this->getGroups()))
				return true;
			return false;
		}

		public function hasRight($rulename) {
			if ($this->getUid() == 1 || $this->isInGIDGroup(1))
				return true;

			$groups = $this->getGroups();
			$count = count($groups);
			for ($i=0;$i<$count;$i++) {
				if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."group_rules","ruleval","rulename = '".
					$rulename."' AND gid = '".$groups[$i]."'") == "on") {
					return true;
				}
			}
			if (FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."user_rules","ruleval","rulename = '".
				$rulename."' AND uid = '".$this->getUid()."'") == "on") {
				return true;
			}
			return false;
		}

		private $groupBuf;
		private $secMgr;
		private $dbMgr;
	};
?>
