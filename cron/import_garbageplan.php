<?php
$HOANOHO_DIR = exec('. /etc/environment; echo $HOANOHO_DIR');
require_once $HOANOHO_DIR."/includes/dbconnection.php";
require_once $HOANOHO_DIR."/includes/getConfiguration.php";

if(strlen($__CONFIG['garbageplan_url']) == 0)
    exit;

$planurl = $__CONFIG['garbageplan_url'];
$planurl = str_replace("%YEAR", date('Y',time()), $planurl);

$filetype = "";
$file = "";

if(strpos($planurl, ".ics"))
    $filetype = "ics";
else {
    // check file content to determine filetype
    $file = file_get_contents($planurl);

    if(strstr(substr($file, 0, 20),"BEGIN:VCALENDAR"))
        $filetype = "ics";
}

if ($filetype == "ics") {
    require_once $HOANOHO_DIR."/includes/PhpICS/ICS/index.php";

    if($file == "")
        $file = file_get_contents($planurl);

    date_default_timezone_set('Europe/Paris');

    //$icalc = ICS\ICS::open($file);
    $icalc = ICS\ICS::load($file);

    // truncate database table
    if (strlen($icalc) > 0) {
        mysql_query("TRUNCATE TABLE garbageplan");

        foreach ($icalc as $event) {
            mysql_query("INSERT INTO garbageplan (pickupdate, text) VALUES ('".$event->getDateStart('Y-m-d H:i:s')."','".$event->getSummary()."')");
        }
    }
}
?>
