<?php
define("BACK_END",TRUE);
define("FRONT_END",FALSE);
define("SLOGAN","Administration Panel");

//Admin Area
$admin_area	= TRUE;

/* Config.Inc.php */
include('common.php');

//Including Massuploader Class,
require_once('classes/mass_upload.class.php');
require_once('classes/ads.class.php');

global $db,$ClipBucket,$Cbucket,$Smarty;

$cbmass 	= new mass_upload();
$ads_query 	= new AdsManager();

$admin_pages = $row['admin_pages'];

if(isset($_POST['update_dp_options']))
{
    if(!is_numeric($_POST['admin_pages']) || $_POST['admin_pages']<1)
    {
        $num = '20';
        $msg = "Please Type Number from 1 to Maximum";
    } else {
        $num = $_POST['admin_pages'];
        $admin_pages = $num;
    }

    $db->update(tbl("config"),array("value"),array($num)," name='admin_pages'");
    $ClipBucket->configs = $Cbucket->configs = $Cbucket->get_configs();
}

define('RESULTS', $admin_pages);
Assign('admin_pages',$admin_pages);

//Do No Edit Below This Line
define('ADMIN_TEMPLATE', 'cb_2014');
define('TEMPLATEDIR',BASEDIR.'/'.ADMINDIR.'/'.TEMPLATEFOLDER.'/'.ADMIN_TEMPLATE);
define('SITETEMPLATEDIR',BASEDIR.'/'.TEMPLATEFOLDER.'/'.$row['template_dir']);
define('TEMPLATEURL','/'.ADMINDIR.'/'.TEMPLATEFOLDER.'/'.ADMIN_TEMPLATE);
define('TEMPLATEURLFO','/'.TEMPLATEFOLDER.'/'.$Cbucket->template);
define('LAYOUT',TEMPLATEDIR.'/layout');
define('TEMPLATE',$row['template_dir']);

/*
* Calling this function to check server configs
* Checks : MEMORY_LIMIT, UPLOAD_MAX_FILESIZE, POST_MAX_SIZE, MAX_EXECUTION_TIME
* If any of these configs are less than required value, warning is shown
*/
check_server_confs();

Assign('baseurl',BASEURL);
Assign('admindir',ADMINDIR);
Assign('imageurl',TEMPLATEURL.'/images');
Assign('image_url',TEMPLATEURL.'/layout');
Assign('layout',TEMPLATEURL.'/layout');
Assign('layout_url',TEMPLATEURL.'/layout');
Assign('theme',TEMPLATEURL.'/theme');
Assign('theme_url',TEMPLATEURL.'/theme');
Assign('style_dir',LAYOUT);
Assign('layout_dir', LAYOUT);
Assign('logged_user',@$_SESSION['username']);
Assign('superadmin',@$_SESSION['superadmin']);

//Including Plugins
include('plugins.php');

$Smarty->assign_by_ref( 'cbmass',$cbmass );

cb_call_functions( 'clipbucket_init_completed' );

