<?php
class Upload
{
 	var $custom_form_fields = array();  //Step 1 of Uploading
	var $custom_form_fields_groups = array() ; //Groups of custom fields
	var $custom_upload_fields = array(); //Step 2 of Uploading
	var $actions_after_video_upload = array('activate_video_with_file');

	/**
	 * Function used to validate upload form fields
	 *
	 * @param null $array
	 * @param bool $is_upload
	 */
	function validate_video_upload_form($array=NULL,$is_upload=FALSE)
	{
		//First Load All Fields in an array
		$required_fields = $this->loadRequiredFields($array);
		$location_fields = $this->loadLocationFields($array);
		$option_fields = $this->loadOptionFields($array);

		$date_recorded = DateTime::createFromFormat(DATE_FORMAT, $location_fields['date_recorded']['value']);
		if( $date_recorded ){
            $location_fields['date_recorded']['value'] = $date_recorded->format('Y-m-d');
        }
		
		if($array==NULL){
			$array = $_POST;
        }
		
		if(is_array($_FILES)){
			$array = array_merge($array,$_FILES);
        }

		//Merging Array
		$upload_fields = array_merge($required_fields,$location_fields,$option_fields);
		
		//Adding Custom Upload Fields
		if(count($this->custom_upload_fields )>0 && $is_upload){
			$upload_fields = array_merge($upload_fields,$this->custom_upload_fields);
        }
		//Adding Custom Form Fields
		if(count($this->custom_form_fields)>0){
			$upload_fields = array_merge($upload_fields,$this->custom_form_fields);
        }

		validate_cb_form($upload_fields,$array);
	}

