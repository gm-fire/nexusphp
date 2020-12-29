<?php
require_once("include/bittorrent.php");
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

function bark($msg) {
	global $lang_takeedit;
	genbark($msg, $lang_takeedit['std_edit_failed']);
}

if (!mkglobal("id:name:descr:type")){
	global $lang_takeedit;
	bark($lang_takeedit['std_missing_form_data']);
}

$id = $id ?? 0;
if (!$id)
	die();


$res = sql_query("SELECT category, owner, filename, save_as, anonymous, picktype, picktime, added FROM torrents WHERE id = ".mysql_real_escape_string($id));
$row = mysql_fetch_array($res);
$torrentAddedTimeString = $row['added'];
if (!$row)
	die();

if ($CURUSER["id"] != $row["owner"] && get_user_class() < $torrentmanage_class)
	bark($lang_takeedit['std_not_owner']);
$oldcatmode = get_single_value("categories","mode","WHERE id=".sqlesc($row['category']));

$updateset = array();

//$fname = $row["filename"];
//preg_match('/^(.+)\.torrent$/si', $fname, $matches);
//$shortfname = $matches[1];
//$dname = $row["save_as"];

$url = parse_imdb_id($_POST['url'] ?? '');

if ($enablenfo_main=='yes'){
$nfoaction = $_POST['nfoaction'];
if ($nfoaction == "update")
{
	$nfofile = $_FILES['nfo'];
	if (!$nfofile) die("No data " . var_dump($_FILES));
	if ($nfofile['size'] > 65535)
		bark($lang_takeedit['std_nfo_too_big']);
	$nfofilename = $nfofile['tmp_name'];
	if (@is_uploaded_file($nfofilename) && @filesize($nfofilename) > 0)
		$updateset[] = "nfo = " . sqlesc(str_replace("\x0d\x0d\x0a", "\x0d\x0a", file_get_contents($nfofilename)));
	$Cache->delete_value('nfo_block_torrent_id_'.$id);
}
elseif ($nfoaction == "remove"){
	$updateset[] = "nfo = ''";
	$Cache->delete_value('nfo_block_torrent_id_'.$id);
}
}

$catid = ($type ?? 0);
if (!is_valid_id($catid))
bark($lang_takeedit['std_missing_form_data']);
if (!$name || !$descr)
bark($lang_takeedit['std_missing_form_data']);
$newcatmode = get_single_value("categories","mode","WHERE id=".sqlesc($catid));
if ($enablespecial == 'yes' && get_user_class() >= $movetorrent_class)
	$allowmove = true; //enable moving torrent to other section
else $allowmove = false;
if ($oldcatmode != $newcatmode && !$allowmove)
	bark($lang_takeedit['std_cannot_move_torrent']);
$updateset[] = "anonymous = '" . (!empty($_POST["anonymous"]) ? "yes" : "no") . "'";
$updateset[] = "name = " . sqlesc($name);
$updateset[] = "descr = " . sqlesc($descr);
$updateset[] = "url = " . sqlesc($url);
$updateset[] = "small_descr = " . sqlesc($_POST["small_descr"]);
//$updateset[] = "ori_descr = " . sqlesc($descr);
$updateset[] = "category = " . sqlesc($catid);
$updateset[] = "source = " . sqlesc($_POST["source_sel"] ?? 0);
$updateset[] = "medium = " . sqlesc($_POST["medium_sel"] ?? 0);
$updateset[] = "codec = " . sqlesc($_POST["codec_sel"] ?? 0);
$updateset[] = "standard = " . sqlesc($_POST["standard_sel"] ?? 0);
$updateset[] = "processing = " . sqlesc($_POST["processing_sel"] ?? 0);
$updateset[] = "team = " . sqlesc($_POST["team_sel"] ?? 0);
$updateset[] = "audiocodec = " . sqlesc($_POST["audiocodec_sel"] ?? 0);

