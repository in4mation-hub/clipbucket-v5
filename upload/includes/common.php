<?php
ob_start();
define('IN_CLIPBUCKET',true);

//Setting Cookie Timeout
define('COOKIE_TIMEOUT',86400*1); // 1
define('GARBAGE_TIMEOUT',COOKIE_TIMEOUT);
define("REMBER_DAYS",7);

define("DEV_INGNORE_SYNTAX",TRUE);

//Create an empty development.dev file in includes folder
//To Activate Development mode

if(file_exists(dirname(__FILE__).'/development.dev')) {
    define("DEVELOPMENT_MODE",true);
    $__devmsgs = array(
        'insert_queries'=>array(),
        'select_queries'=>array(),
        'update_queries'=>array(),
        'delete_queries'=>array(),
        'count_queries'=>array(),
        'execute_queries'=>array(),
        'insert'=>"0",
        'select'=>"0",
        'update'=>"0",
        'delete'=>"0",
        'count'=>"0",
        'execute'=>"0",
        'total_queries'=>"0",
        'total_query_exec_time'=>"0",
        'total_memory_used'=>"0",
        'expensive_query'=>'',
        'cheapest_query'=>''
    );
} else {
    define("DEVELOPMENT_MODE",false);
}

if(!@$in_bg_cron) {
    //Setting Session Max Life
    ini_set('session.gc_maxlifetime', GARBAGE_TIMEOUT);
    session_set_cookie_params(COOKIE_TIMEOUT,'/');
    session_start();
}

//Required Files
require_once('classes/db.class.php');
# file with most frequently used functions
require_once('functions.php');
check_install('before');

if( file_exists(__DIR__.'/config.php') ){
    require_once 'config.php'; // New config file
} else {
    require_once 'dbconnect.php'; // Old config file
}

# class for storing common ClipBucket functions
require_once('classes/ClipBucket.class.php');
require_once('classes/columns.class.php');
require_once('classes/my_queries.class.php');
require_once('classes/actions.class.php');
require_once('classes/category.class.php');
require_once('classes/user.class.php');
require_once('classes/lang.class.php');
require_once('classes/pages.class.php');
require_once('classes/helper.class.php');

$cb_columns = new cb_columns();
$myquery = new myquery();
$row = $myquery->Get_Website_Details();

if( !in_dev() ) {
    define('DEBUG_LEVEL', 0);
} else {
    define('DEBUG_LEVEL', 2);
}

switch(DEBUG_LEVEL) {
    case 0:
        error_reporting(0);
        ini_set('display_errors', '0');
        break;

    case 1:
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 'on');
        break;

    case 2:
    default:
        error_reporting(E_ALL & ~(E_NOTICE | E_DEPRECATED | E_STRICT | E_WARNING));
        ini_set('display_errors', 'on');
}

$pages = new pages();
$ClipBucket = $Cbucket = new ClipBucket();
define('BASEDIR',$Cbucket->BASEDIR);
if(!file_exists(BASEDIR.'/index.php'))
die('Basedir is incorrect, please set the correct basedir value in \'config\' table');
$baseurl = $row['baseurl'];

if (is_ssl()) {
    define('CB_SSL', true);
    $baseurl = str_replace('http://', 'https://', $baseurl);
} else {
    define('CB_SSL', false);
    $baseurl = str_replace('https://', 'http://', $baseurl);
}

//Removing www. as it effects SEO and updating Config
$wwwcheck = preg_match('/:\/\/www\./',$baseurl,$matches);
if(count($matches)>0) {
    $baseurl = preg_replace('/:\/\/www\./','://',$baseurl);
}

$clean_base = false;
if(defined("CLEAN_BASEURL")) {
    $clean_base = CLEAN_BASEURL;
}

define('BASEURL',$baseurl);
$userquery 	= new userquery();
$lang_obj	= new language;

