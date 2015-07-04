<?php
require_once dirname(__FILE__).'/../includes/sessionhandler.php';

if ( isset($_POST['usertoken']) && $_POST['usertoken'] != "" && isset($_POST['apptoken']) && $_POST['apptoken'] != "" ) {
    curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0",
        CURLOPT_POSTFIELDS => array(
        "token" => $_POST['apptoken'],
        "user" => $_POST['usertoken'],
        "message" => "Dies ist eine Testnachricht - Pushover wurde erfolgreich eingerichtet!",
    )));
    curl_exec($ch);
    curl_close($ch);
}

?>
