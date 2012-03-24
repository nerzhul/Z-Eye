<?php

# This script is a modified version of rrd.php of Cacti project (http://www.cacti.net)

define("RRD_NL", " \\\n");
define("MAX_FETCH_CACHE_SIZE", 5);

function escape_command($command) {
	return ereg_replace("(\\\$|`)", "", $command);
}

function rrd_get_fd(&$rrd_struc, $fd_type) {
	if (sizeof($rrd_struc) == 0) {
		return 0;
	} else {
		return $rrd_struc["fd"];
	}
}

function rrdtool_last($filename) {
    $cmd_line="last $filename";
    return rrdtool_execute($cmd_line, false, RRDTOOL_OUTPUT_STDOUT);
}

function rrdtool_execute($command_line, $log_to_stdout, $output_flag, $rrd_struc = array(), $logopt = "WEBLOG") {
	global $config,$rrdtool;

	if (!is_numeric($output_flag)) {
		$output_flag = RRDTOOL_OUTPUT_STDOUT;
	}

	/* WIN32: before sending this command off to rrdtool, get rid
	of all of the '\' characters. Unix does not care; win32 does.
	Also make sure to replace all of the fancy \'s at the end of the line,
	but make sure not to get rid of the "\n"'s that are supposed to be
	in there (text format) */
	$command_line = str_replace("\\\n", " ", $command_line);

	/* output information to the log file if appropriate 
	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
		cacti_log("CACTI2RRD: " . read_config_option("path_rrdtool") . " $command_line", $log_to_stdout, $logopt);
	} 
	*/

	/* if we want to see the error output from rrdtool; make sure to specify this */
	if (($output_flag == RRDTOOL_OUTPUT_STDERR) && (sizeof($rrd_struc) == 0)) {
		$command_line .= " 2>&1";
	}

	/* use popen to eliminate the zombie issue */
		/* an empty $rrd_struc array means no fp is available */
		if (sizeof($rrd_struc) == 0) {
			$fp = popen("$rrdtool". escape_command(" $command_line"), "r");
		}else{
			fwrite(rrd_get_fd($rrd_struc, RRDTOOL_PIPE_CHILD_READ), escape_command(" $command_line") . "\r\n");
			fflush(rrd_get_fd($rrd_struc, RRDTOOL_PIPE_CHILD_READ));
		}

	switch ($output_flag) {
		case RRDTOOL_OUTPUT_NULL:
			return; break;
		case RRDTOOL_OUTPUT_STDOUT:
			if (isset($fp)) {
				$line = "";
				while (!feof($fp)) {
					$line .= fgets($fp, 4096);
				}

				return $line;
			}

			break;
		case RRDTOOL_OUTPUT_STDERR:
			if (isset($fp)) {
				$output = fgets($fp, 1000000);

				if (substr($output, 1, 3) == "PNG") {
					return "OK";
				}

				if (substr($output, 0, 5) == "GIF87") {
					return "OK";
				}

				print $output;
			}

			break;
		case RRDTOOL_OUTPUT_GRAPH_DATA:
			if (isset($fp)) {
				return fpassthru($fp);
			}

			break;
	}
}

function &rrdtool_function_fetch($local_data_id, $start_time, $end_time, $resolution = 0) {

	if (empty($local_data_id)) {
		return;
	}

	$regexps = array();
	$fetch_array = array();

	#$data_source_path = get_data_source_path($local_data_id, true);
	$data_source_path = $local_data_id;

	/* build and run the rrdtool fetch command with all of our data */
	$cmd_line = "fetch $data_source_path AVERAGE -s $start_time -e $end_time";
	//echo $cmd_line;
	//exit;
	if ($resolution > 0) {
		$cmd_line .= " -r $resolution";
	}
	$output = rrdtool_execute($cmd_line, false, RRDTOOL_OUTPUT_STDOUT);

	/* grab the first line of the output which contains a list of data sources
	in this .rrd file */
	$line_one = substr($output, 0, strpos($output, "\n"));

	/* loop through each data source in this .rrd file ... */
	if (preg_match_all("/\S+/", $line_one, $data_source_names)) {
		/* version 1.0.49 changed the output slightly */

		if (preg_match("/^timestamp/", $line_one)) {
			array_shift($data_source_names[0]);
		}


		$fetch_array["data_source_names"] = $data_source_names[0];

		/* build a unique regexp to match each data source individually when
		passed to preg_match_all() */
		for ($i=0;$i<count($fetch_array["data_source_names"]);$i++) {
			$regexps[$i] = '/([0-9]+):\s+';

			for ($j=0;$j<count($fetch_array["data_source_names"]);$j++) {
				/* it seems that at least some versions of the Windows RRDTool binary pads
				the exponent to 3 digits, rather than 2 on every Unix version that I have
				ever seen */
				if ($j == $i) {
					$regexps[$i] .= '([\-]?[0-9]{1}[\.,][0-9]+)e([\+-][0-9]{2,3})';
				}else{
					$regexps[$i] .= '[\-]?[0-9]{1}[\.,][0-9]+e[\+-][0-9]{2,3}';
				}

				if ($j < count($fetch_array["data_source_names"])) {
					$regexps[$i] .= '\s+';
				}
			}

			$regexps[$i] .= '/';
		}
	}


	$fetch_array["timestamps"] = array();

	/* loop through each regexp determined above (or each data source) */
	for ($i=0;$i<count($regexps);$i++) {
		$fetch_array["values"][$i] = array();

		/* match the regexp against the rrdtool fetch output to get a mantisa and
		exponent for each line */
		if (preg_match_all($regexps[$i], $output, $matches)) {
			for ($j=0; ($j < count($matches[2])); $j++) {
				$line = (str_replace(",",".",$matches[2][$j]) * (pow(10,(float)$matches[3][$j])));
				//echo $j." - ".$matches[1][$j]."\n";
				array_push($fetch_array["values"][$i], ($line * 1));
				if ($i == 0) 
					array_push($fetch_array["timestamps"], $matches[1][$j]);

			}
		}
	}

	return $fetch_array;
}