//Setting Time Zone date_default_timezone_set
require_once('classes/search.class.php');
require_once('classes/calcdate.class.php');
require_once('classes/signup.class.php');
require_once('classes/image.class.php');
require_once('classes/upload.class.php');
require_once('classes/ads.class.php');
require_once('classes/form.class.php');
require_once('classes/plugin.class.php');
require_once('classes/errorhandler.class.php');
require_once('classes/session.class.php');
require_once('classes/log.class.php');
require_once('classes/swfObj.class.php');
require_once('classes/image.class.php');
require_once('classes/video.class.php');
require_once('classes/player.class.php');
require_once('classes/cbemail.class.php');
require_once('classes/pm.class.php');
require_once('classes/cbpage.class.php');
require_once('classes/reindex.class.php');
require_once('classes/collections.class.php');
require_once('classes/photos.class.php');
require_once('classes/cbfeeds.class.php');
require_once('classes/resizer.class.php');
require_once('classes/translation.class.php');

//Adding Gravatar
require_once('classes/gravatar.class.php');
require 'defined_links.php';
require_once 'languages.php';
$lang_obj->init();
$LANG = $lang_obj->lang_phrases('file');

$calcdate	= new CalcDate();
$signup 	= new signup();
$Upload 	= new Upload();
$adsObj		= new AdsManager();
$formObj	= new formObj();

$cbplugin	= new CBPlugin();
$eh			= new errorhandler();

$sess		= new Session();
$cblog		= new CBLogs();
$imgObj		= new ResizeImage();
$cbvideo	= $cbvid = new CBvideo();
$cbplayer	= new CBPlayer();
$cbemail	= new CBEmail();
$cbsearch	= new CBSearch();
$cbpm		= new cb_pm();
$cbpage		= new cbpage();
$cbindex	= new CBreindex();
$cbcollection = new Collections();
$cbphoto    = new CBPhotos();

$cbfeeds 	= new cbfeeds();
//$GoogleTranslator = new MrsTranslator($Clientid, $secretId);
$GoogleTranslator = new GoogleTranslator();

check_install('after');
@include($Cbucket->BASEDIR.'/includes/clipbucket.php');
$Cbucket->cbinfo = array("version"=>VERSION,"state"=>STATE,"rev"=>REV);

# Holds Advertisment IDS that are being Viewed
$ads_array = array();

# Website Details
define('CB_VERSION', $row['version']);
define('TITLE', $row['site_title']);
if(!defined('SLOGAN')){
    define('SLOGAN',$row['site_slogan']);
}

# Seo URLS
define('SEO',$row['seo']); //Set yes / no

# Registration & Email Settings
define('EMAIL_VERIFICATION',$row['email_verification']);
define('ALLOW_REG',getArrayValue($row, 'allow_registration'));
define('WEBSITE_EMAIL',$row['website_email']);
define('SUPPORT_EMAIL',$row['support_email']);
define('WELCOME_EMAIL',$row['welcome_email']);
@define('VIDEO_REQUIRE_LOGIN',$row['video_require_login']);
define('ACTIVATION',$row['activation']);
define('DATE_FORMAT', config('date_format'));

# Listing Of Videos , Channels
define('VLISTPP',$row['videos_list_per_page']);				//Video List Per page
define('VLISTPT',$row['videos_list_per_tab']);				//Video List Per tab
define('CLISTPP',$row['channels_list_per_page']);			//Channels List Per page
define('CLISTPT',$row['channels_list_per_tab']);			//Channels List Per tab
define('SLISTPP',$row['search_list_per_page']);				//Search Results List Per page
define('RVLIST',$row['recently_viewed_limit']);				//Search Results List Per page

# Defining Photo Limits
define('MAINPLIST',$row['photo_main_list']);
define('HOMEPLIST',$row['photo_home_tabs']);
define('SEARCHPLIST',$row['photo_search_result']);
define('CHANNELPLIST',$row['photo_channel_page']);
define('USERPLIST',$row['photo_user_photos']);
define('UFAVPLIST',$row['photo_user_favorites']);
define('OTHERPLIST',$row['photo_other_limit']);

