<?php
	class Config {
		private static $lang = "FR-fr";
		private static $stylesheet = "fss1.css";
		private static $websiteName = "Z-Monitor";
		/* 0 (no crypt, not recommended
		1: sha1
		2: md5(sha1)
		3: md5(sha1.username)
		4: sha1(md5(sha1.username).uid)*/
		private static $cryptlevel = 1;
		private static $favicon = false;
		private static $svnrev = true;
		private static $pgsqlen = true;
		private static $snmpen = true;

		public static function getSysLang() { return Config::$lang; }
		public static function getSysStylesheet() { return Config::$stylesheet; }
		public static function getWebsiteName() { return Config::$websiteName; }
		public static function getCryptLevel() { return Config::$cryptlevel; }
		public static function hasFavicon() { return Config::$favicon; }
		public static function showSVNrev() { return Config::$svnrev; }
		public static function enablePostgreSQL() { return Config::$pgsqlen; }
		public static function enableSNMP() { return Config::$snmpen; }

	};
?>