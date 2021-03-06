<?php
$HOANOHO_DIR = exec('. /etc/environment; echo $HOANOHO_DIR');
require_once $HOANOHO_DIR."/includes/dbconnection.php";
require_once $HOANOHO_DIR."/includes/pushover.php";

/* ************************************
   Query battery state in FHEM database
   ************************************ */

$sql = "select ".$fhem_dbname.".current.DEVICE, ".$fhem_dbname.".current.VALUE, ".$dbname.".rooms.name roomname, ".$dbname.".devices.name devicename, ".$dbname.".device_floors.name floorname from ".$fhem_dbname.".current join ".$dbname.".devices on ".$fhem_dbname.".current.DEVICE like concat(".$dbname.".devices.identifier,'%') join ".$dbname.".rooms on ".$dbname.".rooms.room_id = ".$dbname.".devices.room_id join ".$dbname.".device_floors on ".$dbname.".device_floors.floor_id = ".$dbname.".devices.floor_id where ".$fhem_dbname.".current.READING = 'battery' and ".$fhem_dbname.".current.VALUE != 'ok'";
$result = mysql_query($sql);

$title = "Batterie(n) ersetzen";
$message = "Bei folgenden Aktoren/Sensoren müssen die Batterien ersetzt werden:\n\n";
$dosend = false;
while ($row = mysql_fetch_assoc($result)) {
    $message .= $row['DEVICE']." = ".$row['floorname']." > ".$row['roomname']." > ".$row['devicename'].";\n\n";
    $dosend = true;
}

if($dosend)
    pushMessageToUsers($title, $message, 1);

function pushMessageToUsers($title, $message, $priority)
{
    $sql ="select * from ".$dbname.".users join ".$dbname.".usersettings on ".$dbname.".usersettings.uid = ".$dbname.".users.uid where pushover_usertoken is not null and pushover_apptoken is not null";
    $result = mysql_query($sql);

    while ($row = mysql_fetch_array($result)) {
        pushMessage($row['pushover_apptoken'], $row['pushover_usertoken'], $title, $message, $priority);
    }
}
?>
