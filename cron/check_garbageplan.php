<?php
$HOANOHO_DIR = exec('. /etc/environment; echo $HOANOHO_DIR');
require_once $HOANOHO_DIR."/includes/dbconnection.php";
require_once $HOANOHO_DIR."/includes/pushover.php";

function pushMessageToUsers($title, $message, $priority)
{
    $sql ="select * from users join usersettings on usersettings.uid = users.uid where pushover_usertoken is not null and pushover_apptoken is not null";
    $result = mysql_query($sql);

    while ($row = mysql_fetch_array($result)) {
        pushMessage($row['pushover_apptoken'], $row['pushover_usertoken'], $title, $message, $priority);
    }
}

$result = mysql_query("select pickupdate,text from garbageplan where date(NOW()) = pickupdate -INTERVAL 1 DAY");
while ($pickup = mysql_fetch_object($result)) {
    $message = $pickup->text;
    $message = explode(":",$pickup->text)[0];

    pushMessageToUsers("Erinnerung: Abfall bereitstellen", $message, 0);
}
?>
