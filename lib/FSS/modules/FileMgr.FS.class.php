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

	define('MAX_LINE_LENGHT',4096);
	
	class FSFileMgr {
		function FSFileMgr() {
			$this->file = NULL;
			$this->open_mode = "";
			$this->path = "";
			$this->translate_mode = "";
			$this->binary_mode = "";			
		}
		
		public function open() {
			$this->file = fopen($this->path,$this->open_mode.($this->binary_mode ? "b" : "").($this->translate_mode ? "t" : ""));
			if(!$this->file)
				return false;
				
			return true;
		}
		
		public function write($str) {
			if($this->file)
				fwrite($this->file,$str);
		}
		
		public function writeLine($str) {
			if($this->file)
				fwrite($this->file,$str."\n");
		}
		
		public function readLine() {
			if($this->file && $this->isReadable())
				return fgets($this->file,MAX_LINE_LENGHT);
			return false;
		}
		
		public function close() {
			fclose($this->file);
			$this->file = NULL;
		}
		
		public function setBinaryMode($bin) {
			$this->binary_mode = $bin;
		}
		
		public function setTranslateMode($tr) {
			$this->translate_mode = $tr;	
		}
		
		public function setOpeningMode($op) {
			$this->open_mode = $op;
		}
		
		public function setPath($path) {
			$this->path = $path;
		}
		
		private function isReadable() {
			switch($this->open_mode) {
				case "r":
				case "r+":
				case "w+":
				case "a+":
				case "x":
				case "x+":
				case "c+":
					return true;
			}
			return false;
		}
			
		private $file;
		private $translate_mode;
		private $binary_mode;
		private $open_mode;
		private $path;
	};
