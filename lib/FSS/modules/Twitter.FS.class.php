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