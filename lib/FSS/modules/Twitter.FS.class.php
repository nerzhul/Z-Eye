<?php
	require_once(dirname(__FILE__)."/../../twitteroauth/twitteroauth.php");
	require_once(dirname(__FILE__)."/../../../config/twitter.conf.php");
	class FSTwitter {
		function FSTwitter() {
			$this->msg = "";
			$this->url = "";
			$this->connect = NULL;
		}
		
		public function FSConnect() {
			$this->connect = new TwitterOAuth(TwitterConf::getConsumerKey(), TwitterConf::getConsumerSecret(), TwitterConf::getOAuthToken(), TwitterConf::getOAuthTokenCredentials());
		}
		
		public function publish() {
			$message = substr($this->msg,0,138-strlen($this->url))." ".$this->url;
			$twitarr = array('status' => $message);
			
			$this->connect->post('statuses/update', $twitarr);
		}
		
		public function setMsg($message) {
			$this->msg = $message;
		}
		
		public function setURL($link) {
			$this->url = $link;	
		}
		
		// TODO: auth for everybody to our website
		
		private $connect;
		private $msg;
		private $url;
	};
?>