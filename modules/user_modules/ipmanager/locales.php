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

    require_once(dirname(__FILE__)."/../../../lib/FSS/objects/Locales.FS.class.php");
    
	class lIPManager extends zLocales {
		function lIPManager() {
			$this->locales = array(
				"fr" => array(
					"Available-s" => "Distribuables",
					"bad-datas" => "Requête invalide !",
					"bad-filter" => "Filtre invalide !",
					"Baux" => "Baux",
					"change-interval" => "Changer l'intervalle",
					"choose-net" => "Veuillez choisir le réseau IP à monitorer",
					"Contact" => "Contact",
					"crit-line" => "Seuil critique",
					"days" => "jour(s)",
					"Distributed" => "Disponible",
					"En-monitor" => "Activer le monitoring",
					"err-bad-subnet" => "Le subnet entré est invalide !",
					"err-miss-data" => "Certaines données sont manquantes ou invalides !",
					"err-invalid-req" => "Requête invalide !",
					"Expert-tools" => "Outils avancés",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux ou la page indisponible",
					"Free" => "Non utilisée",
					"Free-s" => "Non utilisées",
					"History" => "Historique",
					"Hostname" => "Nom d'hôte",
					"intval-days" => "Intervalle (jours)",
					"IP-Addr" => "Adresse IP",
					"last-view" => "Dernière vue",
					"MAC-Addr" => "Adresse MAC",
					"max-age" => "Age maximum",
					"modif-record" => "Modifications enregistrées",
					"Monitoring" => "Monitoring",
					"no-net-found" => "Aucun réseau distribué par DHCP découvert",
					"no-old-record" => "Aucune réservation obsolète trouvée",
					"no-tab" => "Cet onglet n'existe pas",
					"Port" => "Port",
					"Reserved" => "Réservée",
					"Reservations" => "Reservations",
					"Save" => "Enregistrer",
					"Search" => "Rechercher",
					"Stats" => "Statistiques",
					"Status" => "Statut",
					"Stuck-IP" => "IP fixe",
					"Switch" => "Equipement",
					"title-history-1d" => "Historique depuis 24 heures",
					"title-history-1w" => "Historique depuis 1 semaine",
					"title-history-1m" => "Historique depuis 1 mois",
					"title-history-1y" => "Historique depuis 1 an",
					"title-history-since" => "Historique depuis",
					"title-ip-supervision" => "Supervision IP",
					"title-old-record" => "Réservations obsolètes trouvées !",
					"title-search-old" => "Recherche de réservations obsolètes",
					"tooltip-contact" => "@ mail recevant les alertes d'obsolescence",
					"tooltip-max-age" => "Délai maximum (en jours) avant d'avertir de l'obsolescence d'une réservation.<br />0 = pas de vérification",
					"Used" => "Utilisée",
					"warn-line" => "Seuil d'avertissement",
					"%use" => "% d'utilisation",
				),
				"en" => array(
					"Available-s" => "Distributable",
					"bad-datas" => "Invalid request",
					"bad-filter" => "Bad filter",
					"Baux" => "Leases",
					"change-interval" => "Change interval",
					"choose-net" => "Please choose an IP network to monitor",
					"Contact" => "Contact",
					"crit-line" => "Critical step",
					"days" => "day(s)",
					"Distributed" => "Available",
					"En-monitor" => "Enable monitoring",
					"err-bad-subnet" => "This subnet is invalid !",
					"err-miss-data" => "Some datas are missing or invalid !",
					"err-invalid-req" => "Invalid request !",
					"Expert-tools" => "Expert tools",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"Free" => "Not used",
					"Free-s" => "Not used",
					"History" => "History",
					"Hostname" => "Hostname",
					"intval-days" => "Interval (days)",
					"IP-Addr" => "IP address",
					"last-view" => "Last view",
					"MAC-Addr" => "MAC address",
					"max-age" => "Max age",
					"modif-record" => "Saved",
					"Monitoring" => "Monitoring",
					"no-net-found" => "No DHCP distributed network found",
					"no-old-record" => "No old record found ",
					"no-tab" => "This tab doesn't exist",
					"Port" => "Port",
					"Reserved" => "Reserved",
					"Reservations" => "Reservations",
					"Save" => "Save",
					"Search" => "Search",
					"Stats" => "Statistics",
					"Status" => "Status",
					"Stuck-IP" => "Fixed IP",
					"Switch" => "Device",
					"title-history-1d" => "History since 24 hours",
                                        "title-history-1w" => "History since 1 week",
                                        "title-history-1m" => "History since 1 month",
                                        "title-history-1y" => "History since 1 year",
					"title-history-since" => "History since",
					"title-ip-supervision" => "IP supervision",
					"title-old-record" => "Old reservations found !",
					"title-search-old" => "Search old reservations",
					"tooltip-contact" => "mail address which receive alerts",
					"tooltip-max-age" => "Max delay (in days) before advertise for old datas.<br />0 = No verification",
					"Used" => "Used",
					"warn-line" => "Warning step",
					"%use" => "% of use",
				)
			);
		}
	};
?>
