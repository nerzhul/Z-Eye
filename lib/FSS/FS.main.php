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
	define('CLASS_EXT','.FS.class.php');
	
	require_once(dirname(__FILE__)."/MySQLMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/PgSQLMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/SecurityMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/modules/MailMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/InterfaceMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/modules/FileMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/../../modules/LocalInterface.class.php");
	require_once(dirname(__FILE__)."/../../modules/Ajax.class.php");
	require_once(dirname(__FILE__)."/SessionMgr".CLASS_EXT);
	require_once(dirname(__FILE__)."/SNMP".CLASS_EXT);
	class FS {
		function FS() {}
		
		public static function LoadFSModules() {
			// Start MySQL connector
			FS::$dbMgr = new FSMySQLMgr();
			FS::$dbMgr->Connect();
			
			// PostgreSQL connector
			if(Config::enablePostgreSQL()) {
				FS::$pgdbMgr = new FSPostgreSQLMgr();
				FS::$pgdbMgr->Connect();
			}
			
			// Load Security Manager
			FS::$secMgr = new FSSecurityMgr(FS::$dbMgr);
			
			// Load Interface Manager
			FS::$iMgr = new LocalInterface(FS::$dbMgr);
			
			// Load Session Manager
			FS::$sessMgr = new FSSessionMgr(FS::$dbMgr);
			
			// Load Mail Manager
			FS::$mailMgr = new FSMailMgr();
			
			// Load File Mgr
			FS::$fileMgr = new FSFileMgr();
			
			// Load Ajax Mgr
			FS::$ajaxMgr = new AjaxManager();
			
			// Load SNMP Mgr
			if(Config::enableSNMP()) {
				FS::$snmpMgr = new SNMPMgr();	
			}
		}
		
		public static function isAJAXCall() {
			if(FS::$secMgr->checkAndSecuriseGetData("at"))
				return true;
				
			return false;
		}
		
		public static function isActionToDo() {
			if(isset($_GET["act"]) && strlen($_GET["act"]) > 0 && FS::$secMgr->isNumeric($_GET["act"])) {
				FS::$secMgr->SecuriseStringForDB($_GET["act"]);
				return true;
			}
			return false;
		}
		
		public static $fileMgr;
		public static $dbMgr;
		public static $pgdbMgr;
		public static $secMgr;
		public static $iMgr;
		public static $sessMgr;
		public static $mailMgr;
		public static $ajaxMgr;
		public static $snmpMgr;
	};
?>