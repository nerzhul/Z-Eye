<?php
	/*
	* Copyright (c) 2010-2014, Loïc BLOT, CNRS <http://www.unix-experience.fr>
	* All rights reserved.
	*
	* Redistribution and use in source and binary forms, with or without
	* modification, are permitted provided that the following conditions are met:
	*
	* 1. Redistributions of source code must retain the above copyright notice, this
	*    list of conditions and the following disclaimer.
	* 2. Redistributions in binary form must reproduce the above copyright notice,
	*    this list of conditions and the following disclaimer in the documentation
	*    and/or other materials provided with the distribution.
	*
	* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	* ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	* DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	* ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*
	* The views and conclusions contained in the software and documentation are those
	* of the authors and should not be interpreted as representing official policies,
	* either expressed or implied, of the FreeBSD Project.
    */
    
    class FSTimeMgr {
		public static function timeSince($time) {
			$dt1 = new DateTime("now");
			$dt2 = new DateTime(date("Y-m-d H:i:s",$time));
			$interval = $dt1->diff($dt2);
			
			return sprintf("%s%s%s%s",
				$interval->d > 0 ? $interval->d."d " : "",
				$interval->h > 0 ? $interval->h."h " : "",
				$interval->i > 0 ? $interval->i."m " : "",
				$interval->s > 0 ? $interval->s."s" : ""
			);
		}
		
		/*
		 * It's a value in second and this returns value in a string format
		 */
		public static function genStr($value) {
			$days = floor($value/(60*60*24));
			$value = $value % (60*60*24);
			
			$hours = floor($value/(60*60));
			$value = $value % (60*60);
			
			$minutes = floor($value/60);
			
			$seconds = $value % 60;
			
			$dayStr = "";
			if ($days > 0) {
				if ($days == 1) {
					$dayStr = sprintf("%s %s", $days, _("day"));
				}
				else {
					$dayStr = sprintf("%s %s", $days, _("days"));
				}
			}
			
			$hourStr = "";
			if ($hours > 0) {
				if ($hours == 1) {
					$hourStr = sprintf("%s %s", $hours, _("hour"));
				}
				else {
					$hourStr = sprintf("%s %s", $hours, _("hours"));
				}
			}
			
			$minStr = "";
			if ($minutes > 0) {
				if ($minutes == 1) {
					$minStr = sprintf("%s %s", $minutes, _("minute"));
				}
				else {
					$minStr = sprintf("%s %s", $minutes, _("minutes"));
				}
			}
			
			$secStr = "";
			if ($seconds > 0) {
				if ($seconds == 1) {
					$secStr = sprintf("%s %s", $seconds, _("second"));
				}
				else {
					$secStr = sprintf("%s %s", $seconds, _("seconds"));
				}
			}
			
			return sprintf("%s%s%s%s%s%s%s",
				$dayStr,
				($dayStr && ($hourStr || $minStr || $secStr) ? " " : ""),
				$hourStr,
				($hourStr && ($minStr || $secStr) ? " " : ""),
				$minStr,
				($minStr && $secStr ? " " : ""),
				$secStr
			);
		}
	}
