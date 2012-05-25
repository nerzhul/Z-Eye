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