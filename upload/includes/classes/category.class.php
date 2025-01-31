<?php

abstract class CBCategory
{
	var $cat_tbl = ''; //Name of category Table
	var $section_tbl = ''; //Name of table that related to $cat_tbl
	var $use_sub_cats = FALSE; // Set to true if you using Sub-Categories
	var $cat_thumb_height = '125';
	var $cat_thumb_width = '125';
	var $default_thumb = 'no_thumb.jpg';

	/**
	 * Function used to check weather category exists or not
	 *
	 * @param $cid
	 *
	 * @return bool|array
	 */
	function category_exists($cid)
	{
		return $this->get_category($cid); 
	}

	/**
	 * Function used to get category details
	 *
	 * @param $cid
	 *
	 * @return bool|array
	 */
	function get_category($cid)
	{
		$cid = mysql_clean($cid);

		global $db;
		$results = $db->select(tbl($this->cat_tbl),'*',' category_id=\''.$cid.'\' ');
		if(count($results)>0){
			return $results[0];
        }
		return false;
	}

	/**
	 * Function used to get category name
	 *
	 * @param $cid
	 *
	 * @return bool
	 */
	function get_category_name($cid)
	{
		$cid = mysql_clean($cid);

		global $db;
		$results = $db->select(tbl($this->cat_tbl),'category_name',' category_id=\''.$cid.'\' ');
		if(count($results)>0){
			return $results[0];
        }
		return false;
	}

	/**
	 * Function used to get category by name
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	function get_cat_by_name($name)
	{
		$name = mysql_clean($name);

		global $db;
		$results = $db->select(tbl($this->cat_tbl),'*',' category_name=\''.$name.'\' ');
		if(count($results)>0){
			return $results[0];
        }
		return false;
	}

	/**
	 * Function used to add new category
	 *
	 * @param $array
	 */
	function add_category($array)
	{
		global $db;
		$name = $array['name'];
		$desc = $array['desc'];
		$default = mysql_clean($array['default']);
		
		$flds = array('category_name','category_desc','date_added');
		$values = array($name,$desc,now());
		
		if(!empty($this->use_sub_cats)) {
			$parent_id = mysql_clean($array['parent_cat']);
			$flds[] = 'parent_id';
			$values[] = $parent_id;	
		}
		
		if($this->get_cat_by_name($name))
		{
			e(lang('add_cat_erro'));
		} else if(empty($name)) {
			e(lang('add_cat_no_name_err'));
		} else {
			$cid = $db->insert(tbl($this->cat_tbl),$flds,$values);

			if($default=='yes' || !$this->get_default_category()){
				$this->make_default_category($cid);
            }

            if( !error() ) {
			    e(lang('cat_add_msg'),'m');
            }

			//Uploading thumb
			if(!empty($_FILES['cat_thumb']['tmp_name'])){
				$this->add_category_thumb($cid,$_FILES['cat_thumb']);
            }
		}
	}

	/**
	 * Function used to make category as default
	 *
	 * @param $cid
	 */
	function make_default_category($cid)
	{
		$cid = mysql_clean($cid);

		global $db;
		if($this->category_exists($cid)) {
			$db->update(tbl($this->cat_tbl),array('isdefault'),array('no'),' isdefault=\'yes\' ');
			$db->update(tbl($this->cat_tbl),array('isdefault'),array('yes'),' category_id=\''.$cid.'\' ');
			e(lang('cat_set_default_ok'),'m');
		} else {
			e(lang('cat_exist_error'));
        }
	}

	/**
	 * Function used to get list of categories
	 */
	function get_categories(): array
    {
		global $db;
		return $db->select(tbl($this->cat_tbl),'*',NULL,NULL,' category_order ASC');
	}
	
