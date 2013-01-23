<?php
	/*
        * Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
	require_once(dirname(__FILE__)."/../generic_module.php");
	require_once(dirname(__FILE__)."/locales.php");
	
	class iInstall extends genModule{
		function iInstall() { parent::genModule(); $this->loc = new lInstall(); }

		public function Load() {
			$output = "";
			if(!FS::isAjaxCall())
				$output .= $this->showMain();
			else
				$output .= $this->showInstaller();
			return $output;
		}

		private function showMain() {
			$output = "<h1>".$this->loc->s("title-master-install")."</h1><div id=\"installer\">";
			// For future, maybe we could resume install
			$pwdset = FS::$dbMgr->GetOneData("z_eye_users","sha_pwd","username = 'admin'");
			if(!$pwdset) {
				$output .= $this->showInstaller();
				return $output."</div>";
			}
			// If installer has finished but no lock found
			else {
				$file = fopen(dirname(__FILE__)."/../../../config/LOCK","w");
				if(file_exists(dirname(__FILE__)."/../../../config/LOCK") && !$file) {
					$output .= FS::$iMgr->printError($this->lock->s("err-lock-write"));
					return $output."</div>";
				}
				fwrite($file,"1");
				fclose($file);
			}
			return $output."</div>";
		}

		private function showInstaller() {
			if(!FS::isAjaxCall()) $step = 0; 
			else $step = FS::$secMgr->checkAndSecuriseGetData("step");
			$output = "";

			switch($step) {
				case 0:
					$output .= "<script type=\"text/javascript\">function loadStep1() {
						$('#installer').html('<center><img src=\"/styles/images/loader.gif\" /></center>');
						$.post('index.php?mod=".$this->mid."&at=2&step=1', function(data) {
							$('#installer').html(data);
							});
						}</script>";
					$output .= "<h2>".$this->loc->s("title-welcome")."</h2>";
					$output .= $this->loc->s("text-welcome")."<br /><br /><center>".FS::$iMgr->button("",$this->loc->s("Lets-Go"),"loadStep1();")."</center>";
					break;
				case 1:
					$output .= "<script type=\"text/javascript\">function loadStep2() {
						$('#installer').html('<center><img src=\"/styles/images/loader.gif\" /></center>');
						$.post('index.php?mod=".$this->mid."&at=2&step=2', function(data) {
							$('#installer').html(data);
							});
						};
						function sendAdmCfg() {
						$.post('index.php?mod=".$this->mid."&act=1',$('#admcfg').serialize(), function(data) {
							if(data == 0) loadStep2();
							else if(data == 1) { ".FS::$iMgr->showNotification($this->loc->s("err-fields-missing"),5000,false)." } 
							else if(data == 2) { ".FS::$iMgr->showNotification($this->loc->s("err-username-invalid"),5000,false)." } 
							else if(data == 3) { ".FS::$iMgr->showNotification($this->loc->s("err-mail-invalid"),5000,false)." } 
							else if(data == 4) { ".FS::$iMgr->showNotification($this->loc->s("err-pwd-match"),5000,false)." } 
							else if(data == 5) { ".FS::$iMgr->showNotification($this->loc->s("err-mail-match"),5000,false)." } 
							else if(data == 6) { ".FS::$iMgr->showNotification($this->loc->s("err-pwd-too-weak"),5000,false)." } 
							else if(data == 7) { ".FS::$iMgr->showNotification($this->loc->s("err-surname-invalid"),5000,false)." } 
							else if(data == 8) { ".FS::$iMgr->showNotification($this->loc->s("err-name-invalid"),5000,false)." } 
							else { ".FS::$iMgr->showNotification($this->loc->s("err-unhandled-answer"),5000,false)." }
						});
							return false;
						}</script>";
					$output .= "<h2>".$this->loc->s("title-admin-set")."</h2>";
					$output .= $this->loc->s("text-admin-set")."<br /><br />".FS::$iMgr->form("",array("id" => "admcfg","js" => "false"))."<table><tr><th>".$this->loc->s("Option")."</th><th>".$this->loc->s("Value")."</th></tr>";
					$output .= FS::$iMgr->idxLine($this->loc->s("Username"),"username");
					$output .= FS::$iMgr->idxLine($this->loc->s("Name"),"name");
					$output .= FS::$iMgr->idxLine($this->loc->s("Surname"),"surname");
					$output .= FS::$iMgr->idxLine($this->loc->s("Password"),"pwd","",array("type" => "pwd"));
					$output .= FS::$iMgr->idxLine($this->loc->s("Password-repeat"),"pwd2","",array("type" => "pwd"));
					$output .= FS::$iMgr->idxLine($this->loc->s("Mail"),"mail");
					$output .= FS::$iMgr->idxLine($this->loc->s("Mail-repeat"),"mail2");
					$output .= FS::$iMgr->tableSubmit($this->loc->s("Send"),array("js" => "sendAdmCfg();"))."</form>";
					break;	
				case 2:
					$output .= "<script type=\"text/javascript\">function loadStep3() {
						$('#installer').html('<center><img src=\"/styles/images/loader.gif\" /></center>');
						$.post('index.php?mod=".$this->mid."&act=2', function(data) {
							window.location = '/index.php'; });
						}</script>";
					$output .= "<h2>".$this->loc->s("title-install-finished")."</h2>";
					$output .= $this->loc->s("text-finish")."<br /><br /><center>".FS::$iMgr->button("",$this->loc->s("Finish"),"loadStep3();")."</center>";
					break;	
				default:
					return FS::$iMgr->printError($this->loc->s("err-step-invalid"));
			}
			return $output;
		}

		public function handlePostDatas($act) {
                        switch($act) {
				case 1: 
					$username = FS::$secMgr->checkAndSecurisePostData("username");		
					$pwd = FS::$secMgr->checkAndSecurisePostData("pwd");
					$pwd2 = FS::$secMgr->checkAndSecurisePostData("pwd2");
					$mail = FS::$secMgr->checkAndSecurisePostData("mail");
					$mail2 = FS::$secMgr->checkAndSecurisePostData("mail2");
					$surname = FS::$secMgr->checkAndSecurisePostData("surname");
					$name = FS::$secMgr->checkAndSecurisePostData("name");

					if(!$username || !$pwd || !$pwd2 || !$mail || !$mail2 || !$surname || !$name) {
						echo "1";
						return;
					}

					if($pwd != $pwd2) {
						echo "4";
						return;
					}

					if($mail != $mail2) {
						echo "5";
						return;
					}

					if(!FS::$secMgr->isAlphaNumeric($username)) {
						echo "2";
						return;
					}

					if(!FS::$secMgr->isAlphabetic($surname)) {
						echo "7";
						return;
					}

					if(!FS::$secMgr->isAlphabetic($name)) {
						echo "8";
						return;
					}

					if(!FS::$secMgr->isMail($mail)) {
						echo "3";
						return;
					}

					if(!FS::$secMgr->isStrongPwd($pwd)) {
						echo "6";
						return;
					}
					$user = new User();
	                                $user->setUsername($username);
        	                        $user->setSubName($surname);
                	                $user->setName($name);
                        	        $user->setUserLevel(4);
                                	$user->setMail($mail);
					$user->Create(1);
					$user->changePassword($pwd);

					echo "0";
					return;
				case 2:
					$file = fopen(dirname(__FILE__)."/../../../config/LOCK","w");
					fwrite($file,"1");
					fclose($file);
					return;
			}
		}
	};
?>
