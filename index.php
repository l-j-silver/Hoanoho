<?php
	// Look if DB Configuration is available. If not, redirect to install
	if(!file_exists(__DIR__ . "/config/dbconfig.inc.php")) {
		header("Location: ./install/");
	}

	// Redirector for sharefile in case user does not access via dedicated vhost
	if(isset($_GET['f'])) {
		header("Location: ./sharefile_ext".$_SERVER['REQUEST_URI']);
		exit;
	}


    require_once dirname(__FILE__).'/includes/sessionhandler.php';
    require_once dirname(__FILE__).'/includes/dwd_parser.php';

    function generateNoteColors()
    {
        echo "<div id=\"colorchooser_yellow\" onclick=\"javascript:setNoteColor('yellow');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_orange\" onclick=\"javascript:setNoteColor('orange');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_red\" onclick=\"javascript:setNoteColor('red');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_pink\" onclick=\"javascript:setNoteColor('pink');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_green\" onclick=\"javascript:setNoteColor('green');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_blue\" onclick=\"javascript:setNoteColor('blue');\">&nbsp;</div>";
        echo "<div id=\"colorchooser_purple\" onclick=\"javascript:setNoteColor('purple');\">&nbsp;</div>";
    }

    if (isset($_POST['cmd']) && $_POST['cmd'] == "closeNote" && isset($_POST['no_id']) && strlen($_POST['no_id']) > 0) {
        $sql = "UPDATE notes SET isActive = 0 where no_id = " . $_POST['no_id'];
        mysql_query($sql);
    } elseif (isset($_POST['cmd']) && $_POST['cmd'] == "savenote") {
        $title = $_POST['title'];
        $content = $_POST['content'];

        if (strlen($title) > 0 || strlen($content) > 0) {
            // set urls to real hyperlinks
            $content = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', '<a href="$1" target="blank">$1</a>', $content);
            $content = str_replace("href=\"www.","href=\"http://www.",$content);

            $sql = "INSERT INTO notes set title = '" . $title . "', content = '" . str_replace("\n", "<br/>", $content) . "', created_by = " . $_SESSION['uid'] . ", create_date = now(), modified_date = now(), papercolor = '" . $_POST['papercolor'] . "'";
            mysql_query($sql);
        }
    }
?>

