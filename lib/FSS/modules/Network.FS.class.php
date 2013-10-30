<?php
	/*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */

	class FSNetwork {
		function __construct() {}

		public function getFirstUsableIP() {
			return long2ip(ip2long($this->net_addr) + 1);
		}

		public function getFirstUsableIPLong() {
			return ip2long($this->getFirstUsableIP());
		}

		public function getLastUsableIP() {
			return long2ip(ip2long($this->net_addr) + $this->getMaxHosts());
		}

		public function getLastUsableIPLong() {
			return ip2long($this->getLastUsableIP());
		}

		public function getMaxHosts() {
			return ip2long("255.255.255.255")-ip2long($this->net_mask);
		}

		public function isUsableIP($ip) {
			if(ip2long($ip) >= $this->getFirstUsableIPLong() && ip2long($ip) <= $this->getLastUsableIPLong()) {
				return true;
			}

			return false;
		}

		public function isIPinRange($start,$end,$ip) {
			if(ip2long($ip) < ip2long($start) || ip2long($ip) > ip2long($end))
				return false;

			return true;
		}

		public function isIPTouchRange($start,$end,$ip) {
			if($this->isIPinRange($start,$end,$ip))
				return true;

			if(ip2long($start)-1 == ip2long($ip) || ip2long($end)+1 == ip2long($ip))
				return true;

			return false;
		}

		public function isIPAfterIP($ip,$comp_ip) {
			if(ip2long($ip) > ip2long($comp_ip))
				return true;
				
			return false;
		}
		
		public function calcNetAddr() {
			$this->net_addr = long2ip(ip2long($this->ip_addr) & ip2long($this->net_mask));
		}
		
		public function calcBroadcastAddr() {
			$this->broadcast_addr = long2ip(ip2long($this->ip_addr) | ~ip2long($this->net_mask));
		}
		
		public function calcCIDR() {
			$bin = decbin(ip2long($this->net_mask));

			$this->cidr = substr_count($bin,"1");
		}
		
		public function setNetAddr($net) { $this->net_addr = $net; }
		public function setIPAddr($ip) { $this->ip_addr = $ip; }
		public function setNetMask($mask) { $this->net_mask = $mask; }
		
		public function getNetAddr() { return $this->net_addr; }
		public function getBroadcastAddr() { return $this->broadcast_addr; }
		public function getCIDR() { return $this->cidr; }
		public function getInvertedMask() { return long2ip(~ip2long($this->net_mask)); }
		
		private $net_addr;
		private $ip_addr;
		private $broadcast_addr;
		private $net_mask;
		private $cidr;
		
	}

?>
