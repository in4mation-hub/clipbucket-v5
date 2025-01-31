<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Fawaz
	 * Date: 9/3/13
	 * Time: 11:38 AM
	 * To change this template use File | Settings | File Templates.
	 */

	function get_photo_fields() {
		global $cb_columns;
		return $cb_columns->object( 'photos' )->get_columns();
	}

	/**
	 * function used to get photos
	 */
	function get_photos($param)
	{
		global $cbphoto;
		return $cbphoto->get_photos($param);
	}

	//Simple Width Fetcher
	function getWidth($file)
	{
		$sizes = getimagesize($file);
		if($sizes)
			return $sizes[0];
	}

	//Simple Height Fetcher
	function getHeight($file)
	{
		$sizes = getimagesize($file);
		if($sizes)
			return $sizes[1];
	}

	//Load Photo Upload Form
	function loadPhotoUploadForm($params)
	{
		global $cbphoto;
		return $cbphoto->loadUploadForm($params);
	}
	//Photo File Fetcher
	function get_photo($params)
	{
		return get_image_file( $params );
	}

	//Photo Upload Button
	function upload_photo_button($params)
	{
		global $cbphoto;
		return $cbphoto->upload_photo_button($params);
	}

	//Photo Embed Cides
	function photo_embed_codes($params)
	{
		global $cbphoto;
		return $cbphoto->photo_embed_codes($params);
	}

	//Create download button

	function photo_download_button($params)
	{
		global $cbphoto;
		return $cbphoto->download_button($params);
	}

	function add_photo_plupload_javascript_block() {
		if( THIS_PAGE == 'photo_upload' ) {
			return Fetch( JS_DIR.'/plupload/uploaders/photo.plupload.html', true );
		}
	}

	function plupload_photo_uploader() {
		$photoUploaderDetails = array
		(
			'uploadSwfPath' => JS_URL.'/plupload/Moxie.swf',
			'uploadScriptPath' => '/actions/photo_uploader.php?plupload=true',
		);

		assign('photoUploaderDetails',$photoUploaderDetails);
	}


	/**
	 * Function is used to confirm the current photo has photo file saved in
	 * structured folders. If file is found at structured folder, function
	 * will the dates folder structure.
	 *
	 * @param INT|array $photo_id
	 * @return bool|string $directory
	 */
	function get_photo_date_folder( $photo_id )
	{
		global $cbphoto, $db;

		if ( is_array( $photo_id ) ) {
			$photo = $photo_id;
		} else {
			$photo = $cbphoto->get_photo( $photo_id );
		}

		if ( !$photo ) {
			return false;
		}

		/**
		 * Check if file_directory index has value or not
		 */
		if( $photo[ 'file_directory' ] ) {
			$directory = $photo[ 'file_directory' ];
		}

		if ( !$directory )
		{
			/**
			 * No value found. Extract time from filename
			 */
			$random = substr( $photo['filename'], -6, 6 );
			$time = str_replace( $random, '', $photo['filename'] );
			$directory = date('Y/m/d', $time );

			/**
			 * Making sure file exists at path
			 */
			$path = PHOTOS_DIR.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$photo[ 'filename' ].'.'.$photo[ 'ext' ];
			$photo[ 'file_path' ] = $path;
			$photo = apply_filters( $photo, 'checking_photo_at_structured_path' );

			if( file_exists( $photo[ 'file_path' ] ) ) {
				/**
				 * Photo exists, update file_directory index
				 */
				$db->update( tbl( 'photos' ), array( 'file_directory' ), array( $directory ), " photo_id = '".$photo[ 'photo_id' ]."' " );
			} else {
				$directory = false;
			}
		}

		return $directory;
	}

	function get_photo_default_thumb( $size = null, $output = null ) {
		global $cbphoto;
		return $cbphoto->default_thumb( $size, $output );
	}

	function get_image_file( $params )
	{
		global $cbphoto, $Cbucket;
		$details = $params['details'];
		$output = $params['output'] ?? false;
		$static = $params['static'] ?? false;

		$default = array( 't', 'm', 'l', 'o' );
		$size = $params['size'];
		$size = ( !in_array( $size, $default ) or !$size ) ? 't' : $size;

		if( !$details ) {
			return get_photo_default_thumb($size, $output);
		}
		if ($static) {
			return '/files/photos/'.$details['file_directory'].'/'.$details['filename'].'_'.$size.'.'.$details['ext'];
		}

		if ( !is_array( $details ) ) {
			$photo = $cbphoto->get_photo($details);
		} else {
			$photo = $details;
		}

		if ( empty( $photo['photo_id'] ) or empty($photo['photo_key']) ) {
			return get_photo_default_thumb($size, $output);
		}

		if( empty( $photo['filename'] ) or empty($photo['ext']) ) {
			return get_photo_default_thumb($size, $output);
		}

		$params['photo'] = $photo;

		if( isset($Cbucket->custom_get_photo_funcs) && count( $Cbucket->custom_get_photo_funcs ) > 0 ) {
			$functions = $Cbucket->custom_get_photo_funcs;
			foreach( $functions as $func ) {
				if( function_exists( $func ) ) {
					$func_data = $func( $params );
					if( $func_data ) {
						return $func_data;
					}
				}
			}
		}

		$directory = get_photo_date_folder($photo);
		$with_path = $params['with_path'] = ( $params['with_path'] === false ) ? false : true;
		$with_original = isset($params['with_orig']) ? $params['with_orig'] : false;

		if( $directory ) {
			$directory .= '/';
		}

		$path = PHOTOS_DIR.'/'.$directory;
		$filename = $photo['filename'].'%s.'.$photo['ext'];

		$files = glob( $path.sprintf($filename, '*') );
		if ( !empty( $files ) )
		{
			$thumbs = array();
			foreach($files as $file)
			{
				$splitted   = explode("/", $file);
				$thumb_name = end( $splitted );
				$thumb_type = $cbphoto->get_image_type($thumb_name);

				if( $with_original ) {
					$thumbs[] = ( ( $with_path ) ? PHOTOS_URL.'/' : '' ) . $directory . $thumb_name;
				} else if( !empty( $thumb_type ) ) {
					$thumbs[] = ( ( $with_path ) ? PHOTOS_URL.'/' : '' ) . $directory . $thumb_name;
				}
			}

			if ( empty( $output ) or $output == 'non_html' )
			{
				if ( isset($params['assign']) && isset($params['multi']) ) {
					assign( $params['assign'], $thumbs );
				} else if( ( isset($params['multi']) ) ) {
					return $thumbs;
				} else {
					$search_name = sprintf($filename, "_".$size);
					$return_thumb = array_find($search_name, $thumbs);

					if( empty( $return_thumb ) ) {
						return get_photo_default_thumb($size, $output);
					}

					if( isset($params['assign']) ) {
						assign($params['assign'], $return_thumb);
					} else {
						return $return_thumb;
					}
				}
			}

			if ( $output == 'html' )
			{
				$search_name = sprintf( $filename, "_".$size );
				$src = array_find( $search_name, $thumbs );

				$src = ( empty( $src ) ) ? get_photo_default_thumb( $size ) : $src;
				$attrs = array( 'src' => $src );

				$attrs[ 'id' ] = ( ( $params[ 'id' ] ) ? $params[ 'id' ].'_' : 'photo_' ).$photo['photo_id'];

				if( $params['class'] ) {
					$attrs['class'] = mysql_clean($params['class']);
				}

				if ( $params['align'] ) {
					$attrs['align'] = mysql_clean( $params['align'] );
				}

				$attrs['title'] = $photo['photo_title'];

				if ( isset($params['title']) and $params['title'] == '' ) {
					unset($attrs['title']);
				}

				$attrs[ 'alt' ] = TITLE.' - '.$photo['photo_title'];

				$anchor_p = array( "place" => 'photo_thumb', "data" => $photo );
				$params['extra'] = ANCHOR($anchor_p);

				if ( $params['style'] ) {
					$attrs['style'] = ( $params['style'] );
				}

				if ( $params['extra'] ) {
					$attrs['extra'] = ( $params['extra'] );
				}

				$image = cb_create_html_tag( 'img', true, $attrs );

				if ( $params['assign'] ) {
					assign( $params['assign'], $image );
				} else {
					return $image;
				}
			}
		} else {
			return get_photo_default_thumb( $size, $output );
		}
	}

	function get_photo_file( $photo_id, $size = 't', $multi = false, $assign = null, $with_path = true, $with_orig = false )
	{
		$args = array(
			'details' => $photo_id,
			'size' => $size,
			'multi' => $multi,
			'assign' => $assign,
			'with_path' => $with_path,
			'with_orig' => $with_orig
		);

		return get_image_file( $args );
	}