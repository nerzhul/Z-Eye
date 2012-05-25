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
	
require_once(dirname(__FILE__)."/../../config/snmp.conf.php");
class SNMPMgr {
        function SNMPMgr() {}
		
		public function get($addr,$path) {
			return snmpget($addr,SNMPConfig::$SNMPReadCommunity,$path);			
		}
		
		public function set($addr,$path,$valuetype,$value) {
			snmpset($addr,SNMPConfig::$SNMPWriteCommunity,$path,$valuetype,$value);
		}
		
		public function setInt($addr,$path,$value) {
			$this->set($addr,$path,"i",$value);	
		}
		
		public function walk($addr,$path) {
			return snmpwalk($addr,SNMPConfig::$SNMPReadCommunity,$path);
		}
	};
?>