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
