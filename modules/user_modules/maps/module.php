<?php
	/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
	class iMap extends genModule{
		function iMap($iMgr) { parent::genModule($iMgr); $this->img = NULL; }
		public function Load() {
			$output = "ee";
			$this->createImage(800,600);
			$this->backgroundColor(255,0,0);
			$this->render();
			echo "toto";
			return $output;
		}

		private function writeCircle($x,$y,$l,$h, $color) {
			ImageEllipse($this->img,$x,$y,$l,$h,$color);
		}

		private function backgroundColor($red,$blue,$green) {
			if($red < 0 || $red > 255 || $blue < 0 || $blue > 255 || $green < 0 || $green > 255)
				return;
			ImageColorAllocate($this->img,$red,$blue,$green);
		}

		private function createImage($x,$y) {
			header("Content-type: image/png");
			$this->img = ImageCreate($x,$y);
		}

		private function render() {
			if($this->img != NULL)
				ImagePng($this->img);
		}

		private $img;
	};
?>
