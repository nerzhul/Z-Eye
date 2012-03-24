<?php
	/**
		Database exported configuration
	*/

	// Class DB config to interface with FS DB MGR
	class PGDbConfig {
		public static function getDbName() { return PGDbConfig::$dbName; }
		public static function getDbPort() { return PGDbConfig::$dbPort; }
		public static function getDbHost() { return PGDbConfig::$dbHost; }
		public static function getDbPwd() { return PGDbConfig::$dbPwd; }
		public static function getDbUser() { return PGDbConfig::$dbUser; }
		public static function getDbPrefix() { return PGDbConfig::$dbPrefix; }

		private static $dbName	= "netdisco";
		private static $dbPort = '5432';
		private static $dbHost = 'localhost';
		private static $dbPwd	= 'dbpassword';
		private static $dbUser	= 'netdisco';
		private static $dbPrefix = 'fss_';
	};
?>
