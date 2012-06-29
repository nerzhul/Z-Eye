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

#include "stdmgmt.h"
#include <libpq-fe.h>
#include <time.h>
#include <my_global.h>
#include <mysql.h>

uint16_t threadNum;
pthread_mutex_t numMutex = PTHREAD_MUTEX_INITIALIZER;
void addNumThread() {
	pthread_mutex_lock(&numMutex);
	threadNum++;
	pthread_mutex_unlock(&numMutex);
}

void subNumThread() {
	pthread_mutex_lock(&numMutex);
	threadNum--;
	pthread_mutex_unlock(&numMutex);
}

void* execThreadCommand(void* data)
{
	char** addr = (char**)data;
	if(strlen(addr) == 0)
		return;
	addNumThread();
	char command[1024];
	bzero(command,1024);
	sprintf(command,"cfgmaker %s > /var/www/datas/mrtg-config/mrtg-%s.cfg",addr[0],addr[1]);
	printSystem("[Z-Eye] mrtg_config_discover: discovering %s",addr[1]);
	system(command);
	bzero(command,1024);
	sprintf(command,"cat /var/www/datas/mrtg-config/mrtg-%s.cfg | sed 's/\\/var\\/www\\/mrtg/\\/var\\/www\\/datas\\/rrd/' > /var/www/datas/mrtg-config/mrtg-%s.cfg",addr[1],addr[1]);
	system(command);
	subNumThread();
	return (void*)0;
}

int main(int argc, const char** argv)
{
	printSystem("[Z-Eye] mrtg_config_discover with %s",mysql_get_client_info());
	
	time_t starttime = time(0);
	struct tm now = *localtime(&starttime);

	printSystem("[Z-Eye] mrtg_config_discover started at %s",asctime(&now));

	threadNum = 0;
	const char* pg_host = "localhost";
	const char* pg_user = "netdisco";
	const char* pg_pwd = "dbpassword";
	const char* pg_dbname = "netdisco";

	PGresult   *res;
	char conninfo[1024];
	bzero(conninfo,1024);
	sprintf(conninfo,"host=%s user=%s password=%s dbname=%s",pg_host,pg_user,pg_pwd,pg_dbname);
	PGconn* pg_conn = PQconnectdb(conninfo);
	if (PQstatus(pg_conn) != CONNECTION_OK)
	{
		printError("[Z-Eye][FATAL] mrtg_config_discover can't connect to PostGreSQL %s!",PQerrorMessage(pg_conn));
		return 1;
	}

	MYSQL *conn = mysql_init(NULL);
	if(conn == NULL)
	{
		printError("[Z-Eye][FATAL] mrtg_config_discover can't init MySQL !");
		return 1;
	}
	
	const char* mysql_host = "localhost";
	const char* mysql_dbname = "fssmanager";
	const char* mysql_user = "root";
	const char* mysql_password = "root";
	
	if(mysql_real_connect(conn, mysql_host, mysql_user, mysql_password, mysql_dbname, 0, NULL, 0) == NULL)
	{
		printError("[Z-Eye][ERROR] Unable to connect to MySQL server (host %s, database %s, user %s, password %s)",
			mysql_host,mysql_dbname,mysql_user,mysql_password);
		return 2;
	}

	res = PQexec(pg_conn,"SELECT ip,name FROM device");
	int i=0;
	for (; i < PQntuples(res); i++)
    	{
		char mysql_req[1024];
		bzero(mysql_req,1024);
		strcpy(mysql_req,"SELECT snmpro FROM fss_snmp_cache where device = '");
		strcat(mysql_req,PQgetvalue(res, i, 1));
		strcat(mysql_req,"'");
		mysql_query(conn, mysql_req);
	        MYSQL_RES* result = mysql_store_result(conn);
		MYSQL_ROW row;

		if((row = mysql_fetch_row(result)))
		{
			char** datas = malloc(2*sizeof(char*));
			datas[0] = malloc((strlen(row[0])+1+strlen(PQgetvalue(res, i, 0))+1)*sizeof(char));
			sprintf(datas[0],"%s@%s",row[0],PQgetvalue(res, i, 0));
			datas[1] = malloc((strlen(PQgetvalue(res, i, 1))+1)*sizeof(char));
			strcpy(datas[1],PQgetvalue(res, i, 1));
			pthread_t* thread = malloc(sizeof(pthread_t));
			pthread_create(thread, NULL, (void*)execThreadCommand, (void*)datas);
		}
			mysql_free_result(result);
	}

	uint16_t tNum;
	pthread_mutex_lock(&numMutex);
	tNum = threadNum;
	pthread_mutex_unlock(&numMutex);
	while(tNum > 0)
	{
		printSystem("Working Threads %u",tNum);
		pthread_mutex_lock(&numMutex);
	        tNum = threadNum;
        	pthread_mutex_unlock(&numMutex);
		sleep(1);
	}
	mysql_close(conn);
	PQfinish(pg_conn);
	time_t endtime = time(0);
	now = *localtime(&endtime);
	time_t interval = endtime - starttime;
	printSystem("[Z-Eye] mrtg_config_discover ended at %s (Exec time %ds)",asctime(&now),interval);
	return 0;
}
