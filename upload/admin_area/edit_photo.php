<?php
global $userquery,$pages,$cbphoto;
define('THIS_PAGE','edit_photo');
require_once '../includes/admin_config.php';

$userquery->admin_login_check();
$userquery->login_check('video_moderation');
$pages->page_redir();

// TODO : Complete URL
/* Generating breadcrumb */
global $breadcrumb;
$breadcrumb[0] = array('title' => 'Photos', 'url' => '');
$breadcrumb[1] = array('title' => 'Edit Photo', 'url' => '');

$id = mysql_clean($_GET['photo']);

if(isset($_POST['photo_id'])) {
    $cbphoto->update_photo();
}

//Performing Actions
if($_GET['mode'] != '') {
    $cbphoto->photo_actions($_GET['mode'],$id);
}

$p = $cbphoto->get_photo($id);
$p['user'] = $p['userid'];

assign('data',$p);

$requiredFields = $cbphoto->load_required_forms($p);
$otherFields = $cbphoto->load_other_forms($p);
assign('requiredFields',$requiredFields);
assign('otherFields',$otherFields);

subtitle('Edit Photo');
template_files('edit_photo.html');
display_it();