    /**
     * @throws phpmailerException
     */
    function submit_upload($array=NULL)
	{
		global $eh,$Cbucket,$db,$userquery;
		
		if(!$array){
			$array = $_POST;
        }

		$this->validate_video_upload_form($array,TRUE);

		$errors = $eh->get_error();
		if( empty($errors) )
		{
			$required_fields = $this->loadRequiredFields($array);
			$location_fields = $this->loadLocationFields($array);
			$option_fields = $this->loadOptionFields($array);
			
			$upload_fields = array_merge($required_fields,$location_fields,$option_fields);
			//Adding Custom Upload Fields
			if(count($this->custom_upload_fields)>0){
				$upload_fields = array_merge($upload_fields,$this->custom_upload_fields);
            }
			//Adding Custom Form Fields
			if(count($this->custom_form_fields)>0){
				$upload_fields = array_merge($upload_fields,$this->custom_form_fields);
            }
			
			$userid = userid();
			if( !$userid ){
			    if( has_access('allow_video_upload',true,false) ){
                    $userid = $userquery->get_anonymous_user();
                } else {
			        return false;
                }
            } else if( !has_access('allow_video_upload',true,true) ) {
			    return false;
            }
			
			if(is_array($_FILES)){
				$array = array_merge($array,$_FILES);
            }
		
			foreach($upload_fields as $field)
			{
				$name = formObj::rmBrackets($field['name']);
				$val = $array[$name];
				
				if($field['use_func_val']){
					$val = $field['validate_function']($val);
                }

				if(!empty($field['db_field'])){
					$query_field[] = $field['db_field'];
                }
				
				if(is_array($val)) {
					$new_val = '';
					foreach($val as $v) {
						$new_val .= '#'.$v.'# ';
					}
					$val = $new_val;
				}
				
				if(!$field['clean_func'] || (!apply_func($field['clean_func'],$val) && !is_array($field['clean_func']))){
					$val = mysql_clean($val);
                } else {
					$val = apply_func($field['clean_func'], mysql_clean($val));
                }
				
				if(empty($val) && !empty($field['default_value'])){
					$val = $field['default_value'];
                }
					
				if(!empty($field['db_field'])){
					$query_val[] = $val;
                }
			}
			
			//Adding Video Code
			$query_field[] = 'file_name';
			$file_name = mysql_clean($array['file_name']);
			$query_val[] = $file_name;
			
			//Adding Video Key
			$query_field[] = 'videokey';
			$query_val[] = $this->video_keygen();

			if(!isset($array['file_directory']) && isset($array['time_stamp']))
			{
				$query_field[] = 'file_directory';
				$file_directory = create_dated_folder(NULL,$array['time_stamp']);
				$query_val[] = $file_directory;
			} else if(isset($array['file_directory'])) {
				$query_field[] = 'file_directory';
				$file_directory = mysql_clean($array['file_directory']);
				$query_val[] = $file_directory;
			}

			//Userid
			$query_field[] = 'userid';
			
			if(!$array['userid']){
				$query_val[] = $userid;
            } else {
				$query_val[] = $array['userid'];
            }

			if (isset($array['serverUrl'])) {
				$query_field[] = 'file_thumbs_path';
				$query_val[] = $array['thumbsUrl'];
			}

			//video_version
            $query_field[] = 'video_version';
            $query_val[] = '2.7';

			//thumbs_version
            $query_field[] = 'thumbs_version';
            $query_val[] = '2.8';

			//Upload Ip
			$query_field[] = 'uploader_ip';
			$query_val[] = $_SERVER['REMOTE_ADDR'];
			
			$activation = ACTIVATION;
			
			//Setting Activation Option
			if($activation == 0){
				$active = 'yes';
			} else {
				$active = 'no';
			}
			$query_field[] = 'active';
			$query_val[] = $active;
			
			$query_field[] = 'date_added';
			$query_val[] = dateNow();
			$config_for_mp4 = $Cbucket->configs['stay_mp4'];
			
			if ($config_for_mp4 == 'yes') {
				$query_field[] = 'status';
				$query_val[] = 'Successful';
			}

			$query = 'INSERT INTO '.tbl('video').' (';
			$total_fields = count($query_field);
			
			//Adding Fields to query
			$i = 0;
			foreach($query_field as $qfield)
			{
				$i++;
				$query .= $qfield;
				if($i<$total_fields){
					$query .= ',';
                }
			}
			
			$query .= ') VALUES (';

			$i = 0;
			$query_val[0] = str_replace('&lt;!--', '', $query_val[0]);
			$query_val[1] = str_replace('&lt;!--', '', $query_val[1]);
			
			//Adding Fields Values to query
			foreach($query_val as $qval) {
				$i++;
				$query .= "'$qval'";
				if($i<$total_fields){
					$query .= ',';
                }
			}
			
			//Finalizing Query
			$query .= ')';

			if(!userid() && !has_access('allow_video_upload',false,false))
			{
				e(lang('you_not_logged_in'));
			} else {
				$insert_id = file_name_exists($file_name);
				if(!$insert_id)
				{
					$db->Execute($query);
					$insert_id = $db->insert_id();

					//logging Upload
					$log_array = array(
						'success'=>'yes',
						'action_obj_id' => $insert_id,
						'userid' => $userid,
						'details' => $array['title']
					);
					insert_log('Uploaded a video',$log_array);

					$db->update(tbl('users'),array('total_videos'),array('|f|total_videos+1')," userid='".$userid."'");
				}
			}
		}

		//Adding Video Feed
		addFeed(array('action' => 'upload_video','object_id' => $insert_id,'object'=>'video'));
		return $insert_id;
	}

	/**
	 * Function used to get available name for video thumb
	 *
	 * @param      FILE_Name
	 * @param bool $big
	 *
	 * @return int
	 */
	function get_available_file_num($file_name,$big=false): int
    {
		$code = 1;
        if($big){
			$big = 'big-';
        }

       	if(defined('dir'))
       	{
            while(1){
		  		//setting variable for CB 2.8 greater versions
              	$path = THUMBS_DIR.DIRECTORY_SEPARATOR.dir.DIRECTORY_SEPARATOR.$file_name.'-original-'.$code.'.';
              	if(!file_exists($path.'jpg') && !file_exists($path.'png') && !file_exists($path.'gif')){
              		//setting variable for CB 2.8 lower versions
              		$path = THUMBS_DIR.DIRECTORY_SEPARATOR.dir.DIRECTORY_SEPARATOR.$file_name.'-'.$big.$code.'.';
              	}

		      	if(!file_exists($path.'jpg') && !file_exists($path.'png') && !file_exists($path.'gif')){
		          	break;
                }
			  	$code = $code + 1;
			}
		} else {
            while(1)
			{
		        $path = THUMBS_DIR.DIRECTORY_SEPARATOR.$file_name.'-'.$big.$code.'.';
			    if(!file_exists($path.'jpg') && !file_exists($path.'png') && !file_exists($path.'gif')){
			      	break;
			    }
				$code = $code + 1;
			}
	    }
		return $code;
	}

