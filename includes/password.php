<?php
include dirname(__FILE__).'/dbconnection.php';
include dirname(__FILE__).'/getConfiguration.php';

// include our own variant with SHA512 support if required
// this is needed together with HAproxy basic authentication in case the base system
// does not support correct blowfish $2y$ (e.g. on Debian Wheezy)
if (!defined('PASSWORD_DEFAULT') && $__CONFIG['hash_algorithm'] == "PASSWORD_SHA512") {
	include dirname(__FILE__)."/password_compat/lib/password_sha512.php";

// just include the default compatibility lib for PHP <5.5
} else {
	include dirname(__FILE__)."/password_compat/lib/password.php";
}

// default hash settings in case it might not be existing
if (!defined(constant($__CONFIG['hash_algorithm']))) {
  $__CONFIG['hash_algorithm'] = "PASSWORD_BCRYPT";
  $__CONFIG['hash_options'] = '{"cost":"10"}';
}
