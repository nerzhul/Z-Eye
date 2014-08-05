<?php
	/*
	* Copyright (C) 2010-2014 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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

	final class rIcinga extends FSRules {
		function __construct($locales) { parent::__construct($locales); }

		public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLines(_("menu-title"),$activerules,array(
				array(_("rule-read-datas"),			"mrule_icinga_read"),
				array(_("rule-write-datas"),		"mrule_icinga_write"),
				array(_("rule-modify-cmd"),			"mrule_icinga_cmd_write"),
				array(_("rule-modify-ctg"),			"mrule_icinga_ctg_write"),
				array(_("rule-modify-contact"),		"mrule_icinga_ct_write"),
				array(_("rule-modify-notif"),		"mrule_icinga_notif_write"),
				array(_("rule-modify-timeperiod"),		"mrule_icinga_tp_write"),
				array(_("rule-modify-service"),		"mrule_icinga_srv_write"),
				array(_("rule-modify-hg"),			"mrule_icinga_hg_write"),
				array(_("rule-modify-host"),		"mrule_icinga_host_write")
			));
            return $output;
		}

		public function canAccessToModule() {
			if (!FS::$sessMgr->isConnected()) {
				return -1;
			}

			if (FS::$sessMgr->hasRight("read","icinga")) {
				return true;
			}

			return false;
		}
	};
?>
