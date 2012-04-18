<?php
	/**
		Database exported configuration
	*/

	// Class DB config to interface with FS DB MGR
	class RadiusDbConfig {
		public static function getDbName() { return DbConfig::$dbName; }
		public static function getDbPort() { return DbConfig::$dbPort; }
		public static function getDbHost() { return DbConfig::$dbHost; }
		public static function getDbPwd() { return DbConfig::$dbPwd; }
		public static function getDbUser() { return DbConfig::$dbUser; }
		public static function getDbPrefix() { return DbConfig::$dbPrefix; }

		private static $dbName	= "radius";
		private static $dbPort = '3306';
		private static $dbHost = 'localhost';
		private static $dbPwd	= 'root';
		private static $dbUser	= 'root';
	};
?>
