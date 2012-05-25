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
	
	class FSNetwork {
		function FSNetwork() {
		}
		
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
			$longMask = ip2long($this->net_mask);
			return ~$longMask-1;
		}
		
		public function isUsableIP($ip) {
			if(ip2long($ip) >= $this->getFirstUsableIPLong() && ip2long($ip) <= $this->getLastUsableIPLong())
				return true;
				
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