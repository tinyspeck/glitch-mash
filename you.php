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

	$ret = db_fetch("SELECT * FROM glitchmash_avatars WHERE player_tsid='$tsid_enc' ORDER BY is_active DESC, date_updated DESC");
	foreach ($ret['rows'] as $row){

		$row['details'] = unserialize($row['details']);
		foreach ($row['details'] as $slot => $id){
			$clothing[$id]++;
		}

		$row['code'] = substr(sha1($row['id'].$cfg['user']['oauth_token']), 0, 10);

		$avatars[] = $row;
	}


	#
	# activate an old outfit?
	#

	if ($_GET['activate']){

		list($id, $code) = explode('-', $_GET['activate']);

		foreach ($avatars as $row){

			if ($id == $row['id'] && $code == $row['code']){

				db_write("UPDATE glitchmash_avatars SET is_active=0 WHERE player_tsid='$tsid_enc'");
				db_write("UPDATE glitchmash_avatars SET is_active=1 WHERE player_tsid='$tsid_enc' AND id=$row[id]");

				header("location: /you/?active=1");
				exit;
			}
		}
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