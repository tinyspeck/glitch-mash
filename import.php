<?
	include('include/init.php');

	login_ensure_loggedin();

	loadlib('curl');


	#
	# get player info
	#

	$args = array(
		'player_tsid' => $cfg['user']['tsid'],
	);

	$ret = curl_api_call('/simple/players.getClothing', $args);

	if (!$ret['ok']) error_misc($ret);


	#
	# store details for each clothing item
	#

	$avatar = array();

	foreach ($ret['clothing'] as $slot => $row){

		if ($row['id']){

			$avatar[$slot] = $row['id'];

			$hash = array(
				'id'		=> intval($row['id']),
				'name'		=> AddSlashes($row['name']),
				'url'		=> AddSlashes($row['url']),
				'image'		=> AddSlashes($row['image']),
				'image_small'	=> AddSlashes($row['image_small']),
				'image_large'	=> AddSlashes($row['image_large']),
				'sub_only'	=> $row['sub_only'] ? 1 : 0,
				'credits'	=> intval($row['credits']),
				'slot'		=> AddSlashes($slot),
			);

			$hash2 = $hash;
			unset($hash2['id']);

			db_insert_dupe('glitchmash_clothing', $hash, $hash2);
		}
	}


	#
	# now store the avatar
	#

	$hash = array(
		'player_tsid'	=> AddSlashes($cfg['user']['tsid']),
		'url'		=> AddSlashes($ret['avatar_172']),
		'date_added'	=> time(),
		'date_updated'	=> time(),
		'details'	=> AddSlashes(serialize($avatar)),
	);

	$hash2 = $hash;
	unset($hash2['date_added']);

	db_insert_dupe('glitchmash_avatars', $hash, $hash2);


	#
	# mark only the latest one as active
	#

	$tsid_enc = AddSlashes($cfg['user']['tsid']);

	list($latest_id) = db_list(db_fetch("SELECT id FROM glitchmash_avatars WHERE player_tsid='$tsid_enc' ORDER BY date_updated DESC LIMIT 1"));

	$latest_id = intval($latest_id);

	db_write("UPDATE glitchmash_avatars SET is_active=0 WHERE player_tsid='$tsid_enc' AND id!=$latest_id");
	db_write("UPDATE glitchmash_avatars SET is_active=1 WHERE player_tsid='$tsid_enc' AND id=$latest_id");


	#
	# done
	#

	header("location: /you/?imported=1");
	exit;
?>