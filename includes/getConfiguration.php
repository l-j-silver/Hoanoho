<?php
    $sql = "select configstring, value from configuration where dev_id = 0 order by configstring asc";
    $result = mysql_query($sql);

    $__CONFIG = array();

    while ($row = mysql_fetch_array($result)) {
        $__CONFIG[$row[0]] = $row[1];
    }

	// Read HSE environment variables from /etc/environment if possible
	$handle = fopen("/etc/environment", "r");
	if ($handle) {
	    while (($line = fgets($handle)) !== false) {
			if (substr( $line, 0, 0 ) == "#")
				next;
			$array = explode("=", $line);
			$__CONFIG[ $array[0] ] = substr($array[1], 1, -2);
	    }
	}
	fclose($handle);
