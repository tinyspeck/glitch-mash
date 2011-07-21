<?
	include('include/init.php');

	#
	# fetch avatar
	#

	$id_enc = intval($_GET['id']);

	$avatar = db_single(db_fetch("SELECT * FROM glitchmash_avatars WHERE id='$id_enc'"));

	if (!$avatar['id']) error_404();

	$avatar['details'] = unserialize($avatar['details']);
	foreach ($avatar['details'] as $slot => $id){
		$clothing[$id]++;
	}


	#
	# fetch player
	#

	$tsid_enc = AddSlashes($avatar['player_tsid']);

	$avatar['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$tsid_enc'"));


	#
	# fetch all clothing rows
	#

	if (count($clothing)){
		$clothing_ids = implode(',', array_keys($clothing));
		$clothing = array();

		$ret = db_fetch("SELECT * FROM glitchmash_clothing WHERE id IN ($clothing_ids)");
		foreach ($ret['rows'] as $row){

			$clothing[$row['id']] = $row;
		}

		foreach ($avatar['details'] as $slot => $id){

			$avatar['details'][$slot] = $clothing[$id];
		}
	}

	$smarty->assign('avatar', $avatar);


	#
	# fetch the most recent 5 battles
	#

	$recent_count = 5;

	$ret1 = db_fetch("SELECT * FROM glitchmash_votes WHERE win_id=$id_enc ORDER BY date_updated DESC LIMIT $recent_count");
	$ret2 = db_fetch("SELECT * FROM glitchmash_votes WHERE lose_id=$id_enc ORDER BY date_updated DESC LIMIT $recent_count");

	$votes = array();
	$smarty->assign_by_ref('votes', $votes);

	foreach (array_merge($ret1['rows'], $ret2['rows']) as $row){

		$opp_id = $row['win_id'] == $avatar['id'] ? $row['lose_id'] : $row['win_id'];
		$opp = db_single(db_fetch("SELECT * FROM glitchmash_avatars WHERE id='$opp_id'"));
		$opp_tsid_enc = AddSlashes($opp['player_tsid']);
		$opp['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$opp_tsid_enc'"));

		$opp['url_50'] = str_replace('_172.png', '_50.png', $opp['url']);

		$votes[] = array(
			'did_win'	=> !!($row['win_id'] == $avatar['id']),
			'avatar'	=> $opp,
			'date_updated'	=> $row['date_updated'],
		);
	}

	usort($votes, 'local_vote_sort');

	function local_vote_sort($a, $b){
		return $b['date_updated'] - $a['date_updated'];
	}

	$votes = array_slice($votes, 0, $recent_count);



	#
	# output
	#

	$smarty->display('page_avatar.txt');
?>