<html>
    <head>
        <meta charset="UTF-8"; />
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <script src="js/jquery-ui.min.js"></script>
		<script type="text/javascript" src="js/cookie.js"></script>

        <script language="javascript">
            window.onload = function () {
                connectWebSocket(<?php echo "\"".$__CONFIG['main_socketport']."\""; ?>);
            }

            // public method for encoding an Uint8Array to base64
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

                    var value = "---";

                    var el_value = document.getElementsByName("value_" + messageObj['dev_id'])[0];
                    if (el_value != null) {
                        if (messageObj['typename'] == "Raspberry Pi GPIO") {
                            if(messageObj['value'] == "1")
                                value = "Eingeschaltet";
                            else if(messageObj['value'] == "0")
                                value = "Ausgeschaltet";
                            else
                                value = "---";

                            el_value.firstChild.nodeValue = value;
                        } else if (messageObj['typename'] == "Temperaturregelung") {
                            var split = [];
                            var splitSign = ", ";

                            value = el_value.firstChild.nodeValue;
                            if(value != "---")
                                split = value.split(splitSign);

                            var index = "";
                            var postfix = "";
                            switch (messageObj['reading']) {
                                case 'desired-temp':
                                    index = "sT:";
                                    postfix = "\xB0C";

                                    break;
                                case 'measured-temp':
                                    index = "rT:";
                                    postfix = "\xB0C";

                                    break;
                                case 'controlMode':
                                    index = "M:";
                                    postfix = "";

                                    break;
                                case 'humidity':
                                    index = "H:";
                                    postfix = "%";

                                    break;
                                default:
                                    break;
                            }

                            if (index.length > 0) {
                                if (split.length > 0) {
                                    var indexFound = false;
                                    for (var i = split.length - 1; i >= 0; i--) {
                                        if (split[i].indexOf(index) >= 0) {
                                            indexFound = true;
                                            split[i] = index+" "+messageObj['value']+postfix;
                                        }
                                    };

                                    if(!indexFound)
                                        split[split.length] = index+" "+messageObj['value']+postfix;
                                } else
                                    split[0] = index+" "+messageObj['value']+postfix;

                                split.sort();

                                value = "";
                                for (var i = split.length - 1; i >= 0; i--) {
                                    value += split[i]+splitSign;
                                };

                                value = value.substring(0, value.length - splitSign.length);
                            }

                            el_value.firstChild.nodeValue = value;
                        } else if (messageObj['typename'] == "Jalousie") {
                            switch (messageObj['reading']) {
                                case 'pct':
                                    if(messageObj['value'] == 100)
                                        value = "Offen";
                                    else if(messageObj['value'] == 0)
                                        value = "Geschlossen";
                                    else
                                        value = messageObj['value']+"%";

                                    el_value.firstChild.nodeValue = value;
                                    break;
                                case 'motor':
                                    var movement = ""
                                    if(messageObj['value'].indexOf('up:') > -1)
                                        movement = "hoch";
                                    if(messageObj['value'].indexOf('down:') > -1)
                                        movement = "runter";

                                    value = 'Fährt '+movement+' ...';

                                    el_value.firstChild.nodeValue = value;
                                    break;
                                default:
                                    break;
                            }
                        } else {
                            switch (messageObj['reading']) {
                                case 'state':
                                    if(messageObj['value'] == "on")
                                        value = "Eingeschaltet";
                                    else if(messageObj['value'] == "off")
                                        value = "Ausgeschaltet";
                                    else if(messageObj['value'] == "open")
                                        value = "Offen";
                                    else if(messageObj['value'] == "closed")
                                        value = "Geschlossen";
                                    else
                                        value = messageObj['value'];

                                    el_value.firstChild.nodeValue = value;
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                };
            }

            function setNoteColor(papercolor)
            {
                var element = document.getElementById("papercolor");

                element.value = papercolor;

                var element = document.getElementById("newnote");
                element.className = "note_" + papercolor;
            }
        </script>

        <script type="text/javascript">
            $(document).ready(function () {
                //##### send savenote Ajax request to dataoperator.php #########
                $("#savenote").click(function (e) {
                        e.preventDefault();

                        $("#savenote").hide();
                        $("#savingnote").show();

                        if ($("#notecontent").val() === '') {
                            $("#notecontent").effect("highlight", {color:"#ff5f5f"});
                            $("#savingnote").hide();
                            $("#savenote").show();

                            return false;
                        }

                        var myData = 'cmd=savenote&title='+$("#notetitle").val()+'&content='+$("#notecontent").val()+'&papercolor='+$("#papercolor").val();
                        jQuery.ajax({
                        type: "POST",
                        url: "helper-client/datacontroller.php",
                        dataType:"text", // Data type, HTML
                        data:myData, //Form variables
                        success:function (response) {
                            $("#notes").append(response);
                            $("#notetitle").val('');
                            $("#notecontent").val('');

                            $("#savingnote").hide();
                            $("#savenote").show();
                        },
                        error:function (xhr, ajaxOptions, thrownError) {
                            alert(thrownError);

                            $("#savingnote").hide();
                            $("#savenote").show();
                        }
                        });
                });

                //##### Send closenote Ajax request to dataoperator.php #########
                $("#notes").delegate(".closenote", "click", function (e) {
                    e.preventDefault();

                    var thiselem = this;
                    var clickedID = thiselem.id.split('-'); //Split ID string (Split works as PHP explode)
                    var DbNumberID = clickedID[1]; //and get number from array
                    var myData = 'cmd=closenote&id='+DbNumberID; //build a post data structure

                    jQuery.ajax({
                    type: "POST",
                    url: "helper-client/datacontroller.php",
                    dataType:"text", // Data type
                    data:myData, //Form variables
                    success:function (response) {
                        //on success, hide  element user wants to delete.
                        $('#note-'+DbNumberID).fadeOut();
                    },
                    error:function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError);
                    }
                    });
                });
            });
        </script>

        <link rel="stylesheet" href="css/style.css" type="text/css" media="screen" title="no title" charset="UTF-8">
        <link rel="stylesheet" href="css/pinboard.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <link rel="stylesheet" href="css/nav.css" type="text/css" media="screen" title="no title" charset="UTF-8">

        <?php require_once dirname(__FILE__).'/includes/getUserSettings.php'; ?>

        <?php require_once dirname(__FILE__).'/includes/mobile-app.php'; ?>

        <title><?php echo $__CONFIG['main_sitetitle'] . " - Pinnwand" ?></title>
    </head>
