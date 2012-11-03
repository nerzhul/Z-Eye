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
    
	class lIcinga extends zLocales {
		function lIcinga() {
			$this->locales = array(
				"fr" => array(
					"active-check-en" => "Vérifications actives",
					"Add" => "Ajouter",
					"Alias" => "Alias",
					"alivecommand" => "Commande de vérification (présence)",
					"check-freshness" => "Vérifier la fraîcheur",
					"check-interval" => "Vérification (min)",
					"Command" => "Commande",
					"Commands" => "Commandes",
					"Contactgroups" => "Groupes de contacts",
					"Contacts" => "Contacts",
					"Email" => "E-Mail",
					"err-bad-data" => "Données manquantes ou invalides",
					"err-data-exist" => "La donnée existe déjà",
					"err-data-not-exist" => "La donnée n'existe pas",
					"eventhdl-en" => "Gestionnaire d'évènements",
					"failpredict-en" => "Prévision de panne",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux ou la page indisponible",
					"flap-en" => "Détection d'instabilité",
					"Friday" => "Vendredi",
					"From" => "De",
					"General" => "Général",
					"hostnotifcmd" => "Commande de notification (hôtes)",
					"Hosts" => "Hôtes",
					"Hostgroups" => "Groupes d'hôtes",
					"is-template" => "Template ?",
					"max-check" => "Vérifications max (avant notification)",
					"Monday" => "Lundi",
					"Name" => "Nom",
					"new-cmd" => "Nouvelle commande",
					"new-contact" => "Nouveau contact",
					"new-host" => "Nouvel hôte",
					"new-service" => "Nouveau service",
					"new-timeperiod" => "Nouvelle période temporelle",
					"notif-en" => "Notifications",
					"notif-interval" => "Intervalle de notification",
					"obs-over-srv" => "Service nécessaire",
					"Option" => "Option",
					"parallel-check" => "Vérifications en parallèle",
					"passive-check-en" => "Vérifications passives",
					"perfdata" => "Vérification des performances",
					"Periods" => "Périodes",
					"retainstatus" => "Garder les infos de status (reboot Z-Eye)",
					"retainnonstatus" => "Garder les infos hors status (reboot Z-Eye)",
					"retry-check-interval" => "Revérification (min)",
					"Saturday" => "Samedi",
					"Services" => "Services",
					"srvnotifcmd" => "Commande de notification (services)",
					"Sunday" => "Sunday",
					"Thursday" => "Jeudi",
					"Timeperiods" => "Périodes temporelles",
					"To" => "à",
					"Tuesday" => "Mardi",
					"Value" => "Valeur",
					"Wednesday" => "Mercreci",
				),
				"en" => array(
					"active-check-en" => "Active checks",
					"Add" => "Add",
					"Alias" => "Alias",
					"alivecommand" => "Alive presence command",
					"check-freshness" => "Check freshness",
					"check-interval" => "Check interval (min)",
					"Command" => "Command",
					"Commands" => "Commands",
					"Contactgroups" => "Contact Groups",
					"Contacts" => "Contacts",
					"Email" => "E-Mail",
					"err-bad-data" => "Bad/Missing datas",
					"err-data-exist" => "Data already exists",
					"err-data-not-exist" => "Data doesn't exist",
					"eventhdl-en" => "Event handler",
					"failpredict-en" => "Fail prediction",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"flap-en" => "Flap enable",
					"Friday" => "Friday",
					"From" => "From",
					"General" => "General",
					"hostnotifcmd" => "Notification command (hosts)",
					"Hosts" => "Hôtes",
					"Hostgroups" => "Host Groups",
					"is-template" => "Template ?",
					"max-check" => "Max checks (before notification)",
					"Monday" => "Monday",
					"Name" => "Name",
					"new-cmd" => "New command",
					"new-contact" => "New contact",
					"new-host" => "New host",
					"new-service" => "New service",
					"new-timeperiod" => "New timeperiod",
					"notif-en" => "Notifications",
					"notif-interval" => "Notification interval",
					"obs-over-srv" => "Necessary service",
					"Option" => "Option",
					"parallel-check" => "Parallel checks",
					"passive-check-en" => "Passive checks",
					"perfdata" => "Check performance data",
					"Periods" => "Periods",
					"retainstatus" => "Keep status infos (Z-Eye reboot)",
					"retainnonestatus" => "Keep non-status infos (Z-Eye reboot)",
					"retry-check-interval" => "Retry (min)",
					"Saturday" => "Saturday",
					"Services" => "Services",
					"srvnotifcmd" => "Notification command (services)",
					"Sunday" => "Sunday",
					"Thursday" => "Thursday",
					"Timeperiods" => "Timeperiods",
					"To" => "To",
					"Tuesday" => "Tuesday",
					"Value" => "Value",
					"Wednesday" => "Wednesday",
				)
			);
		}
	};
?>
