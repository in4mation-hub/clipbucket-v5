<?php
ob_start();
session_start();
 
//Required Files
if( file_exists(__DIR__.'/config.php') ){
    require_once 'config.php'; // New config file
} else {
    require_once 'dbconnect.php'; // Old config file
}
require_once 'functions.php';
require_once('classes/pages.class.php');
require_once('classes/my_queries.class.php');
require_once('classes/user.class.php');
require_once('classes/calcdate.class.php');
require_once('classes/signup.class.php');
require_once('classes/image.class.php');
require_once('classes/upload.class.php');

$pages 		= new pages();
$myquery 	= new myquery();
$userquery 	= new userquery();
$calcdate	= new CalcDate();
$signup 	= new signup();
$Upload 	= new Upload();

$row = $myquery->Get_Website_Details();
$email_data = $myquery->Get_Email_Settings();
$ads = $myquery->Get_Advertisments();

//Website Details

define('TITLE',$row['site_title']);
define('SLOGAN',$row['site_slogan']);

//Seo URLS

define('SEO',$row['seo']); //Set yes / no
define('FFMPEG_BINARY', $row['ffmpegpath']);
define('PHP_PATH', $row['php_path']);

//Registration & Email Settings

define('EMAIL_VERIFICATION',$row['email_verification']);
define('ALLOW_REGISTERATION',$row['allow_registration']);
define('WEBSITE_EMAIL',$email_data['website_email']);
define('SUPPORT_EMAIL',$email_data['support_email']);
define('WELCOME_EMAIL',$email_data['welcome_email']);
define('VIDEO_REQUIRE_LOGIN',@$row['video_require_login']);
define('ACTIVATION',$row['activation']);

//Listing Of Videos , Channels

define('VLISTPP',$row['videos_list_per_page']);				//Video List Per page
define('VLISTPT',$row['videos_list_per_tab']);				//Video List Per tab
define('CLISTPP',$row['channels_list_per_page']);			//Channels List Per page
define('CLISTPT',$row['channels_list_per_tab']);			//Channels List Per tab

//Video Options

define('VIDEO_COMMENT',$row['video_comments']);
define('VIDEO_RATING',$row['video_rating']);
define('COMMENT_RATING',$row['comment_rating']);
define('VIDEO_DOWNLOAD',$row['video_download']);
define('VIDEO_EMBED',$row['video_embed']);

//Required Settings For Video Conversion

define('VBRATE', $row['vbrate']);
define('SRATE', $row['srate']);
define('SBRATE', $row['sbrate']);
define('R_HEIGHT', $row['r_height']);
define('R_WIDTH', $row['r_width']);
define('RESIZE', $row['resize']);
define('KEEP_ORIGINAL', $row['keep_original']);
define('MAX_UPLOAD_SIZE', $row['max_upload_size']);
define('THUMB_HEIGHT', $row['thumb_height']);
define('THUMB_WIDTH', $row['thumb_width']);
define('FFMPEG_TYPE', $row['ffmpeg_type']);

//Required Paths Relative and Direct (Editable In Admin Panel)

if(is_dir($row['basedir'])){
define('BASEDIR',$row['basedir']);
} else {
define('BASEDIR',dirname(__FILE__).'/..');
}
define('BASEURL',$row['baseurl']);							//Direct Path To Script ie http://yourwebsite.com/subdir

define('TEMPLATEFOLDER','styles');							//Template Folder Name, usually STYLES
define('TEMPLATE',$row['template_dir']);					//Select Any Template Name, usually 'clipbucketblue'
define('JSDIR','js');										//Javascript Directory Name
define('ADMINDIR','admin_area');							//Admin Accessible Folder

//DIRECT PATHS OF VIDEO FILES
define('FILES_DIR',BASEDIR.'/files');
define('VIDEOS_DIR',FILES_DIR.'/videos');
define('THUMBS_DIR',FILES_DIR.'/thumbs');
define('ORIGINAL_DIR',FILES_DIR.'/original');
define('TEMP_DIR',FILES_DIR.'/temp');

//DIRECT URL OF VIDEO FILES
define('FILES_URL','/files');
define('VIDEOS_URL',FILES_URL.'/videos');
define('THUMBS_URL',FILES_URL.'/thumbs');
define('ORIGINAL_URL',FILES_URL.'/original');
define('TEMP_URL',FILES_URL.'/temp');

//Do No Edit Below This Line

define('TEMPLATEDIR','/'.TEMPLATEFOLDER.'/'.TEMPLATE);
define('TEMPLATEURL','/'.TEMPLATEFOLDER.'/'.TEMPLATE);
define('LAYOUT',TEMPLATEDIR.'/layout');
define('ADMINLAYOUT',BASEDIR.'/'.ADMINDIR.'/'.TEMPLATEFOLDER.'/'.TEMPLATE.'/layout');

//Assigning Smarty Tags & Values

require BASEDIR.'/includes/templatelib/Template.class.php';
require BASEDIR.'/includes/classes/template.class.php';
require BASEDIR.'/includes/classes/TConfig.php';
require BASEDIR.'/includes/classes/TError.php';
require BASEDIR.'/includes/active.php';
require BASEDIR.'/includes/defined_links.php';

require_once(BASEDIR.'/includes/classes/conversion/ffmpeg.class.php');
