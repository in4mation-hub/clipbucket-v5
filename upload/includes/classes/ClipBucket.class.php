<?php

/**
 * @ Author Arslan Hassan
 * @ License : Attribution Assurance License -- http://www.opensource.org/licenses/attribution.php
 * @ Class : CLipBucket Class
 * @ date : 12 MARCH 2009
 * @ Version : v1.8
 */
class ClipBucket
{
    var $BASEDIR;
    var $JSArray = array();
    var $AdminJSArray = array();
    var $moduleList = array();
    var $actionList = array();
    var $anchorList = array();
    var $ids = array(); //IDS WILL BE USED FOR JS FUNCTIONS
    var $AdminMenu = array();
    var $configs = array();
    var $header_files = array(); // these files will be included in <head> tag
    var $admin_header_files = array(); // these files will be included in <head> tag
    var $anchor_function_list = array();
    var $show_page = true;
    var $upload_opt_list = array(); //this will have array of upload opts like upload file, emebed or remote upload
    var $temp_exts = array(); //Temp extensions
    var $actions_play_video = array();
    var $template_files = array();
    var $links = array();
    var $captchas = array();
    var $clipbucket_footer = array('cb_bottom','smarty_catch_error');
    var $clipbucket_functions = array();
    var $head_menu = array();
    var $foot_menu = array();
    var $template = "";
    var $cbinfo = array();
    var $search_types = array();
    var $theUploaderDetails = array();

    /**
     * All Functions that are called
     * before after converting a video
     * are saved in these arrays
     */
    var $after_convert_functions = array();

    /**
     * This array contains
     * all functions that are called
     * when we call video to play on watch_video page
     */
    var $watch_video_functions = array();

    /**
     * Email Function list
     */
    var $email_functions = array();

    /**
     * This array contains
     * all functions that are called
     * on CBvideo::remove_files
     */
    var $on_delete_video = array();

    //This array contains the public pages name for private access to website 
    var $public_pages = array("signup","view_page");

    function __construct()
    {
        global $pages;
        //Assign Configs
        $this->configs = $this->get_configs();
        //Get Current Page and Redirects it to without www.
        $pages->redirectOrig();
        //Get Base Directory
        $this->BASEDIR = $this->getBasedir();
        //Listing Common JS File
        $this->addJS(array(
            'jquery_plugs/cookie.js' => 'global',
            'functions.js' => 'global',
        ));

        //This is used to create Admin Menu
        //Updating Upload Options		
        $this->temp_exts = array('ahz', 'jhz', 'abc', 'xyz', 'cb2', 'tmp', 'olo', 'oar', 'ozz');
        $this->template = $this->configs['template_dir'];

        if (!defined("IS_CAPTCHA_LOADING")){
            $_SESSION['total_captchas_loaded'] = 0;
        }

        $this->clean_requests();

        $sort_array = sorting_links();

        if (!isset($_GET['sort'])  || !isset($sort_array[$_GET['sort']]) ){
            $_GET['sort'] = 'most_recent';
        }

        $time_array = time_links();

        if (!isset($_GET['time']) || !isset($time_array[$_GET['time']])){
            $_GET['time'] = 'all_time';
        }

        if (!isset($_GET['page']) || !is_numeric($_GET['page'])){
            $_GET['page'] = 1;
        }
    }

    function getBasedir()
    {
        $dirname = dirname(__FILE__);
        $dirname = preg_replace(array('/includes/', '/classes/'), '', $dirname);
        $dirname = substr($dirname, 0, strlen($dirname) - 2);
        return $dirname == '/' ? '' : $dirname;
    }

    function addJS($files)
    {
        if (is_array($files)) {
            foreach ($files as $key => $file){
                $this->JSArray[$key] = $file;
            }
        } else {
            $this->JSArray[$files] = 'global';
        }
    }

