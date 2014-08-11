#!/usr/bin/env python
import os, sys, logging

if __name__ == "__main__":
	os.environ.setdefault("DJANGO_SETTINGS_MODULE", "Z_Eye.settings")

	sys.path.append("%s/%s" % (os.path.dirname(os.path.abspath(__file__)),"Z_Eye"))
    
	from django.core.management import execute_from_command_line

	# Init logger
	logger = logging.getLogger("Z-Eye")
	handler = logging.FileHandler("/var/log/z-eye.log")
	formatter = logging.Formatter('%(asctime)s [%(levelname)s] - %(message)s')
	handler.setFormatter(formatter)
	logger.addHandler(handler)
	logger.setLevel(logging.INFO)
	
	# Now execute Django
	execute_from_command_line(sys.argv)
