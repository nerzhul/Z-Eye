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
	class FSMailMgr {
		function FSMailMgr() {}	
		
		public function Reinit() {
			$this->sender = "";
			$this->senderMail = "";
			$this->replyto = "";
			$this->dest = "";
			$this->subject = "";
			$this->msg = "";
		}
		
		public function Send() {
			if(mail($this->dest,$this->subject,$this->msg,$this->genHeaders()))
				return true;
			return false;
		}
		
		private function genHeaders() {
			 $headers = 'From: "'.$this->sender.'"<'.$this->senderMail.'>'."\n";
			 $headers .= 'Reply-To: '.$this->replyto."\n";
			 $headers .= 'Content-Type: text/html; charset="utf-8"'."\n";
			 $headers .= 'Content-Transfer-Encoding: 8bit'; 	
			 return $headers;
		}
		
		public function setSender($send,$smail) { $this->sender = $send; $this->senderMail = $smail; }
		public function setReply($rto) { $this->replyto = $rto; }
		public function setDest($dst) { $this->dest = $dst; }
		public function setSubject($subject) { $this->subject = $subject; }
		public function setMsg($mess) { $this->msg = $mess; }
		
		private $sender;
		private $senderMail;
		private $replyto;
		private $dest;
		private $subject;
		private $msg;		
	}
?>