    function addAdminJS($files)
    {
        if (is_array($files)) {
            foreach ($files as $key => $file){
                $this->AdminJSArray[$key] = $file;
            }
        } else {
            $this->AdminJSArray[$files] = 'global';
        }
    }

	/**
	 * Function add_header()
	 * this will be used to add new files in header array
	 * this is basically for plugins
	 *
	 * @param $file
	 * @param string $place
	 */
    function add_header($file, $place = 'global')
    {
        if (!is_array($place)) {
            $place = array($place);
        }
        $this->header_files[$file] = $place;
    }

	/**
	 * Function add_admin_header()
	 * this will be used to add new files in header array
	 * this is basically for plugins
	 *
	 * @param $file
	 * @param string $place
	 */
    function add_admin_header($file, $place = 'global')
    {
        if (!is_array($place)) {
            $place = array($place);
        }
        $this->admin_header_files[$file] = $place;
    }

	/**
	 * Function used to get list of function of any type
	 *
	 * @param : type (category,title,date etc)
	 *
	 * @return mixed
	 */
    function getFunctionList($type)
    {
        return $this->actionList[$type];
    }

	/**
	 * Function used to get anchors that are registered in plugins
	 *
	 * @param $place
	 *
	 * @return bool|mixed
	 */
    function get_anchor_codes($place)
    {
        //Getting list of codes available for $place
        if(isset($this->anchorList[$place])){
            return $this->anchorList[$place];
        }
        return false;
    }

	/**
	 * Function used to get function of anchors that are registered in plugins
	 *
	 * @param $place
	 *
	 * @return bool|mixed
	 */
    function get_anchor_function_list($place)
    {
        //Getting list of functions
        return $this->anchor_function_list[ $place ] ?? false;
    }

    function addMenuAdmin($menu_params, $order = null){
        global $Cbucket;
        $menu_already_exists = false;

        if( is_null($order) ){
            $order = max(array_keys($Cbucket->AdminMenu))+1;
        } else {
            if( array_key_exists($order, $Cbucket->AdminMenu) ){
                do{
                    $order++;
                } while( array_key_exists($order, $Cbucket->AdminMenu) );
            }
        }

        foreach($Cbucket->AdminMenu as &$menu){
            if( $menu['title'] == $menu_params['title'] ){
                foreach($menu_params['sub'] as $subMenu){
                    $submenu_already_exists = false;

                    foreach( $menu['sub'] as $tmp_submenu ){
                        if( $tmp_submenu['title'] == $subMenu['title'] && $tmp_submenu['url'] == $subMenu['url'] ){
                            $submenu_already_exists = true;
                            break;
                        }
                    }

                    if( !$submenu_already_exists ){
                        $menu['sub'][] = $subMenu;
                    }
                }
                $menu_already_exists = true;
            }
        }
        if( !$menu_already_exists ){
            $Cbucket->AdminMenu[$order] = $menu_params;
        }
        ksort($Cbucket->AdminMenu);
    }

