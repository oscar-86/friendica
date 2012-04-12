<?php

function notifications_post(&$a) {

	if(! local_user()) {
		goaway(z_root());
	}
	
	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);
	
	if($request_id === "all")
		return;

	if($request_id) {

		$r = q("SELECT * FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "` WHERE `id` = %d  AND `uid` = %d LIMIT 1",
			intval($request_id),
			intval(local_user())
		);
	
		if(count($r)) {
			$intro_id = $r[0]['id'];
			$contact_id = $r[0]['contact-id'];
		}
		else {
			notice( t('Invalid request identifier.') . EOL);
			return;
		}

		// If it is a friend suggestion, the `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` is not a new friend but an existing friend
		// that should not be deleted.

		$fid = $r[0]['fid'];

		if($_POST['submit'] == t('Discard')) {
			$r = q("DELETE FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "` WHERE `id` = %d LIMIT 1", 
				intval($intro_id)
			);	
			if(! $fid) {
				$r = q("DELETE FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` WHERE `id` = %d AND `uid` = %d AND `self` = 0 LIMIT 1", 
					intval($contact_id),
					intval(local_user())
				);
			}
			goaway($a->get_baseurl() . '/notifications/intros');
		}
		if($_POST['submit'] == t('Ignore')) {
			$r = q("UPDATE  `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "` SET `ignore` = 1 WHERE `id` = %d LIMIT 1",
				intval($intro_id));
			goaway($a->get_baseurl() . '/notifications/intros');
		}
	}
}





function notifications_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	nav_set_selected('notifications');		

	$o = '';
	$tabs = array(
		array(
			'label' => t('System'),
			'url'=>$a->get_baseurl() . '/notifications/system',
			'sel'=> (($a->argv[1] == 'system') ? 'active' : ''),
		),
		array(
			'label' => t('Network'),
			'url'=>$a->get_baseurl() . '/notifications/network',
			'sel'=> (($a->argv[1] == 'network') ? 'active' : ''),
		),
		array(
			'label' => t('Personal'),
			'url'=>$a->get_baseurl() . '/notifications/personal',
			'sel'=> (($a->argv[1] == 'personal') ? 'active' : ''),
		),
		array(
			'label' => t('Home'),
			'url' => $a->get_baseurl() . '/notifications/home',
			'sel'=> (($a->argv[1] == 'home') ? 'active' : ''),
		),
		array(
			'label' => t('introductions'),
			'url' => $a->get_baseurl() . '/notifications/intros',
			'sel'=> (($a->argv[1] == 'intros') ? 'active' : ''),
		),
		array(
			'label' => t('Messages'),
			'url' => $a->get_baseurl() . '/message',
			'sel'=> '',
		),
	);
	
	$o = "";

	
	if( (($a->argc > 1)  &&  ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		nav_set_selected('introductions');
		if(($a->argc > 2)  &&  ($a->argv[2] == 'all'))
			$sql_extra = '';
		else
			$sql_extra = " AND `ignore` = 0 ";
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$notif_content .= '<a href="' . ((strlen($sql_extra)) ? 'notifications/intros/all' : 'notifications/intros' ) . '" id="notifications-show-hide-link" >'
			. ((strlen($sql_extra)) ? t('Show Ignored Requests') : t('Hide Ignored Requests')) . '</a></div>' . "\r\n";

		$r = q("SELECT COUNT(*)	AS `total` FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`
			WHERE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`uid` = %d $sql_extra AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`blocked` = 0 ",
				intval($_SESSION['uid'])
		);
		if($r  &&  count($r)) {
			$a->set_pager_total($r[0]['total']);
			$a->set_pager_itemspage(20);
		}

		$r = q("SELECT `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`id` AS `intro_id`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.*, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "`.*, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "`.`name` AS `fname`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "`.`url` AS `furl`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "`.`photo` AS `fphoto`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "`.`request` AS `frequest`
			FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "` LEFT JOIN `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` ON `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "`.`id` = `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`contact-id` LEFT JOIN `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "` ON `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`fid` = `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "fcontact") . "`.`id`
			WHERE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`uid` = %d $sql_extra AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "intro") . "`.`blocked` = 0 ",
				intval($_SESSION['uid']));

		if(($r !== false)  &&  (count($r))) {

			$sugg = get_markup_template('suggestions.tpl');
			$tpl = get_markup_template("intros.tpl");

			foreach($r as $rr) {
				if($rr['fid']) {

					$return_addr = bin2hex($a->user['nickname'] . '@' . $a->get_hostname() . (($a->path) ? '/' . $a->path : ''));
					$notif_content .= replace_macros($sugg,array(
						'$str_notifytype' => t('Notification type: '),
						'$notify_type' => t('Friend Suggestion'),
						'$intro_id' => $rr['intro_id'],
						'$madeby' => sprintf( t('suggested by %s'),$rr['name']),
						'$contact_id' => $rr['contact-id'],
						'$photo' => ((x($rr,'fphoto')) ? $rr['fphoto'] : "images/default-profile.jpg"),
						'$fullname' => $rr['fname'],
						'$url' => $rr['furl'],
						'$hidden' => array('hidden', t('Hide this contact FROM others'), ($rr['hidden'] == 1), ''),
						'$activity' => array('activity', t('Post a new friend activity'), 1, t('if applicable')),

						'$knowyou' => $knowyou,
						'$approve' => t('Approve'),
						'$note' => $rr['note'],
						'$request' => $rr['frequest'] . '?addr=' . $return_addr,
						'$ignore' => t('Ignore'),
						'$discard' => t('Discard')

					));

					continue;

				}
				$friend_selected = (($rr['network'] !== NETWORK_OSTATUS) ? ' checked="checked" ' : ' disabled ');
				$fan_selected = (($rr['network'] === NETWORK_OSTATUS) ? ' checked="checked" disabled ' : '');
				$dfrn_tpl = get_markup_template('netfriend.tpl');

				$knowyou   = '';
				$dfrn_text = '';

				if($rr['network'] === NETWORK_DFRN || $rr['network'] === NETWORK_DIASPORA) {
					if($rr['network'] === NETWORK_DFRN)
						$knowyou = t('Claims to be known to you: ') . (($rr['knowyou']) ? t('yes') : t('no'));
					else
						$knowyou = '';
					$dfrn_text = replace_macros($dfrn_tpl,array(
						'$intro_id' => $rr['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected' => $fan_selected,
						'$approve_as' => t('Approve as: '),
						'$as_friend' => t('Friend'),
						'$as_fan' => (($rr['network'] == NETWORK_DIASPORA) ? t('Sharer') : t('Fan/Admirer'))
					));
				}			

				$notif_content .= replace_macros($tpl,array(
					'$str_notifytype' => t('Notification type: '),
					'$notify_type' => (($rr['network'] !== NETWORK_OSTATUS) ? t('Friend/Connect Request') : t('New Follower')),
					'$dfrn_text' => $dfrn_text,	
					'$dfrn_id' => $rr['issued-id'],
					'$uid' => $_SESSION['uid'],
					'$intro_id' => $rr['intro_id'],
					'$contact_id' => $rr['contact-id'],
					'$photo' => ((x($rr,'photo')) ? $rr['photo'] : "images/default-profile.jpg"),
					'$fullname' => $rr['name'],
					'$hidden' => array('hidden', t('Hide this contact FROM others'), ($rr['hidden'] == 1), ''),
					'$activity' => array('activity', t('Post a new friend activity'), 1, t('if applicable')),
					'$url' => $rr['url'],
					'$knowyou' => $knowyou,
					'$approve' => t('Approve'),
					'$note' => $rr['note'],
					'$ignore' => t('Ignore'),
					'$discard' => t('Discard')

				));
			}
		}
		else
			info( t('Nointroductions.') . EOL);
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_header' => t('Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));
		
		$o .= paginate($a);
		return $o;
				
	} else if (($a->argc > 1)  &&  ($a->argv[1] == 'network')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$r = q("SELECT `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`id`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`verb`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-name`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-link`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-avatar`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`object` AS `object`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-name` AS `pname`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-link` AS `plink`
				FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` INNER JOIN `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` AS `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "` ON  `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`id` = `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`
				WHERE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`unseen` = 1 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`visible` = 1 AND
				 `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`deleted` = 0 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`uid` = %d AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`wall` = 0 ORDER BY `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created` DESC" ,
			intval(local_user())
		);
		
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		$tpl_item_posts = get_markup_template('notifications_posts_item.tpl');
		
		$notif_content = '';
		
		if (count($r) > 0) {
			
			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					case ACTIVITY_FRIEND:
					
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;
						
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					default:
						$item_text = (($it['id'] == $it['parent'])
							? sprintf( t("%s created a new post"), $it['author-name'])
							: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);

						$notif_content .= replace_macros($tpl,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => $item_text,
							'$item_when' => relative_date($it['created'])
						));
				}
			}
			
		} else {
			
			$notif_content = t('No more network notifications.');
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_header' => t('Network Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));
		
	} else if (($a->argc > 1)  &&  ($a->argv[1] == 'system')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$not_tpl = get_markup_template('notify.tpl');
		require_once($GLOBALS['xoops']->path("/modules/friendica/include/bbcode.php"));

		$r = q("SELECT * FROM notify WHERE uid = %d AND seen = 0 ORDER BY date desc",
			intval(local_user())
		);
		
		if (count($r) > 0) {
			foreach ($r as $it) {
				$notif_content .= replace_macros($not_tpl,array(
					'$item_link' => $a->get_baseurl().'/notify/view/'. $it['id'],
					'$item_image' => $it['photo'],
					'$item_text' => strip_tags(bbcode($it['msg'])),
					'$item_when' => relative_date($it['date'])
				));
			}
		} else {
			$notif_content .= t('No more system notifications.');
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_header' => t('System Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));

	} else if (($a->argc > 1)  &&  ($a->argv[1] == 'personal')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$myurl = $a->get_baseurl() . '/profile/'. $a->user['nickname'];
		$myurl = substr($myurl,strpos($myurl,'://')+3);
		$myurl = str_replace(array('www.','.'),array('','\\.'),$myurl);
		$diasp_url = str_replace('/profile/','/u/',$myurl);
		$sql_extra .= sprintf(" AND ( `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-link` REGEXP %s or `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`tag` REGEXP %s or `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`tag` REGEXP %s ) ",
			dbesc($myurl . '$'),
			dbesc($myurl . '\\]'),
			dbesc($diasp_url . '\\]')
		);


		$r = q("SELECT `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`id`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`verb`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-name`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-link`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-avatar`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`object` AS `object`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-name` AS `pname`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-link` AS `plink`
				FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` INNER JOIN `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` AS `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "` ON  `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`id` = `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`
				WHERE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`unseen` = 1 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`visible` = 1 
				$sql_extra
				AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`deleted` = 0 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`uid` = %d AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`wall` = 0 ORDER BY `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created` DESC" ,
			intval(local_user())
		);
		
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		$tpl_item_posts = get_markup_template('notifications_posts_item.tpl');
		
		$notif_content = '';
		
		if (count($r) > 0) {
			
			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					case ACTIVITY_FRIEND:
					
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;
						
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));
						break;
						
					default:
						$item_text = (($it['id'] == $it['parent'])
							? sprintf( t("%s created a new post"), $it['author-name'])
							: sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']));
						$tpl = (($it['id'] == $it['parent']) ? $tpl_item_posts : $tpl_item_comments);

						$notif_content .= replace_macros($tpl,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => $item_text,
							'$item_when' => relative_date($it['created'])
						));
				}
			}
			
		} else {
			
			$notif_content = t('No more personal notifications.');
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_header' => t('Personal Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));
		





	} else if (($a->argc > 1)  &&  ($a->argv[1] == 'home')) {
		
		$notif_tpl = get_markup_template('notifications.tpl');
		
		$r = q("SELECT `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`id`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`verb`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-name`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-link`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`author-avatar`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`object` AS `object`, 
				`" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-name` AS `pname`, `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`author-link` AS `plink`
				FROM `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` INNER JOIN `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "` AS `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "` ON  `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "pitem") . "`.`id` = `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`parent`
				WHERE `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`unseen` = 1 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`visible` = 1 AND
				 `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`deleted` = 0 AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`uid` = %d AND `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`wall` = 1 ORDER BY `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "item") . "`.`created` DESC",
			intval(local_user())
		);
		
		$tpl_item_likes = get_markup_template('notifications_likes_item.tpl');
		$tpl_item_dislikes = get_markup_template('notifications_dislikes_item.tpl');
		$tpl_item_friends = get_markup_template('notifications_friends_item.tpl');
		$tpl_item_comments = get_markup_template('notifications_comments_item.tpl');
		
		$notif_content = '';
		
		if (count($r) > 0) {
			
			foreach ($r as $it) {
				switch($it['verb']){
					case ACTIVITY_LIKE:
						$notif_content .= replace_macros($tpl_item_likes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s liked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_DISLIKE:
						$notif_content .= replace_macros($tpl_item_dislikes,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s disliked %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					case ACTIVITY_FRIEND:
					
						$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
						$obj = parse_xml_string($xmlhead.$it['object']);
						$it['fname'] = $obj->title;
						
						$notif_content .= replace_macros($tpl_item_friends,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s is now friends with %s"), $it['author-name'], $it['fname']),
							'$item_when' => relative_date($it['created'])
						));

						break;
					default:
						$notif_content .= replace_macros($tpl_item_comments,array(
							'$item_link' => $a->get_baseurl().'/display/'.$a->user['nickname']."/".$it['parent'],
							'$item_image' => $it['author-avatar'],
							'$item_text' => sprintf( t("%s commented on %s's post"), $it['author-name'], $it['pname']),
							'$item_when' => relative_date($it['created'])
						));
				}
			}
				
		} else {
			$notif_content = t('No more home notifications.');
		}
		
		$o .= replace_macros($notif_tpl,array(
			'$notif_header' => t('Home Notifications'),
			'$tabs' => $tabs,
			'$notif_content' => $notif_content,
		));
	}

	$o .= paginate($a);
	return $o;
}