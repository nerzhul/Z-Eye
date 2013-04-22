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

	class rIcinga extends FSRules {
		function rIcinga($locales) { parent::FSRules($locales); }

		public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLines($this->loc->s("menu-title"),$activerules,array(
				array($this->loc->s("rule-read-datas"),			"mrule_icinga_read"),
				array($this->loc->s("rule-write-datas"),		"mrule_icinga_write"),
				array($this->loc->s("rule-modify-cmd"),			"mrule_icinga_cmd_write"),
				array($this->loc->s("rule-modify-ctg"),			"mrule_icinga_ctg_write"),
				array($this->loc->s("rule-modify-contact"),		"mrule_icinga_ctg_write"),
				array($this->loc->s("rule-modify-timeperiod"),		"mrule_icinga_tp_write"),
				array($this->loc->s("rule-modify-service"),		"mrule_icinga_srv_write"),
				array($this->loc->s("rule-modify-hg"),			"mrule_icinga_hg_write"),
				array($this->loc->s("rule-modify-host"),		"mrule_icinga_host_write")
			));
                        return $output;
		}

		public function canAccessToModule() {
			if(FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_icinga_read"))
				return true;
			return false;
		}
	};
?>
