# -*- coding: utf-8 -*-
"""
* Copyright (C) 2010-2014 Loic BLOT <http://www.unix-experience.fr/>
*
* This program is free software you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
"""

from GenericSwitch import GenericSwitch

Mibs = {
	"access_vlan": 						("1.3.6.1.4.1.9.9.68.1.2.2.1.2", "i"),
	"auth_dead_vlan_zero": 				("1.3.6.1.4.1.9.9.656.1.3.3.1.1", "i"),
	"auth_dead_vlan": 					("1.3.6.1.4.1.9.9.656.1.3.3.1.3", "i"),
	"auth_fail_vlan_zero": 				("1.3.6.1.4.1.9.9.656.1.3.1.1.2", "i"),
	"auth_fail_vlan": 					("1.3.6.1.4.1.9.9.656.1.3.1.1.3", "i"),
	"auth_host_mode": 					("1.3.6.1.4.1.9.9.656.1.2.1.1.3", "i"),
	"auth_noresp_vlan_zero": 			("1.3.6.1.4.1.9.9.656.1.3.2.1.1", "i"),
	"auth_noresp_vlan": 				("1.3.6.1.4.1.9.9.656.1.3.2.1.2", "i"),
	"cdp_enable": 						("1.3.6.1.4.1.9.9.23.1.1.1.1.2", "i"),
	"control_mode": 					("1.3.6.1.4.1.9.9.656.1.2.1.1.5", "i"),
	"dhcp_snooping_match_macaddr": 		("1.3.6.1.4.1.9.9.380.1.1.6.0", "i"),
	"dhcp_snooping_option_82":			("1.3.6.1.4.1.9.9.380.1.1.4.0", "i"),
	"dhcp_snooping_rate": 				("1.3.6.1.4.1.9.9.380.1.3.2.1.1", "u"),
	"dhcp_snooping_status":				("1.3.6.1.4.1.9.9.380.1.1.1.0", "i"),
	"dhcp_snooping_trust":				("1.3.6.1.4.1.9.9.380.1.3.1.1.1", "i"),
	"dhcp_snooping_vlans":				("1.3.6.1.4.1.9.9.380.1.2.1.1.2", "i"),
	"duplex_set": 						("1.3.6.1.4.1.9.5.1.4.1.1.10", "i"),
	"duplex_get": 						("1.3.6.1.4.1.522.3.15.5", "i"),
	"mab_enable": 						("1.3.6.1.4.1.9.9.654.1.1.1.1.1", "i"),
	"mab_type": 						("1.3.6.1.4.1.9.9.654.1.1.1.1.2", "i"),
	"portsecurity_enable": 				("1.3.6.1.4.1.9.9.315.1.2.1.1.1", "i"),
	"portsecurity_maximum_macaddr": 	("1.3.6.1.4.1.9.9.315.1.2.1.1.3", "i"),
	"portsecurity_status": 				("1.3.6.1.4.1.9.9.315.1.2.1.1.2", "i"),
	"portsecurity_violation_action": 	("1.3.6.1.4.1.9.9.315.1.2.1.1.8", "i"),
	"port_description": 				("ifAlias", "s"),
	"port_enable": 						("ifAdminStatus", "i"),
	"port_mtu": 						("ifMtu", "i"),
	"speed": 							("1.3.6.1.4.1.9.5.1.4.1.1.9", "i"),
	"switchport_mode": 					("1.3.6.1.4.1.9.9.46.1.6.1.1.13", "i"),
	"transfer_copy_error": 				("1.3.6.1.4.1.9.9.96.1.1.1.1.13", "i"),
	"transfer_dest": 					("1.3.6.1.4.1.9.9.96.1.1.1.1.4", "i"),
	"transfer_init": 					("1.3.6.1.4.1.9.9.96.1.1.1.1.2", "i"),
	"transfer_opt_password": 			("1.3.6.1.4.1.9.9.96.1.1.1.1.8", "s"),
	"transfer_opt_path": 				("1.3.6.1.4.1.9.9.96.1.1.1.1.6", "s"),
	"transfer_opt_server": 				("1.3.6.1.4.1.9.9.96.1.1.1.1.5", "a"),
	"transfer_opt_user": 				("1.3.6.1.4.1.9.9.96.1.1.1.1.7", "s"),
	"transfer_source": 					("1.3.6.1.4.1.9.9.96.1.1.1.1.3", "i"),
	"transfer_start":					("1.3.6.1.4.1.9.9.96.1.1.1.1.14", "i"),
	"transfer_state": 					("1.3.6.1.4.1.9.9.96.1.1.1.1.10", "i"),
	"trunk_native_vlan": 				("1.3.6.1.4.1.9.9.46.1.6.1.1.5", "i"),
	"trunk_allowed_vlan_1": 			("1.3.6.1.4.1.9.9.46.1.6.1.1.4", "x"),
	"trunk_allowed_vlan_2": 			("1.3.6.1.4.1.9.9.46.1.6.1.1.17", "x"),
	"trunk_allowed_vlan_3": 			("1.3.6.1.4.1.9.9.46.1.6.1.1.18", "x"),
	"trunk_allowed_vlan_4": 			("1.3.6.1.4.1.9.9.46.1.6.1.1.19", "x"),
	"trunk_encapsulation": 				("1.3.6.1.4.1.9.9.46.1.6.1.1.3", "i"),
	"voice_vlan": 						("1.3.6.1.4.1.9.9.68.1.5.1.1.1", "i"),
	
}
	
class CiscoSwitch(GenericSwitch):
	def __init__(self):
		GenericSwitch.__init__(self)
		
		self.vendor = "cisco"
		self.mibs = Mibs