<body>
    <?php require_once dirname(__FILE__).'/includes/nav.php'; ?>

    <section class="board">
        <div id="notes">
        <?php
        $current_notecolor = "";
        if (isset($_SESSION['uid'])) {
          $sql = "SELECT notecolor FROM usersettings where uid = " . $_SESSION['uid'];
          $result = mysql_query($sql);
          while ($row = mysql_fetch_object($result)) {
              $current_notecolor = $row->notecolor;
          }
        }
        ?>

        <form method="POST" enctype="multipart/form-data" name="newNoteForm" id="newNoteForm">
            <div class="note_<?php echo $current_notecolor; ?>" style="-webkit-transform:rotate(-3deg); margin-top: 20px;" id="newnote">
                <div id="pin5"></div>
                <div id="title"><input type="text" id="notetitle"></div>
                <div id="content"><textarea id="notecontent"></textarea></div>
                <div id="footer"><?php generateNoteColors(); ?><div id="savenote" title="Notiz veröffentlichen">&nbsp;</div><div id="savingnote" title="Notiz veröffentlichen" style="display:none;">&nbsp;</div></div>
                <input type="hidden" name="papercolor" id="papercolor" value="<?php echo $current_notecolor; ?>">
            </div>
        </form>

        <?php
        if (isset($_SESSION['uid'])) {
          $sql = "SELECT no_id, title, content, DATE_FORMAT(create_date , '%d.%m.%Y') create_date, DATE_FORMAT(create_date , '%H:%i') create_time, papercolor, users.username from notes join users on users.uid = notes.created_by where notes.isActive = 1 and (notes.visible_to = 'public' or notes.visible_to like '%".$_SESSION['uid']."%') order by no_id asc";
          $result = mysql_query($sql);
          while ($note = mysql_fetch_object($result)) {
              // random pin cap
              $pin = rand(1,5);
              // random pin rotation
              $rotation_pin = rand(-45,0);
              // random note rotation
              $rotation_note = rand(-3,3);
              // random note margin-top;
              $margin_note = rand(-1,20);
        ?>
                <div class="note_<?php echo $note->papercolor; ?>" style="-webkit-transform:rotate(<?php echo $rotation_note; ?>deg); margin-top:<?php echo $margin_note; ?>px" id="note-<?php echo $note->no_id; ?>">
                    <div id="pin<?php echo $pin; ?>" style="-webkit-transform:rotate(<?php echo $rotation_pin; ?>deg);"></div>
                    <div class="closenote" id="closenote-<?php echo $note->no_id; ?>"></div>
                    <div id="title"><?php echo $note->title; ?></div>
                    <div id="content"><?php echo $note->content; ?></div>
                    <div id="footer"><?php echo $note->username . " am " . $note->create_date . " um " . $note->create_time; ?></div>
                </div>
        <?php
          }
        }
        ?>
        </div>
        <div class="note_last">&nbsp;</div>

        <div id="page_left">
            <div id="page" class="lined_paper">
                <div id="pin_ul"></div><div id="pin_ur"></div>
                <?php
                    $sql = "select * from pinboard_configuration where owner='page_left' and parentid is null order by position asc";
                    $result = mysql_query($sql);
                    while ($block = mysql_fetch_object($result)) {
                        $block_meta = json_decode($block->meta);

                        // value
                        if ($block_meta->type == 1) {
                            print("<div id=\"block\">");
                                if($block_meta->iconid != -1)
                                    print("<div id=\"headline_icon\" style=\"background-image: url('helper-client/datacontroller.php?cmd=getimage&id=".$block_meta->iconid."')\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                else
                                    print("<div id=\"headline_icon\"></div><div id=\"headline\">".$block_meta->title."</div>");

                                $sql = "select * from pinboard_configuration where owner='appendRows-".$block->id."' and parentid = ".$block->id." order by position asc";
                                $result2 = mysql_query($sql);
                                while ($row = mysql_fetch_object($result2)) {
                                    $row_meta = json_decode($row->meta);

                                    $sql = "select devices.dev_id, devices.identifier, devices.name, types.name typename from devices join device_types on device_types.dtype_id = devices.dtype_id join types on types.type_id = device_types.type_id where dev_id = ".$row_meta->dev_id." order by devices.name asc";
                                    $device = mysql_fetch_object(mysql_query($sql));
                                    if ($device->typename == "Datensammler" || $device->typename == "PVServer") {
                                        $sql = "select value, valueunit from device_data where deviceident = '".$device->identifier."' and valuename = '".$row_meta->dev_value."' order by ddid desc limit 0,1";
                                        $data = mysql_fetch_object(mysql_query($sql));
                                        print("<div id=\"text\">".$row_meta->title."</div><div id=\"value\" name=\"value_".$device->dev_id."\">".$data->value." ".$data->valueunit."</div>");
                                    } else {
                                        print("<div id=\"text\">".$row_meta->title."</div><div id=\"value\" name=\"value_".$device->dev_id."\">---</div>");
                                    }
                                }
                            print("</div>");
                        }
                        // garbage
                        else if ($block_meta->type == 2) {
                            $sql = "select date_format(pickupdate, '%d.%m.%Y') pickupdate,text from garbageplan where date(NOW()) = pickupdate -INTERVAL 1 DAY";
                            $result2 = mysql_query($sql);
                            if (mysql_num_rows($result2) > 0) {
                                echo "<div id=\"block\">";

                                if($block_meta->iconid != -1)
                                    print("<div id=\"headline_icon\" style=\"background-image: url('helper-client/datacontroller.php?cmd=getimage&id=".$block_meta->iconid."')\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                else
                                    print("<div id=\"headline_icon\"></div><div id=\"headline\">".$block_meta->title."</div>");

                                while ($item = mysql_fetch_object($result2)) {
                                    echo "<div id=\"value_oneline_normal\">Am ".$item->pickupdate." wird ".explode(":",$item->text)[0]." abgeholt!</div>";
                                }

                                echo "</div>";
                            }
                        }
                        // missed calls
                        else if ($block_meta->type == 3) {
                            $lastlogin = date("Y.m.d H:i",$_SESSION['logintime']);
                            //$lastlogin = '2013.12.01 00:00';
                            $sql = "select concat(lpad(day(date),2,0),'.',lpad(month(date),2,0),'.',year(date),' ', lpad(hour(date),2,0),':',lpad(minute(date),2,0)) date, rufnummer  from callerlist where date > '".$lastlogin."' and typ = 2";
                            $result2 = mysql_query($sql);
                            if (mysql_num_rows($result2) > 0) {
                                echo "<div id=\"block\">";

                                if($block_meta->iconid != -1)
                                    print("<div id=\"headline_icon\" style=\"background-image: url('helper-client/datacontroller.php?cmd=getimage&id=".$block_meta->iconid."')\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                else
                                    print("<div id=\"headline_icon\"></div><div id=\"headline\">".$block_meta->title."</div>");

                                while ($callmissed = mysql_fetch_object($result2)) {
                                    $rufnummer = "unbekannt";
                                    if($callmissed->rufnummer != "")
                                        $rufnummer = $callmissed->rufnummer;

                                    if ($rufnummer != "unbekannt") {
                                      echo "<div id=\"text\">".$callmissed->date."</div><div id=\"value\"><a href=\"tel:".$rufnummer."\">".$rufnummer."</a></div>";
                                    } else {
                                      echo "<div id=\"text\">".$callmissed->date."</div><div id=\"value\">".$rufnummer."</div>";
                                    }
                                }

                                echo "</div>";
                            }
                        }
                        // unread mails
                        else if ($block_meta->type == 4) {
                            $sql = "select mailserver_type, mailserver_encryption, mailserver_port, mailserver_host, mailserver_login, mailserver_password from usersettings where uid = " . $_SESSION['uid'];
                            $result2 = mysql_query($sql);
                            if (mysql_num_rows($result) > 0) {
                                $usersettings = mysql_fetch_object($result2);
                                $mailserver_encryption = "";
                                if($usersettings->mailserver_encryption != "")
                                    $mailserver_encryption = "/".$usersettings->mailserver_encryption."/novalidate-cert";
                                $mailserver = "{".$usersettings->mailserver_host.":".$usersettings->mailserver_port."/".strtolower($usersettings->mailserver_type).strtolower($mailserver_encryption)."}Inbox";
                                $mbox = imap_open($mailserver, $usersettings->mailserver_login, $usersettings->mailserver_password);

                                if (imap_num_msg($mbox) > 0) {
                                    $firstTime = true;

                                    for ($msgno = 1; $msgno <= imap_num_msg($mbox); $msgno++) {
                                        $header = imap_headerinfo($mbox, $msgno);

                                        if ($header->Unseen == 'U') {
                                            if ($firstTime) {
                                                echo "<div id=\"block\">";
                                                if($block_meta->iconid != -1)
                                                    print("<div id=\"headline_icon\" style=\"background-image: url('helper-client/datacontroller.php?cmd=getimage&id=".$block_meta->iconid."')\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                                else
                                                    print("<div id=\"headline_icon\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                                $firstTime = false;
                                            }

                                            $sender = $header->from[0]->personal;
                                            if(strlen(trim($sender)) == 0)
                                                $sender = $header->from[0]->mailbox . "@" . $header->from[0]->host;
                                            $subject = trim(imap_utf8($header->subject));

                                            if(strlen($sender) > 20)
                                                $sender = substr($sender, 0, 17)."...";
                                            if(strlen($subject) > 41)
                                                $subject = substr($subject, 0, 38)."...";
                                            if(strlen($subject) == 0)
                                                $subject = "&nbsp";

                                            echo "<div id=\"text\">".$sender."</div><div id=\"value\">".$subject."</div>";
                                        }
                                    }
                                    if(!$firstTime)
                                     echo "</div>";
                                    imap_close($mbox);
                                }
                            }
                        }
                        // weather warning
                        else if ($block_meta->type == 5) {
                          if ($__CONFIG['dwd_region'] != "") {
                            if (stristr($dwd_warnung_kurz, "Es liegt aktuell keine Warnung") === FALSE) {
                                echo "<div id=\"block\">";
                                    if($block_meta->iconid != -1)
                                        print("<div id=\"headline_icon\" style=\"background-image: url('helper-client/datacontroller.php?cmd=getimage&id=".$block_meta->iconid."')\"></div><div id=\"headline\">".$block_meta->title."</div>");
                                    else
                                        print("<div id=\"headline_icon\"></div><div id=\"headline\">".$block_meta->title."</div>");

                                    echo "<div id=\"value_oneline_thick\"><a href=\"weather_warning.php\"".$dwd_warnung_kurz."</a></div>";
                                echo "</div>";
                            }
                          }
                        }
                    }
                ?>

                <div id="block_last"></div>
            </div>
        </div>

        <div id="page_right">
            <div class="paper_yellow" id="page">
                <div id="pin"></div>
                <div id="block_first">
                    <div id="headline_search"></div><div id="headline">Suchen ...</div>
                    <form action="https://www.google.com/search" target="_blank" onsubmit="javascript:setTimeout('document.searchForm1.reset()', 200);" name="searchForm1"><input type="text" name="q" placeholder="bei Google"></input></form>
                </div>
                <?php
				if (isset($_SESSION['uid'])) {
					$sql = "SELECT name, url from pinboard_links where (uid = 0 or uid = " . $_SESSION['uid'] . ") and ( type = 0 or type = 1 ) order by uid desc, name asc";
                    $result = mysql_query($sql);
					if (mysql_num_rows($result) > 0) {
				?>
                <div id="block">
					<div id="headline_hyperlink"></div><div id="headline">Links</div>
					<?php  while ($link = mysql_fetch_object($result)) { ?>
                    <div id="text"><a href="<?php echo $link->url; ?>"><?php echo $link->name; ?></a></div>
                    <?php } ?>
                </div>
				<?php } } ?>
                <div id="block_last">&nbsp;</div>
            </div>
        </div>

    </section>

<?php if ($__CONFIG['php_debugbar'] == "1" && is_object($debugbar)) { echo $debugbarRenderer->render(); } ?>
</body>
</html>