	function getCbCategories($params): array
    {
		global $db; 
		$params['use_sub_cats'] = $params['use_sub_cats'] ? $params['use_sub_cats'] : 'yes';
		if($this->use_sub_cats && config('use_subs') == 1 && $params['use_sub_cats'] == 'yes' &&
		   ($params['type'] == 'videos' || $params['type'] == 'video' || $params['type'] == 'v'))
		{
			$cond = ' parent_id = 0';
			$subCategories = TRUE;	
		} else {
			$cond = NULL;
        }
		$orderby = $params['orderby'] = $params['orderby'] ? $params['orderby'] : 'category_order';
		$order = $params['order'] = $params['order'] ? $params['order'] : 'ASC';
		$limit = $params['limit'] = $params['limit'] ? (is_numeric($params['limit']) ? $params['limit'] : NULL) : NULL;

		$categories = $db->select(tbl($this->cat_tbl),'*',$cond,$limit," $orderby $order");

		$finalArray = array();
		if($params['with_all']){
			$finalArray[] = array('category_id'=>'all','category_name'=>lang('cat_all'));
        }

		foreach($categories as $cat)
		{
			$finalArray[$cat['category_id']] = $cat;	
			if($subCategories === TRUE && $this->is_parent($cat['category_id'])){
				$finalArray[$cat['category_id']]['children'] = $this->getCbSubCategories($cat['category_id'],$params);
            }
		}

		return $finalArray;
	}
	
	function getCbSubCategories($category_id,$params)
	{
		global $db;
		if(empty($category_id)){
			return false;
        }

		$orderby = $params['orderby']; $order = $params['order'];
		$finalOrder = $orderby.' '.$order;

		$limit = NULL;
		if($params['limit_sub']) {
			if(is_numeric($params['limit_sub'])){
				$limit = $params['limit_sub'];
            } elseif($params['limit_sub'] == 'parent') {
				$limit = $params['limit'];
            }
		}

		if($params['sub_order']){
			$finalOrder = $params['sub_order'];
        }
		$subCats = $db->select(tbl($this->cat_tbl),'*',' parent_id = \''.$category_id.'\'',$limit,$finalOrder);

		if($subCats) {
			$subArray = array();
			foreach($subCats as $subCat) {
				$subArray[$subCat['category_id']] = $subCat;
				if($this->is_parent($subCat['category_id'])) {
					$subArray[$subCat['category_id']]['children'] = $this->getCbSubCategories($subCat['category_id'],$params);
				}
			}
			return $subArray;
		}
	}

	function displayOptions($catArray,$params,$spacer=""): string
    {
		$html = '';
		foreach($catArray as $catID=>$cat)
		{
			if($_GET['cat'] == $cat['category_id'] || ($params['selected'] && $params['selected'] == $cat['category_id'])){
				$selected = ' selected=selected';
            } else {
				$selected = '';
            }
			if($params['value'] == 'link'){
				$value = cblink(array('name'=>'category','data'=>$cat,'type'=>$params['type']));
            } else {
			    $value = $cat['category_id'];
            }

			$html .= "<option value='$value' $selected>";
			$html .= $spacer.display_clean($cat['category_name']);
			$html .= '</option>';

			if($cat['children']){
				$html .= $this->displayOptions($cat['children'],$params,$spacer.($params['spacer']?$params['spacer']:'- '));
            }
		}
		
		return $html;
	}
	
	function displayDropdownCategory($catArray,$params): string
    {
		$html = '';
		if($params['name']){
		    $name = $params['name'];
		} else {
		    $name = 'cat';
        }
		if($params['id']){
		    $id = $params['id'];
        } else {
		    $id = 'cat';
        }
		if($params['class']){
		    $class = $params['class'];
        } else {
		    $class = 'cbSelectCat';
        }
		
		$html .= "<select name='$name' id='$id' class='$class'>";
		if($params['blank_option']){
			$html .= "<option value='0'>None</option>";
        }
		$html .= $this->displayOptions($catArray,$params);
		$html .= '</select>';
		return $html;
	}
	
	function displayOutput($CatArray,$params)
	{
		if(is_array($CatArray)) {
            return $this->displayDropdownCategory($CatArray,$params);
		}
		return false;
	}
	
