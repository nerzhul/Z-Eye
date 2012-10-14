<?php
	class zLocales {
		public function s($str) {
			if(!isset($_SESSION["lang"]) || !isset($this->locales[$_SESSION["lang"]])) $lang = "fr";
			else $lang = $_SESSION["lang"];
			
			if(!$str || $str == "" || !isset($this->locales[$lang][$str]))
				return "String '".$str."' not found";
				
			return $this->locales[$lang][$str];
		}
		
		protected $locales;
	};
?>
