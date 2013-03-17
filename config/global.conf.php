<?php
	class Config {
		private static $lang = "FR-fr";
		private static $stylesheet = "fss1.css";
		private static $websiteName = "Z-Eye 1.0";
		private static $OS = "FreeBSD";
		/* 0 (no crypt, not recommended
		1: sha1
		2: md5(sha1)
		3: md5(sha1.username)
		4: sha1(md5(sha1.username).uid)*/
		private static $cryptlevel = 4;
		private static $favicon = true;
		private static $pgsqlen = true;
		private static $mysqlen = false;
		private static $snmpen = true;
		private static $defaultlang = "fr";
		private static $passwordMinLength = 8;
		private static $passwordComplexity = true;

		public static function getSysLang() { return Config::$lang; }
		public static function getSysStylesheet() { return Config::$stylesheet; }
		public static function getWebsiteName() { return Config::$websiteName; }
		public static function getOS() { return Config::$OS; }
		public static function getCryptLevel() { return Config::$cryptlevel; }
		public static function getPasswordMinLength() { return Config::$passwordMinLength; }
		public static function getPasswordComplexity() { return Config::$passwordComplexity; }
		public static function hasFavicon() { return Config::$favicon; }
		public static function enablePostgreSQL() { return Config::$pgsqlen; }
		public static function enableMySQL() { return Config::$mysqlen; }
		public static function enableSNMP() { return Config::$snmpen; }
		public static function getDefaultLang() { return Config::$defaultlang; }

	};
?>
