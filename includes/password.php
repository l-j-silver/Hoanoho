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
if (constant($__CONFIG['hash_algorithm']) === null ) {
  $__CONFIG['hash_algorithm'] = "PASSWORD_BCRYPT";
  $__CONFIG['hash_options'] = '{"cost":"10"}';
} elseif (
  $__CONFIG['hash_algorithm'] == "PASSWORD_DEFAULT" &&
  (
      !isset(json_decode($__CONFIG['hash_options'], true)['cost']) ||
      json_decode($__CONFIG['hash_options'], true)['cost'] == "")
  ) {
    $__CONFIG['hash_options'] = '{"cost":"10"}';
} elseif (
  $__CONFIG['hash_algorithm'] == "PASSWORD_SHA512" &&
  (
      !isset(json_decode($__CONFIG['hash_options'], true)['cost']) ||
      json_decode($__CONFIG['hash_options'], true)['cost'] == "")
  ) {
    $__CONFIG['hash_options'] = '{"cost":"5000"}';
}