	function cbCategories($params=NULL)
	{
		$p = $params;
		$p['type'] = $p['type'] ? $p['type'] : 'video';
		$p['echo'] = $p['echo'] ? $p['echo'] : FALSE; 
		$p['with_all'] = $p['with_all'] ? $p['with_all'] : FALSE;

		$categories = $this->getCbCategories($p);

		if($categories)
		{
			if($p['echo'] == TRUE){
				$html = $this->displayOutput($categories,$p);
				if($p['assign']){
					assign($p['assign'],$html);
                } else {
					echo $html;
                }
			} else {
				if($p['assign']){
					assign($p['assign'],$categories);
                } else {
					return $categories;
                }
			}
		} else {
			return false;
		}
	}

	/**
	 * Function used to count total number of categories
	 */
	function total_categories()
	{
		global $db;
		return $db->count(tbl($this->cat_tbl),'*');
	}

	/**
	 * Function used to delete category
	 *
	 * @param $cid
	 */
	function delete_category($cid)
	{
		global $db;
		$cat_details = $this->category_exists($cid);
		if(!$cat_details){
			e(lang('cat_exist_error'));
        } elseif($cat_details['isdefault'] == 'yes') { //Checking if category is default or not
			e(lang('cat_default_err'));
        } else {
			$pcat = $this->has_parent($cid,true);
			
			//Checking if category is both parent and child
			if($pcat && $this->is_parent($cid)) {
				$to = $pcat[0]['category_id'];
				$has_child = TRUE;
			} elseif($pcat && !$this->is_parent($cid)) { //Checking if category is only child
				$to = $pcat[0]['category_id'];
				$has_child = TRUE;
			} elseif(!$pcat && $this->is_parent($cid)) { //Checking if category is only parent
				$to = NULL;
				$has_child = NULL;
				$db->update(tbl($this->cat_tbl),array('parent_id'),array('0'),' parent_id = '.mysql_clean($cid));
			}
				
			//Moving all contents to parent OR default category									
			$this->change_category($cid,$to,$has_child);
			
			//Removing Category
			$db->execute('DELETE FROM '.tbl($this->cat_tbl).' WHERE category_id=\''.mysql_clean($cid).'\'');
			e(lang('class_cat_del_msg'),'m');
		}
	}
	
	/**
	 * Function used to get default category
	 */
	function get_default_category()
	{
		global $db;
		$results = $db->select(tbl($this->cat_tbl),"*",' isdefault=\'yes\' ');
		if(count($results)>0){
			return $results[0];
        }
		return false;
	}
	
	/**
	 * Function used to get default category ID
	 */
	function get_default_cid()
	{
		$default = $this->get_default_category();
		return $default['category_id'];
	}

	/**
	 * Function used to move contents from one section to other
	 *
	 * @param      $from
	 * @param null $to
	 * @param null $has_child
	 */
	function change_category($from,$to=NULL,$has_child=NULL)
	{
		global $db;

		if(!$this->category_exists($to)){
			$to = $this->get_default_cid();
        }

		if($has_child) {
			$db->update(tbl($this->cat_tbl),array('parent_id'),array($to),' parent_id = '.$from);
		}

		if( !empty($this->section_tbl) ){
            $db->execute('UPDATE '.tbl($this->section_tbl)." SET category = replace(category,'#".$from."#','#".$to."#') WHERE category LIKE '%#".$from."#%'");
            $db->execute('UPDATE '.tbl($this->section_tbl)." SET category = replace(category,'#".$to."# #".$to."#','#".$to."#') WHERE category LIKE '%#".$to."#%'");
        }
	}

	/**
	 * Function used to edit category
	 * submit values and it will update category
	 *
	 * @param $array
	 */
	function update_category($array)
	{
		global $db;
		$name = $array['name'];
		$desc = $array['desc'];
		$default = $array['default_categ'];
		$pcat = $array['parent_cat'];

		$flds = array('category_name','category_desc','isdefault');
		if( $this->cat_tbl != 'user_categories' ){
			$flds[] = 'parent_id';
        }

		$values = array($name, $desc, $default, $pcat);
		$cur_name = $array['cur_name'];
		$cid = mysql_clean($array['cid']);

		if($this->get_cat_by_name($name) && $cur_name != $name) {
			e(lang('add_cat_erro'));
		} elseif (empty($name)) {
			e(lang('add_cat_no_name_err'));
		} elseif ($pcat == $cid){
			e(lang('You can not make category parent of itself'));
		} else {
			$db->update(tbl($this->cat_tbl), $flds, $values," category_id='".$cid."' ");
			if($default == lang('yes')){
				$this->make_default_category($cid);
            }
			e(lang('cat_update_msg'),'m');

			//Uploading thumb
			if(!empty($_FILES['cat_thumb']['tmp_name'])) {
				$this->add_category_thumb($cid,$_FILES['cat_thumb']);
			}
		}
	}