    function initAdminMenu()
    {
        global $userquery;
        $per = $userquery->get_user_level(userid());

        $menu_dashboard = array(
            'title' => 'Dashboard'
            ,'class' => 'icon-dashboard'
            ,'url' => ADMIN_BASEURL.'/index.php'
        );
        $this->addMenuAdmin($menu_dashboard, 1);

        if ($per['web_config_access'] == "yes") {
            $menu_general = array(
                'title' => 'General Configurations'
                ,'class' => 'glyphicon glyphicon-stats'
                ,'sub' => array(
                    array(
                        'title' => 'Reports &amp; Stats'
                        ,'url' => ADMIN_BASEURL.'/reports.php'
                    )
                    ,array(
                        'title' => 'Website Configurations'
                        ,'url' => ADMIN_BASEURL.'/main.php'
                    )
                    ,array(
                        'title' => 'Email Templates'
                        ,'url' => ADMIN_BASEURL.'/email_settings.php'
                    )
                    ,array(
                        'title' => 'Email Tester'
                        ,'url' => ADMIN_BASEURL.'/email_tester.php'
                    )
                    ,array(
                        'title' => 'Language Settings'
                        ,'url' => ADMIN_BASEURL.'/language_settings.php'
                    )
                    ,array(
                        'title' => 'Add New Phrases'
                        ,'url' => ADMIN_BASEURL.'/add_phrase.php'
                    )
                    ,array(
                        'title' => 'Manage Pages'
                        ,'url' => ADMIN_BASEURL.'/manage_pages.php'
                    )
                    ,array(
                        'title' => 'Manage Comments'
                        ,'url' => ADMIN_BASEURL.'/comments.php'
                    )
                    ,array(
                        'title' => 'Update Logos'
                        ,'url' => ADMIN_BASEURL.'/upload_logo.php'
                    )
                )
            );

            $this->addMenuAdmin($menu_general, 10);
		}

        if ($per['member_moderation'] == "yes") {
            $menu_users = array(
                'title' => lang('users')
                ,'class' => 'glyphicon glyphicon-user'
                ,'sub' => array(
                    array(
                        'title' => lang('grp_manage_members_title')
                        ,'url' => ADMIN_BASEURL.'/members.php'
                    )
                    ,array(
                        'title' => 'Add Member'
                        ,'url' => ADMIN_BASEURL.'/add_member.php'
                    )
                    ,array(
                        'title' => 'Manage categories'
                        ,'url' => ADMIN_BASEURL.'/user_category.php'
                    )
                    ,array(
                        'title' => 'Inactive Only'
                        ,'url' => ADMIN_BASEURL.'/members.php?search=yes&status=ToActivate'
                    )
                    ,array(
                        'title' => 'Active Only'
                        ,'url' => ADMIN_BASEURL.'/members.php?search=yes&status=Ok'
                    )
                    ,array(
                        'title' => 'Reported Users'
                        ,'url' => ADMIN_BASEURL.'/flagged_users.php'
                    )
                    ,array(
                        'title' => 'Mass Email'
                        ,'url' => ADMIN_BASEURL.'/mass_email.php'
                    )
                )
            );

            if($per['allow_manage_user_level']=='yes' || $userquery->level == 1){
                $menu_users['sub'][] = array(
                    'title' => 'User Levels'
                    ,'url' => ADMIN_BASEURL.'/user_levels.php'
                );
            }

            $this->addMenuAdmin($menu_users, 20);
        }

        if ($per['ad_manager_access'] == "yes" && config("enable_advertisement") == "yes") {
            $menu_ad = array(
                'title' => 'Advertisement'
                ,'class' => 'glyphicon glyphicon-bullhorn'
                ,'sub' => array(
                    array(
                        'title' => 'Manage Advertisments'
                        ,'url' => ADMIN_BASEURL.'/ads_manager.php'
                    )
                    ,array(
                        'title' => 'Manage Placements'
                        ,'url' => ADMIN_BASEURL.'/ads_add_placements.php'
                    )
                )
            );

            $this->addMenuAdmin($menu_ad, 30);
        }

        if ($per['manage_template_access'] == "yes") {
            $menu_template = array(
                'title' => 'Templates And Players'
                ,'class' => 'glyphicon glyphicon-play-circle'
                ,'sub' => array(
                    array(
                        'title' => 'Templates Manager'
                        ,'url' => ADMIN_BASEURL.'/templates.php'
                    )
                    ,array(
                        'title' => 'Templates Editor'
                        ,'url' => ADMIN_BASEURL.'/template_editor.php'
                    )
                    ,array(
                        'title' => 'Players Manager'
                        ,'url' => ADMIN_BASEURL.'/manage_players.php'
                    )
                    ,array(
                        'title' => lang('player_settings')
                        ,'url' => ADMIN_BASEURL.'/manage_players.php?mode=show_settings'
                    )
                )
            );

            $this->addMenuAdmin($menu_template, 40);
        }

        if ($per['plugins_moderation'] == "yes"){
            $menu_plugin = array(
                'title' => 'Plugin Manager'
                ,'class' => 'glyphicon glyphicon-tasks'
                ,'sub' => array(
                    array(
                        'title' => 'Plugin Manager'
                        ,'url' => ADMIN_BASEURL.'/plugin_manager.php'
                    )
                )
            );

            $this->addMenuAdmin($menu_plugin, 50);
        }

        if ($per['tool_box'] == "yes") {
            $menu_tool = array(
                'title' => 'Tool Box'
                ,'class' => 'glyphicon glyphicon-wrench'
                ,'sub' => array(
                    array(
                        'title' => 'PHP Info'
                        ,'url' => ADMIN_BASEURL.'/phpinfo.php'
                    )
                    ,array(
                        'title' => 'Development Mode'
                        ,'url' => ADMIN_BASEURL.'/dev_mode.php'
                    )
                    ,array(
                        'title' => 'View online users'
                        ,'url' => ADMIN_BASEURL.'/online_users.php'
                    )
                    ,array(
                        'title' => 'Action Logs'
                        ,'url' => ADMIN_BASEURL.'/action_logs.php?type=login'
                    )
                    ,array(
                        'title' => 'Server Modules Info'
                        ,'url' => ADMIN_BASEURL.'/cb_mod_check.php'
                    )
                    ,array(
                        'title' => 'Server Configuration Info'
                        ,'url' => ADMIN_BASEURL.'/cb_server_conf_info.php'
                    )
                    ,array(
                        'title' => 'Conversion Queue Manager'
                        ,'url' => ADMIN_BASEURL.'/cb_conversion_queue.php'
                    )
                    ,array(
                        'title' => 'ReIndexer'
                        ,'url' => ADMIN_BASEURL.'/reindex_cb.php'
                    )
                    ,array(
                        'title' => 'Conversion Lab &alpha;'
                        ,'url' => ADMIN_BASEURL.'/conversion_lab.php'
                    )
                    ,array(
                        'title' => 'Repair video duration'
                        ,'url' => ADMIN_BASEURL.'/repair_vid_duration.php'
                    )
                )
            );


            if ($per['web_config_access'] == "yes"){
                $menu_tool['sub'][] = array(
                    'title' => 'Maintenance'
                    ,'url' => ADMIN_BASEURL.'/maintenance.php'
                );
            }

            $this->addMenuAdmin($menu_tool, 60);
        }
    }

