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

	final class rUserMgmt extends FSRules {
		function __construct() { parent::__construct(); }

		public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLines(_("menu-title"),$activerules,array(
				array(_("rule-read-datas"),			"mrule_usermgmt_read"),
				array(_("rule-modify-user"),		"mrule_usermgmt_write"),
				array(_("rule-modify-directory"),		"mrule_usermgmt_ldapwrite"),
				array(_("rule-import-user"),		"mrule_usermgmt_ldapuserimport")
			));
			return $output;
		}

		public function canAccessToModule() {
			if (!FS::$sessMgr->isConnected()) {
				return -1;
			}

			if(FS::$sessMgr->hasRight("read","usermgmt")) {
				return true;
			}

			return false;
		}
	};
?>
