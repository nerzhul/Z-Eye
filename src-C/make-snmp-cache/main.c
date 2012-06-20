/*
	* Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
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
#include "mysqlmgmt.h"
#include "stdmgmt.h"
#include <time.h>

int main(int argc, const char** argv)
{
	printSystem("[Z-Eye] make_snmp_cache with %s",mysql_get_client_info());
	
	time_t starttime = time(0);
    struct tm now = *localtime(&starttime);

	printSystem("[Z-Eye] make_snmp_cache started at %s",asctime(&now));

	MYSQL *conn = mysql_init(NULL);
	if(conn == NULL)
	{
		printError("[Z-Eye][FATAL] make_snmp_cache can't init MySQL !");
		return 1;
	}
	
	mysql_host = "localhost";
	mysql_dbname = "fssmanager";
	mysql_user = "root";
	mysql_password = "root";
	
	if(mysql_real_connect(conn, mysql_host, mysql_user, mysql_password, mysql_dbname, 0, NULL, 0) == NULL)
	{
		printError("[Z-Eye][ERROR] Unable to connect to MySQL server (host %s, database %s, user %s, password %s)",
			mysql_host,mysql_dbname,mysql_user,mysql_password);
		return 2;
	}
	
	time_t endtime = time(0);
    now = *localtime(&endtime);
    time_t interval = endtime - starttime;
	printSystem("[Z-Eye] make_snmp_cache ended at %s (Exec time %ds)",asctime(&now),interval);
	return 0;
}