	function upload_thumb($file_name,$file_array,$key=0,$files_dir=NULL,$thumbs_ver=false)
	{
		global $imgObj;
		$file = $file_array;
		if(!empty($file['name'][$key]))
		{   
			define('dir',$files_dir);
			
			$file_num = $this->get_available_file_num($file_name);
			$ext = getExt($file['name'][$key]);
			if($imgObj->ValidateImage($file['tmp_name'][$key],$ext))
			{
				//One more IF statement considering CB 2.8.1 thumbs strucure
				if (!empty($thumbs_ver) && $thumbs_ver == '2.8')
				{
					$thumbs_settings_28 = thumbs_res_settings_28();
					$temp_file_path = THUMBS_DIR.'/'.$files_dir.'/'.$file_name.'-'.$file_num.'.'.$ext;
					
					$imageDetails = getimagesize($file['tmp_name'][$key]);
					
					move_uploaded_file($file['tmp_name'][$key],$temp_file_path);

					foreach ($thumbs_settings_28 as $key => $thumbs_size) {
						$height_setting = $thumbs_size[1];
						$width_setting = $thumbs_size[0];
						if ( $key != 'original' ){
							$dimensions = implode('x',$thumbs_size);
						} else {
							$dimensions = 'original';
							$width_setting  = $imageDetails[0];
							$height_setting = $imageDetails[1];
						}
						$outputFilePath = THUMBS_DIR.DIRECTORY_SEPARATOR.$files_dir.DIRECTORY_SEPARATOR.$file_name.'-'.$dimensions.'-'.$file_num.'.'.$ext;
						$imgObj->CreateThumb($temp_file_path,$outputFilePath,$width_setting,$ext,$height_setting,false);
					}

					unlink($temp_file_path);
				} else {
					if($files_dir!=NULL){
						$file_path = THUMBS_DIR.DIRECTORY_SEPARATOR.$files_dir.DIRECTORY_SEPARATOR.$file_name.'-'.$file_num.'.'.$ext;
						$big_file_path = THUMBS_DIR.DIRECTORY_SEPARATOR.$files_dir.DIRECTORY_SEPARATOR.$file_name.'-big-'.$file_num.'.'.$ext;
					} else {
						$file_path = THUMBS_DIR.DIRECTORY_SEPARATOR.$file_name.'-'.$file_num.'.'.$ext;
						$big_file_path = THUMBS_DIR.DIRECTORY_SEPARATOR.$file_name.'-big-'.$file_num.'.'.$ext;
					}
					move_uploaded_file($file['tmp_name'][$key],$file_path);
					$imgObj->CreateThumb($file_path,$big_file_path,config('big_thumb_width'),$ext,config('big_thumb_height'),false);
					$imgObj->CreateThumb($file_path,$file_path,config('thumb_width'),$ext,config('thumb_height'),false);
				}

				e(lang('upload_vid_thumb_msg'),'m');
			}	
		}
	}

	/**
	 * Function used to upload video thumbs
	 *
	 * @param      $file_name
	 * @param      $file_array
	 * @param null $files_dir
	 * @param bool $thumbs_ver
	 *
	 * @internal param $FILE_NAME
	 * @internal param array $_FILES name
	 */
	function upload_thumbs($file_name,$file_array,$files_dir=NULL,$thumbs_ver=false)
	{
		if(count($file_array['name'])>1) {
			for($i=0;$i<count($file_array['name']);$i++) {
				$this->upload_thumb($file_name,$file_array,$i,$files_dir,$thumbs_ver);
			}
			e(lang('upload_vid_thumbs_msg'),'m');
		} else {
			$file = $file_array;
			$this->upload_thumb($file_name,$file,$key=0,$files_dir,$thumbs_ver);
		}
	}

