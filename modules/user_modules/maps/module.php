<?php
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
