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

        class rDNSMgmt extends FSRules {
                function rDNSMgmt($locales) { parent::FSRules($locales); }

                public function showMgmtInterface($activerules = array()) {
			$output = FS::$iMgr->ruleLine($this->loc->s("rule-read-datas"),"mrule_dnsmgmt_read",$activerules,$this->loc->s("menu-title"));
			return $output;
                }

                public function canAccessToModule() {
			if (FS::$sessMgr->isConnected() && FS::$sessMgr->hasRight("mrule_dnsmgmt_read"))
                                return true;
                        return false;
                }
        };
?>
