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
	
	require_once("NamedObject.FS.class.php");
	class User extends NamedObject {
		function User() {}
		
		public function LoadByName($username) {
			$uid = FS::$dbMgr->GetOneData("fss_users","uid","username = '".$username."'");
			$this->LoadFromDB($uid);
		}
		
		public function LoadFromDB($uid) {
			$query = FS::$dbMgr->Select("fss_users","username, ulevel, subname, name, mail, join_date,last_conn, last_ip","uid = '".$uid."'");
			if($data = mysql_fetch_array($query)) {
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
			FS::$dbMgr->Insert("fss_users","username, ulevel, subname, name, mail, join_date, last_ip","'".$this->username."','0','".$this->subname."','".$this->name."','".$this->mail."',NOW(),'0.0.0.0'");
		}
		
		public function SaveToDB() {
			FS::$dbMgr->Update("fss_users","subname ='".$this->subname."', ulevel = '".$this->ulevel."', join_date = '".$this->joindate."', 
			last_ip = '".$this->lastip."', last_conn = '".$this->lastconn."', ulevel = '".$this->ulevel."'","uid = '".$_SESSION["uid"]."'");
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