	/**
	 * FUNCTION USED TO LOAD UPLOAD FORM REQUIRED FIELDS
	 * title [Text Field]
	 * description [Text Area]
	 * tags [Text Field]
	 * categories [Check Box]
	 *
	 * @param null $default
	 *
	 * @return array
	 */
	function loadRequiredFields($default=NULL): array
    {
		if($default == NULL){
			$default = $_POST;
        }

		$title = $default['title'];
		$desc = $default['description'];

		if(is_array($default['category'])) {
			$cat_array = array($default['category']);
        } else {
			preg_match_all('/#([0-9]+)#/',$default['category'],$m);
			$cat_array = array($m[1]);
		}
		
		$tags = $default['tags'];
		
		$uploadFormRequiredFieldsArray = array(
			/**
			 * this function will create initial array for fields
			 * this will tell
			 * array(
			 *       title [text that will represents the field]
			 *       type [type of field, either radio button, textfield or text area]
			 *       name [name of the fields, input NAME attribute]
			 *       id [id of the fields, input ID attribute]
			 *       value [value of the fields, input VALUE attribute]
			 *       id [name of the fields, input NAME attribute]
			 *       size
			 *       class
			 *       label
			 *       extra_params
			 *       hint_1 [hint before field]
			 *       hint_2 [hint after field]
			 *       anchor_before [before after field]
			 *       anchor_after [anchor after field]
			 *      )
			 */

			'title'	=> array(
				'title'      => lang('vdo_title'),
				'type'       => 'textfield',
				'name'       => 'title',
				'id'         => 'title',
				'value'      => $title,
				'size'       => '45',
				'db_field'   => 'title',
				'required'   => 'yes',
				'min_length' => config('min_video_title'),
				'max_length' => config('max_video_title')
			),
			'desc' => array(
				'title'        => lang('vdo_desc'),
				'type'         => 'textarea',
				'name'         => 'description',
				'class'        => 'desc',
				'value'        => $desc,
				'size'         => '35',
				'extra_params' => ' rows="4"',
				'db_field'     => 'description',
				'required'     => 'yes',
				'anchor_after' => 'after_desc_compose_box'
			),
			'cat' => array(
				'title'             => lang('vdo_cat'),
				'type'              => 'checkbox',
				'name'              => 'category[]',
				'id'                => 'category',
				'value'             => array('category',$cat_array),
				'hint_1'            => sprintf(lang('vdo_cat_msg'),ALLOWED_VDO_CATS),
				'db_field'          => 'category',
				'required'          => 'yes',
				'validate_function' => 'validate_vid_category',
				'invalid_err'       => lang('vdo_cat_err3'),
				'display_function'  => 'convert_to_categories'
			),
			'tags' => array(
				'title'             => lang('tag_title'),
				'type'              => 'textfield',
				'name'              => 'tags',
				'id'                => 'tags',
				'value'             => genTags($tags),
				'hint_1'            => '',
				'hint_2'            => lang('vdo_tags_msg'),
				'db_field'          => 'tags',
				'required'          => 'yes',
				'validate_function' => 'genTags'
			)
		);

		$tracks = $default['tracks'];
		if( !empty($tracks)) {
			$uploadFormRequiredFieldsArray['audio_track'] = array(
				'title'=> lang('track_title'),
				'type'=> 'dropdown',
				'name'=> 'track',
				'id'=> 'track',
				'value'=> $tracks,
				'required'=>'no'
			);
		}

		//Setting Anchors
		$uploadFormRequiredFieldsArray['desc']['anchor_before'] = 'before_desc_compose_box';
		
		//Setting Sizes
		return $uploadFormRequiredFieldsArray;
	}

