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

	class SSH {
		function SSH($server,$port=22) {
			$this->conn = NULL;
			$this->addr = $server;
			$this->port = $port;
			$this->is_auth = false;
			$this->stdio = NULL;
		}

		public function Connect() {
			$this->conn = ssh2_connect($this->addr,$this->port);
			if(!$this->conn)
				return false;
			return true;
		}

		public function Authenticate($user,$pwd) {
			if(!$this->conn)
				return false;

			if(!ssh2_auth_password($this->conn, $user, $pwd))
				return false;

			$this->is_auth = true;
			return true;
		}

		public function OpenShell() {
			if(!$this->conn || !$this->is_auth)
				return false;

			$this->stdio = @ssh2_shell($this->conn,"xterm");
			return true;
		}

		public function tryPrivileged($cmd,$pwd,$failmsg) {
			if(!$this->conn || !$this->is_auth || !$this->stdio)
				return false;

			fwrite($this->stdio,$cmd."\n");
			usleep(250000);
			while($line = fgets($this->stdio)) {}

			fwrite($this->stdio,$pwd."\n");
			usleep(250000);
			while($line = fgets($this->stdio)) {
				if($line == $failmsg)
					return false;
			}
			return true;
		}	

		public function sendCmd($cmd) {
			if(!$this->conn || !$this->is_auth || !$this->stdio)
				return false;

			$output = "";
			$output_arr = array();
			$promptfind = false;

			fwrite($this->stdio,$cmd."\n");
			usleep(10000);

			while(!$promptfind) {
				while($line = fgets($this->stdio)) {
					if(preg_match("# --More-- #",$line))
						fwrite($this->stdio," ");
					else if(preg_match("/^(.+)[#]$/",$line))
						$promptfind = true;
					else array_push($output_arr,$line);
				}
			}

			for($i=0;$i<count($output_arr)-2;$i++)
				$output .= $output_arr[$i];
			return $output;
		}
		
		private $conn;
		private $stdio;
		private $addr;
		private $port;
		private $is_auth;
	}
?>
