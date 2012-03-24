<?php
	class TwitterConf {
		private static $consumer_key = "";
		private static $consumer_secret = "";
		private static $oauth_token = "";
		private static $oauth_token_credentials = "";
		
		private static $bitly_login = "";
		private static $bitly_key = "";
		
		public static function getConsumerKey() { return TwitterConf::$consumer_key; }
		public static function getConsumerSecret() { return TwitterConf::$consumer_secret; }
		public static function getOAuthToken() { return TwitterConf::$oauth_token; }
		public static function getOAuthTokenCredentials() { return TwitterConf::$oauth_token_credentials; }
		
		public static function getBitLyLogin() { return TwitterConf::$bitly_login; }
		public static function getBitLyKey() { return TwitterConf::$bitly_key; }
	};
?>