if (get_user_class() >= $torrentmanage_class) {
	if (!empty($_POST["banned"])) {
		$updateset[] = "banned = 'yes'";
		$_POST["visible"] = 0;
	}
	else
		$updateset[] = "banned = 'no'";
}
$updateset[] = "visible = '" . (!empty($_POST["visible"]) ? "yes" : "no") . "'";
if(get_user_class()>=$torrentonpromotion_class)
{
	if(!isset($_POST["sel_spstate"]) || $_POST["sel_spstate"] == 1)
		$updateset[] = "sp_state = 1";
	elseif(($_POST["sel_spstate"] ?? 0) == 2)
		$updateset[] = "sp_state = 2";
	elseif(($_POST["sel_spstate"] ?? 0) == 3)
		$updateset[] = "sp_state = 3";
	elseif(($_POST["sel_spstate"] ?? 0) == 4)
		$updateset[] = "sp_state = 4";
	elseif(($_POST["sel_spstate"] ?? 0) == 5)
		$updateset[] = "sp_state = 5";
	elseif(($_POST["sel_spstate"] ?? 0) == 6)
		$updateset[] = "sp_state = 6";
	elseif(($_POST["sel_spstate"] ?? 0) == 7)
		$updateset[] = "sp_state = 7";

	//promotion expiration type
	if(!isset($_POST["promotion_time_type"]) || $_POST["promotion_time_type"] == 0) {
		$updateset[] = "promotion_time_type = 0";
		$updateset[] = "promotion_until = '0000-00-00 00:00:00'";
	} elseif ($_POST["promotion_time_type"] == 1) {
		$updateset[] = "promotion_time_type = 1";
		$updateset[] = "promotion_until = '0000-00-00 00:00:00'";
	} elseif ($_POST["promotion_time_type"] == 2) {
		if ($_POST["promotionuntil"] && strtotime($torrentAddedTimeString) <= strtotime($_POST["promotionuntil"])) {
			$updateset[] = "promotion_time_type = 2";
			$updateset[] = "promotion_until = ".sqlesc($_POST["promotionuntil"]);
		} else {
			$updateset[] = "promotion_time_type = 0";
			$updateset[] = "promotion_until = '0000-00-00 00:00:00'";
		}
	}
}
if(get_user_class()>=$torrentsticky_class)
{
	if(($_POST["sel_posstate"] ?? 0) == 0)
		$updateset[] = "pos_state = 'normal'";
	elseif(($_POST["sel_posstate"] ?? 0) == 1)
		$updateset[] = "pos_state = 'sticky'";
}

$pick_info = "";
$place_info = "";
if(get_user_class()>=$torrentmanage_class && $CURUSER['picker'] == 'yes')
{
	if(($_POST["sel_recmovie"] ?? 0) == 0)
	{
		if($row["picktype"] != 'normal')
			$pick_info = ", recomendation canceled!";
		$updateset[] = "picktype = 'normal'";
		$updateset[] = "picktime = '0000-00-00 00:00:00'";
	}
	elseif(($_POST["sel_recmovie"] ?? 0) == 1)
	{
		if($row["picktype"] != 'hot')
			$pick_info = ", recommend as hot movie";
		$updateset[] = "picktype = 'hot'";
		$updateset[] = "picktime = ". sqlesc(date("Y-m-d H:i:s"));
	}
	elseif(($_POST["sel_recmovie"] ?? 0) == 2)
	{
		if($row["picktype"] != 'classic')
			$pick_info = ", recommend as classic movie";
		$updateset[] = "picktype = 'classic'";
		$updateset[] = "picktime = ". sqlesc(date("Y-m-d H:i:s"));
	}
	elseif(($_POST["sel_recmovie"] ?? 0) == 3)
	{
		if($row["picktype"] != 'recommended')
			$pick_info = ", recommend as recommended movie";
		$updateset[] = "picktype = 'recommended'";
		$updateset[] = "picktime = ". sqlesc(date("Y-m-d H:i:s"));
	}
}
sql_query("UPDATE torrents SET " . join(",", $updateset) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);

if($CURUSER["id"] == $row["owner"])
{
	if ($row["anonymous"]=='yes')
	{
		write_log("Torrent $id ($name) was edited by Anonymous" . $pick_info . $place_info);
	}
	else
	{
		write_log("Torrent $id ($name) was edited by $CURUSER[username]" . $pick_info . $place_info);
	}
}
else
{
	write_log("Torrent $id ($name) was edited by $CURUSER[username], Mod Edit" . $pick_info . $place_info);
}
$returl = "details.php?id=$id&edited=1";
if (isset($_POST["returnto"]))
	$returl = $_POST["returnto"];
header("Refresh: 0; url=$returl");
