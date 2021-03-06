<?php

require_once($GLOBALS['xoops']->path("/modules/friendica/boot.php"));

function expire_run($argv, $argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}
  
	if(is_null($db)) {
	    @include_once($GLOBALS['xoops']->path("/modules/friendica/include/.htconfig.php"));
    	require_once($GLOBALS['xoops']->path("/modules/friendica/include/dba.php"));
	    $db = new dba($db_host, $db_user, $db_pass, $db_data);
    	unset($db_host, $db_user, $db_pass, $db_data);
  	};

	require_once($GLOBALS['xoops']->path("/modules/friendica/include/session.php"));
	require_once($GLOBALS['xoops']->path("/modules/friendica/include/datetime.php"));
	require_once($GLOBALS['xoops']->path("/modules/friendica/library/simplepie/simplepie.inc"));
	require_once($GLOBALS['xoops']->path("/modules/friendica/include/items.php"));
	require_once($GLOBALS['xoops']->path("/modules/friendica/include/Contact.php"));

	load_config('config');
	load_config('system');


	$a->set_baseurl(get_config('system','url'));


	// physically remove anything that has been deleted for more than two months

	$r = q("DELETE FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` WHERE deleted = 1 AND changed < UTC_TIMESTAMP() - INTERVAL 60 DAY");
	q("optimize table item");

	logger('expire: start');
	
	$r = q("SELECT `uid`, `username`, `expire` FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "user") . "` WHERE `expire` != 0");
	if(count($r)) {
		foreach($r as $rr) {
			logger('Expire: ' . $rr['username'] . ' interval: ' . $rr['expire'], LOGGER_DEBUG);
			item_expire($rr['uid'],$rr['expire']);
		}
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($argv,$argc);
  killme();
}