    /**
     * Function used to assign ClipBucket configurations
     */
    function get_configs()
    {
        global $myquery;
        return $myquery->Get_Website_Details();
    }

	/**
	 * Function used to get list of countries
	 *
	 * @param $type
	 *
	 * @return mixed
	 */
    function get_countries($type = 'iso2')
    {
        global $db;
        $results = $db->select(tbl("countries"), "*");
        $carray = array();
        switch ($type)
        {
            case 'iso2':
                foreach ($results as $result) {
                    $carray[$result['iso2']] = $result['name_en'];
                }
                break;
            case 'iso3':
                foreach ($results as $result) {
                    $carray[$result['iso3']] = $result['name_en'];
                }
                break;
			case 'id':
            default:
                foreach ($results as $result) {
                    $carray[$result['country_id']] = $result['name_en'];
                }
                break;
        }

        return $carray;
    }

	/**
	 * Function used to set show_page = false or true
	 *
	 * @param bool $val
	 */
    function show_page($val = true)
    {
        $this->show_page = $val;
    }

	/**
	 * Function used to set template (Frontend)
	 *
	 * @param bool $ctemplate
	 *
	 * @return bool|mixed|string
	 */
    function set_the_template($ctemplate = false)
    {
        global $cbtpl, $myquery;
        if ($ctemplate) {
            $_GET['template'] = $ctemplate;
        }
        $template = $this->template;

        if (isset($_SESSION['the_template']) && $cbtpl->is_template($_SESSION['the_template'])){
            $template = $_SESSION['the_template'];
        }

        if (isset($_GET['template'])) { //@todo : add permission
            if (is_dir(STYLES_DIR . '/' . $_GET['template']) && $_GET['template']){
                $template = $_GET['template'];
            }
        }
        if (isset($_GET['set_the_template']) && $cbtpl->is_template($_GET['set_the_template'])){
            $template = $_SESSION['the_template'] = $_GET['set_the_template'];
        }

        if (!is_dir(STYLES_DIR . '/' . $template) || !$template) {
            $template = $cbtpl->get_any_template();
        }

        if (!is_dir(STYLES_DIR . '/' . $template) || !$template){
            exit("Unable to find any template, please goto <a href='http://clip-bucket.com/no-template-found'><strong>ClipBucket Support!</strong></a>");
        }


        if (isset($_GET['set_template'])) {
            $myquery->set_template($template);
        }

        //$this->smarty_version
        $template_details = $cbtpl->get_template_details($template);
        $cbtpl->smarty_version = $template_details['smarty_version'];

        define('SMARTY_VERSION',$cbtpl->smarty_version);

        return $this->template = $template;
    }

