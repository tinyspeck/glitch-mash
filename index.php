<?
	#
	# $Id$
	#

	include('include/init.php');


	#
	# if we're not signed in, show the logged out homepage
	#

	if (!$cfg['user']['tsid']){

		$smarty->display('page_index_loggedout.txt');
		exit;
	}


	#
	# process a vote?
	#

	function hash_ids($a, $b){
		$ids = array($a, $b);
		sort($ids);
		return implode('/', $ids);
	}

	$prev_ids = array();

	$vote_limit = 30;
	$dbl_vote_limit = $vote_limit + $vote_limit;

	if ($_GET['vote']){

		list($win, $lose, $sig) = explode('-', $_GET['vote']);
		if ($sig == vote_sig($win, $lose)){

			#
			# this vote counts - store it
			#

			$win = intval($win);
			$lose = intval($lose);

			$prev_ids = array($win, $lose);
			$hash = hash_ids($win, $lose);

			db_insert_dupe('glitchmash_votes', array(
				'player_tsid'	=> AddSlashes($cfg['user']['tsid']),
				'hash'		=> AddSlashes($hash),
				'win_id'	=> $win,
				'lose_id'	=> $lose,
				'date_updated'	=> time(),
			), array(
				'win_id'	=> $win,
				'lose_id'	=> $lose,
				'date_updated'	=> time(),
			));


			#
			# update counts for the 2 choices
			#

			list($wins1) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_votes WHERE win_id=$win"));
			list($wins2) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_votes WHERE win_id=$lose"));

			list($losses1) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_votes WHERE lose_id=$win"));
			list($losses2) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_votes WHERE lose_id=$lose"));

			db_update('glitchmash_avatars', array(
				'votes'		=> $wins1+$losses1,
				'wins'		=> $wins1,
				'enough_votes'	=> (($wins1+$losses1) >= $vote_limit) ? 1 : 0,
				'ratio'		=> $wins1 / ($wins1+$losses1),
			), "id=$win");

			db_update('glitchmash_avatars', array(
				'votes'		=> $wins2+$losses2,
				'wins'		=> $wins2,
				'enough_votes'	=> (($wins2+$losses2) >= $vote_limit) ? 1 : 0,
				'ratio'		=> $wins2 / ($wins2+$losses2),
			), "id=$lose");


			#
			# fetch details for display
			#

			$winner = db_single(db_fetch("SELECT * FROM glitchmash_avatars WHERE id=$win"));
			$loser = db_single(db_fetch("SELECT * FROM glitchmash_avatars WHERE id=$lose"));

			$winner_tsid_enc = AddSlashes($winner['player_tsid']);
			$loser_tsid_enc = AddSlashes($loser['player_tsid']);

			$winner['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$winner_tsid_enc'"));
			$loser['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$loser_tsid_enc'"));

			$winner['url_50'] = str_replace('_172.png', '_50.png', $winner['url']);

			$smarty->assign('voted', 1);
			$smarty->assign('winner', $winner);
			$smarty->assign('loser', $loser);
		}
	}



	#
	# find 2 avatars that weren't in the last round, by taking 4 pure randoms,
	# 4 randoms with less than 30 votes and 4 randoms with less than 60 votes.
	# make sure the list is unique too :)
	#

	$rows = array();

	$ret1 = db_fetch("SELECT * FROM glitchmash_avatars WHERE is_active=1 ORDER BY RAND() LIMIT 4");
	$ret2 = db_fetch("SELECT * FROM glitchmash_avatars WHERE is_active=1 AND enough_votes=0 ORDER BY RAND() LIMIT 4");
	$ret3 = db_fetch("SELECT * FROM glitchmash_avatars WHERE is_active=1 AND votes<$dbl_vote_limit ORDER BY RAND() LIMIT 4");

	foreach (array_merge($ret1['rows'], $ret2['rows'], $ret3['rows']) as $row){
		if (!in_array($row['id'], $prev_ids)){
			$rows[$row['id']] = $row;
		}
	}

	shuffle($rows);

	$rows = array_slice($rows, 0, 2);


	#
	# some debugging so i can check if the low-counts are being included
	#

	#if ($cfg['user']['name'] == 'Bees!'){
	#	echo "{$rows[0]['player_tsid']} - {$rows[0]['votes']}<br />\n";
	#	echo "{$rows[1]['player_tsid']} - {$rows[1]['votes']}<br />\n";
	#}


	#
	# get player info
	#

	foreach ($rows as $k => $row){
		$tsid_enc = AddSlashes($row['player_tsid']);
		$rows[$k]['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$tsid_enc'"));
	}

	$choice1 = $rows[0];
	$choice2 = $rows[1];

	$choice1['vote'] = vote_code($choice1['id'], $choice2['id']);
	$choice2['vote'] = vote_code($choice2['id'], $choice1['id']);

	$smarty->assign('choice1', $choice1);
	$smarty->assign('choice2', $choice2);

	function vote_code($win, $lose){

		return $win.'-'.$lose.'-'.vote_sig($win, $lose);
	}

	function vote_sig($win, $lose){

		$base = $win.'/'.$lose.'/'.$GLOBALS['cfg']['user']['tsid'];

		return substr(sha1($GLOBALS['cfg'][''].'/'.$base), 0, 20);
	}


	#
	# output
	#

	$smarty->display('page_index.txt');
?>
