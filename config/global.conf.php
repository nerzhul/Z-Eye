<?php
	class Config {
		private static $lang = "FR-fr";
		private static $stylesheet = "fss1.css";
		private static $websiteName = "Z-Eye (beta 3.3)";
		private static $OS = "FreeBSD";
		/* 0 (no crypt, not recommended
		1: sha1
		2: md5(sha1)
		3: md5(sha1.username)
		4: sha1(md5(sha1.username).uid)*/
		private static $cryptlevel = 4;
		private static $favicon = false;
		private static $pgsqlen = true;
		private static $snmpen = true;
		private static $defaultlang = "fr";

		public static function getSysLang() { return Config::$lang; }
		public static function getSysStylesheet() { return Config::$stylesheet; }
		public static function getWebsiteName() { return Config::$websiteName; }
		public static function getOS() { return Config::$OS; }
		public static function getCryptLevel() { return Config::$cryptlevel; }
		public static function hasFavicon() { return Config::$favicon; }
		public static function enablePostgreSQL() { return Config::$pgsqlen; }
		public static function enableSNMP() { return Config::$snmpen; }
		public static function getDefaultLang() { return Config::$defaultlang; }

	};
?>