	/**
	 * Function used to add category thumbnail
	 *
	 * @param $cid
	 * @param $file
	 *
	 * @internal param and $Cid Array
	 */
	function add_category_thumb($cid,$file)
	{
		global $imgObj;
		if($this->category_exists($cid))
		{
			//Checking for category thumbs directory
			if(isset($this->thumb_dir)){
				$dir = $this->thumb_dir;
            } else {
				$dir = $this->section_tbl;
            }
			
			//Checking File Extension
			$ext = strtolower(getext($file['name']));
			
			if($ext=='jpg' || $ext =='png' || $ext=='gif')
			{
				$dir_path = CAT_THUMB_DIR.DIRECTORY_SEPARATOR.$dir;
				if(!is_dir($dir_path)){
					@mkdir($dir_path,0777);
                }
					
				if(is_dir($dir_path))	
				{
					$path = $dir_path.DIRECTORY_SEPARATOR.$cid.'.'.$ext;
					
					//Removing File if already exists
					if(file_exists($path)){
						unlink($path);
                    }
					move_uploaded_file($file['tmp_name'],$path);
					
					//Now checking if file is really an image
					if(!@$imgObj->ValidateImage($path,$ext)) {
						e(lang('pic_upload_vali_err'));
						unlink($path);
					} else {
						$imgObj->CreateThumb($path,$path,$this->cat_thumb_width,$ext,$this->cat_thumb_height,true);
					}
				} else {
					e(lang('cat_dir_make_err'));
				}
			} else {
				e(lang('cat_img_error'));
			}
		}
	}

	/**
	 * Function used to get category thumb
	 *
	 * @param        $cat_details
	 * @param string $dir
	 *
	 * @return string
	 */
	function get_cat_thumb($cat_details, $dir='')
	{
		$cid = $cat_details['category_id'];
		$path = CAT_THUMB_DIR.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$cid.'.';
		$exts = array('jpg','png','gif');
		
		$file_exists = false;
		foreach($exts as $ext) {
			if(file_exists($path.$ext)) {
				$file_exists = true;
				break;
			}
		}

		if($file_exists){
			return CAT_THUMB_URL.'/'.$dir.'/'.$cid.'.'.$ext;
        }
		return $this->default_thumb();
	}

	function get_category_thumb($i, $dir)
	{
		return $this->get_cat_thumb($i, $dir);
	}
	
	/**
	 * function used to return default thumb
	 */
	function default_thumb(): string
    {
		if(empty($this->default_thumb)){
			$this->default_thumb = 'no_thumb.jpg';
        }
		return CAT_THUMB_URL.'/'.$this->default_thumb;
	}

	/**
	 * Function used to update category id
	 *
	 * @param $id
	 * @param $order
	 */
	function update_cat_order($id,$order)
	{
		$id = mysql_clean($id);

		global $db;
		$cat = $this->category_exists($id);
		if(!$cat){
			e(lang('cat_exist_error'));
        } else {
			if(!is_numeric($order) || $order <1){
				$order = 1;
            }
			$db->update(tbl($this->cat_tbl),array('category_order'),array($order)," category_id='".$id."'");
		}
	}

	/**
	 * Function used get parent category
	 *
	 * @param $pid
	 *
	 * @return array|bool
	 */
	function get_parent_category($pid)
	{
		$pid = mysql_clean($pid);

		global $db;
		$result = $db->select(tbl($this->cat_tbl),"*"," category_id = $pid");
		if(count($result)>0){
			return $result;
        }
		return false;
	}

