<?php
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