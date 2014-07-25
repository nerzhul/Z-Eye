#!/usr/bin/env python
import os
os.environ.setdefault("DJANGO_SETTINGS_MODULE", "Z_Eye.settings")

from django.core.management import execute_from_command_line
execute_from_command_line(["manage.py","runserver","0.0.0.0:8080"])

