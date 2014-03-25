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
import pymysql

import logging
import zConfig

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
	
	def __init__ (self):
		self.dbHost = ""
		self.dbPort = 0
		self.dbName = ""
		self.dbLogin = ""
		self.dbConn = None
		self.dbCursor = None
		self.dbType = ""
		self.logger = logging.getLogger("Z-Eye")
		
	def configAndTryConnect (self, dbType, dbHost, dbPort, dbName, dbLogin, dbPwd):
		if dbType != "my" and dbType != "pg":
			return False
	
		try:
			if dbType == "pg":
				self.dbConn = PgSQL.connect(host=dbHost,user=dbLogin,password=dbPwd,database=dbName)
				if self.dbConn == None:
					return False
					
				self.dbCursor = self.dbConn.cursor()
				if self.dbCursor == None:
					self.dbConn.close()
					return False
				
			elif dbType == "my":
				self.dbConn =  pymysql.connect(host=dbHost, port=dbPort, user=dbLogin, passwd=dbPwd, db=dbName)
				if self.dbConn == None:
					return False
				
				self.dbCursor = self.dbConn.cursor()
				if self.dbCursor == None:
					self.dbConn.close()
					return False

			else:
				self.logger.warn("Database '%s' not supported" % dbType)
				return False
		except Exception, e:
			self.logger.error("DBMgr: connection to DB %s:%s/%s failed: %s" % (dbHost,dbPort,dbName,e))
			return False
			
		self.dbHost = dbHost
		self.dbPort = dbPort
		self.dbName = dbName
		self.dbLogin = dbLogin
		self.dbType = dbType
		return True

	def initForZEye (self):
		return self.configAndTryConnect("pg",zConfig.pgHost,0,zConfig.pgDB,zConfig.pgUser,zConfig.pgPwd)
		
	def Select (self, tableName, fields, suffix = ""):
		if self.dbType == "pg" or self.dbType == "my":
			if suffix == "":
				self.dbCursor.execute("SELECT %s FROM %s" % (fields,tableName))
			else:
				self.dbCursor.execute("SELECT %s FROM %s WHERE %s" % (fields,tableName,suffix))
			return self.dbCursor.fetchall()
		
		return None
	
	def GetOneField (self, tableName, fields, suffix):
		if self.dbType == "pg" or self.dbType == "my":
			if suffix == "":
				self.dbCursor.execute("SELECT %s FROM %s LIMIT 1" % (fields,tableName))
			else:
				self.dbCursor.execute("SELECT %s FROM %s WHERE %s LIMIT 1" % (fields,tableName,suffix))
			return self.dbCursor.fetchone()
		
		return None
	
	def GetOneData (self, tableName, field, suffix = ""):
		if self.dbType == "pg" or self.dbType == "my":
			if suffix == "":
				self.dbCursor.execute("SELECT %s FROM %s LIMIT 1" % (field,tableName))
			else:
				self.dbCursor.execute("SELECT %s FROM %s WHERE %s LIMIT 1" % (field,tableName,suffix))
			res = self.dbCursor.fetchone()
			if res != None:
				return res[0]
		
		return None
	
	def GetMax (self, tableName, field, suffix = ""):
		if self.dbType == "pg" or self.dbType == "my":
			return self.GetOneData(tableName,"MAX(%s)" % field,suffix)
		
		return None
		
	def Insert (self, tableName, fields, values):
		if self.dbType == "pg" or self.dbType == "my":
			self.dbCursor.execute("INSERT INTO %s(%s) VALUES (%s)" % (tableName,fields,values))
			
	def Delete (self, tableName, suffix = ""):
		if self.dbType == "pg" or self.dbType == "my":
			if len(suffix) > 0:
				self.dbCursor.execute("DELETE FROM %s WHERE %s" % (tableName,suffix))
			else:
				self.dbCursor.execute("DELETE FROM %s" % tableName)
	
	def Commit (self):
		if self.dbType == "pg" or self.dbType == "my":
			self.dbConn.commit()

	def getRowCount (self):
		if self.dbType == "pg" or self.dbType == "my":
			return self.dbCursor.rowcount
		return None
	
	def close (self):
		if self.dbType == "pg" or self.dbType == "my":
			if self.dbCursor != None:
				self.dbCursor.close()
				
			if self.dbConn != None:
				self.dbConn.close()
	
