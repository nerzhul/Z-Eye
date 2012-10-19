<?php
	/*
	* Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lSearch extends zLocales {
		function lSearch() {
			$this->locales = array(
				"fr" => array(
					"Accouting" => "Accouting",
					"Alias" => "Alias",
					"and-the" => "et le",
					"attribution-type" => "Type d'attribution",
					"Between" => "Entre le",
					"Bytes" => "Octets",
					"Description" => "Description",
					"Device" => "Equipement",
					"dynamic" => "Dynamique",
					"dhcp-hostname" => "Nom d'hôte DHCP",
					"Download" => "Download",
					"end-session" => "Fin de session",
					"end-session-cause" => "Cause de fin de session",
					"err-no-res" => "Aucun résultat trouvé",
					"err-no-search" => "Pas de données à rechercher",
					"First-view" => "Première vue",
					"Informations" => "Informations",
					"ipv4-addr" => "Adresse IPv4",
					"ipv6-addr" => "Adresse IPv6",
					"Last-view" => "Dernière vue",
					"link-ip" => "Adresse IP liée",
					"link-mac-addr" => "Adresse MAC liée",
					"Model" => "Modèle",
					"Name" => "Nom",
					"netbios-machine" => "Machine Netbios",
					"netbios-user" => "Utilisateur Netbios",
					"Network-device" => "Equipement Réseau",
					"Node" => "Noeud",
					"Other" => "Autre",
					"Plug" => "Prise",
					"Ref-plug" => "Prise(s) référencée(s)",
					"Search" => "Recherche",
					"Since" => "A partir du",
					"start-session" => "Début de session",
					"Static" => "Statique",
					"title-8021x-bw" => "Bande passante 802.1X",
					"title-8021x-users" => "Utilisateurs 802.1X",
					"title-dhcp-distrib" => "Distribution DHCP",
					"title-dns-assoc" => "Noms DNS associés",
					"title-dns-records" => "Enregistrements DNS",
					"title-ip-addr" => "Adresses IP",
					"title-last-device" => "Dernier équipement",
					"title-mac-addr" => "Adresses MAC",
					"title-netbios" => "Domaine/Groupe de Travail Netbios",
					"title-netbios-name" => "Noms Netbios",
					"title-network-places" => "Emplacements réseau",
					"title-res-nb" => "Nombre de résultats",
					"title-vlan-device" => "VLAN présent dans ces équipements",
					"Total" => "Total",
					"Upload" => "Upload",
					"User" => "Utilisateur",
					"Validity" => "Validité",
				),
				"en" => array(
					"Accouting" => "Accouting",
					"Alias" => "Alias",
					"and-the" => "and",
					"attribution-type" => "Attribution type",
					"Between" => "Between",
					"Bytes" => "Bytes",
					"Description" => "Description",
					"Device" => "Device",
					"dynamic" => "Dynamic",
					"dhcp-hostname" => "DHCP hostname",
					"Download" => "Download",
					"end-session" => "Session end",
					"end-session-cause" => "Why session ends",
					"err-no-res" => "No result found",
					"err-no-search" => "No data to find",
					"First-view" => "First seend",
					"Informations" => "Informations",
					"ipv4-addr" => "IPv4 address",
					"ipv6-addr" => "IPv6 address",
					"Last-view" => "Last seen",
					"link-ip" => "Linked IP address",
					"link-mac-addr" => "Linked MAC address",
					"Model" => "Model",
					"Name" => "Name",
					"netbios-machine" => "Netbios machine",
					"netbios-user" => "Netbios user",
					"Network-device" => "Network device",
					"Node" => "Node",
					"Other" => "Other",
					"Plug" => "Plug",
					"Ref-plug" => "Referenced plug(s)",
					"Search" => "Search",
					"Since" => "Since",
					"start-session" => "Session start",
					"Static" => "Static",
					"title-8021x-bw" => "802.1X bandwidth",
					"title-8021x-users" => "802.1X users",
					"title-dhcp-distrib" => "DHCP distribution",
					"title-dns-assoc" => "Linked DNS names",
					"title-dns-records" => "DNS records",
					"title-ip-addr" => "IP addresses",
					"title-last-device" => "Last device",
					"title-mac-addr" => "MAC addresses",
					"title-netbios" => "Netbios Domain/Workgroup",
					"title-netbios-name" => "Netbios names",
					"title-network-places" => "Network places",
					"title-res-nb" => "Number of results",
					"title-vlan-device" => "VLAN in this devices",
					"Total" => "Total",
					"Upload" => "Upload",
					"User" => "User",
					"Validity" => "Validity",
				)
			);
		}
	};
?>
