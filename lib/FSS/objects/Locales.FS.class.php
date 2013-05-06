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

	class FSLocales {
		function __construct() { 
			$this->locales = array(
				"en" => array(
					"Add" => "Add",
					"Cancel" => "Cancel",
					"Confirm" => "Confirm",
					"Connection" => "Connection",
					"Done" => "Done",
					"err-bad-datas" => "Invalid datas",
					"err-no-rights" => "You don't have rights to do that",
					"err-unk-module" => "Unknown module !",
					"Modification" => "Modification",
					"Modify" => "Modify",
					"Name" => "Nom",
					"Remove" => "Remove",
					"rule-read-datas" => "Read datas",
					"rule-write-datas" => "Write datas",
				),
				"fr" => array(
					"Add" => "Ajouter",
					"Cancel" => "Annuler",
					"Confirm" => "Confirmer",
					"Connection" => "Connexion",
					"Done" => "Effectué",
					"err-bad-datas" => "Données invalides",
					"err-no-rights" => "Vous n'avez pas le droit de faire cela",
					"err-unk-module" => "Module inconnu ",
					"Modification" => "Modification",
					"Modify" => "Modifier",
					"Name" => "Nom",
					"Remove" => "Supprimer",
					"rule-read-datas" => "Lire les données",
					"rule-write-datas" => "Ecrire les données",
				)
			);
		}

		public function s($str) {
			if(!isset($_SESSION["lang"]) || !isset($this->locales[$_SESSION["lang"]]))
				$lang = Config::getDefaultLang();
			else $lang = $_SESSION["lang"];
			
			if(!$str || $str == "" || !isset($this->locales[$lang][$str]))
				return "String '".$str."' not found";
				
			return $this->locales[$lang][$str];
		}
		
		protected function concat($moduleLocales=array()) {
			if(!isset($moduleLocales["en"]))
				$modulesLocales["en"] = array();
			if(!isset($moduleLocales["fr"]))
				$modulesLocales["fr"] = array();
			$this->locales["en"] = array_merge($this->locales["en"],$moduleLocales["en"]);
			$this->locales["fr"] = array_merge($this->locales["fr"],$moduleLocales["fr"]);
		}

		protected $locales;
	};
?>
