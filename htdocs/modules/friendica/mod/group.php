<?php

function validate_members(&$item) {
	$item = intval($item);
}

function group_init(&$a) {
	if(local_user()) {
		require_once($GLOBALS['xoops']->path("/modules/friendica/include/group.php"));
		$a->page['aside'] = group_side('contacts','group',false,(($a->argc > 1) ? intval($a->argv[1]) : 0));
	}
}



function group_post(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if(($a->argc == 2)  &&  ($a->argv[1] === 'new')) {
		$name = notags(trim($_POST['groupname']));
		$r = group_add(local_user(),$name);
		if($r) {
			info( t('Group created.') . EOL );
			$r = group_byname(local_user(),$name);
			if($r)
				goaway($a->get_baseurl() . '/group/' . $r);
		}
		else
			notice( t('Could not create group.') . EOL );	
		goaway($a->get_baseurl() . '/group');
		return; // NOTREACHED
	}
	if(($a->argc == 2)  &&  (intval($a->argv[1]))) {
		$r = q("SELECT * FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "group") . "` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Group not found.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
			return; // NOTREACHED
		}
		$group = $r[0];
		$groupname = notags(trim($_POST['groupname']));
		if((strlen($groupname))   &&  ($groupname != $group['name'])) {
			$r = q("UPDATE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "group") . " SET `name` = %s WHERE `uid` = %d AND `id` = %d LIMIT 1",
				dbesc($groupname),
				intval(local_user()),
				intval($group['id'])
			);
			if($r)
				info( t('Group name changed.') . EOL );
		}

		$a->page['aside'] = group_side();
	}
	return;	
}

function group_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied') . EOL);
		return;
	}

	// Switch to text mode interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(),'system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = get_config('system','groupedit_image_limit');
	if($switchtotext === false)
		$switchtotext = 400;

	$tpl = get_markup_template('group_edit.tpl');
	$context = array('$submit' => t('Submit'));

	if(($a->argc == 2)  &&  ($a->argv[1] === 'new')) {
		
		return replace_macros($tpl, $context + array(
			'$title' => t('Create a group of contacts/friends.'),
			'$gname' => array('groupname',t('Group Name: '),$group['name'], ''),
			'$gid' => 'new',
		));


	}

	if(($a->argc == 3)  &&  ($a->argv[1] === 'drop')) {
		if(intval($a->argv[2])) {
			$r = q("SELECT `name` FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "group") . "` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);
			if(count($r)) 
				$result = group_rmv(local_user(),$r[0]['name']);
			if($result)
				info( t('Group removed.') . EOL);
			else
				notice( t('Unable to remove group.') . EOL);
		}
		goaway($a->get_baseurl() . '/group');
		// NOTREACHED
	}

	if(($a->argc > 2)  &&  intval($a->argv[1])  &&  intval($a->argv[2])) {
		$r = q("SELECT `id` FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` WHERE `id` = %d AND `uid` = %d AND `self` = 0 AND `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if(count($r))
			$change = intval($a->argv[2]);
	}

	if(($a->argc > 1)  &&  (intval($a->argv[1]))) {

		require_once($GLOBALS['xoops']->path("/modules/friendica/include/acl_selectors.php"));
		$r = q("SELECT * FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "group") . "` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if(! count($r)) {
			notice( t('Group not found.') . EOL );
			goaway($a->get_baseurl() . '/contacts');
		}
		$group = $r[0];
		$members = group_get_members($group['id']);
		$preselected = array();
		if(count($members))	{
			foreach($members as $member)
				$preselected[] = $member['id'];
		}

		if($change) {
			if(in_array($change,$preselected)) {
				group_rmv_member(local_user(),$group['name'],$change);
			}
			else {
				group_add_member(local_user(),$group['name'],$change);
			}

			$members = group_get_members($group['id']);
			$preselected = array();
			if(count($members))	{
				foreach($members as $member)
					$preselected[] = $member['id'];
			}
		}


		$drop_tpl = get_markup_template('group_drop.tpl');
		$drop_txt = replace_macros($drop_tpl, array(
			'$id' => $group['id'],
			'$delete' => t('Delete')
		));

		$celeb = ((($a->user['page-flags'] == PAGE_SOAPBOX) || ($a->user['page-flags'] == PAGE_COMMUNITY)) ? true : false);

		
		$context = $context + array(
			'$title' => t('Group Editor'),
			'$gname' => array('groupname',t('Group Name: '),$group['name'], ''),
			'$gid' => $group['id'],
			'$drop' => $drop_txt,
		);

	}

	if(! isset($group))
		return;

	$groupeditor = array(
		'label_members' => t('Members'),
		'members' => array(),
		'label_contacts' => t('All Contacts'),
		'contacts' => arraY(),
	);
		

	$textmode = (($switchtotext  &&  (count($members) > $switchtotext)) ? true : false);
	foreach($members as $member) {
		if($member['url']) {
			$member['click'] = 'groupChangeMember(' . $group['id'] . ',' . $member['id'] . '); return true;';
			$groupeditor['members'][] = micropro($member,true,'mpgroup', $textmode);
		}
		else
			group_rmv_member(local_user(),$group['name'],$member['id']);
	}

	$r = q("SELECT * FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` WHERE `uid` = %d AND `blocked` = 0 AND `pending` = 0 AND `self` = 0 ORDER BY `name` ASC",
		intval(local_user())
	);

	if(count($r)) {
		$textmode = (($switchtotext  &&  (count($r) > $switchtotext)) ? true : false);
		foreach($r as $member) {
			if(! in_array($member['id'],$preselected)) {
				$member['click'] = 'groupChangeMember(' . $group['id'] . ',' . $member['id'] . '); return true;';
				$groupeditor['contacts'][] = micropro($member,true,'mpall', $textmode);
			}
		}
	}

	$context['$groupeditor'] = $groupeditor;
	$context['$desc'] = t('Click on a contact to ADD or remove.');

	if($change) {
		$tpl = get_markup_template('groupeditor.tpl');
		echo replace_macros($tpl, $context);
		killme();
	}
	
	return replace_macros($tpl, $context);

}

