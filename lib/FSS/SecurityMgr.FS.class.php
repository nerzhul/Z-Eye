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

	require_once(dirname(__FILE__)."/FS.main.php");
	require_once(dirname(__FILE__)."/AbstractSQLMgr".CLASS_EXT);

	class FSSecurityMgr {
		function __construct() {}

		public function isNumeric($str) {
			return is_numeric($str) == true;
		}

		public function isAlphaNumeric($str) {
			return preg_match("#^[\w]+$#i",$str) == true;
		}

		public function isAlphabetic($str) {
			return preg_match("#^[a-zA-Z]+$#",$str) == true;
		}

		public function isPersonName($str) {
			return preg_match("#^[\w]+([- ][\w]+)*$#i",$str) == true;
		}

		public function isSentence($str) {
			return preg_match("#^[\w]+([-_ ][\w]+)*$#i",$str) == true;
		}

		public function isMail($str) {
			return preg_match('#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,4}$#',$str) == true;
		}

		public function isHostname($str) {
			// hostname cannot start with - or numeric
			if (preg_match("#^[0-9-]#",$str))
				return false;

			// hostname cannot finish with - or has to - consecutive
			if (preg_match("#[-]$#",$str) || preg_match("#[-]{2,}#",$str))
				return false;

			// hostname contain only letters, numerics and dashes
			if (preg_match("#^[a-zA-Z]([a-zA-Z0-9-])+$#",$str))
				return true;	

			return false;
		}

		public function isDNSName($str) {
			$spl = preg_split("#[.]#",$str);
			$count = count($spl);

			// a DNS name has 2 parts or more
			if ($count < 2) {
				return false;
			}

			// each part must be a hostname
			for ($i=0;$i<$count;$i++) {
				if (!$this->isHostname($spl[$i]))
					return false;
			}

			return true;
		}

		public function getDNSNameList($buffer) {
			$tmparr = explode("\r\n",$buffer);
			$count = count($tmparr);
			for ($i=0;$i<$count;$i++) {
				if ($tmparr[$i] == "") {
					continue;
				}

				if (!$this->isDNSName($tmparr[$i])) {
					return NULL;
				}
			}
			return $tmparr;
		}

		public function isPath($str) {
			if ($str == "/" || preg_match("#^(/(?:(?:(?:(?:[a-zA-Z0-9\\-_.!~*'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\\-_.!~*'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*)(?:/(?:(?:(?:[a-zA-Z0-9\\-_.!~*'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*)(?:;(?:(?:[a-zA-Z0-9\\-_.!~*'():\@&=+\$,]+|(?:%[a-fA-F0-9][a-fA-F0-9]))*))*))*))$#",$str))
				return true;
			else
				return false;
		}

		public function hasJS($str) {
			if (preg_match("#<(.*)script(.*)>#",$str))
				return true;
			else
				return false;
		}

		public function isIP($str) {
			if (preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$#",$str)) {
				$str_array = split('\.',$str);
				if (count($str_array) != 4)
					return false;

				for ($i=0;$i<4;$i++) {
					if ($str_array[$i] > 255)
						return false;
				}

				return true;
			}
			else
				return false;
		}

		public function getIPList($buffer) {
			$tmparr = explode("\r\n",$buffer);
			$count = count($tmparr);
			for ($i=0;$i<$count;$i++) {
				if ($tmparr[$i] == "") {
					continue;
				}

				if (!$this->isIP($tmparr[$i])) {
					return NULL;
				}
			}
			return $tmparr;
		}

		public function isCIDR($str) {
			if (preg_match("#^(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])(\/(\d|[1-2]\d|3[0-2]))$#",$str)) {
				$str_array = split('\.',$str);
				if (count($str_array) != 4)
					return false;

				for ($i=0;$i<4;$i++) {
					if ($str_array[$i] > 255)
						return false;
				}

				return true;
			}
			return false;
		}

		public function isIPorCIDR($str) {
			if ($this->isIP($str) || $this->isCIDR($str))
				return true;
			return false;
		}

		public function isIPv6($str) {
			if (preg_match("#^([0-9A-F]{4}:){5}[0-9A-F]{4}$#",$str))
				return true;

			return false;
		}

		public function isSocketPort($str) {
			if (!$this->isNumeric($str))
				return false;

			if ($str < 0 || $str > 65535)
				return false;

			return true;
		}

		public function isDNSAddr($str) {
			if (preg_match("#^[a-z][a-z0-9.-]{1,}[a-z0-9]{2,}$#",$str))
				return true;

			return false;
		}

		public function isMacAddr($str) {
			if (preg_match('#^([0-9A-F]{2}:){5}[0-9A-F]{2}$#i', $str) || preg_match('#^([0-9A-F]{2}-){5}[0-9A-F]{2}$#i', $str))
				return true;

			return false;
		}

		private function isMaskElem($num) {
			$mask = 0;
			$add = 256;

			if ($num == 255)
				return true;
				
			while($add != 1) {
				if ($num == $mask)
					return true;
					
				$add /= 2;
				$mask += $add;
			}
			
			return false;
		}
		
		public function isMaskAddr($str) {
			$arr = preg_split("#\.#",$str);
			if (count($arr) != 4)
				return false;
				
			if ($arr[0] == 255) {
				if ($arr[1] == 255) {
					if ($arr[2] == 255) {
						if (!$this->isMaskElem($arr[3]))
							return false;
					}
					else if ($arr[3] != 0)
						return false;
						
					if (!$this->isMaskElem($arr[2]))
						return false;
				}
				else if ($arr[2] != 0 || $arr[3] != 0)
					return false;
					
				if (!$this->isMaskElem($arr[1]))
					return false;
			}
			else if ($arr[1] != 0 || $arr[2] != 0 || $arr[3] != 0) 
				return false;

			if (!$this->isMaskElem($arr[0]))
				return false;

			return true;
		}

		public function isLDAPDN($str) {
			if (preg_match("#^(\w+[=]{1}\w+)([,{1}]\w+[=]{1}\w+)*$#",$str))
				return true;
			else
				return false;
		}

		private function checkSentData($data) {
			if (!isset($data))
				return NULL;
			if ($data == "")
				return NULL;
			return $data;
		}

		public function checkGetData($data) {
			if (!isset($_GET[$data]))
				return NULL;

			return $this->checkSentData($_GET[$data]);
		}

		public function checkPostData($data) {
			if (!isset($_POST[$data]))
				return NULL;

			return $this->checkSentData($_POST[$data]);
		}

		public function checkAndSecurisePostData($data) {
			$data_new = $this->checkPostData($data);
			if (is_array($data_new)) {
				$count = count($data_new);
				for ($i=0;$i<$count;$i++) {
					$this->SecuriseString($data_new[$i]);
				}
			}
			else
				$this->SecuriseString($data_new);
			return $data_new;

		}

		public function checkAndSecuriseGetData($data) {
			$data_new = $this->checkGetData($data);
			if (is_array($data_new)) {
				$count = count($data_new);
				for ($i=0;$i<$count;$i++) {
					$this->SecuriseString($data_new[$i]);
				}
			}
			else
				$this->SecuriseString($data_new);
			return $data_new;

		}
		
		// Function to get Post Data + check
		public function getPost($str,$pattern) {
			$data = $this->checkAndSecurisePostData($str);
			// Only numerics
			if (preg_match("#[n]#",$pattern)) {
				if (!$this->isNumeric($data))
					return NULL;	
				
				// Positive
				if (preg_match("#[+]#",$pattern)) {
					if ($data < 0 || !preg_match("#[=]#",$pattern) && $data == 0)
						return NULL;
				}
				// Negative
				else if (preg_match("#[-]#",$pattern)) {
					if ($data > 0 || !preg_match("#[=]#",$pattern) && $data > 0)
						return NULL;
				}
				return $data;
			}
			// String a-Z
			else if (preg_match("#[s]#",$pattern) && $this->isAlphabetic($data))
				return $data;
			// String a-Z + numerics
			else if (preg_match("#[w]#",$pattern) && $this->isAlphaNumeric($data))
				return $data;
			// IPv4/IPv6 + CIDR
			else if (preg_match("#[i]#",$pattern)) {
				if (preg_match("#[4]#",$pattern)) {
					if (preg_match("#[c]#",$pattern) && $this->isIPorCIDR($data))
						return $data;
					if ($this->isIP($data))
						return $data;
				}
				else if (preg_match("#[6]#",$pattern) && $this->isIPv6($data))
					return $data;
			}
			return NULL;
		}

		public function SecuriseString(&$str) {
			$str = pg_escape_string($str);
			if ($this->hasJS($str))
				$str = "";
		}

		public function isStrongPwd($pwd) {
			if (strlen($pwd) < Config::getPasswordMinLength())
				return false;
			if (Config::getPasswordComplexity() && $this->isAlphaNumeric($pwd))
				return false;
			return true;
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

		public function genRandStr($nb) {
			$str = "";
			$chars = "abcdefghijklmnpqrstuvwxyz0123456789@#!-_/";
			srand((double)microtime()*1000);

			for ($i=0; $i<$nb; $i++) {
				$str .= $chars[rand()%strlen($chars)];
			}
			return $str;
		}
	};

?>
