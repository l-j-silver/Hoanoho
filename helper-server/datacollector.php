<?php
require_once dirname(__FILE__)."/../includes/dbconnection.inc.php";

if (isset($_POST['json'])) {
    $json_decoded = json_decode($_POST['json']);

    for ($i=0; $i < count($json_decoded->Values); $i++) {
        // insert only if value doesnt already exist
        $result = mysql_query("SELECT ddid from device_data where timestamp_unix = '".$json_decoded->Timestamp."' and valuename = '".str_replace(".", "_", $json_decoded->Values[$i]->Name)."' and deviceident = '".$json_decoded->Name."'");
        if(mysql_num_rows($result) == 0)
            mysql_query("INSERT INTO device_data (deviceident, timestamp_unix, timestamp, valuename, value, valueunit, year, month, day) VALUES ('".$json_decoded->Name."', ".$json_decoded->Timestamp.", '".date("Y-m-d H:i:s", $json_decoded->Timestamp)."', '".str_replace(".", "_", $json_decoded->Values[$i]->Name)."', '".$json_decoded->Values[$i]->Value."', '".$json_decoded->Values[$i]->Unit."', ".date('Y', $json_decoded->Timestamp).", ".date('m', $json_decoded->Timestamp).", ".date('d', $json_decoded->Timestamp).")");
    }
}
?>
