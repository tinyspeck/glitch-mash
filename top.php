<?
	include('include/init.php');

	loadlib('curl');


	#
	# fetch the best avatars
	#

	$avatars = array();
	$smarty->assign_by_ref('avatars', $avatars);

	$num = 1;

	$ret = db_fetch("SELECT * FROM glitchmash_avatars WHERE is_active=1 AND enough_votes=1 ORDER BY ratio DESC LIMIT 30");
	foreach ($ret['rows'] as $row){

		$tsid_enc = AddSlashes($row['player_tsid']);
		$row['player'] = db_single(db_fetch("SELECT * FROM glitchmash_players WHERE tsid='$tsid_enc'"));

		$row['details'] = unserialize($row['details']);
		foreach ($row['details'] as $slot => $id){
			$clothing[$id]++;
		}

		$row['num'] = $num;
		$num++;

		$avatars[] = $row;
	}


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

		foreach ($avatars as $k => $row){
			foreach ($row['details'] as $slot => $id){

				$avatars[$k]['details'][$slot] = $clothing[$id];
			}
		}
	}


	#
	# some totals
	#

	list($count_avatars) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_avatars"));
	list($count_votes) = db_list(db_fetch("SELECT COUNT(*) FROM glitchmash_votes"));

	$smarty->assign('count_avatars', $count_avatars);
	$smarty->assign('count_votes', $count_votes);


	#
	# output
	#

	$smarty->display('page_top.txt');
?>