<?php
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