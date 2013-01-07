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

	require_once("NamedObject.FS.class.php");
	class User extends NamedObject {
		function User() {}

		public function LoadByName($username) {
			$uid = FS::$pgdbMgr->GetOneData("z_eye_users","uid","username = '".$username."'");
			$this->LoadFromDB($uid);
		}

		public function LoadFromDB($uid) {
			$query = FS::$pgdbMgr->Select("z_eye_users","username, ulevel, subname, name, mail, join_date,last_conn, last_ip","uid = '".$uid."'");
			if($data = pg_fetch_array($query)) {
				$this->id = $uid;
				$this->username = $data["username"];
				$this->subname = $data["mail"];
				$this->name = $data["name"];
				$this->subname = $data["subname"];
				$this->joindate = $data["join_date"];
				$this->lastconn = $data["last_conn"];
				$this->lastip = $data["last_ip"];
				$this->ulevel = $data["ulevel"];
			}
		}

		public function Create() {
			$id = FS::$pgdbMgr->GetMax("z_eye_http_links","id")+1;
			FS::$pgdbMgr->Insert("z_eye_users","uid, username, ulevel, subname, name, mail, join_date, last_ip","'".$id."','".$this->username."','0','".$this->subname."','".$this->name."','".$this->mail."',NOW(),'0.0.0.0'");
		}

		public function SaveToDB() {
			FS::$pgdbMgr->Update("z_eye_users","subname ='".$this->subname."', ulevel = '".$this->ulevel."', join_date = '".$this->joindate."', 
			last_ip = '".$this->lastip."', last_conn = '".$this->lastconn."', ulevel = '".$this->ulevel."'","uid = '".$_SESSION["uid"]."'");
		}

		public function changePassword($password) {
			if(strlen($password) < Config::getPasswordMinLength())
				return 1;

			if(Config::getPasswordComplexity()) {
				if(!preg_match("#[a-z]#",$password) || !preg_match("#[A-Z]#",$password) || !preg_match("#[0-9]#",$password))
					return 2;
			}

			$hash = FS::$secMgr->EncryptPassword($password,$this->username,$this->id);
			FS::$pgdbMgr->Update("z_eye_users","sha_pwd = '".$hash."'","uid = '".$this->id."'");
			return 0;
		}

		public function getUserName() { return $this->username; }
		public function setUsername($uname) { $this->username = $uname; }

		public function getSubName() { return $this->subname; }
		public function setSubName($sname) { return $this->subname = $sname; }

		public function getUserLevel() { return $this->ulevel;	}
		public function setUserLevel($ulevel) { $this->ulevel = $ulevel; }

		public function getMail() { return $this->mail; }
		public function setMail($m) { $this->mail = $m; }

		public function getUid() {	return $this->id; }
		public function getJoinDate() { return $this->joindate; }
		public function getLastConnect() { return $this->lastconn; }
		public function getLastIP() { return $this->lastip; }

		private $username;
		private $mail;
		private $subname;
		private $joindate;
		private $lastconn;
		private $lastip;
		private $ulevel;
	}

?>
