<?php

if ( isset($_GET['cmd']) && isset($_GET['pin']) && isset($_GET['value']) && isset($_GET['remote_addr']) && isset($_GET['identifier']) && isset($_GET['protocol']) ) {
    $url = "http://localhost/helper-server/gpio.php?cmd=".$_GET['cmd']."&pin=".$_GET['pin']."&value=".$_GET['value']."&identifier=".$_GET['identifier'];
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0");
    curl_exec($curl);
    curl_close($curl);
}

?>
