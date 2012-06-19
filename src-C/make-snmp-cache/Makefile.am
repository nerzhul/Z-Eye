bin_PROGRAMS = zeye-make-snmp-cache

AM_CPPFLAGS = -I/usr/include/mysql -lpthread -lmysqlclient

zeye_make_snmp_cache_SOURCES = \
	common.h \
	main.c \
	mysqlmgmt.c \
	mysqlmgmt.h \
	stdmgmt.c \
	stdmgmt.h


zeye_make_snmp_cache_LDADD = -lpthread -lmysqlclient

zeye_make_snmp_cache_LDFLAGS = -I/usr/include/mysql