    /**
     * Function used to list available extension for clipbucket
     */
    function list_extensions()
    {
        $exts = $this->configs['allowed_video_types'];
        $exts = preg_replace('/ /', '', $exts);
        $exts = explode(',', $exts);
        $new_form = '';
        foreach ($exts as $ext) {
            if (!empty($new_form)){
                $new_form .= ';';
            }
            $new_form .= "*.$ext";
        }

        return $new_form;
    }

    function get_extensions($type = 'video')
    {
        switch($type){
            default:
            case 'video':
                $exts = $this->configs['allowed_video_types'];
                break;
            case 'photo':
                $exts = $this->configs['allowed_photo_types'];
                break;
        }

        $exts = preg_replace('/ /', '', $exts);
        $exts = explode(',', $exts);
        $new_form = '';
        foreach ($exts as $ext) {
            $new_form .= "$ext,";
        }

        return $new_form;
    }

	/**
	 * Function used to load head menu
	 *
	 * @param null $params
	 *
	 * @return array
	 */
    function head_menu($params = NULL)
    {
        $this->head_menu[] = array('name' => lang('menu_home'),'icon'=>'<i class="fa fa-home"></i>', 'link' => BASEURL, 'this' => 'home', 'section' => 'home', 'extra_attr' => '');
        $this->head_menu[] = array('name' => lang('videos'), 'icon' => '<i class="fa fa-video-camera"></i>', 'link' => cblink(array('name' => 'videos')), 'this' => 'videos', 'section' => 'home');
        $this->head_menu[] = array('name' => lang('photos'), 'icon' => '<i class="fa fa-camera"></i>','link' => cblink(array('name' => 'photos')), 'this' => 'photos');
        $this->head_menu[] = array('name' => lang('channels'),'icon' => '<i class="fa fa-desktop"></i>', 'link' => cblink(array('name' => 'channels')), 'this' => 'channels', 'section' => 'channels');
        $this->head_menu[] = array('name' => lang('collections'), 'icon' => '<i class="fa fa-bars"></i>', 'link' => cblink(array('name' => 'collections')), 'this' => 'collections', 'section' => 'collections');

        /* Calling custom functions for headMenu. This can be used to add new tabs */
        if ($params['assign']){
            assign($params['assign'], $this->head_menu);
        } else {
            return $this->head_menu;
        }
    }

