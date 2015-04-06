<?php
    function pushMessage($applicationToken, $userToken, $title, $message, $priority)
    {
        curl_setopt_array($ch = curl_init(), array(
          CURLOPT_URL => "https://api.pushover.net/1/messages.json",
          CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0",
          CURLOPT_POSTFIELDS => array(
          "token" => $applicationToken,
          "user" => $userToken,
          "title" => $title,
          "message" => $message,
          "priority" => $priority,
        )));
        curl_exec($ch);
        curl_close($ch);
    }
?>
