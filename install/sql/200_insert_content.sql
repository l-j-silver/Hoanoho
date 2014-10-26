SET NAMES utf8 COLLATE utf8_unicode_ci;
SET CHARACTER SET utf8;

INSERT IGNORE INTO `types` (`type_id`, `name`) VALUES
(1, 'Ein/Aus-Schalter'),
(2, 'Temperaturregelung'),
(3, 'Webcam'),
(4, 'Jalousie'),
(5, 'Wertanzeige'),
(6, 'Dimmer'),
(7, 'Tür/Fenster-Kontakt'),
(8, 'Philips Hue'),
(9, 'Brandmelder'),
(10, 'Netzwerkgerät'),
(11, 'Datensammler'),
(12, 'Raspberry Pi GPIO'),
(13, 'PVServer');

INSERT IGNORE INTO `configuration` (`dev_id`, `configstring`, `value`, `title`, `hint`, `type`, `category`, `visible`) VALUES
(0, 'main_sitetitle', 'Hoanoho', 'Seitentitel', '', 'text', 'Allgemein', '1'),
(0, 'maintenance_msg', '', 'Systemnachricht', 'Nachricht an Benutzer', 'text', 'Allgemein', '1'),
(0, 'fbox_address', '', 'Fritzbox Adresse', '', 'text', 'Fritzbox', '1'),
(0, 'fbox_user', '', 'Fritzbox Benutzer', 'optional', 'text', 'Fritzbox', '1'),
(0, 'fbox_password', '', 'Fritzbox Passwort', '', 'password', 'Fritzbox', '1'),
(0, 'main_socketport', '8000', 'Websocket Port', '8000', 'text', 'Allgemein', '1'),
(0, 'dwd_region', '', 'Region', '', 'dwd_region', 'Wetter', '1'),
(0, 'fhem_url_admin', 'http://localhost:8082/admin', 'FHEM Frontend URL Admin', 'http://localhost:8082/admin', 'text', 'FHEM', '1'),
(0, 'fhem_url_bk', 'http://localhost:8083/fhem', 'FHEM Backend URL', 'http://localhost:8083/fhem', 'text', 'FHEM', '1'),
(0, 'fhem_url_web', 'http://localhost:8083/fhem', 'FHEM Frontend URL', 'http://localhost:8083/fhem', 'text', 'FHEM', '1'),
(0, 'fhem_url_mobile', 'http://localhost:8084/phone', 'FHEM Frontend URL Mobil', 'http://localhost:8084/phone', 'text', 'FHEM', '1'),
(0, 'fhem_url_tablet', 'http://localhost:8085/tablet', 'FHEM Frontend URL Tablet', 'http://localhost:8085/tablet', 'text', 'FHEM', '1'),
(0, 'fhem_url_webhook', 'http://localhost:8088/webhook', 'FHEM Frontend URL Webhook', 'http://localhost:8088/webhook', 'text', 'FHEM', '1'),
(0, 'position_longitude', '', 'Ortsangabe Längengrad', '13.406091199999992000', 'text', 'Wetter', '1'),
(0, 'position_latitude', '', 'Ortsangabe Breitengrad', '52.519171000000000000', 'text', 'Wetter', '1'),
(0, 'garbageplan_url', '', 'URL zum iCal Abfallkalender', '', 'text', 'Kalender', '1'),
(0, 'sharefile_remoteaddress', '', 'Hostname/IP für Dateibereitstellung', 'z.B. cloud.dyndns.org', 'text', 'Allgemein', '1'),
(0, 'hash_algorithm', 'PASSWORD_DEFAULT', 'Password hashing algorithm', '', 'text', 'Extended Settings', '0'),
(0, 'hash_options', '{"cost":"10"}', 'Password hashing options', '', 'text', 'Extended Settings', '0');

INSERT IGNORE INTO `groups` (`gid`, `isAdmin`, `grpname`) VALUES (1, 0, 'Benutzer'), (2, 1, 'Administrator');