	/**
	 * FUNCTION USED TO LOAD FORM OPTION FIELDS
	 * broadacast [Radio Button]
	 * embedding [Radio Button]
	 * rating [Radio Button]
	 * comments [Radio Button]
	 * comments rating [Radio Button]
	 *
	 * @param null $default
	 *
	 * @return array
	 */
	function loadOptionFields($default=NULL): array
    {
		if($default == NULL){
			$default = $_POST;
        }
			
		$broadcast = $default['broadcast'] ?? 'public';
		$comments = $default['allow_comments'] ?? 'yes';
		$comment_voting = $default['comment_voting'] ?? 'yes';
		$rating = $default['allow_rating'] ?? 'yes';
		$embedding = $default['allow_embedding'] ?? 'yes';
		
		//Checking weather to enabled or disable password field
		$video_pass_disable = 'disabled="disabled" ';
		$video_user_disable = 'disabled="disabled" ';
		
		if($broadcast=='unlisted'){
			$video_pass_disable = '';
        } else if($broadcast=='private') {
			$video_user_disable = '';
        }
			
		return array(
			'broadcast'=> array(
				'title'             => lang('vdo_br_opt'),
				'type'              => 'radiobutton',
				'name'              => 'broadcast',
				'value'             => array('public'=>lang('vdo_br_opt1'),'private'=>lang('vdo_br_opt2'),
				'unlisted'          => lang('vdo_broadcast_unlisted'),'logged'=>lang('logged_users_only')),
				'checked'           => $broadcast,
				'db_field'          => 'broadcast',
				'required'          => 'no',
				'validate_function' => 'yes_or_no',
				'display_function'  => 'display_sharing_opt',
				'default_value'     => 'public',
				'extra_tags'        => ' onClick="
				    $(this).closest(\'form\').find(\'#video_password\').attr(\'disabled\',\'disabled\');
                    $(this).closest(\'form\').find(\'#video_users\').attr(\'disabled\',\'disabled\');
					if($(this).val()==\'unlisted\'){
					    $(this).closest(\'form\').find(\'#video_password\').attr(\'disabled\',false);
					} else if($(this).val()==\'private\') {
					    $(this).closest(\'form\').find(\'#video_users\').attr(\'disabled\',false);
                    }"
                '
			),
		 	'video_password'=> array(
		 		'title'       => lang('video_password'),
				 'type'       => 'password',
				 'name'       => 'video_password',
				 'id'         => 'video_password',
				 'value'      => $default['video_password'],
				 'db_field'   => 'video_password',
				 'required'   => 'no',
				 'extra_tags' => " $video_pass_disable ",
				 'hint_2'     => lang('set_video_password')
			),
		 	'video_users' => array(
		 		'title'             => lang('video_users'),
				'type'              => 'textarea',
				'name'              => 'video_users',
				'id'                => 'video_users',
				'value'             => $default['video_users'],
				'db_field'          => 'video_users',
				'required'          => 'no',
				'extra_tags'        => " $video_user_disable ",
				'hint_2'            => lang('specify_video_users'),
				'validate_function' => 'video_users',
				'use_func_val'      => true
			),
			'comments'=> array(
				'title'             => lang('comments'),
				'type'              => 'radiobutton',
				'name'              => 'allow_comments',
				'value'             => array('yes'=>lang('vdo_allow_comm'),'no'=>lang('vdo_dallow_comm')),
				'checked'           => $comments,
				'db_field'          => 'allow_comments',
				'required'          => 'no',
				'validate_function' => 'yes_or_no',
				'display_function'  => 'display_sharing_opt',
				'default_value'     => 'yes'
			),
		 	'commentsvote'=> array(
		 		'title'             => lang('vdo_comm_vote'),
				'type'              => 'radiobutton',
				'name'              => 'comment_voting',
				'value'             => array('yes'=>lang('vdo_allow_comm').' Voting','no'=>lang('vdo_dallow_comm').' Voting'),
				'checked'           => $comment_voting,
				'db_field'          => 'comment_voting',
				'required'          => 'no',
				'validate_function' => 'yes_or_no',
				'display_function'  => 'display_sharing_opt',
				'default_value'     => 'yes'
			),
		 	'rating'=> array(
				'title'             => lang('ratings'),
				'type'              => 'radiobutton',
				'name'              => 'allow_rating',
				'value'             => array('yes'=>lang('vdo_allow_rating'),'no'=>lang('vdo_dallow_ratig')),
				'checked'           => $rating,
				'db_field'          => 'allow_rating',
				'required'          => 'no',
				'validate_function' => 'yes_or_no',
				'display_function'  => 'display_sharing_opt',
				'default_value'     => 'yes'
			),
		 	'embedding'=> array(
				'title'             => lang('vdo_embedding'),
				'type'              => 'radiobutton',
				'name'              => 'allow_embedding',
				'value'             => array('yes'=>lang('vdo_embed_opt1'),'no'=>lang('vdo_embed_opt2')),
				'checked'           => $embedding,
				'db_field'          => 'allow_embedding',
				'required'          => 'no',
				'validate_function' => 'yes_or_no',
				'display_function'  => 'display_sharing_opt',
				'default_value'     => 'yes'
			)
		 );
	}

	/**
	 * FUNCTION USED TO LOAD DATE AND LOCATION OPTION OF UPLOAD FORM
	 * - day - month - year
	 * - country
	 * - city
	 *
	 * @param null $default
	 *
	 * @return array
	 */
	function loadLocationFields($default=NULL): array
    {
		global $Cbucket;
		
		if($default == NULL){
			$default = $_POST;
        }

        $date_recorded = date(config('date_format'),time());
		if( isset($default['datecreated']) ){
            $date_recorded = $default['datecreated'];
        }

        $datecreated = DateTime::createFromFormat('Y-m-d', $date_recorded);
        if( $datecreated ){
            $date_recorded = $datecreated->format(DATE_FORMAT);
        }
		
		return array(
			'country' => array(
				'title'         => lang('country'),
				'type'          => 'dropdown',
				'name'          => 'country',
				'id'            => 'country',
				'value'         => $Cbucket->get_countries(),
				'checked'       => $default['country'],
				'db_field'      => 'country',
				'required'      => 'no',
				'default_value' => ''
			),
			'location' => array(
				'title'         => lang('location'),
				'type'          =>'textfield',
				'name'          => 'location',
				'id'            => 'location',
				'value'         => $default['location'],
				'hint_2'        => lang('vdo_add_eg'),
				'db_field'      => 'location',
				'required'      => 'no',
				'default_value' => ''
			),
		 	'date_recorded'	=> array(
				 'title'             => 'Date Recorded',
				 'type'              => 'textfield',
				 'name'              => 'datecreated',
				 'id'                => 'datecreated',
				 'class'             => 'date_field',
				 'anchor_after'      => 'date_picker',
				 'value'             => $date_recorded,
				 'db_field'          => 'datecreated',
				 'required'          => 'no',
				 'default_value'     => '',
				 'use_func_val'      => true,
				 'validate_function' => 'datecreated',
				 'hint_2'            => config('date_format')
			)
		);
	}


	/**
	 * Function used to add files in conversion queue
	 *
	 * @param $file
	 *
	 * @return bool|int
	 */
	function add_conversion_queue($file)
	{
		global $Cbucket,$db;
		$tmp_ext = $Cbucket->temp_exts;

		//Checking file exists or not
		if(file_exists(TEMP_DIR.DIRECTORY_SEPARATOR.$file))
		{
			$ext = strtolower(getExt($file));
			$name = getName($file);
			if(!$name){
				return false;
            }
			//Get Temp Ext
			$tmp_ext = $tmp_ext[rand(0,count($tmp_ext)-1)];
			//Creating New File Name
			$new_file = $name.'.'.$tmp_ext;
			//Renaming File for security purpose

			rename(TEMP_DIR.DIRECTORY_SEPARATOR.$file,TEMP_DIR.DIRECTORY_SEPARATOR.$new_file);
			//Adding Details to database
			$db->Execute('INSERT INTO '.tbl('conversion_queue')." (cqueue_name,cqueue_ext,cqueue_tmp_ext,date_added)
							VALUES ('".mysql_clean($name)."','".mysql_clean($ext)."','".mysql_clean($tmp_ext)."','".NOW()."') ");
			return $db->insert_id();
		}
		return false;
	}

	/**
	 * Video Key Gen
	 * * it is use to generate video key
	 */
	function video_keygen(): string
    {
		$char_list = 'ABDGHKMNORSUXWY';
		$char_list .= '123456789';
		while(1) {
			$vkey = '';
			srand((double)microtime()*1000000);
			for($i = 0; $i < 12; $i++) {
			    $vkey .= substr($char_list,(rand()%(strlen($char_list))), 1);
			}
			
			if(!vkey_exists($vkey)){
			    break;
            }
		}
		
		return $vkey;
	}

	/**
	 * Function used to load upload form
	 */
	function load_upload_options(): array
    {
		global $Cbucket,$Smarty;
		$opt_list = $Cbucket->upload_opt_list;
		
		foreach($opt_list as $opt) {
			$Smarty->register_function($opt['load_func'],$opt['load_func']);
		}
		
		return $opt_list;
	}

	/**
	 * Function used to perform some actions , after video is upload
	 * @param int Videoid
	 */
	function do_after_video_upload($vid)
	{
		foreach($this->actions_after_video_upload as $funcs) {
			if(function_exists($funcs)){
				$funcs($vid);
            }
		}
	}

	/**
	 * Function used to load custom upload fields
	 *
	 * @param      $data
	 * @param bool $ck_display_admin
	 * @param bool $ck_display_user
	 *
	 * @return array
	 */
	function load_custom_upload_fields($data,$ck_display_admin=FALSE,$ck_display_user=FALSE)
    {
		$array = $this->custom_upload_fields;
		foreach($array as $key => $fields)
		{
			$ok = 'yes';
			if($ck_display_admin && $fields['display_admin'] == 'no_display') {
					$ok = 'no';
			}
			
			if($ok=='yes') {
				if(!$fields['value']){
					$fields['value'] = $data[$fields['db_field']];
                }
				$new_array[$key] = $fields;
			}
		}
		
		return $new_array;
	}


	/**
	 * Function used to load custom form fields
	 *
	 * @param      $data
	 * @param bool $insertion
	 * @param bool $group_based
	 * @param bool $user
	 *
	 * @return array : { array } { $new_array } { an array with all custom fields }
	 * { $new_array } { an array with all custom fields }
	 */
	function load_custom_form_fields($data, $insertion = false,$group_based=false, $user = false)
    {
		if(!$group_based) {
			if (function_exists('pull_custom_fields')) {
				if ($user) {
					$array = pull_custom_fields('signup');
				} else {
					$array = pull_custom_fields('video');
				}
			}
			$cleaned = array();

			if (!$insertion) {
				foreach ($array as $key => $field) {
					$cleaned[$key]['title'] = $field['custom_field_title'];
					$cleaned[$key]['type'] = $field['custom_field_type'];
					$cleaned[$key]['name'] = 'cfld_'.$field['custom_field_name'];
					$cleaned[$key]['value'] = $field['custom_field_value'];
					$cleaned[$key]['db_field'] = 'cfld_'.$field['custom_field_name'];
				}
			} else {
				foreach ($array as $key => $field) {
					$cleaned[$field['custom_field_name']]['title'] = $field['custom_field_title'];
					$cleaned[$field['custom_field_name']]['type'] = $field['custom_field_type'];
					$cleaned[$field['custom_field_name']]['name'] = 'cfld_'.$field['custom_field_name'];
					$cleaned[$field['custom_field_name']]['value'] = $field['custom_field_value'];
					$cleaned[$field['custom_field_name']]['db_field'] = 'cfld_'.$field['custom_field_name'];
				}
			}
			foreach($cleaned as $key => $fields) {
                if($data[$fields['db_field']]) {
                    $value = $data[$fields['db_field']];
                } elseif($data[$fields['name']]) {
                    $value = $data[$fields['name']];
                }
                if($fields['type']=='radiobutton' || $fields['type']=='checkbox' || $fields['type']=='dropdown') {
                    $fields['checked'] = $value;
                }

                $new_array[$key] = $fields;
			}
			return $new_array;
		}
		return $this->custom_form_fields_groups;
	}


	/**
	 * function used to upload user avatar and or background
	 *
	 * @param string $type
	 * @param        $file
	 * @param        $uid
	 *
	 * @return string|bool
	 */
	function upload_user_file($type,$file,$uid)
    {
		global $userquery,$cbphoto,$imgObj;
		$av_details = getimagesize($file['tmp_name']);
		
		if($userquery->user_exists($uid))
		{
			switch($type)
			{
				case 'a':
				case 'avatar':
					if($file['size']/1024 > config('max_profile_pic_size')){
						e(sprintf(lang('file_size_exceeds'),config('max_profile_pic_size')));
					} elseif($av_details[0] > config('max_profile_pic_width')) {
						e(lang('File width exeeds').' '.config('max_profile_pic_width').'px');
					} elseif(file_exists($file['tmp_name'])) {
						$ext = getext($file['name']);
						$file_name = $uid.'.'.$ext;
						$file_path = AVATARS_DIR.DIRECTORY_SEPARATOR.$file_name;
						if(move_uploaded_file($file['tmp_name'],$file_path)) {
							if(!$imgObj->ValidateImage($file_path,$ext)) {
								e(lang('Invalid file type'));
								@unlink($file_path);
							} else {
								$small_size = AVATARS_DIR.DIRECTORY_SEPARATOR.$uid.'-small.'.$ext;
								$cbphoto->CreateThumb($file_path,$file_path,$ext,AVATAR_SIZE,AVATAR_SIZE);
								$cbphoto->CreateThumb($file_path,$small_size,$ext,AVATAR_SMALL_SIZE,AVATAR_SMALL_SIZE);
							}
						} else {
							e(lang('class_error_occured'));
						}
					}
				    break;
			}
			return $file_name;
		} else {
			e(lang('user_doesnt_exist'));
        }
		return false;
	}
	
	
	/** 
	 * Function used to upload website logo
	 * @param logo_file
	 * @return string $file_name.'.'.$ext;
	 */
	function upload_website_logo($file)
	{
		global $imgObj;

		if(!empty($file['name'])) {
			$ext = getExt($file['name']);
			$file_name = 'plaery-logo';
			if($imgObj->ValidateImage($file['tmp_name'],$ext)) {
				$file_path = BASEDIR.'/images/'.$file_name.'.'.$ext;
				if(file_exists($file_path)){
					if(!unlink($file_path)) {
						e("Unable to remove '$file_path' , please chmod it to 0777");
						return false;
					}
                }

				move_uploaded_file($file['tmp_name'],$file_path);
				e('Logo has been uploaded','m');
				return $file_name.'.'.$ext;
			} else {
				e('Invalid Image file');
            }
		}
		return false;
	}

	/**
	 * load_video_fields
	 * 
	 * @param array $input default values for all videos
	 * @return array of video fields
	 *
	 * Function used to load Video fields
	 * in clipbucket v2.5 , video fields are loaded in form of groups arrays
	 * each group has it name and fields wrapped in array 
	 * and that array will be part of video fields
	 */
	function load_video_fields($input): array
    {
		$fields = array(
			array(
				'group_name' => lang('required_fields'),
				'group_id'	 => 'required_fields',
				'fields'	 => $this->loadRequiredFields($input)
			),
			array(
				'group_name' => lang('vdo_share_opt'),
				'group_id'	 => 'sharing_fields',
				'fields'	 => $this->loadOptionFields($input)
			),
			array(
				'group_name' => lang('date_recorded_location'),
				'group_id'	 => 'date_location_fields',
				'fields'	 => $this->loadLocationFields($input)
			)
		);
		
		//Adding Custom Fields
		$custom_fields = $this->load_custom_form_fields($input,false);
		
		if($custom_fields) {
			$more_fields_group = array(
				'group_name' => lang('more_fields'),
				'group_id'	 => 'custom_fields',
				'fields'	 => $custom_fields
			);
		}
		
		//Adding Custom Fields With Groups
		$custom_fields_with_group = $this->load_custom_form_fields($input,true);
		
		//Finally putting them together in their main array called $fields
		if($custom_fields_with_group)
		{
			$custFieldGroups = $custom_fields_with_group;
		
			foreach($custFieldGroups as $gKey => $fieldGroup)
			{
				foreach($fieldGroup['fields'] as $mainKey => $nField)
				{
					$updatedNewFields[$mainKey] = $nField;
					if($input[$nField['db_field']]){
						$value = $input[$nField['db_field']];
                    } elseif($input[$nField['name']]) {
						$value = $input[$nField['name']];
                    }
						
					if($nField['type']=='radiobutton' || $nField['type']=='checkbox' || $nField['type']=='dropdown'){
						$updatedNewFields[$mainKey]['checked'] = $value;
                    } else {
						$updatedNewFields[$mainKey]['value'] = $value;
                    }
				}
				
				$fieldGroup['fields'] = $updatedNewFields;
				$group_id = $fieldGroup['group_id'];
				
				foreach($fields as $key => $field)
				{
					if($field['group_id'] == $group_id)
					{
						$inputFields = $field['fields'];
						//Setting field values
						$newFields = $fieldGroup['fields'];
						$mergeField = array_merge($inputFields,$newFields);

						//Finally Updating array
						$newGroupArray = array(
							'group_name' => $field['group_name'],
							'group_id'   => $field['group_id'],
							'fields'     => $mergeField
						);
						
						$fields[$key] = $newGroupArray;
						
						$matched = true;
						break;
					}
					$matched = false;
				}

				if(!$matched){
					$fields[] = $fieldGroup;
                }
			}
		}
		
		if($more_fields_group){
			$fields[] = $more_fields_group;
        }
				
		return $fields;
	}
	
	function isTime($time)
	{
		preg_match('/(([0-9]?[0-9]{1}):)?([0-5]{1}[0-9]{1}):([0-5]{1}[0-9]{1})/',$time,$match);
		if(!empty($match[0])){
			return ($match[0]);
        }
		return false;
	}
}	
