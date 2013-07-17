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

	class LDAP {
		function __construct() {
			$this->isConnected = false;
			$this->connection = null;
		}

		public function Connect($user="",$pass="") {
			$URI = ($this->ssl ? "ldaps://" : "ldap://").$this->server;
			$conn = @ldap_connect($URI,$this->port);
			if ($conn){
				@ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
				@ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
				if ($user == "")
					$bindServerLDAP = @ldap_bind($conn);
				else if ($pass == "")
					$bindServerLDAP = @ldap_bind($conn,$user);
				else
					$bindServerLDAP = @ldap_bind($conn,$user,$pass);
				if (!$bindServerLDAP)
					return false;
				$this->connection = $conn;
				return true;
			}
			return false;
		}
		public function RootConnect() {
			if (!$this->Connect($this->rootDN,$this->rootpwd))
				return false;
			return true;
		}

		public function Authenticate($user,$pwd) {
			if (!$this->RootConnect())
				return false;
			$result = $this->getOneEntry($this->uidAttr."=".$user);
			$this->Disconnect();
			if (!$result)
				return false;
			$result = $this->Connect($result["dn"],$pwd);
			if (!$result)
				return false;
			return true;
		}

		public function Disconnect() {
			if ($this->isConnected)
				ldap_close($conn);
		}

		public function getOneEntry($query) {
			if (!$this->connection)
				die("LDAP Not Connected: getOneEntry fail");
			$res = $this->getEntries($query);
			if (count($res))
				return $res[0];
			return NULL;
		}

		public function getEntries($query) {
			if (!$this->connection)
				die("LDAP Not Connected: getEntries fail");
			else {
				$filter = ($this->filter != "" ? "(&".$this->filter."(".$query."))" : $query);
				$result = @ldap_search($this->connection, $this->baseDN, $filter);
				if (!$result)
					return null;
				$cleaned = $this->cleanEntries(ldap_get_entries($this->connection, $result));
				return $cleaned;
			}
			return null;
		}

		private function & cleanEntries ($entries) {
			unset ($entries ['count']);

			// For each entry
			foreach ($entries as $key => $attributes) {
				unset ($entries [$key]['count']);

				for ($i=0;array_key_exists($i, $entries[$key]);$i++) {
					unset ($entries [$key][$i]);
				}

				// for each attribute on the entry
				foreach ($entries[$key] as $attName => $attValue) {
					if (is_array ($entries[$key][$attName])) {
					       if ($entries[$key][$attName]['count'] > 1) {
						      unset ($entries [$key][$attName]['count']);
						}
						else {
							$entries [$key][$attName] = $entries [$key][$attName][0];
						}
					}
				}
			}

		    return $entries;
		}

		public function getCount($query) {
			if (!$this->connection)
				die("LDAP Not Connected: getCount fail");
			else {
				$result = ldap_search($this->connection, $this->baseDN, $query);
				return ldap_count_entries($this->connection, $result);
			}
		}

		public function addEntry($dn,$attributes) {
			if (!$this->connection)
				die("LDAP Not Connected: addEntry fail");
			if (!is_array($attributes) /*|| !FS::$secMgr->isLDAPDN($dn)*/)
				return false;
			return ldap_add($this->connection,$dn,$attributes);
		}

		public function modifyEntry($dn,$attributes) {
			if (!$this->connection)
				die("LDAP Not Connected: addEntry fail");
			if (!is_array($attributes)/* || !FS::$secMgr->isLDAPDN($dn)*/)
				return false;

			$entryName = preg_split("#[,]#",$dn);
			$entryName = $entryName[0];

			$entryType = preg_split("#[=]#",$entryName);
			$entryType = $entryType[0];

			$res = $this->getOneEntry($entryName);
			$infosRepl = array();
			$infosRemove = array();

			// Compare our entries with stored entries and generate a diff/add table
			foreach ($attributes as $key => $values) {
				if ($key == "dn")
					continue;
				if (!is_array($values)) {
					// If there is old datas
					if (isset($res[$key])) {
						// If old is array we replace
						if (is_array($res[$key]))
							$infosRepl[$key] = $values;
						// else we replace if different
						else if ($values != $res[$key])
							$infosRepl[$key] = $values;
					}
					// If not, we replace
					else
						$infosRepl[$key] = $values;
				}
				else {
					// If old datas and array, we compare array
					if (isset($res[$key]) && is_array($res[$key])) {
						// if differences, we must replace attributes
						if (count(array_diff($values,$res[$key])) > 0)
							$infosRepl[$key] = $values;
					}
					// If not, we replace
					else
						$infosRepl[$key] = $values;
				}
			}
			// Compare stored entries with our entries and generate a diff/remove table
			foreach ($res as $key => $values) {
				// Refuse to add DN and entry type to removal informations
				if (!isset($attributes[$key]) && $key != "dn" && $key != $entryType)
					$infosRemove[$key] = array();
			}
			// Remove useless attributes is there is something to remove
			if (count($infosRemove) > 0) {
				if (!$this->modDelete($dn,$infosRemove))
					return false;
			}
			return $this->modReplace($dn,$infosRepl);
		}

		public function removeEntry($dn) {
			if (!$this->connection)
				die("LDAP Not Connected: removeEntry fail");
			/*if (!FS::$secMgr->isLDAPDN($dn))
				return false;*/
			return ldap_delete($this->connection,$dn);
		}

		public function modAdd($dn,$attributes) {
			if (!$this->connection)
				die("LDAP Not Connected: modAdd fail");
			if (!is_array($attributes)/* || !FS::$secMgr->isLDAPDN($dn)*/)
				return false;
			return ldap_mod_add($this->connection,$dn,$attributes);
		}

		public function modReplace($dn,$attributes) {
			if (!$this->connection)
				die("LDAP Not Connected: modReplace fail");
			if (!is_array($attributes) /*|| !FS::$secMgr->isLDAPDN($dn)*/)
				return false;
			return ldap_mod_replace($this->connection,$dn,$attributes);
		}

		public function modDelete($dn,$attributes) {
			if (!$this->connection)
				die("LDAP Not Connected: modDelete fail");
			if (!is_array($attributes) /*|| !FS::$secMgr->isLDAPDN($dn)*/)
				return false;
			return ldap_mod_del($this->connection,$dn,$attributes);
		}

		public function IsConnected() { return $this->isConnected; }

		public function setServerInfos($addr,$port,$ssl,$baseDN,$rootDN,$rootpwd,$uidAttr,$filter) {
			$this->server = $addr;
			$this->port = $port;
			$this->ssl = $ssl;
			$this->baseDN = $baseDN;
			$this->rootDN = $rootDN;
			$this->rootpwd = $rootpwd;
			$this->uidAttr = $uidAttr;
			$this->filter = $filter;
		}

		private $baseDN;
		private $server;
		private $port;
		private $ssl;
		private $rootDN;
		private $rootpwd;
		private $uidAttr;
		private $filter;

		private $isConnected;
		private $connection;
	};

?>
