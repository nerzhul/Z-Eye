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
	
	class FSLogger {
		function Log() {}
		
		/*
		 * Level:
		 * 0: normal
		 * 1: warn
		 * 2: crit
		 * 3: info
		 */

		// Insert function
		public static function i($user,$module,$level,$str) {
			FS::$secMgr->SecuriseStringForDB($str);
			FS::$secMgr->SecuriseStringForDB($module);
			FS::$pgdbMgr->Insert("z_eye_logs","date,module,level,user,txt","NOW(),'".$module."','".$level."','".$user."','".$str."'");
		}
	};	
?>
