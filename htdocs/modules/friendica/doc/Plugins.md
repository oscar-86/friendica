**Friendica Addon/Plugin development**

Please see the sample addon 'randplace' for a working example of using some of these features. The facebook addon provides an example of integrating both "addon" AND "module" functionality. Addons work by intercepting event hooks - which must be registered. Modules work by intercepting specific page requests (by URL path). 


Plugin names cannot contain spaces or other punctuation AND are used as filenames AND function names. You may supply a "friendly" name within the comment block. Each addon must contain both an install AND an uninstall function based on the addon/plugin name. For instance "plugin1name_install()". These two functions take no arguments AND are usually responsible for registering (and unregistering) event hooks that your plugin will require. The install AND uninstall functions will also be called (i.e. re-installed) if the plugin changes after installation - therefore your uninstall should not destroy data AND install should consider that data may already exist. Future extensions may provide for "setup" amd "remove". 

Plugins should contain a comment block with the four following parameters: 

	/*
	* Name: My Great Plugin 
 	* Description: This is what my plugin does. It's really cool
 	* Version: 1.0
 	* Author: John Q. Public <john@myfriendicasite.com>
	*/




Register your plugin hooks during installation.

    register_hook($hookname, $file, $function);

$hookname is a string AND corresponds to a known Friendica hook.

$file is a pathname relative to the top-level Friendica directory. This *should* be 'addon/plugin_name/plugin_name.php' in most cases.

$function is a string AND is the name of the function which will be executed when the hook is called.


Your hook callback functions will be called with at least one AND possibly two arguments


    function myhook_function(&$a, &$b) {


    }


If you wish to make changes to the calling data, you must declare them as
reference variables (with '&') during function declaration.

$a is the Friendica 'App' class - which contains a wealth ofinformation
about the current state of Friendica, such as which module has been called,
configuration info, the page contents at the point the hook was invoked, profile
and userinformation, etc. It is recommeded you call this '$a' to match its usage
elsewhere.

$b can be called anything you like. This isinformation which is specific to the hook
currently being processed, AND generally containsinformation that is being immediately
processed or acted on that you can use, display, or alter. Remember to declare it with
'&' if you wish to alter it.

**Modules**

Plugins/addons may also act as "modules" AND intercept all page requests for a given URL path. In order for a plugin to act as a module it needs to define a function "plugin_name_module()" which takes no arguments AND need not do anything.

If this function exists, you will now receive all page requests for "http://my.web.site/plugin_name" - with any number of URL components as additional arguments. These are parsed into an array $a->argv, with a corresponding $a->argc indicating the number of URL components. So http://my.web.site/plugin/arg1/arg2 would look for a module named "plugin" AND pass its module functions the $a App structure (which is available to many components). This will include:
     $a->argc = 3
     $a->argv = array(0 => 'plugin', 1 => 'arg1', 2 => 'arg2');

Your module functions will often contain the function plugin_name_content(&$a), which defines AND returns the page body content. They may also contain plugin_name_post(&$a) which is called before the _content function AND typically handles the results of POST forms. You may also have plugin_name_init(&$a) which is called very early on AND often does module initialisation. 



**Current hooks:**

**'authenticate'** - called when a user attempts to login.
    $b is an array
        'username' => the supplied username
        'password' => the supplied password
        'authenticated' => SET this to non-zero to authenticate the `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "user") . "`.
        'user_record' => successful authentication must also return a valid user record FROM the database

**'logged_in'** - called after a user has successfully logged in.
    $b contains the $a->user array


**'display_item'** - called when formatting a post for display.
    $b is an array
        'item' => The item (array) details pulled FROM the database
        'output' => the (string) HTML representation of this item prior to adding it to the page

**'post_local'** - called when a status post or comment is entered on the local system
    $b is the item array of theinformation to be stored in the database
        {Please note: body contents are bbcode - not HTML)

**'post_local_end'** - called when a local status post or comment has been stored on the local system
    $b is the item array of theinformation which has just been stored in the database
        {Please note: body contents are bbcode - not HTML)

**'post_remote'** - called when receiving a post FROM another source. This may also be used to post local activity or system generated messages.
    $b is the item array ofinformation to be stored in the database AND the item
    body is bbcode.

**'settings_form'** - called when generating the HTML for the user Settings page
    $b is the (string) HTML of the settings page before the final '</form>' tag.

