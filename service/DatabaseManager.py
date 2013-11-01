# -*- Coding: utf-8 -*-
"""
* Copyright (C) 2010-2012 Loic BLOT, CNRS <http://www.unix-experience.fr/>
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
"""

from pyPgSQL import PgSQL

import logging
import netdiscoCfg

class ZEyeSQLMgr:
	dbHost = ""
	dbPort = 0
	dbName = ""
	dbLogin = ""
	dbPwd = ""
	dbConn = None
	dbCursor = None
	dbType = ""
	logger = None
	
	def __init__(self):
		self.dbHost = ""
		self.dbPort = 0
		self.dbName = ""
		self.dbLogin = ""
		self.dbConn = None
		self.dbCursor = None
		self.dbType = ""
		self.logger = logging.getLogger("Z-Eye")
		
	def configAndTryConnect(self,dbType,dbHost,dbPort,dbName,dbLogin,dbPwd):
		if dbType != "my" and dbType != "pg":
			return False
	
		if dbType == "pg":
			self.dbConn = PgSQL.connect(host=dbHost,user=dbLogin,password=dbPwd,database=dbName)
			if self.dbConn == None:
				return False
				
			self.dbCursor = self.dbConn.cursor()
			if self.dbCursor == None:
				self.dbConn.close()
				return False
			
		elif dbType == "my":
			self.logger.info("Database MySQL not supported")
			return False
			
		self.dbHost = dbHost
		self.dbPort = dbPort
		self.dbName = dbName
		self.dbLogin = dbLogin
		self.dbType = dbType
		return True

	def initForZEye(self):
		return self.configAndTryConnect("pg",netdiscoCfg.pgHost,0,netdiscoCfg.pgDB,netdiscoCfg.pgUser,netdiscoCfg.pgPwd)
		
	def Select(self,tableName,fields,suffix=""):
		if self.dbType == "pg":
			if suffix == "":
				self.dbCursor.execute("SELECT %s FROM %s" % (fields,tableName))
			else:
				self.dbCursor.execute("SELECT %s FROM %s WHERE %s" % (fields,tableName,suffix))
			return self.dbCursor.fetchall()
		
		return None
	
	def GetOneField(self,tableName,fields,suffix):
		if self.dbType == "pg":
			self.dbCursor.execute("SELECT %s FROM %s %s LIMIT 1" % (fields,tableName,suffix))
			return self.dbCursor.fetchone()
		
		return None
	
	def GetOneData(self,tableName,field,suffix):
		if self.dbType == "pg":
			self.dbCursor.execute("SELECT %s FROM %s %s LIMIT 1" % (field,tableName,suffix))
			pgres = self.dbCursor.fetchone()
			if pgres != None:
				return pgres[0]
		
		return None
	
	def getRowCount(self):
		if self.dbType == "pg":
			return self.dbCursor.rowcount
		return None
	
	def close(self):
		if self.dbType == "pg" and self.dbConn != None:
			self.dbConn.close()
	
