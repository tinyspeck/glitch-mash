<?
	##################################################################

	#
	# perform a 'simple' HTTP POST.
	#

	function curl_http_post($url, $post_args){

		$curl_handler = curl_init();

		curl_setopt($curl_handler, CURLOPT_URL, $url);
		curl_setopt($curl_handler, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handler, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl_handler, CURLOPT_FAILONERROR, FALSE);

		#
		# ignore invalid HTTPS certs. you probably want to comment out
		# these lines...		
		#

		curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl_handler, CURLOPT_SSL_VERIFYHOST, FALSE);


		#
		# it's a post
		#

		curl_setopt($curl_handler, CURLOPT_POST, 1);
		curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $post_args);


		#
		# send the request
		#

		$body = @curl_exec($curl_handler);
		$info = @curl_getinfo($curl_handler);


		#
		# close the connection
		#

		curl_close($curl_handler);


		#
		# return
		#

		return array(
			'status'	=> $info['http_code'],
			'body'		=> $body,
			'info'		=> $info,
		);
	}

	##################################################################

	function curl_api_call($path, $args){

		$ret = curl_http_post($GLOBALS['cfg']['api_url'].$path, $args);

		if ($ret['status'] != 200 && $ret['status'] != 400){
			return array(
				'ok'		=> 0,
				'error'		=> 'bad_http_status',
				'details'	=> $ret,
			);
		}

		$obj = @json_decode($ret['body'], true);
		if (!is_array($obj) || !count($obj)){
			return array(
				'ok'		=> 0,
				'error'		=> 'bad_json',
				'details'	=> $ret,
			);			
		}

		if (!isset($obj['ok'])) $obj['ok'] = 1;

		return $obj;
	}

	##################################################################

?>