# Defining Collection Limits
define('COLLPP',$row['collection_per_page']);
define('COLLHP',$row['collection_home_page']);
define('COLLIP',$row['collection_items_page']);
define('COLLSP',$row['collection_search_result']);
define('COLLCP',$row['collection_channel_page']);
define('COLLUCP',$row['collection_user_collections']);
define('COLLUFP',$row['collection_user_favorites']);

# Video Options
define('VIDEO_COMMENT',$row['video_comments']);
define('VIDEO_RATING',$row['video_rating']);
define('COMMENT_RATING',$row['comment_rating']);
define('VIDEO_DOWNLOAD',$row['video_download']);
define('VIDEO_EMBED',$row['video_embed']);
define('TEMPLATEFOLDER','styles');							//Template Folder Name, usually STYLES
define('STYLES_DIR',BASEDIR.DIRECTORY_SEPARATOR.TEMPLATEFOLDER);

# Define Lang Select & Style Select

define('ALLOW_LANG_SELECT',$row['allow_language_change']);
define('ALLOW_STYLE_SELECT',$row['allow_template_change']);
define('SUBTITLE',$row['code_dev']);
//Javascript Directory Name
define('ADMINDIR','admin_area');
define('ADMINBASEDIR',BASEDIR.DIRECTORY_SEPARATOR.'admin_area');				//Admin Accessible Folder
define('ADMIN_BASEURL',DIRECTORY_SEPARATOR.ADMINDIR);

# DIRECT PATHS OF VIDEO FILES
define('FILES_DIR',BASEDIR.'/files');
define('VIDEOS_DIR',FILES_DIR.'/videos');
define('THUMBS_DIR',FILES_DIR.'/thumbs');
define('AUDIOS_DIR',FILES_DIR.'/audios');
define('SPRITES_DIR',FILES_DIR.'/sprites');
define('ORIGINAL_DIR',FILES_DIR.'/original');
define('TEMP_DIR',FILES_DIR.'/temp');
define('CON_DIR',FILES_DIR.'/conversion_queue');
define('MASS_UPLOAD_DIR',FILES_DIR.'/mass_uploads');
define('LOGS_DIR',FILES_DIR.'/logs');
define('IMAGES_DIR', BASEDIR.'/images' );
define('IMAGES_URL', '/images' );
define("USER_THUMBS_DIR",BASEDIR.'/images/avatars');
define("USER_BG_DIR",BASEDIR.'/images/backgrounds');
define("ICONS_URL",'/images/icons');
define('JS_DIR',BASEDIR.'/js');
define('JS_URL','/js');

#DIRECT URL OF VIDEO FILES
define('FILES_URL',BASEURL.'/files');
define('VIDEOS_URL',FILES_URL.'/videos');
define('THUMBS_URL',FILES_URL.'/thumbs');
define('SPRITES_URL',FILES_URL.'/sprites');
define('ORIGINAL_URL',FILES_URL.'/original');
define('TEMP_URL',FILES_URL.'/temp');
define('PLAYER_DIR',BASEDIR.'/player');
define('PLAYER_URL','/player');

define('USER_THUMBS_URL','/images/avatars');
define('USER_BG_URL','/images/backgrounds');

# Required Settings For Video Conversion
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
define('PHP_PATH', $row['php_path']);

# Defining Plugin Directory
define('PLUG_DIR',BASEDIR.'/plugins');
define('PLUG_URL','/plugins');

define('MAX_COMMENT_CHR',$Cbucket->configs['max_comment_chr']);
define('USER_COMMENT_OWN',$Cbucket->configs['user_comment_own']);

# Defining Category Thumbs directory
define('CAT_THUMB_DIR',BASEDIR.'/images/category_thumbs');
define('CAT_THUMB_URL','/images/category_thumbs');

