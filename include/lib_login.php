<?php
	#
	# $Id$
	#

	#################################################################

	function login_ensure_loggedin(){

		if (!$GLOBALS['cfg']['user']['tsid']){

			header('location: /auth/');
			exit;
		}
	}

	#################################################################

	function login_check_login(){

		$auth_cookie = $_COOKIE[$GLOBALS['cfg']['auth_cookie_name']];

		if (!$auth_cookie) return;

		$auth_cookie_enc = AddSlashes($auth_cookie);

		$user = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE oauth_token='$auth_cookie_enc'"));

		if (!$user['tsid']) return;

		$GLOBALS['cfg']['user'] = $user;
	}

	#################################################################

	function login_do_logout(){
		$GLOBALS['cfg']['user'] = null;
		login_unset_cookie($GLOBALS['cfg']['auth_cookie_name']);
	}

	#################################################################

	function login_do_login($token){

		login_set_cookie($GLOBALS['cfg']['auth_cookie_name'], $token, time() + (60 * 60 * 24 * 365));
	}

	#################################################################

	function login_set_cookie($name, $value, $expire=0, $path='/'){
		$res = setcookie($name, $value, $expire, $path, $GLOBALS['cfg']['auth_cookie_domain']);
	}

	#################################################################

	function login_unset_cookie($name){
		login_set_cookie($name, "", time() - 3600);
	}

	#################################################################
?>
