<?php

function friendica_init(&$a) {
	if ($a->argv[1]=="json"){
		$register_policy = Array('REGISTER_CLOSED', 'REGISTER_APPROVE', 'REGISTER_OPEN');

		if (isset($a->config['admin_email'])  &&  $a->config['admin_email']!=''){
			$r = q("SELECT username, nickname FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "user") . "` WHERE email=%s", $a->config['admin_email']);
			$admin = array(
				'name' => $r[0]['username'],
				'profile'=> $a->get_baseurl().'/profile/'.$r[0]['nickname'],
			);
		} else {
			$admin = false;
		}

		$data = Array(
			'version' => FRIENDICA_VERSION,
			'url' => z_root(),
			'plugins' => $a->plugins,
			'register_policy' =>  $register_policy[$a->config['register_policy']],
			'admin' => $admin,
			'site_name' => $a->config['sitename'],
			'platform' => FRIENDICA_PLATFORM,
			'info' => ((x($a->config,'info')) ? $a->config['info'] : '')			
		);

		echo json_encode($data);
		killme();
	}
}



function friendica_content(&$a) {

	$o = '';
	$o .= '<h3>Friendica</h3>';


	$o .= '<p></p><p>';

	$o .= t('This is Friendica, version') . ' ' . FRIENDICA_VERSION . ' ';
	$o .= t('running at web location') . ' ' . z_root() . '</p><p>';

	$o .= t('Please visit <a href="http://friendica.com">Friendica.com</a> to learn more about the Friendica project.') . '</p><p>';	

	$o .= t('Bug reports AND issues: please visit') . ' ' . '<a href="http://bugs.friendica.com">Bugs.Friendica.com</a></p><p>';
	$o .= t('Suggestions, praise, donations, etc. - please email "Info" at Friendica - dot com') . '</p>';

	$o .= '<p></p>';

	if(count($a->plugins)) {
		$o .= '<p>' . t('installed plugins/addons/apps') . '</p>';
		$o .= '<ul>';
		foreach($a->plugins as $p)
			if(strlen($p))
				$o .= '<li>' . $p . '</li>';
		$o .= '</ul>';
	}
	else
		$o .= '<p>' . t('No installed plugins/addons/apps');

	call_hooks('about_hook', $o); 	

	return $o;

}