	/**
	 * Function used to check category is parent or not
	 *
	 * @param $cid
	 *
	 * @return bool
	 */
	 function is_parent($cid)
	 {
		 $cid = mysql_clean($cid);

		 global $db;
		 $result = $db->count(tbl($this->cat_tbl),'category_id',' parent_id = '.$cid);
		 
		 if($result > 0){
		 	return true;
         }
		 return false;
	 }

	/**
	 * Function used to check wheather category has parent or not
	 *
	 * @param      $cid
	 * @param bool $return_parent
	 *
	 * @return array|bool
	 */
	function has_parent($cid,$return_parent=false)
	{
		$cid = mysql_clean($cid);

		global $db;
		$result = $db->select(tbl($this->cat_tbl),'*'," category_id = $cid AND parent_id != 0");
		 
		if($result > 0) {
			if($return_parent) {
				$pid = $this->get_parent_category($result[0]['parent_id']);
				return $pid;
			}
			return true;
		}
		return false;
	}

	/**
	 * Function used to get parent categories
	 *
	 * @param bool $count
	 *
	 * @return array|bool
	 */
	function get_parents($count=false)
	{
		global $db;
		
		if($count) {
			$result = $db->count(tbl($this->cat_tbl),'*',' parent_id = 0');
		} else {	
			$result = $db->select(tbl($this->cat_tbl),'*',' parent_id = 0');
		}
		
		return $result;
	}

	/**
	 * Function used to list categories in admin area
	 * with indention
	 *
	 * @param $selected
	 *
	 * @return string
	 */
	function admin_area_cats($selected)
	{
		$html = '';
		$pcats = $this->get_parents();
		
		if(!empty($pcats))
		{
			foreach($pcats as $key=>$pcat)
			{
				if($selected == $pcat['category_id']){
					$select = "selected='selected'";
                } else {
					$select = NULL;
                }
					
				$html .= "<option value='".$pcat['category_id']."' $select>";
				$html .= $pcat['category_name'];
				$html .= '</option>';
				if($this->is_parent($pcat['category_id'])){
					$html .= $this->get_sub_subs($pcat['category_id'],$selected);
                }
			}
			
			return $html;
		}
	}

	/**
	 * Function used to get child categories
	 *
	 * @param $cid
	 *
	 * @return array|bool
	 */
	function get_sub_categories($cid)
	{
		$cid = mysql_clean($cid);

		global $db;
		$result = $db->select(tbl($this->cat_tbl),'*',' parent_id = '.$cid);
		if($result > 0){
		 	return $result;		
		}
		return false;
	}

	/**
	 * Function used to get child child categories
	 *
	 * @param        $cid
	 * @param        $selected
	 * @param string $space
	 *
	 * @return string
	 */
	function get_sub_subs($cid,$selected,$space='&nbsp; - ')
	{
		$html = '';
		$subs = $this->get_sub_categories($cid);
		if(!empty($subs))
		{
			foreach($subs as $sub_key=>$sub)
			{
				if($selected == $sub['category_id']){
					$select = "selected='selected'";
                } else {
					$select = NULL;
                }
					
				$html .= "<option value='".$sub['category_id']."' $select>";
				$html .= $space.$sub['category_name'];
				$html .= '</option>';
				if($this->is_parent($sub['category_id'])){
					$html .= $this->get_sub_subs($sub['category_id'],$selected,$space.' - ');
                }
			}
			return $html;
		}
	}

	function get_category_field($cid,$field)
	{
		global $db;
		$result = $db->select(tbl($this->cat_tbl),$field," category_id = $cid");

		if($result){
			return $result[0][$field];
        }
		return false;
	}

    /**
     * Function used to get multiple category names
     *
     * @param $cid_array
     *
     * @return array
     */
    function get_category_names($cid_array)
    {
        $cat_name = array();
        $cid = explode(' ', $cid_array);
        $cid = array_slice($cid,0,-1);
        foreach ($cid as $key => $value) {
            $cat_id = str_replace('#','', $value);
            $results = $this->get_category($cat_id);
            $cat_name[]= $results;
        }
        return $cat_name;
    }

}
