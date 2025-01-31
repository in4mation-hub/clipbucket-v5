<?php
define('THIS_PAGE','photo_upload');
define('PARENT_PAGE','upload');

global $userquery,$cbphoto,$Cbucket;

require 'includes/config.inc.php';
$userquery->logincheck();
subtitle(lang('photos_upload'));
if(isset($_GET['collection'])) {
    $selected_collection = $cbphoto->decode_key($_GET['collection']);
    assign('selected_collection',$cbphoto->collection->get_collection($selected_collection));
}

if(isset($_POST['EnterInfo'])) {
    assign('step',2);
    $datas = $_POST['photoIDS'];
    $moreData = explode(",",$datas);
    $details = array();

    foreach($moreData as $key=>$data) {
        $data = str_replace(' ','',$data);
        $data = $cbphoto->decode_key($data);
        $details[] = $data;
    }
    assign('photos',$details);
}

if(isset($_POST['updatePhotos'])) {
    assign('step',3);
}

$collections = $cbphoto->collection->get_collections(array('type'=>'photos','public_upload'=>'yes','user'=>userid()),1);

assign('collections',$collections);
subtitle(lang('photos_upload'));

//Displaying The Template
if (!isSectionEnabled('photos')) {
    e('Photo are disabled the moment');
    $Cbucket->show_page = false;
}

template_files('photo_upload.html');
display_it();