    function cbMenu($params = NULL)
    {
        $this->head_menu($params);

		if (!$params['class']){
			$params['class'] = '';
        }

        if (!isset($params['getSubTab'])){
            $params['getSubTab'] = '';
        }

        if (!isset($params['parentTab'])){
            $params['parentTab'] = '';
        }

        if (!isset($params['selectedTab'])){
            $params['selectedTab'] = '';
        }

		$headMenu = $this->head_menu;

		$custom = (isset($this->custom_menu)) ? $this->custom_menu : false;
		if (is_array($custom)){
			$headMenu = array_merge($headMenu, $custom);
        }

		/* Excluding tabs from menu */
		if (isset($params['exclude']))
		{
			if (is_array($params['exclude'])){
				$exclude = $params['exclude'];
            } else {
				$exclude = explode(",", $params['exclude']);
            }

			foreach ($headMenu as $key => $hm)
			{
				foreach ($exclude as $ex)
				{
					$ex = trim($ex);
					if (strtolower(trim($hm['name'])) == strtolower($ex)){
						unset($headMenu[$key]);
                    }
				}
			}
		}

		$main_menu = array();
		foreach($headMenu as $menu)
		{
			if (isSectionEnabled($menu['this']))
			{
				$selected = current_page(array('page' => $menu['this']));
				if($selected){
					$menu['active'] = true;
                }

				$main_menu[] = $menu;
			}
		}

		$output = "";
		foreach ($main_menu as $menu)
		{
			$selected = getArrayValue($menu, 'active');
			$output .= '<li ';
			$output .= "id = 'cb" . $menu['name'] . "Tab'";

			$output .= " class = '";
			if ($params['class'])
				$output .= $params['class'];
			if ($selected)
				$output .= ' selected';
			$output .= "'";

			if (isset($params['extra_params']))
				$output .= ($params['extra_params']);
			$output .= '>';
			$output .= "<a href='" . $menu['link'] . "'>";
			$output .= $menu['name'] . "</a>";
			$output .= '</li>';
		}

        if (isset($params['echo'])) {
            echo $output;
        } else {
            return $main_menu;
        }
    }

	/**
	 * Function used to load head menu
	 *
	 * @param null $params
	 *
	 * @return array
	 */
    function foot_menu($params = NULL)
    {
        global $cbpage;

        $pages = $cbpage->get_pages(array('active' => 'yes', 'display_only' => 'yes', 'order' => 'page_order ASC'));

        if ($pages){
            foreach ($pages as $p){
               $this->foot_menu[] = array('name' => lang($p['page_name']), 'link' => $cbpage->page_link($p), 'this' => 'home');
            }
        }

        if ($params['assign']){
            assign($params['assign'], $this->foot_menu);
        } else {
            return $this->foot_menu;
        }
    }

    /**
     * Function used to call footer
     */
    function footer()
    {
        ANCHOR(array('place' => 'the_footer'));
    }

    /**
     * Function used to clean requests
     */
    function clean_requests()
    {
        $posts = $_POST;
        $gets = $_GET;
        $request = $_REQUEST;

        //Cleaning post..
        if (is_array($posts) && count($posts) > 0)
        {
            $clean_posts = array();
            foreach ($posts as $key => $post)
            {
                if (!is_array($post))
                {
                    $clean_posts[$key] = preg_replace(array('/\|no_mc\|/', '/\|f\|/'), '', $post);
                } else {
                    $clean_posts[$key] = $post;
                }
            }
            $_POST = $clean_posts;
        }

        //Cleaning get..
        if (is_array($gets) && count($gets) > 0)
        {
            $clean_gets = array();
            foreach ($gets as $key => $get)
            {
                if (!is_array($get))
                {
                    $clean_gets[$key] = preg_replace(array('/\|no_mc\|/', '/\|f\|/'), '', $get);
                } else {
                    $clean_gets[$key] = $get;
                }
            }
            $_GET = $clean_gets;
        }

        //Cleaning request..
        if (is_array($request) && count($request) > 0)
        {
            $clean_request = array();
            foreach ($request as $key => $request)
            {
                if (!is_array($request)) {
                    $clean_request[$key] = preg_replace(array('/\|no_mc\|/', '/\|f\|/'), '', $request);
                } else {
                    $clean_request[$key] = $request;
                }
            }
            $_REQUEST = $clean_request;
        }
    }

}
