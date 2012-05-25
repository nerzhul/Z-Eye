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
	
require_once(dirname(__FILE__)."/../../config/ldap.conf.php");
class LDAP {
        function LDAP() {
                $this->isConnected = false;
                $this->connection = null;
        }

        public function Connect($user="",$pass="") {
                $URI = (LDAPConfig::$SSL ? "ldaps://" : "ldap://").LDAPConfig::$ldapServer;
                $conn = @ldap_connect($URI,LDAPConfig::$ldapServerPort);
                if($conn){
                        @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
                        @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
                        if($user == "")
                                $bindServerLDAP = @ldap_bind($conn);
                        else if($pass == "")
                                $bindServerLDAP = @ldap_bind($conn,$user);
                        else
                                $bindServerLDAP = @ldap_bind($conn,$user,$pass);
                        if(!$bindServerLDAP)
                                return false;
                        $this->connection = $conn;
                        return true;
                }
                return false;
        }
        public function RootConnect() {
                if(!$this->Connect(LDAPConfig::$DN,LDAPConfig::$LDAPmdp))
                        return false;
                return true;
        }

        public function Authenticate($user,$pwd) {
                if(!$this->RootConnect())
                        return false;
                $result = $this->GetOneEntry(LDAPConfig::$LDAPuid."=".$user);
                $this->Disconnect();
                if(!$result)
                        return false;
                $result = $this->Connect($result["dn"],$pwd);
                if(!$result)
                        return false;
                return true;
        }

        public function Disconnect() {
                if($this->isConnected)
                        ldap_close($conn);
        }

        public function getOneEntry($query) {
                if(!$this->connection)
                        die("LDAP Not Connected: getOneEntry fail");
                $res = $this->getEntries($query);
                if(count($res))
                        return $res[0];
                return null;
        }

        public function getEntries($query) {
                if(!$this->connection)
                        die("LDAP Not Connected: getEntries fail");
                else {
			$filter = (LDAPConfig::$LDAPfilter != "" ? "(&".LDAPConfig::$LDAPfilter."(".$query."))" : $query);
                        $result = @ldap_search($this->connection, LDAPConfig::$baseDN, $filter);
                        if(!$result)
                                return null;
                        $cleaned = $this->cleanEntries(ldap_get_entries($this->connection, $result));
                        return $cleaned;
                }
                return null;
        }

        private function & cleanEntries (array &$entries)
        {
                unset ($entries ['count']);

            // FOr each entry
            foreach ($entries as $key => $attributes)
            {
              unset ($entries [$key]['count']);

              for ($i = 0; array_key_exists ($i, $entries [$key]); $i++)
                  unset ($entries [$key][$i]);

              // for earch attribute on the entry
              foreach ($entries[$key] as $attName => $attValue)
              {
                if (is_array ($entries[$key][$attName]))
                {
                  if ($entries[$key][$attName]['count'] > 1)
                    unset ($entries [$key][$attName]['count']);
                  else
                    $entries [$key][$attName] = $entries [$key][$attName][0];
                }
              }
            }

            return $entries;
        }

        public function getCount($query) {
                if(!$this->connection)
                        die("LDAP Not Connected: getCount fail");
                else {
                        $result = ldap_search($this->connection, LDAPConfig::$baseDN, $query);
                        return ldap_count_entries($this->connection, $result);
                }

        }
        public function IsConnected() { return $this->isConnected; }
        private $isConnected;
        private $connection;
};

?>