**'settings_post'** - called when the Settings pages are submitted.
    $b is the $_POST array

**'plugin_settings'** - called when generating the HTML for the addon settings page
    $b is the (string) HTML of the addon settings page before the final '</form>' tag.

**'plugin_settings_post'** - called when the Addon Settings pages are submitted.
    $b is the $_POST array

**'profile_post'** - called when posting a profile page.
    $b is the $_POST array

**'profile_edit'** - called prior to output of profile edit page
    $b is array
        'profile' => profile (array) record FROM the database
        'entry' => the (string) HTML of the generated entry


**'profile_advanced'** - called when the HTML is generated for the 'Advanced profile', corresponding to the 'Profile' tab within a person's profile page.
    $b is the (string) HTML representation of the generated profile
    (The profile array details are in $a->profile)

**'directory_item'** - called FROM the Directory page when formatting an item for display
    $b is an array
        'contact' => `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` (array) record for the person FROM the database
        'entry' => the (string) HTML of the generated entry

**'profile_sidebar_enter'** - called prior to generating the sidebar "short" profile for a page
    $b is (array) the person's profile array

**'profile_sidebar'** - called when generating the sidebar "short" profile for a page
    $b is an array
        'profile' => profile (array) record for the person FROM the database
        'entry' => the (string) HTML of the generated entry

**'contact_block_end'** - called when formatting the block of contacts/friends on a profile sidebar has completed
    $b is an array
          'contacts' => array of contacts
          'output' => the (string) generated HTML of the `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` block

**'bbcode'** - called during conversion of bbcode to html
    $b is (string) converted text

**'html2bbcode'** - called during conversion of html to bbcode (e.g. remote message posting)
    $b is (string) converted text

**'page_header'** - called after building the page navigation section
    $b is (string) HTML of nav region

**'personal_xrd'** - called prior to output of personal XRD file.
    $b is an array
        'user' => the user record for the person
        'xml' => the complete XML to be output

**'home_content'** - called prior to output home page content, shown to unlogged users
    $b is (string) HTML of section region

**'contact_edit'** - called when editing `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` details on an individual FROM the Contacts page
    $b is (array)
        'contact' => `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` record (array) of target contact
        'output' => the (string) generated HTML of the `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` edit page

**'contact_edit_post'** - called when posting the `" . $GLOBALS['xoopsDB']->prefix(_MI_FDC_MODULE_DB_PREFIX . "contact") . "` edit page
    $b is the $_POST array

**'init_1'** - called just after DB has been opened AND before session start
    $b is not used or passed


**'page_end'** - called after HTML content functions have completed
    $b is (string) HTML of content div


A complete list of all hook callbacks with file locations (generated 14-Feb-2012): Please see the source for details of any hooks not documented above.


boot.php:	call_hooks('login_hook',$o);

boot.php:	call_hooks('profile_sidebar_enter', $profile);

boot.php:	call_hooks('profile_sidebar', $arr);

boot.php:	call_hooks("proc_run", $arr);

include/contact_selectors.php:	call_hooks('network_to_name', $nets);

include/api.php:				call_hooks('logged_in', $a->user);

include/api.php:		call_hooks('logged_in', $a->user);

include/queue.php:		call_hooks('queue_predeliver', $a, $r);

include/queue.php:				call_hooks('queue_deliver', $a, $params);

include/text.php:	call_hooks('contact_block_end', $arr);

include/text.php:	call_hooks('smilie', $s);

include/text.php:	call_hooks('prepare_body_init', $item); 

include/text.php:	call_hooks('prepare_body', $prep_arr);

include/text.php:	call_hooks('prepare_body_final', $prep_arr);

include/nav.php:	call_hooks('page_header', $a->page['nav']);

include/auth.php:		call_hooks('authenticate', $addon_auth);

include/bbcode.php:	call_hooks('bbcode',$Text);

include/oauth.php:		call_hooks('logged_in', $a->user);		

include/acl_selectors.php:	call_hooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	call_hooks($a->module . '_post_' . $selname, $o);

include/acl_selectors.php:	call_hooks('contact_select_options', $x);

include/acl_selectors.php:	call_hooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	call_hooks($a->module . '_post_' . $selname, $o);

include/acl_selectors.php:	call_hooks($a->module . '_pre_' . $selname, $arr);