function rrdtool_getversion() {
	global $rrdtool;
	$handle = popen($rrdtool.' | grep "tobi@oetiker.ch" | cut -d" " -f2 2>&1', 'r');
	$read = fread($handle, 2096);
	list($major, $majorsub, $minorsub)= explode(".", $read);
	pclose($handle);
	return ($major.".".$majorsub);
}

function get_rra_name($rrdfile,$rrapos) {

	if (empty($rrdfile)) {
		return;
	}

	$fetch_array = array();

	$data_source_path = $rrdfile;

	/* build and run the rrdtool fetch command with all of our data */
	$cmd_line = "fetch $data_source_path AVERAGE --start=now";
#	if ($resolution > 0) {
#		$cmd_line .= " -r $resolution";
#	}
	$output = rrdtool_execute($cmd_line, false, RRDTOOL_OUTPUT_STDOUT);

	/* grab the first line of the output which contains a list of data sources
	in this .rrd file */
	$line_one = substr($output, 0, strpos($output, "\n"));
	#echo "line_one=*$line_one*<br>";

	/* loop through each data source in this .rrd file ... */
	#if (preg_match_all("/\S+/", $line_one, $data_source_names)) {
	preg_match_all("/\S+/", $line_one, $data_source_names);

	return $data_source_names[0][$rrapos-1];

	#echo "datasourcenames=".$data_source_names[0][0]."<br>";
	
	#echo LIST_CONTENTS($data_source_names);	
}

function rrdtool_graph($rrdfilein,$rranamein,$rrdfileout,$rranameout,$nodea,$nodeb,$period,$coefin,$coefout) {
	global $rrdtool;
	$timenow = time();
	$datenow = date("d/m/Y ").date("H")."\:".date("i")."\:".date("s");

	if (! $period) { $period="6hours"; }
	$cmd_line="graph - ".
		'--imgformat=PNG  ' .
		'--start=now-'.$period . 
		' --end=now ' .
		'--title="'.$nodea.' - '.$nodeb.'"  ' .
		'--rigid  ' .
		'--base=1024  ' .
		'--height=120  ' .
		'--width=500  ' .
		'--alt-autoscale-max  ' .
		'--lower-limit=0  ' .
		'COMMENT:"  \n"  ' .
		'--vertical-label="Bits per second"  ' .
		'DEF:a="'.$rrdfilein .
		'":'.$rranamein.':AVERAGE  ' .
		'DEF:b="'.$rrdfileout.
		'":'.$rranameout.':AVERAGE  ' .
		'CDEF:cdefa=a,'.$coefin.',*  ' .
		'CDEF:cdefa2=cdefa,8,*  ' .
		'CDEF:cdefb=b,'.$coefout.',*  ' .
		'CDEF:cdefb2=cdefb,8,*  ' .
		'AREA:cdefa2#00CF00:"'.$nodeb.'-->'.$nodea.'"   ' .
		'GPRINT:cdefa2:LAST:" Cur\:%8.2lf %s"   ' .
		'GPRINT:cdefa2:AVERAGE:"Aver\:%8.2lf %s"   ' .
		'GPRINT:cdefa2:MAX:"Max\:%8.2lf %s\n"   ' .
		'LINE1:cdefb2#002A97:"'.$nodea.'-->'.$nodeb.'"   ' .
		'GPRINT:cdefb2:LAST:"Cur\:%8.2lf %s"   ' .
		'GPRINT:cdefb2:AVERAGE:"Aver\:%8.2lf %s"   ' .
		'GPRINT:cdefb2:MAX:"Max\:%8.2lf %s\n" ' .
		'COMMENT:" \n"  ' .
		'COMMENT:"\t\t\t\t\t\t\t\tGraph generated \:'.$datenow.' \n"  ' ;


		header("Content-type: image/png");
		$fp = popen("$rrdtool $cmd_line","r");
		print fpassthru($fp);
		pclose($fp);
}
function rrdtool_get_last_value($rrdfile,$pos,$rrdstep) {

	$start=rrdtool_last($rrdfile);
	$unixtime=$start;
	if (rrdtool_getversion() >= 1.2) { 
		$end=$start;
		$start=$start-$rrdstep; 
	} else {
		$end=$start;
	}
	$result=rrdtool_function_fetch($rrdfile,$start,$end);

	# Get values from array read before and convert values in bytes
	return $result["values"][$pos-1][0];

}
?>
