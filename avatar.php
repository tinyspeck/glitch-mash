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
	# output
	#

	$smarty->display('page_avatar.txt');
?>