#!/usr/bin/env python
import os,re
os.environ.setdefault("DJANGO_SETTINGS_MODULE", "Z_Eye.settings")

from django.core.management import execute_from_command_line
args = "manage.py runserver 0.0.0.0:8080"
execute_from_command_line(re.split(" ",args))