# COLLECTIONS ICON DIR
define('COLLECT_THUMBS_DIR',BASEDIR.'/images/collection_thumbs');
define('COLLECT_THUMBS_URL','/images/collection_thumbs');

# PHOTOS DETAILS
define('PHOTOS_DIR',FILES_DIR."/photos");
define('PHOTOS_URL',"/files/photos");

# AVATARS DIR
define('AVATARS_DIR',FILES_DIR."/avatars");
define('AVATARS_URL',"/files/avatars");

# LOGOS DIR
define('LOGOS_DIR',FILES_DIR."/logos");
define('LOGOS_URL',"/files/logos");

# ADVANCE CACHING
define('CACHE_DIR',BASEDIR.'/cache');
define('COMM_CACHE_DIR',CACHE_DIR.'/comments');
define('COMM_CACHE_TIME',1000) ; //in seconds

# User Feeds
define("USER_FEEDS_DIR",CACHE_DIR.'/userfeeds');

# Number of activity feeds to display on channel page
define("USER_ACTIVITY_FEEDS_LIMIT",15);

# SETTING PHOTO SETTING
$cbphoto->thumb_width = $row['photo_thumb_width'];
$cbphoto->thumb_height = $row['photo_thumb_height'];
$cbphoto->mid_width = $row['photo_med_width'];
$cbphoto->mid_height = $row['photo_med_height'];
$cbphoto->lar_width = $row['photo_lar_width'];
$cbphoto->cropping = $row['photo_crop'];
$cbphoto->position = $row['watermark_placement'];

# Enable youtube videos
define("EMBED_VDO_WIDTH",$row['embed_player_width']);
define("EMBED_VDO_HEIGHT",$row['embed_player_height']);

# Checking Website Template
include 'plugin.functions.php';
include 'plugins_functions.php';

require BASEDIR.'/includes/classes/template.class.php';
require BASEDIR.'/includes/classes/objects.class.php';
$cbtpl = new CBTemplate();

# STOP CACHING
$cbtpl->caching = 0;
$cbobjects = new CBObjects();
$swfobj	   = new SWFObject();

# Initializng Userquery class
$userquery->init();
$cbvideo->init();
$cbpm->init();
$cbphoto->init_photos();
$thisurl = curPageURL();

$Cbucket->set_the_template();

require_once BASEDIR.'/includes/smartyv3/bootstrap.php';

$cbtpl->init();
require BASEDIR.'/includes/active.php';
Assign('THIS_URL', $thisurl);
define("ALLOWED_GROUP_CATEGORIES",$row['grp_categories']);
define('ALLOWED_VDO_CATS',$row['video_categories']);
define('ALLOWED_CATEGORIES',3);

$ClipBucket->initAdminMenu();

# Assigning Smarty Tags & Values
Assign('CB_VERSION',CB_VERSION);
Assign('PHP_PATH',PHP_PATH);
Assign('FFMPEG_BINARY',getConstant('FFMPEG_BINARY'));
Assign('FFMPEG_MENCODER_BINARY',getConstant('FFMPEG_MENCODER_BINARY'));
Assign('js',JS_URL);
Assign('title',TITLE);
Assign('slogan',SLOGAN);
Assign('avatardir','/images/avatars');
Assign('whatis',getArrayValue($row, 'whatis'));
Assign('category_thumbs',CAT_THUMB_URL);
Assign('video_thumbs',THUMBS_URL);

Assign('email_verification',EMAIL_VERIFICATION);
Assign('bg_dir','/images/backgrounds');
Assign('captcha_type',$row['captcha_type']);
Assign('languages',(isset($languages)) ? $languages : false);

Assign('VIDEOS_URL',VIDEOS_URL);
Assign('THUMBS_URL',THUMBS_URL);
Assign('PLUG_URL','/plugins');