include/acl_selectors.php:	call_hooks($a->module . '_post_' . $selname, $o);

include/notifier.php:		call_hooks('notifier_normal',$target_item);

include/notifier.php:	call_hooks('notifier_end',$target_item);

include/items.php:	call_hooks('atom_feed', $atom);

include/items.php:		call_hooks('atom_feed_end', $atom);

include/items.php:	call_hooks('atom_feed_end', $atom);

include/items.php:	call_hooks('parse_atom', $arr);

include/items.php:	call_hooks('post_remote',$arr);

include/items.php:	call_hooks('atom_author', $o);

include/items.php:	call_hooks('atom_entry', $o);

include/bb2diaspora.php:	call_hooks('bb2diaspora',$Text);

include/cronhooks.php:	call_hooks('cron', $d);

include/security.php:		call_hooks('logged_in', $a->user);

include/html2bbcode.php:	call_hooks('html2bbcode', $text);

include/Contact.php:	call_hooks('remove_user',$r[0]);

include/Contact.php:	call_hooks('contact_photo_menu', $args);

include/conversation.php:	call_hooks('conversation_start',$cb);

include/conversation.php:				call_hooks('render_location',$locate);

include/conversation.php:				call_hooks('display_item', $arr);

include/conversation.php:				call_hooks('render_location',$locate);

include/conversation.php:				call_hooks('display_item', $arr);

include/conversation.php:	call_hooks('item_photo_menu', $args);

include/conversation.php:	call_hooks('jot_tool', $jotplugins);

include/conversation.php:	call_hooks('jot_networks', $jotnets);

include/plugin.php:if(! function_exists('call_hooks')) {

include/plugin.php:function call_hooks($name, &$data = null) {

index.php:	call_hooks('init_1');

index.php:call_hooks('app_menu', $arr);

index.php:call_hooks('page_end', $a->page['content']);

mod/photos.php:	call_hooks('photo_post_init', $_POST);

mod/photos.php:	call_hooks('photo_post_file',$ret);

mod/photos.php:		call_hooks('photo_post_end',$foo);

mod/photos.php:		call_hooks('photo_post_end',$foo);

mod/photos.php:		call_hooks('photo_post_end',$foo);

mod/photos.php:	call_hooks('photo_post_end',intval($item_id));

mod/photos.php:		call_hooks('photo_upload_form',$ret);

mod/friendica.php:	call_hooks('about_hook', $o); 	

mod/editpost.php:	call_hooks('jot_tool', $jotplugins);

mod/editpost.php:	call_hooks('jot_networks', $jotnets);

mod/parse_url.php:	call_hooks('parse_link', $arr);

mod/home.php:	call_hooks('home_init',$ret);

mod/home.php:	call_hooks("home_content",$o);

mod/contacts.php:	call_hooks('contact_edit_post', $_POST);

mod/contacts.php:		call_hooks('contact_edit', $arr);

mod/settings.php:		call_hooks('plugin_settings_post', $_POST);

mod/settings.php:		call_hooks('connector_settings_post', $_POST);

mod/settings.php:	call_hooks('settings_post', $_POST);

mod/settings.php:		call_hooks('plugin_settings', $settings_addons);

mod/settings.php:		call_hooks('connector_settings', $settings_connectors);

mod/settings.php:	call_hooks('settings_form',$o);

mod/register.php:	call_hooks('register_account', $newuid);

mod/like.php:	call_hooks('post_local_end', $arr);

mod/xrd.php:	call_hooks('personal_xrd', $arr);

mod/item.php:	call_hooks('post_local_start', $_REQUEST);

mod/item.php:	call_hooks('post_local',$datarray);

mod/item.php:	call_hooks('post_local_end', $datarray);

mod/profile.php:			call_hooks('profile_advanced',$o);

mod/profiles.php:	call_hooks('profile_post', $_POST);

mod/profiles.php:		call_hooks('profile_edit', $arr);

mod/tagger.php:	call_hooks('post_local_end', $arr);

mod/cb.php:	call_hooks('cb_init');

mod/cb.php:	call_hooks('cb_post', $_POST);

mod/cb.php:	call_hooks('cb_afterpost');

mod/cb.php:	call_hooks('cb_content', $o);

mod/directory.php:			call_hooks('directory_item', $arr);

