<?php
    require_once dirname(__FILE__).'/../includes/sessionhandler.php';

    if(!isset($_GET['device']) && isset($_GET['room']))
        header('Location: ./mobile/room.php?room='.$_GET['room']);
    else if(!isset($_GET['device']) && !isset($_GET['room']))
        header('Location: ./mobile/');

    $sql = "SELECT rooms.name roomname, devices.dev_id, devices.name, devices.identifier, devices.isStructure, devices.isHidden, devices.nd_id, device_types.name typename, types.name basetype FROM devices join device_types on devices.dtype_id = device_types.dtype_id join types on device_types.type_id = types.type_id left outer join rooms on rooms.room_id = devices.room_id where dev_id = " . $_GET['device'];
    $result = mysql_query($sql);
    $device = mysql_fetch_object($result);
?>

<html>
    <head>
        <meta charset="UTF-8" />

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <link rel="stylesheet" href="css/ratchet.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/cookie.js"></script>

        <script language="javascript">
            window.onload = function () {
                connectWebSocket(<?php echo "\"".$__CONFIG['main_socketport']."\""; ?>);
            }

            function encode(input)
            {
                var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
                var output = "";
                var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                var i = 0;

                while (i < input.length) {
                    chr1 = input[i++];
                    chr2 = i < input.length ? input[i++] : Number.NaN; // Not sure if the index
                    chr3 = i < input.length ? input[i++] : Number.NaN; // checks are needed here

                    enc1 = chr1 >> 2;
                    enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                    enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                    enc4 = chr3 & 63;

                    if (isNaN(chr2)) {
                        enc3 = enc4 = 64;
                    } else if (isNaN(chr3)) {
                        enc4 = 64;
                    }
                    output += keyStr.charAt(enc1) + keyStr.charAt(enc2) + keyStr.charAt(enc3) + keyStr.charAt(enc4);
                }

                return output;
            }

            function ajaxRequest()
            {
                var activexmodes=["Msxml2.XMLHTTP", "Microsoft.XMLHTTP"] //activeX versions to check for in IE

                if (window.ActiveXObject) { //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
                    for (var i=0; i<activexmodes.length; i++) {
                        try {
                            return new ActiveXObject(activexmodes[i])
                        } catch (e) {
                            //suppress error
                        }
                    }
                } else if (window.XMLHttpRequest) // if Mozilla, Safari etc

                    return new XMLHttpRequest()
                else
                    return false
            }

            var disableValueRefreshForDeviceID = null;
            var devicesWithLowBattery = [];
            function connectWebSocket(port)
            {
				if (typeof connectWebSocket.connectCnt == 'undefined') {
					connectWebSocket.connectCnt = 0;
				}
				if (typeof connectWebSocket.connectProt == 'undefined') {
					connectWebSocket.connectProt = getCookie("websocketProtocol");
			
					if (connectWebSocket.connectProt == null) {
						if (window.location.protocol == "http:") {
							connectWebSocket.connectProt = "ws";
						} else if(window.location.protocol == "https:") {
							connectWebSocket.connectProt = "wss";
						}
					}
				}
				var host = window.location.hostname;
      	if (port == "80" || port == "443") {
      	  var address = connectWebSocket.connectProt + "://" + host + "/ws";
        } else {
        	var address = connectWebSocket.connectProt + "://" + host +  ":" + port + "/ws";
        }
			
                // Connect to Socketserver
                var socket = new WebSocket(address);
                socket.binaryType = 'arraybuffer';

                socket.onclose = function () {

                    // special handling for Safari to fall back to HTTP/WS
                    // in case self-signed certificate is used
                		if(navigator.userAgent.indexOf('Safari') != -1 &&
                      navigator.userAgent.indexOf('Chrome') == -1 &&
                      event.wasClean == false &&
                      connectWebSocket.connectProt == "wss") {
                			connectWebSocket.connectProt = "ws";
                			connectWebSocket.connectCnt = 0;
                		}

                    //try to reconnect to socketserver in 5 seconds
                    setTimeout(function () {connectWebSocket(port)}, 5000);
                };
				
				socket.onopen = function () {
					// set cookie
					setCookie("websocketProtocol", connectWebSocket.connectProt);
				};

                socket.onmessage = function (message) {
                    var messageObj = JSON.parse(message['data']);

                    // disable refresh when some value is being changed
                    if(disableValueRefreshForDeviceID != null && disableValueRefreshForDeviceID == messageObj['dev_id'])
                        return false;

                    var el_value = document.getElementById("value_" + messageObj['dev_id']);

                    if (el_value != null) {
                        if (messageObj['typename'] == "Ein/Aus-Schalter" || messageObj['typename'] == "Raspberry Pi GPIO") {
                            switch (messageObj['reading']) {
                                case 'state':
                                    if (messageObj['value'] == "on" || messageObj['value'] == "1") {
                                        css_class = "toggle active";
                                        el_value.className = css_class;
                                    } else if (messageObj['value'] == "off" || messageObj['value'] == "0") {
                                        css_class = "toggle";
                                        el_value.className = css_class;
                                    }

                                    // workaround: toggle will not move if it was clicked before websocket event comes in
                                    el_value.innerHTML = "<div class=\"toggle-handle\"></div>";
                                    break;
                            }
                        } else if (messageObj['typename'] == "Temperaturregelung") {
                            switch (messageObj['reading']) {
                                case 'desired-temp': // set temperature
                                    var postfix = '';
                                    if (messageObj['value'] != "off")
                                        postfix = " \xB0C";

                                    var el_soll_value = document.getElementById("soll_value_" + messageObj['dev_id']);
                                    el_soll_value.innerHTML = messageObj['value']+postfix;
                                    break;
                                case 'measured-temp': // current temperature
                                    el_value.innerHTML = messageObj['value']+" \xB0C";
                                    break;
                                case 'battery': // battery status
                                    el_value = document.getElementById("value_battery_" + messageObj['dev_id']);
                                    if(el_value)
                                        el_value.innerHTML = messageObj['value'];
                                    break;
                                case 'humidity': // current humidity
                                    var el_hum_value = document.getElementById("value_hum_" + messageObj['dev_id']);
                                    el_hum_value.innerHTML = messageObj['value']+" %";
                                    break;
                                default:
                                    break;
                            }
                        } else if (messageObj['typename'] == "Jalousie") {
                            switch (messageObj['reading']) {
                                case 'pct': // set blinds
                                    var postfix = '';
                                    var value = messageObj['value'];

                                    if (value != "off" && value != "on" && value != "stop") {
                                        postfix = '%';
                                        value = parseInt(value);
                                    }

                                    $('#value_'+messageObj['dev_id']).text(value+postfix);
                                    $('#value_cur'+messageObj['dev_id']).val(value);
                                    break;
                                case 'motor': // motor movement
                                    break;
                                default:
                                    break;
                            }
                        } else if(messageObj['typename'] == "Dimmer") {
                            switch (messageObj['reading']) {
                                case 'pct':
                                    var value = messageObj['value'];

                                    if(value == "on")
                                        value = "100%";
                                    else if(value == "off")
                                        value = "0%";

                                    $('#value_'+messageObj['dev_id']).html(value);

                                    break;
                                default:
                                    break;
                            }
                        } else {
                            switch (messageObj['reading']) {
                                case 'state':
                                    el_value.innerHTML = messageObj['value'];
                                    break;
                                case 'battery':
                                    el_value = document.getElementById("value_battery_" + messageObj['dev_id']);
                                    if(el_value)
                                        el_value.innerHTML = messageObj['value'];
                                    break;
                            }
                        }
                    }
                };
            }

            var timeout = null;
            function toggleDevice(device_id, d_identifier, type, value)
            {
                var cmdurl = "../helper-client/fhem.php?cmd=set";

                var mygetrequest = new ajaxRequest();
                mygetrequest.onreadystatechange=function () {
                    if (mygetrequest.readyState == 4) {
                        if (mygetrequest.status == 200 || window.location.href.indexOf("http") == -1) {
                            var thisdoc = document.getElementById("result")
                            if(thisdoc != null)
                                thisdoc.innerHTML = mygetrequest.responseText;
                        }
                    }
                }

                if (type == "Temperaturregelung") {
                    disableValueRefreshForDeviceID = device_id;

                    var reading = "desired-temp";
                    d_identifier += '_Climate';

                    var stepsize = 0.5; // TBD: configure & take out of database

                    var el_soll = document.getElementById("soll_value_" + device_id);

                    var setvalue;
                    var current_value = el_soll.innerHTML.split("°")[0];

                    if(current_value == "---" || current_value == "NaN")
                        return false

                    if(current_value == "off" || setvalue == "off")
                        current_value = 4.5;

                    if(timeout) window.clearTimeout(timeout);

                    var postfix = '';

                    if (value == "up") {
                        postfix = ' \u00B0C';

                        setvalue = parseFloat(current_value) + parseFloat(stepsize);

                        if(setvalue >= 30.0)
                            setvalue = 30.0;

                        setvalue = setvalue.toFixed(1);
                    } else if (value == "down") {
                        setvalue = parseFloat(current_value) - parseFloat(stepsize);
                        setvalue = setvalue.toFixed(1);

                        if(setvalue < 5.0)
                            setvalue = "off";
                        else
                            postfix =  ' \u00B0C';
                    }

                    el_soll.innerHTML = setvalue+postfix;

                    timeout = setTimeout(function () {
                        mygetrequest.open("GET", cmdurl+"&device="+d_identifier+"&value="+setvalue+"&reading="+reading, true);
                        mygetrequest.send(null);
                        setTimeout(function () { disableValueRefreshForDeviceID = null; }, 3000);
                    }, 2000);
                } else if (type == "Jalousie") {
                    if(timeout) window.clearTimeout(timeout);

                    if (value != "on" && value != "off" && value != "stop") {
                        var direction = value;
                        var reading = "pct";

                        var stepsize = 5; // TBD: configure & take out of database

                        var el_soll = document.getElementById("value_" + device_id);

                        var el_ist = document.getElementById("value_cur" + device_id);

                        var value = el_soll.innerHTML.split("%")[0];

                        if (direction == "up") {
                            value = parseInt(value) + parseInt(stepsize);
                            if(value > 100)
                                value = 100;

                            el_soll.innerHTML = value+"%";
                        } else if (direction == "down") {
                            value = parseInt(value) - parseInt(stepsize);
                            if(value < 0)
                                value = 0;

                            el_soll.innerHTML = value+"%";
                        } else
                            el_soll.innerHTML = value+"%";

                        setvalue = value;

                        timeout = setTimeout(function () {
                            mygetrequest.open("GET", cmdurl+"&device="+d_identifier+"&value="+setvalue+"&reading="+reading, true);
                            mygetrequest.send(null);
                        }, 2000);
                    } else {
                        var setvalue = value;

                        timeout = setTimeout(function () {
                            mygetrequest.open("GET", cmdurl+"&device="+d_identifier+"&value="+setvalue, true);
                            mygetrequest.send(null);
                        }, 2000);
                    }
                }

                /*else if (type == "Jalousie") {
                    var stepsize = 5; // TBD: configure & take out of database

                    var el_soll = document.getElementById("value_" + device_id);

                    var current_value = el_soll.innerHTML.split("%")[0];

                    if(timeout) window.clearTimeout(timeout);

                    if (value == "up") {
                        value = parseInt(current_value) + parseInt(stepsize);
                        if(value > 100)
                            value = 100;

                        el_soll.innerHTML = value+"%";

                        timeout = setTimeout(function () {
                            mygetrequest.open("GET", cmdurl+value, true);
                            mygetrequest.send(null);
                        }, 2000);
                    } else if (value == "down") {
                        value = parseInt(current_value) - parseInt(stepsize);
                        if(value < 0)
                            value = 0;

                        el_soll.innerHTML = value+"%";

                        timeout = setTimeout(function () {
                            mygetrequest.open("GET", cmdurl+value, true);
                            mygetrequest.send(null);
                        }, 2000);
                    } else {
                        mygetrequest.open("GET", cmdurl+value, true);
                        mygetrequest.send(null);
                    }
                }*/
                else if (type == "Dimmer") {
                    if(timeout) window.clearTimeout(timeout);

                    var direction = value;
                    var reading = "pct";

                    var stepsize = 5; // TBD: configure & take out of database

                    var el_soll = document.getElementById("value_" + device_id);

                    if (el_soll.value != '---') {
                        if (direction == "up") {
                            if(el_soll.innerHTML.indexOf('%') > -1)
                                value = el_soll.innerHTML.split("%")[0];

                            value = parseInt(value) + parseInt(stepsize);
                            if(value > 100)
                                value = 100;

                            el_soll.innerHTML = value+'%';
                        } else if (direction == "down") {
                            if(el_soll.innerHTML.indexOf('%') > -1)
                                value = el_soll.innerHTML.split("%")[0];

                            value = parseInt(value) - parseInt(stepsize);
                            if(value < 0)
                                value = 0;

                            el_soll.innerHTML = value+'%';
                        } else
                            el_soll.innerHTML = value+'%';

                        if(el_soll.innerHTML.indexOf('%') > -1)
                            setvalue = el_soll.innerHTML.split("%")[0];
                        else
                            setvalue = el_soll.innerHTML;

                        timeout = setTimeout(function () {
                            mygetrequest.open("GET", cmdurl+"&device="+d_identifier+"&value="+setvalue+"&reading="+reading, true);
                            mygetrequest.send(null);
                        }, 2000);
                    }
                } else if (type == "Raspberry Pi GPIO") {
                    var el_raspi_address = document.getElementById("gpio_raspi_address" + device_id);
                    var el_outputpin = document.getElementById("gpio_outputpin" + device_id);

                    cmdurl = "http://"+el_raspi_address.value+"/helper-client/gpio.php?cmd=set&pin="+el_outputpin.value+"&value="+value+"&identifier="+d_identifier+"&t=0";

                    mygetrequest.open("GET", cmdurl+value, true);
                    mygetrequest.send(null);
                } else {
                    mygetrequest.open("GET", cmdurl+"&device="+d_identifier+"&value="+value, true);
                    mygetrequest.send(null);
                }

                return false;
            }

            function moveCameraStep(direction, ipaddress, port, user, password, camtype)
            {
                var step = 10;

                if (camtype == "instar") {
                    var command;

                    switch (direction) {
                        case "up":
                            command = 0;
                            break;
                        case "down":
                            command = 2;
                            break;
                        case "left":
                            command = 4;
                            break;
                        case "right":
                            command = 6;
                            break;
                        default:
                            command = 25; // home position
                            break;
                    }

                    var script = document.createElement('script');
                    script.src = 'http://' + ipaddress + ':' + port +'/decoder_control.cgi?command=' + command + '&user=' + user + '&pwd=' + password + '&onestep=' + step;
                    script.type = "text/javascript";
                    document.getElementsByTagName('head')[0].appendChild(script);
                    document.getElementsByTagName('head')[0].removeChild(script)
                }
                else if(camtype == "wansview")
                {
                    var command;
                    step = 0;
                    
                    switch (direction) {
                        case "up":
                            command = 0;
                            break;
                        case "down":
                            command = 2;
                            break;
                        case "left":
                            command = 4;
                            break;
                        case "right":
                            command = 6;
                            break;
                        default:
                            command = 25; // home position
                            break;
                    }

                    var script = document.createElement('script'); 
                    script.src = 'http://' + ipaddress + ':' + port +'/decoder_control.cgi?command=' + command + '&loginuse=' + user + '&loginpas=' + password + '&onestep=' + step; 
                    script.type = "text/javascript";
                    document.getElementsByTagName('head')[0].appendChild(script);
                    document.getElementsByTagName('head')[0].removeChild(script)
                }
            }

            function stopCamera(direction, ipaddress, port, user, password, camtype)
            {
                if (camtype == "instar") {
                    var command;

                    switch (direction) {
                        case "up":
                            command = 1;
                            break;
                        case "down":
                            command = 3;
                            break;
                        case "left":
                            command = 5;
                            break;
                        case "right":
                            command = 7;
                            break;
                    }

                    var script = document.createElement('script');
                    script.src = 'http://' + ipaddress + ':' + port +'/decoder_control.cgi?command=' + command + '&user=' + user + '&pwd=' + password;
                    script.type = "text/javascript";
                    document.getElementsByTagName('head')[0].appendChild(script);
                    document.getElementsByTagName('head')[0].removeChild(script)
                }
                else if(camtype == "wansview")
                {
                    var command;
                    var step = 0;
                    
                    switch (direction) {
                        case "up":
                            command = 1;
                            break;
                        case "down":
                            command = 3;
                            break;
                        case "left":
                            command = 5;
                            break;
                        case "right":
                            command = 7;
                            break;
                    }

                    var script = document.createElement('script'); 
                    script.src = 'http://' + ipaddress + ':' + port +'/decoder_control.cgi?command=' + command + '&loginuse=' + user + '&loginpas=' + password + '&onestep=' + step; 
                    script.type = "text/javascript";
                    document.getElementsByTagName('head')[0].appendChild(script);
                    document.getElementsByTagName('head')[0].removeChild(script)
                }
            }

            function moveCameraPosition(position, ipaddress, port, user, password, camtype, dev_id)
            {
                if (position != "") {
                    if (camtype == "instar") {
                        position = position.split(' ');
                        var command = eval("30+" + position[1] + "+" + eval(position[1] + "-1"));
                        var script = document.createElement('script');
                        script.src = 'http://' + ipaddress + ':' + port +'/decoder_control.cgi?command=' + command + '&user=' + user + '&pwd=' + password;
                        script.type = "text/javascript";
                        document.getElementsByTagName('head')[0].appendChild(script);
                        document.getElementsByTagName('head')[0].removeChild(script);

                        var elem = document.getElementById("position"+dev_id);
                        elem.selectedIndex = 0;
                    }
                }
            }
        </script>

        <script src="js/ratchet.js"></script>
        <script src="js/standalone.js"></script>

        <title><?php echo $__CONFIG['main_sitetitle'] . " - " . $device->name; ?></title>
    </head>
    <body>
        <header class="bar-title">
            <?php
                if(isset($_GET['prevsite']) && $_GET['prevsite'] == "webcam")
                    echo "<a class=\"button-prev\" href=\"webcam.php\" data-transition=\"slide-out\">Zurück</a>";
                else if(!isset($_GET['room']) && isset($_GET['device']))
                    echo "<a class=\"button-prev\" href=\"automation.php\" data-transition=\"slide-out\">Zurück</a>";
                else
                    echo "<a class=\"button-prev\" href=\"room.php?room=".$_GET['room']."\">Zurück</a>";
            ?>
            <a href="#devicedetails"><h1 class="title"><?php echo $device->name; ?></h1></a>
        </header>

        <div class="content">
            <br>
            <?php
                if ($device->basetype == "Ein/Aus-Schalter" || $device->basetype == "Raspberry Pi GPIO") {
                    echo "<ul class=\"list inset\">";
                        echo "<li>Zustand:<div id=\"value_".$device->dev_id."\" class=\"toggle\"><div class=\"toggle-handle\"></div></div></li>";
                    echo "</ul>";

                    if ($device->basetype == "Raspberry Pi GPIO") {
                        $configResult = mysql_fetch_assoc(mysql_query("SELECT value from configuration where configstring = 'gpio_raspi_address' and dev_id = " . $device->dev_id));
                        print("<input type=\"hidden\" id=\"gpio_raspi_address".$device->dev_id."\" value=\"".$configResult['value']."\">");
                        $configResult = mysql_fetch_assoc(mysql_query("SELECT value from configuration where configstring = 'gpio_outputpin' and dev_id = " . $device->dev_id));
                        print("<input type=\"hidden\" id=\"gpio_outputpin".$device->dev_id."\" value=\"".$configResult['value']."\">");
                    }
                } elseif ($device->basetype == "Temperaturregelung") {
                    echo "<ul class=\"list inset\">";
                        echo "<li>rel. Luftfeuchte: <b id=\"value_hum_".$device->dev_id."\">--- %</b></li>";
                        echo "<li>Raumtemperatur: <b id=\"value_".$device->dev_id."\">--- &deg;C</b></li>";
                    echo "</ul>";
                    echo "<br>";

                    echo "<ul class=\"list inset\">";
                        echo "<li>&nbsp;<a class=\"button-negative\" href=\"#\" onclick=\"javascript: toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'up');\">+ 0.5&deg;C</a></li>";
                        echo "<li>Soll Temperatur: <b id=\"soll_value_".$device->dev_id."\">--- &deg;C</b></li>";
                        echo "<li>&nbsp;<a class=\"button-main\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'down');\">- 0.5&deg;C</a></li>";
                    echo "</ul>";
                    echo "<br>";
                    echo "<ul class=\"list inset\">";
                        echo "<li>Batterie Status: <b id=\"value_battery_".$device->dev_id."\">---</b></li>";
                    echo "</ul>";
                } elseif ($device->basetype == "Webcam") {
                    echo "<ul class=\"list inset\">";
                        $sql = "SELECT value from configuration where configstring = 'vendor' and dev_id = " . $device->dev_id;
                        $result2 = mysql_query($sql);
                        $resultArr = mysql_fetch_assoc($result2);
                        $cam_vendor = $resultArr['value'];

                        if ($cam_vendor == "instar") {
                            $cam_ipaddress = "";
                            $cam_port = "80";
                            $cam_username = "admin";
                            $cam_password = "";

                            $sql = "SELECT value from configuration where configstring = 'ipaddress' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_ipaddress = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'port' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_port = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'username' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_username = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'password' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_password = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'invertcontrols' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $invertcontrols = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'positionslots' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $positionslots = $resultArr['value'];

                            echo "<li class=\"webcam\"><img id=\"webcamstream_img".$device->dev_id."\" src='http://".$cam_ipaddress.":".$cam_port."/videostream.cgi?user=".$cam_username."&pwd=".$cam_password."&resolution=32&rate=0'></li>";
                            print("<div id=\"controlpad\">&nbsp;&nbsp;&nbsp;");
                                if ($invertcontrols == "on") {
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera links schwenken\"><img src=\"../img/left.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera oben schwenken\"><img src=\"../img/up.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera zentrieren\"><img src=\"../img/dot.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera unten schwenken\"><img src=\"../img/down.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera rechts schwenken\"><img src=\"../img/right.png\"></button>");
                                } else {
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera links schwenken\"><img src=\"../img/left.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera oben schwenken\"><img src=\"../img/up.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera zentrieren\"><img src=\"../img/dot.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera unten schwenken\"><img src=\"../img/down.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\")' title=\"Kamera rechts schwenken\"><img src=\"../img/right.png\"></button>");
                                }
                            print("</div>");
                            if ($positionslots > 0) {
                                print("<br><div id=\"controlpad\">");
                                    print("<select id=\"position".$device->dev_id."\" onchange=\"javascript:moveCameraPosition(this.value, '".$cam_ipaddress."', '".$cam_port."', '".$cam_username."', '".$cam_password."', '".$cam_vendor."', ".$device->dev_id.");\">");
                                    print("<option value=\"\">Gehe zu ...</option>");
                                    for ($i=1; $i <= $positionslots ; $i++) {
                                        print("<option>Position ".$i."</option>");
                                    }
                                    print("</select>");
                                print("</div>");
                            }
                        }
                        else if($cam_vendor == "wansview")
                        {
                            $cam_ipaddress = "";
                            $cam_port = "80";
                            $cam_username = "admin";
                            $cam_password = "";

                            $sql = "SELECT value from configuration where configstring = 'ipaddress' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_ipaddress = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'port' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_port = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'username' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_username = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'password' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $cam_password = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'invertcontrols' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $invertcontrols = $resultArr['value'];

                            $sql = "SELECT value from configuration where configstring = 'positionslots' and dev_id = " . $device->dev_id;
                            $result2 = mysql_query($sql);
                            $resultArr = mysql_fetch_assoc($result2);
                            $positionslots = $resultArr['value'];

                            echo "<li class=\"webcam\"><img id=\"webcamstream_img".$device->dev_id."\" src='http://".$cam_ipaddress.":".$cam_port."/videostream.cgi?loginuse=".$cam_username."&loginpas=".$cam_password."'></li>";
                            print("<div id=\"controlpad\">&nbsp;&nbsp;&nbsp;");
                                if($invertcontrols == "on") {
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera links schwenken\"><img src=\"../img/left.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera oben schwenken\"><img src=\"../img/up.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera zentrieren\"><img src=\"../img/dot.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera unten schwenken\"><img src=\"../img/down.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera rechts schwenken\"><img src=\"../img/right.png\"></button>");
                                } else {
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"left\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera links schwenken\"><img src=\"../img/left.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"up\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera oben schwenken\"><img src=\"../img/up.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"home\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera zentrieren\"><img src=\"../img/dot.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"down\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera unten schwenken\"><img src=\"../img/down.png\"></button>");
                                        print("<button id=\"controlbutton\" onclick='moveCameraStep(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");stopCamera(\"right\",\"".$cam_ipaddress."\",\"".$cam_port."\",\"".$cam_username."\",\"".$cam_password."\",\"".$cam_vendor."\");' title=\"Kamera rechts schwenken\"><img src=\"../img/right.png\"></button>");
                                }
                            print("</div>");
                            if($positionslots > 0)
                            {
                                print("<br><div id=\"controlpad\">");
                                    print("<select id=\"position".$device->dev_id."\" onchange=\"javascript:moveCameraPosition(this.value, '".$cam_ipaddress."', '".$cam_port."', '".$cam_username."', '".$cam_password."', '".$cam_vendor."', ".$device->dev_id.");\">");
                                    print("<option value=\"\">Gehe zu ...</option>");
                                    for ($i=1; $i <= $positionslots ; $i++) { 
                                        print("<option>Position ".$i."</option>");
                                    }
                                    print("</select>");
                                print("</div>");
                            }
                        }
                    echo "</ul>";
                } elseif ($device->basetype == "Jalousie") {
                    echo "<ul class=\"list inset\">";
                        echo "<input hidden id=\"value_cur".$device->dev_id."\">";
                        echo "<li>Zustand: <b id=\"value_".$device->dev_id."\">---%</b> geöffnet</li>";
                    echo "</ul>";
                    echo "<br>";

                    echo "<a class=\"button button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'up');\">Hoch</a>";
                    echo "<a class=\"button button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'down');\">Runter</a>";
                    echo "<br>";
                    echo "<a class=\"button-main button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'on');\">komplett öffnen</a>";
                    echo "<a class=\"button-main button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', '84');\">zu 3/4 offen</a>";
                    echo "<a class=\"button-main button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', '68');\">zu 1/2 offen</a>";
                    echo "<a class=\"button-main button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', '52');\">zu 1/4 offen</a>";
                    echo "<a class=\"button-main button-block\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'off');\">komplett schließen</a>";
                    echo "<br>";
                    echo "<a class=\"button-negative button-block\" href=\"#\" name=\"stopbutton\" onclick=\"javascript: toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'stop');\">STOP!</a>";
                } elseif ($device->basetype == "Tür/Fenster-Kontakt") {
                    echo "<ul class=\"list inset\">";
                        echo "<li>Zustand: <b id=\"value_".$device->dev_id."\">---</b></li>";
                        echo "<li>Batterie Status: <b id=\"value_battery_".$device->dev_id."\">---</b></li>";
                    echo "</ul>";

                } elseif ($device->basetype == "Dimmer") {
                    echo "<ul class=\"list inset\">";
                        echo "<li>&nbsp;<a class=\"button-main\" href=\"#\" onclick=\"javascript: toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'up');\">+</a></li>";
                        echo "<li>Zustand: <b id=\"value_".$device->dev_id."\">---</b></li>";
                        echo "<li>&nbsp;<a class=\"button-main\" href=\"#\" onclick=\"javascript:toggleDevice('".$device->dev_id."','".$device->identifier."','".$device->basetype."', 'down');\">-</a></li>";
                    echo "</ul>";
                } else {
                    echo "<ul class=\"list inset\">";
                        echo "<li>Zustand: <b id=\"value_".$device->dev_id."\">---</b></li>";
                    echo "</ul>";
                }

                echo "<div id=\"devicedetails\" class=\"popover\">";
                    echo "<header class=\"popover-header\">";
                        echo "<h3 class=\"title\">Informationen</h3>";
                    echo "</header>";
                    echo "<ul class=\"list\">";
                        echo "<li><b>Raum:</b> ".$device->roomname."</li>";
                        echo "<li><b>Identifier:</b> ".$device->identifier."</li>";
                        echo "<li><b>Typ:</b> ".$device->typename."</li>";
                        echo "<li><b>Basistyp:</b> ".$device->basetype."</li>";
                    echo "</ul>";
                echo "</div>";
            ?>
            <br><br><br>
        </div>

        <?php require_once "includes/nav.php"; ?>

        <script language="javascript">
            if ("<?php echo $device->basetype; ?>" == "Ein/Aus-Schalter" || "<?php echo $device->basetype; ?>" == "Raspberry Pi GPIO") {
                document.querySelector('#value_<?php echo $device->dev_id; ?>').addEventListener('toggle', function myFunction() {
                    var el_value = document.querySelector('#value_<?php echo $device->dev_id; ?>');
                    var value = 'off';
                    if(el_value.className == "toggle active")
                        value = 'on';

                    toggleDevice('<?php echo $device->dev_id; ?>','<?php echo $device->identifier; ?>','<?php echo $device->basetype; ?>', value);
                } );
            }
        </script>
    </body>
</html>