#Remote and Embed
Assign('remoteUpload',$row['remoteUpload']);
Assign('embedUpload',$row['embedUpload']);

# Video Options
Assign('video_comment',$row['video_comments']);
Assign('video_rating',$row['video_rating']);
Assign('comment_rating',$row['comment_rating']);
Assign('video_download',$row['video_download']);
Assign('video_embed',$row['video_embed']);
assign('icons_url',ICONS_URL);
define( 'PLAYLIST_COVERS_DIR', IMAGES_DIR.'/playlist_covers' );
define( 'PLAYLIST_COVERS_URL', IMAGES_URL.'/playlist_covers' );
assign('development_mode', DEVELOPMENT_MODE);

if (!file_exists( PLAYLIST_COVERS_DIR)) {
    mkdir(PLAYLIST_COVERS_DIR, 0777);
}

$ClipBucket->upload_opt_list = array();

if( config('load_upload_form') == 'yes' ){
    $ClipBucket->upload_opt_list['file_upload_div'] = array(
        'title'      => lang('upload_file'),
        'func_class' => 'Upload',
        'load_func'  => 'load_upload_form',
    );
}

if( config('load_remote_upload_form') == 'yes' ){
    $ClipBucket->upload_opt_list['remote_upload_div'] = array(
        'title'      => lang('remote_upload'),
        'func_class' => 'Upload',
        'load_func'  => 'load_remote_upload_form',
    );
}

Assign('LANG',$LANG);
Assign('langf',getConstant('LANG'));
Assign('lang_count',(isset($languages)) ? count($languages) : false);

# Configuration of time format
$config['date'] = '%I:%M %p';
$config['time'] = '%H:%M';
assign('config', $config);
# Assign Player Div Id
Assign('player_div_id',$row['player_div_id']);

# Assigning Page
Assign('page',getConstant('PAGE'));

# REGISTER OBJECTS FOR SMARTY
global $Smarty;
$Smarty->assign_by_ref('pages', $pages);
$Smarty->assign_by_ref('myquery', $myquery);
$Smarty->assign_by_ref('userquery', $userquery);
$Smarty->assign_by_ref('signup', $signup);
$Smarty->assign_by_ref('Upload', $Upload);
$Smarty->assign_by_ref('db', $db);
$Smarty->assign_by_ref('adsObj', $adsObj);
$Smarty->assign_by_ref('formObj', $formObj);
$Smarty->assign_by_ref('Cbucket', $Cbucket);
$Smarty->assign_by_ref('ClipBucket', $Cbucket);
$Smarty->assign_by_ref('eh', $eh);
$Smarty->assign_by_ref('lang_obj', $lang_obj);
$Smarty->assign_by_ref('cbvid', $cbvid);
$Smarty->assign_by_ref('cbtpl',$cbtpl);
$Smarty->assign_by_ref('cbobjects',$cbobjects);
$Smarty->assign_by_ref('cbplayer',$cbplayer);
$Smarty->assign_by_ref('cbsearch',$cbsearch);
$Smarty->assign_by_ref('cbpm',$cbpm);
$Smarty->assign_by_ref('cbpage',$cbpage);
$Smarty->assign_by_ref('cbemail',$cbemail);
$Smarty->assign_by_ref('cbcollection',$cbcollection);
$Smarty->assign_by_ref('cbphoto',$cbphoto);
$Smarty->assign_by_ref('cbfeeds',$cbfeeds);

# REGISTERING FUNCTION FOR SMARTY TEMPLATES
function show_video_rating($params){ global $cbvid; return $cbvid->show_video_rating($params); }

