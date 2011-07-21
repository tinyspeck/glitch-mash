<?
	include('include/init.php');

	login_ensure_loggedin();

	loadlib('curl');


	#
	# fetch all of our avatars
	#

	$tsid_enc = AddSlashes($cfg['user']['tsid']);

	$avatars = array();
	$smarty->assign_by_ref('avatars', $avatars);

	$ret = db_fetch("SELECT * FROM glitchmash_avatars WHERE player_tsid='$tsid_enc' ORDER BY date_updated DESC");
	foreach ($ret['rows'] as $row){

		$row['details'] = unserialize($row['details']);
		foreach ($row['details'] as $slot => $id){
			$clothing[$id]++;
		}

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
	# output
	#

	$smarty->display('page_you.txt');
?>