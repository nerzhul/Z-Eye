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
	
	require_once(dirname(__FILE__)."/FS.main.php");
	require_once(dirname(__FILE__)."/MySQLMgr".CLASS_EXT);
	class FSSecurityMgr {
		function FSSecurityMgr($DBMgr) {
			$this->dbMgr = $DBMgr;
		}
		
		public function isNumeric($str) {
			if(is_numeric($str))
				return true;
			return false;
		}
		
		public function isAlphaNumeric($str) {
			if(preg_match("#\W#",$str))
				return false;
			else
				return true;
		}
		
		public function isAlphabetic($str) {
			if(preg_match("#[^a-zA-Z]#",$str))
				return false;
			else
				return true;
		}
		
		public function isMail($str) {
			if(preg_match('#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#',$str))
				return true;
			else
				return false;
		}
		
		public function hasJS($str) {
			if(preg_match("#<script>#",$str))
				return true;
			else
				return false;			
		}
		
		public function isIP($str) {
			if(preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$#",$str)) {
				$str_array = split('\.',$str);
				if(count($str_array) != 4)
					return false;
					
				for($i=0;$i<4;$i++)
					if($str_array[$i] > 255)
						return false;
				
				return true;				
			}
			else
				return false;
		}
		
		public function isIPv6($str) {
				if(preg_match("#^([0-9A-F]{4}:){5}[0-9A-F]{4}$#",$str))
					return true;
					
				return false;
		}
		
		public function isSocketPort($str) {
			if(!$this->isNumeric($str))
				return false;
				
			if($str < 0 || $str > 65535)
				return false;
				
			return true;	
		}
		
		public function isDNSAddr($str) {
			if(preg_match("#^[a-z][a-z0-9.-]{1,}[a-z0-9]{2,}$#",$str))
				return true;
				
			return false;			
		}
		
		public function isMacAddr($str) {
			if(preg_match( '#^([0-9A-F]{2}:){5}[0-9A-F]{2}$#i', $str))
				return true;
				
			return false;
		}
		
		private function isMaskElem($num) {
			$mask = 0;
			$add = 256;
			
			if($num == 255)
				return true;
				
			while($add != 1) {
				if($num == $mask)
					return true;
					
				$add /= 2;
				$mask += $add;
			}
			
			return false;
		}
		
		public function isMaskAddr($str) {
			$arr = preg_split("#\.#",$str);
			if(count($arr) != 4)
				return false;
				
			if($arr[0] == 255) {
				if($arr[1] == 255) {
					if($arr[2] == 255) {
						if(!$this->isMaskElem($arr[3]))
							return false;
					}
					else if($arr[3] != 0)
						return false;
						
					if(!$this->isMaskElem($arr[2]))
						return false;
				}
				else if($arr[2] != 0 || $arr[3] != 0)
					return false;
					
				if(!$this->isMaskElem($arr[1]))
					return false;
			}
			else if($arr[1] != 0 || $arr[2] != 0 || $arr[3] != 0) 
				return false;
			
			if(!$this->isMaskElem($arr[0]))
				return false;
				
			return true;
		}
		
		private function checkSentData($data) {
			if(!isset($data))
				return NULL;
			if($data == "")
				return NULL;
			return $data;
		}
		
		public function checkGetData($data) {
			if(!isset($_GET[$data]))
				return NULL;
			
			return $this->checkSentData($_GET[$data]);
		}
		
		public function checkPostData($data) {
			if(!isset($_POST[$data]))
				return NULL;
				
			return $this->checkSentData($_POST[$data]);
		}
		
		public function checkAndSecurisePostData($data) {
			$data_new = $this->checkPostData($data);
			$this->SecuriseStringForDB($data_new);
			return $data_new;
				
		}
		
		public function checkAndSecuriseGetData($data) {
			$data_new = $this->checkGetData($data);
			$this->SecuriseStringForDB($data_new);
			return $data_new;
				
		}
		
		public function SecuriseStringForDB(&$str) {
			$str = mysql_real_escape_string($str);
			if($this->hasJS($str))
				$str = "";
		}
		
		public function EncryptPassword($pwd, $name = "", $uid = "") {
			switch(Config::getCryptLevel()) {
				case 0: return $pwd;
				case 1: return sha1($pwd);
				case 2: return md5(sha1($pwd));
				case 3: return md5(sha1($pwd).$name);
				case 4: return sha1(md5(sha1($pwd).$name).$uid);
			}
			
			return $pwd;
		}
		
		private $dbMgr;
	};

?>