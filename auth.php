<?
	include('include/init.php');

	loadlib('curl');


	#
	# start of process
	#

	if (!$_GET['code'] && !$_GET['error']){

		$args = array(
			'response_type'	=> 'code',
			'client_id'	=> $cfg['api_client_id'],
			'redirect_uri'	=> $cfg['api_redir_url'],
			'scope'		=> 'identity',
		);

		$base_url = "{$cfg['api_url']}/oauth2/authorize";

		$auth_url = build_url($base_url, $args);

		header("location: $auth_url");
		exit;
	}

	function build_url($base_url, $args, $more=array()){

		foreach ($more as $k => $v){
			$args[$k] = $v;
		}

		$pairs = array();
		foreach ($args as $k => $v){
			$pairs[] = urlencode($k).'='.urlencode($v);
		}
		return $base_url.'?'.implode('&', $pairs);
	}


	#
	# oauth error
	#

	if ($_GET['error']){

		error_misc(array(
			'ok'	=> 0,
			'error'	=> 'oauth_error',
			'msg'	=> $_GET['error'],
			'desc'	=> $_GET['error_description'],
		));
		exit;
	}


	#
	# exchange token
	#

	$args = array(
		'grant_type'	=> 'authorization_code',
		'code'		=> $_GET['code'],
		'client_id'	=> $cfg['api_client_id'],
		'client_secret'	=> $cfg['api_client_secret'],
		'redirect_uri'	=> $cfg['api_redir_url'],
	);

	$ret = curl_api_call('/oauth2/token', $args);

	if (!$ret['ok']) error_misc($ret);

	$oauth_token = $ret['access_token'];

	login_do_login($oauth_token);


	#
	# good to go - fetch some player info
	#

	$ret = curl_api_call('/simple/auth.check', array(
		'oauth_token'	=> $oauth_token,
	));

	db_insert_dupe('glitchmash_players', array(
		'tsid'		=> AddSlashes($ret['player_tsid']),
		'date_added'	=> time(),
		'oauth_token'	=> AddSlashes($oauth_token),
		'name'		=> AddSlashes($ret['player_name']),
	), array(
		'oauth_token'	=> AddSlashes($oauth_token),
		'name'		=> AddSlashes($ret['player_name']),
	));


	#
	# player is logged in - go and import their avatar
	#

	$tsid_enc = AddSlashes($ret['player_tsid']);

	list($count) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_avatars WHERE player_tsid='$tsid_enc'"));

	if ($count){
		header("location: /checkcookie/?redir=".urlencode('/you/?login=1'));
	}else{
		header("location: /checkcookie/?redir=".urlencode('/import/'));
	}
	exit;
?>