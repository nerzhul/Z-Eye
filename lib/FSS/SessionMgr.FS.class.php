<?php
        /*
        * Copyright (c) 2012, Loïc BLOT, CNRS
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
		function FSSessionMgr() {
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
		
		public function getUserName() {
			if($this->getUid())
				return FS::$pgdbMgr->GetOneData("z_eye_users","username","uid = '".$this->getUid()."'");
			return NULL
		}

		public function getGroups() {
			$groups = array();
			$query = FS::$pgdbMgr->Select("z_eye_user_group","gid","uid = '".$this->getUid()."'");
			while($data = pg_fetch_array($query)) {
				array_push($groups,$data["gid"]);
			}
			$groups = array_unique($groups);
			return $groups;
		}

		public function isInGroup($gname) {
			$gid = FS::$pgdbMgr->GetOneData("z_eye_groups","gid","gname = '".$gname."'");
			if(in_array($gid,$this->getGroups()))
				return true;
			return false;
		}

		public function hasRight($rulename) {
			if($this->getUid() == 1)
				return true;

			$groups = $this->getGroups();
			for($i=0;$i<count($groups);$i++) {
				if(FS::$pgdbMgr->GetOneData("z_eye_group_rules","ruleval","rulename = '".$rulename."' AND gid = '".$groups[$i]."'") == "on")
					return true;
			}
			return false;
		}
	};
?>
