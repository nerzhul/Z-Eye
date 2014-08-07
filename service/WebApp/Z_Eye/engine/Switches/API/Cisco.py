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
	"access_vlan": "1.3.6.1.4.1.9.9.68.1.2.2.1.2",
	"auth_dead_vlan_zero": "1.3.6.1.4.1.9.9.656.1.3.3.1.1",
	"auth_dead_vlan": "1.3.6.1.4.1.9.9.656.1.3.3.1.3",
	"auth_fail_vlan_zero": "1.3.6.1.4.1.9.9.656.1.3.1.1.2",
	"auth_fail_vlan": "1.3.6.1.4.1.9.9.656.1.3.1.1.3",
	"auth_host_mode": "1.3.6.1.4.1.9.9.656.1.2.1.1.3",
	"auth_noresp_vlan_zero": "1.3.6.1.4.1.9.9.656.1.3.2.1.1",
	"auth_noresp_vlan": "1.3.6.1.4.1.9.9.656.1.3.2.1.2",
	"cdp_enable": "1.3.6.1.4.1.9.9.23.1.1.1.1.2",
	"control_mode": "1.3.6.1.4.1.9.9.656.1.2.1.1.5",
	"dhcp_snooping_match_macaddr": "1.3.6.1.4.1.9.9.380.1.1.6.0",
	"dhcp_snooping_option_82": "1.3.6.1.4.1.9.9.380.1.1.4.0",
	"dhcp_snooping_rate": "1.3.6.1.4.1.9.9.380.1.3.2.1.1",
	"dhcp_snooping_status": "1.3.6.1.4.1.9.9.380.1.1.1.0",
	"dhcp_snooping_trust": "1.3.6.1.4.1.9.9.380.1.3.1.1.1",
	"dhcp_snooping_vlans": "1.3.6.1.4.1.9.9.380.1.2.1.1.2",
	"duplex_set": "1.3.6.1.4.1.9.5.1.4.1.1.10",
	"duplex_get": "1.3.6.1.4.1.522.3.15.5",
	"mab_enable": "1.3.6.1.4.1.9.9.654.1.1.1.1.1",
	"mab_type": "1.3.6.1.4.1.9.9.654.1.1.1.1.2",
	"portsecurity_enable": "1.3.6.1.4.1.9.9.315.1.2.1.1.1",
	"portsecurity_maximum_macaddr": "1.3.6.1.4.1.9.9.315.1.2.1.1.3",
	"portsecurity_status": "1.3.6.1.4.1.9.9.315.1.2.1.1.2",
	"portsecurity_violation_action": "1.3.6.1.4.1.9.9.315.1.2.1.1.8",
	"speed": "1.3.6.1.4.1.9.5.1.4.1.1.9",
	"switchport_mode": "1.3.6.1.4.1.9.9.46.1.6.1.1.13",
	"transfer_copy_error": "1.3.6.1.4.1.9.9.96.1.1.1.1.13",
	"transfer_dest": "1.3.6.1.4.1.9.9.96.1.1.1.1.4",
	"transfer_init": "1.3.6.1.4.1.9.9.96.1.1.1.1.2",
	"transfer_opt_password": "1.3.6.1.4.1.9.9.96.1.1.1.1.8",
	"transfer_opt_path": "1.3.6.1.4.1.9.9.96.1.1.1.1.6",
	"transfer_opt_server": "1.3.6.1.4.1.9.9.96.1.1.1.1.5",
	"transfer_opt_user": "1.3.6.1.4.1.9.9.96.1.1.1.1.7",
	"transfer_source": "1.3.6.1.4.1.9.9.96.1.1.1.1.3",
	"transfer_start": "1.3.6.1.4.1.9.9.96.1.1.1.1.14",
	"transfer_state": "1.3.6.1.4.1.9.9.96.1.1.1.1.10",
	"trunk_native_vlan": "1.3.6.1.4.1.9.9.46.1.6.1.1.5",
	"trunk_allowed_vlan_1": "1.3.6.1.4.1.9.9.46.1.6.1.1.4",
	"trunk_allowed_vlan_2": "1.3.6.1.4.1.9.9.46.1.6.1.1.17",
	"trunk_allowed_vlan_2": "1.3.6.1.4.1.9.9.46.1.6.1.1.18",
	"trunk_allowed_vlan_2": "1.3.6.1.4.1.9.9.46.1.6.1.1.19",
	"trunk_encapsulation": "1.3.6.1.4.1.9.9.46.1.6.1.1.3",
	"voice_vlan": "1.3.6.1.4.1.9.9.68.1.5.1.1.1",
	
}
	
	
class CiscoSwitch(GenericSwitch):
	def __init__(self):
		super(GenericSwitch, self).__init__()
		
		self.vendor = "cisco"
