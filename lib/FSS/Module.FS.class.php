<?php
	/*
        * Copyright (c) 2010-2014, Loïc BLOT, CNRS <http://www.unix-experience.fr>
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

	abstract class FSModule {
		function __construct() {
			$this->loc = "";
			$this->modulename = "";
			$this->menu = "";
			$this->menupriority = 0;
			$this->scheduleclass = NULL;
			$this->rulesclass = NULL;
		}

		public function Load() { FS::$iMgr->printError("err-unk-module"); }
		public function LoadForAndroid() { return "{'code': 7}"; }
		public function handlePostDatas($act) {}
		public function getIfaceElmt() {}

		public function setModuleId($id) { $this->mid = $id; }
		public function getModuleId() { return $this->mid; }
		public function getMenuTitle() { return $this->loc->s("menu-title"); }
		public function getLoc() { return $this->loc; }
		public function getName() { return $this->modulename; }

		public function log($level,$str,$user=NULL) {
			if ($user === NULL) {
				$user = FS::$sessMgr->getUserName();
			}
			FS::$log->i($user,$this->modulename,$level,$str);
		}

		public function getRulesClass() { return $this->rulesclass; }
		public function getMenu() { return $this->menu; }
		public function getMenuPriority() { return $this->menupriority; }

		// Footer Plugin
		public function loadFooterPlugin() {}

		/*
		 * The title is the label on the footer bar
		 * Content is a clickable element (NOT IMPLEMENTED)
		 */
		protected function registerFooterPlugin($title,$content) {
			$this->removeFooterPlugin();
			FS::$iMgr->js(
				sprintf("$('<div id=\"footer_%s\" style=\"float: right;\"><div id=\"footerPluginTitle\">%s</div><div id=\"footerPluginContent\">%s</div></div>').appendTo('#footer #content');",
					$this->genFooterPluginName(),
					FS::$secMgr->cleanForJS($title),
					FS::$secMgr->cleanForJS($content)
				)
			);
		}

		protected function removeFooterPlugin() {
			FS::$iMgr->js(
				sprintf("$('#footer_%s').remove();",
					$this->genFooterPluginName()
				)
			);
		}

		protected function setFooterPluginTitle($title) {
			FS::$iMgr->js(
				sprintf("$('#footer_%s #footerPluginTitle').html('%s');",
					$this->genFooterPluginName(),
					FS::$secMgr->cleanForJS($title)
				)
			);
		}

		protected function setFooterPluginContent($content) {
			FS::$iMgr->js(
				sprintf("$('#footer_%s #footerPluginContent').html('%s');",
					$this->genFooterPluginName(),
					FS::$secMgr->cleanForJS($content)
				)
			);
		}

		private function genFooterPluginName() {
			return FS::$iMgr->formatHTMLId($this->modulename);
		}

		// Attributes
		protected $mid;
		protected $modulename;

		protected $loc;

		protected $rulesclass;
		protected $scheduleclass;

		protected $menu;
		protected $menupriority;
	}
?>