$Smarty->register_function('AD','getAd');
$Smarty->register_function('get_thumb','getSmartyThumb');
$Smarty->register_function('getThumb','getSmartyThumb');
$Smarty->register_function('videoLink','videoSmartyLink');
$Smarty->register_function('show_rating','show_rating');
$Smarty->register_function('ANCHOR','ANCHOR');
$Smarty->register_function('FUNC','FUNC');
$Smarty->register_function('avatar','avatar');
$Smarty->register_function('load_form','load_form');
$Smarty->register_function('get_all_video_files', 'get_all_video_files_smarty');
$Smarty->register_function('input_value','input_value');
$Smarty->register_function('userid','userid');
$Smarty->register_function('FlashPlayer','flashPlayer');
$Smarty->register_function('link','cblink');
$Smarty->register_function('show_share_form','show_share_form');
$Smarty->register_function('show_flag_form','show_flag_form');
$Smarty->register_function('show_playlist_form','show_playlist_form');
$Smarty->register_function('show_collection_form','show_collection_form');
$Smarty->register_function('lang','smarty_lang');
$Smarty->register_function('get_videos','get_videos');
$Smarty->register_function('get_users','get_users');
$Smarty->register_function('get_photos','get_photos');
$Smarty->register_function('get_collections','get_collections');
$Smarty->register_function('private_message','private_message');
$Smarty->register_function('show_video_rating','show_video_rating');
$Smarty->register_function('load_captcha','load_captcha');
$Smarty->register_function('cbtitle','cbtitle');
$Smarty->register_function('head_menu','head_menu');
$Smarty->register_function('foot_menu','foot_menu');
$Smarty->register_function('include_header','include_header');
$Smarty->register_function('include_template_file','include_template_file');
$Smarty->register_function('include_js','include_js');
$Smarty->register_function('get_binaries','get_binaries');
$Smarty->register_function('check_module_path','check_module_path');
$Smarty->register_function('rss_feeds','rss_feeds');
$Smarty->register_function('website_logo','website_logo');
$Smarty->register_function('get_photo','get_image_file');
$Smarty->register_function('uploadButton','upload_photo_button');
$Smarty->register_function('embedCodes','photo_embed_codes');
$Smarty->register_function('DownloadButtonP','photo_download_button');
$Smarty->register_function('loadPhotoUploadForm','loadPhotoUploadForm');
$Smarty->register_function('cbCategories','getSmartyCategoryList');
$Smarty->register_function('getCbCategories','getSmartyCategoryList');
$Smarty->register_function('getComments','getSmartyComments');
$Smarty->register_function('cbMenu','cbMenu');

$Smarty->register_modifier('SetTime','SetTime');
$Smarty->register_modifier('getname','getname');
$Smarty->register_modifier('getext','getext');
$Smarty->register_modifier('post_form_val','post_form_val');
$Smarty->register_modifier('get_thumb_num','get_thumb_num');
$Smarty->register_modifier('ad','ad');
$Smarty->register_modifier('get_user_level','get_user_level');
$Smarty->register_modifier('is_online','is_online');
$Smarty->register_modifier('get_age','get_age');
$Smarty->register_modifier('outgoing_link','outgoing_link');
$Smarty->register_modifier('nicetime','nicetime');
$Smarty->register_modifier('country','get_country');
$Smarty->register_modifier('flag_type','flag_type');
$Smarty->register_modifier('get_username','get_username');
$Smarty->register_modifier('formatfilesize','formatfilesize');
$Smarty->register_modifier('getWidth','getWidth');
$Smarty->register_modifier('getHeight','getHeight');

assign('updateEmbedCode','updateEmbed');
# Registering Video Remove Functions
register_action_remove_video('remove_video_thumbs');
register_action_remove_video('remove_video_log');
register_action_remove_video('remove_video_files');
register_anchor_function( 'add_photo_plupload_javascript_block', 'cb_head' );
cb_register_function( 'plupload_photo_uploader', 'uploaderDetails' );

cb_register_action( 'increment_playlist_played', 'view_playlist' );

include('admin.functions.php');
# Other settings
define("SEND_COMMENT_NOTIFICATION",config("send_comment_notification"));
define("SEND_VID_APPROVE_EMAIL",config("approve_video_notification"));
