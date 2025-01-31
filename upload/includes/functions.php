<?php
	define("SHOW_COUNTRY_FLAG",TRUE);
	require 'define_php_links.php';
	include_once 'upload_forms.php';

	/**
	 * This Funtion is use to get CURRENT PAGE DIRECT URL
	 * @return string : { string } { $pageURL } { url of current page }
	 */
	function curPageURL()
	{
 		$pageURL = 'http';
		if (@$_SERVER["HTTPS"] == "on") {
			$pageURL .= "s";
		}
		$pageURL .= "://";
 		$pageURL .= $_SERVER['SERVER_NAME'];
		$pageURL .= $_SERVER['PHP_SELF'];
		$query_string = $_SERVER['QUERY_STRING'];
		if(!empty($query_string)) {
			$pageURL .= '?'.$query_string;
		}
 		return $pageURL;
	}

	/**
	 * Cleans a string by putting it through multiple layers
	 *
	 * @param : { string } { string to be cleaned }
	 *
	 * @return mixed : { string } { $string } { cleaned string }
	 */
	function Replacer($string)
	{
		//Wp-Magic Quotes
		$string = preg_replace("/'s/", '&#8217;s', $string);
		$string = preg_replace("/'(\d\d(?:&#8217;|')?s)/", "&#8217;$1", $string);
		$string = preg_replace('/(\s|\A|")\'/', '$1&#8216;', $string);
		$string = preg_replace('/(\d+)"/', '$1&#8243;', $string);
		$string = preg_replace("/(\d+)'/", '$1&#8242;', $string);
		$string = preg_replace("/(\S)'([^'\s])/", "$1&#8217;$2", $string);
		$string = preg_replace('/(\s|\A)"(?!\s)/', '$1&#8220;$2', $string);
		$string = preg_replace('/"(\s|\S|\Z)/', '&#8221;$1', $string);
		$string = preg_replace("/'([\s.]|\Z)/", '&#8217;$1', $string);
		$string = preg_replace("/ \(tm\)/i", ' &#8482;', $string);
		$string = str_replace("''", '&#8221;', $string);
		$array = array('/& /');
		$replace = array('&amp; ') ;
		return $string = preg_replace($array,$replace,$string);
	}

	function clean($string,$allow_html=false)
	{
		 if($allow_html==false){
			 $string = strip_tags($string);
			 $string = Replacer($string);
		 }
		 return $string;
	}

	/**
	 * This function is for Securing Password, you may change its combination for security reason but
	 * make sure do not change once you made your script run
	 * TODO : Multiple md5/sha1 is useless + this is totally unsecure, must be replaced by sha512 + salt
	 *
	 * @deprecated for security !
	 *
	 * @param $string
	 *
	 * @return string
	 */
	function pass_code_unsecure($string)
	{
 	 	return md5(md5(sha1(sha1(md5($string)))));
	}

	function pass_code($string, $userid)
	{
		$salt = config('password_salt');
		return hash('sha512', $string.$userid.$salt);
	}

	/**
	 * Clean a string and remove malicious stuff before insertion
	 * that string into the database
	 *
	 * @param : { string } { $id } { string to be cleaned }
	 *
	 * @return string
	 */
	function mysql_clean($var)
	{
		global $db;
		return $db->clean_var($var);
	}

	function display_clean($var, $clean_quote = true)
	{
	    if($clean_quote){
		    return htmlentities($var, ENT_QUOTES);
        }
        return htmlentities($var);
	}

	function set_cookie_secure($name,$val,$time = null)
    {
        if( is_null($time) ){
            $time = time() + 3600;
        }

        $path = '/';
        $flag_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        $flag_httponly = true;

        if (version_compare(phpversion(), '7.3.0', '>=')) {
            setcookie($name,$val,[
                'expires' => $time,
                'path' => $path,
                'secure' => $flag_secure,
                'httponly' => $flag_httponly,
                'samesite' => 'Strict'
            ]);
        } else {
            setcookie($name,$val,$time,$path,'',$flag_secure,$flag_httponly);
        }
    }

    function getBytesFromFileSize($size){
        $units = array(
            'B' => 1,
            'kB' => 1024,
            'MB' => pow(1024,2),
            'M' => pow(1024,2),
            'GB' => pow(1024,3),
            'G' => pow(1024,3),
            'TB' => pow(1024,4),
            'T' => pow(1024,4),
            'PB' => pow(1024,5),
            'EB' => pow(1024,6),
            'ZB' => pow(1024,7),
            'YB' => pow(1024,8)
        );

        $unit = preg_replace('/[0-9]/', '', $size );
        $size = preg_replace('/[^0-9]/', '', $size );
        return $size*$units[$unit];
    }

	/**
	 * Generate random string of given length
	 *
	 * @param : { integer } { $length } { length of random string }
	 *
	 * @return string : { string } { $randomString  } { new generated random string }
	 */
	function RandomString($length) {
		$string = md5(microtime());
		$highest_startpoint = 32-$length;
		return substr($string,rand(0,$highest_startpoint),$length);
	}

	/**
	 * Function used to send emails. this is a very basic email function
	 * you can extend or replace this function easily
	 *
	 * @param : { array } { $array } { array with all details of email }
	 *
	 * @return bool
	 * @throws phpmailerException
	 *
	 * @param_list : { content, subject, to, from, to_name, from_name }
	 *
	 * @author : Arslan Hassan
	 */
	function cbmail($array)
	{
		$func_array = get_functions('email_functions');
		if(is_array($func_array)) {
			foreach($func_array as $func) {
				if(function_exists($func)) {
					return $func($array);
				}
			}
		}

		$content = $array['content'];
		$subject = $array['subject'];
		$to		 = $array['to'];
		$from	 = $array['from'];
		$to_name = $array['to_name'];
		$from_name = $array['from_name'];
		if($array['nl2br']) {
			$content = nl2br($content);
		}

		# Checking Content
		if(preg_match('/<html>/',$content,$matches)) {
			if(empty($matches[1])) {
				$content = wrap_email_content($content);
			}
		}
		$message = $content;

		//ClipBucket uses PHPMailer for sending emails
		include_once('classes/phpmailer/class.phpmailer.php');
		include_once('classes/phpmailer/class.smtp.php');
		$mail  = new PHPMailer(); // defaults to using php "mail()"
		$mail_type = config('mail_type');
		//---Setting SMTP ---		
		if($mail_type=='smtp') {
			$mail->IsSMTP(); // telling the class to use SMTP
			$mail->Host       = config('smtp_host'); // SMTP server
			if(config('smtp_auth')=='yes'){
				$mail->SMTPAuth   = true;			 // enable SMTP authentication
            }
			$mail->Port       = config('smtp_port'); // set the SMTP port for the GMAIL server
			$mail->Username   = config('smtp_user'); // SMTP account username
			$mail->Password   = config('smtp_pass'); // SMTP account password
		}
		//--- Ending Smtp Settings
		$mail->SetFrom($from, $from_name);
		if(is_array($to)) {
			foreach($to as $name) {		
				$mail->AddAddress(strtolower($name), $to_name);
			}
		} else {
			$mail->AddAddress(strtolower($to), $to_name);
		}
		$mail->Subject = $subject;
		$mail->MsgHTML($message);

		if(!$mail->Send()) {
			if(has_access('admin_access',TRUE) ) {
		  		e("Mailer Error: " . $mail->ErrorInfo);
			}
		  	return false;
		}
		return true;
	}

	/**
	 * Send email from PHP
	 * @uses : { function : cbmail }
	 *
	 * @param $from
	 * @param $to
	 * @param $subj
	 * @param $message
	 *
	 * @return bool
	 */
	function send_email($from,$to,$subj,$message) {
		return cbmail(array('from'=>$from,'to'=>$to,'subject'=>$subj,'content'=>$message));
	}

	/**
	 * Function used to wrap email content in adds HTML AND BODY TAGS
	 *
	 * @param : { string } { $content } { contents of email to be wrapped }
	 *
	 * @return string
	 */
	function wrap_email_content($content) {
		return '<html><body>'.$content.'</body></html>';
	}

	/**
	 * Function used to get file name
	 *
	 * @param : { string } { $file } { file path to get name for }
	 *
	 * @return bool|string
	 */
	function GetName($file)
	{
		if(!is_string($file)) {
			return false;
		}
		//for server thumb files
		$parts = parse_url($file);
        $query = isset($query) ? parse_str($parts['query'], $query) : false;
        $get_file_name = $query['src'] ?? false;
        $path = explode('.',$get_file_name);
        $server_thumb_name = $path[0];
        if (!empty($server_thumb_name)) {
        	return $server_thumb_name;
        }
        /*server thumb files END */
		$path = explode('/',$file);
		if(is_array($path)) {
			$file = $path[count($path)-1];
		}
		$new_name 	 = substr($file, 0, strrpos($file, '.'));
		return $new_name;
	}

    function old_set_time($temps)
	{
		$temps = round($temps);
		$heures = floor($temps / 3600);
		$minutes = round(floor(($temps - ($heures * 3600)) / 60));
		if ($minutes < 10) {
			$minutes = '0' . round($minutes);
		}
		$secondes = round($temps - ($heures * 3600) - ($minutes * 60));
		if ($secondes < 10) {
			$secondes = '0' .  round($secondes);
		}
		return $minutes . ':' . $secondes;
	}

	/**
	 * Function Used TO Get Extensio Of File
	 *
	 * @param : { string } { $file } { file to get extension of }
	 *
	 * @return string : { string } { extension of file }
	 *
	 */
	function GetExt($file) {
		return strtolower(end(explode('.', $file)));
	}

	/**
	 * Convert given seconds in Hours Minutes Seconds format
	 *
	 * @param : { integer } { $sec } { seconds to conver }
	 * @param bool $padHours
	 *
	 * @return string : { string } { $hms } { formatted time string }
	 */
	function SetTime($sec, $padHours = true)
	{
		if($sec < 3600) {
			return old_set_time($sec);
		}
		$hms = "";
		// there are 3600 seconds in an hour, so if we
		// divide total seconds by 3600 and throw away
		// the remainder, we've got the number of hours
		$hours = intval(intval($sec) / 3600);
		// add to $hms, with a leading 0 if asked for
		$hms .= ($padHours)
			  ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
			  : $hours. ':';
		// dividing the total seconds by 60 will give us
		// the number of minutes, but we're interested in
		// minutes past the hour: to get that, we need to
		// divide by 60 again and keep the remainder
		$minutes = intval(($sec / 60) % 60);
		// then add to $hms (with a leading 0 if needed)
		$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
		// seconds are simple - just divide the total
		// seconds by 60 and keep the remainder
		$seconds = intval($sec % 60);
		// add to $hms, again with a leading 0 if needed
		$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
		return $hms;
	}

	/**
	 * Checks if provided email is valid or not
	 *
	 * @param : { string } { $email } { email address to check }
	 *
	 * @return bool : { boolean } { if valid return true, else false }
	 */
	function isValidEmail($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);
   	}

	/**
	 * Get Directory Size - get_video_file($vdata,$no_video,false);
	 *
	 * @param : { string } { $path } { path to directory to determine size of }
	 *
	 * @return mixed : { integer } { $total } { size of directory }
	 */
	function get_directory_size($path)
	{
		$totalsize = 0;
		$totalcount = 0;
		$dircount = 0;
		if ($handle = opendir ($path))
		{
			while (false !== ($file = readdir($handle)))
			{
				$nextpath = $path . '/' . $file;
				if ($file != '.' && $file != '..' && !is_link ($nextpath))
				{
					if (is_dir ($nextpath))
					{
					  $dircount++;
					  $result = get_directory_size($nextpath);
					  $totalsize += $result['size'];
					  $totalcount += $result['count'];
					  $dircount += $result['dircount'];
					} elseif (is_file ($nextpath)) {
					  $totalsize += filesize ($nextpath);
					  $totalcount++;
					}
				}
			}
		}
		closedir ($handle);
		$total['size'] = $totalsize;
		$total['count'] = $totalcount;
		$total['dircount'] = $dircount;
		return $total;
	}

	/**
	 * Format file size in readable format
	 *
	 * @param : { integer } { $data } { size in bytes }
	 *
	 * @return string : { string } { $data } { file size in readable format }
	 */
	function formatfilesize( $data )
	{
        // bytes
        if( $data < 1024 ) {
            return $data . ' bytes';
        }
        // kilobytes
       	if( $data < 1024000 ) {
			return round( ( $data / 1024 ), 1 ) . 'KB';
        }
        // megabytes
        if($data < 1024000000){
            return round( ( $data / 1024000 ), 1 ) . ' MB';
        }

		return round( ( $data / 1024000000 ), 1 ) . ' GB';
    }

	/**
	 * Function used to get shell output
	 *
	 * @param : { string } { $cmd } { command to run }
	 *
	 * @return string
	 */
	function shell_output($cmd)
	{
		if( !stristr(PHP_OS, 'WIN') ) {
			$cmd = "PATH=\$PATH:/bin:/usr/bin:/usr/local/bin bash -c \"$cmd\" 2>&1";
		}
		return shell_exec( $cmd );
	}

	function getCommentAdminLink($type, $id)
	{
		return '/admin_area/edit_video.php?video='.$id;
	}

	/**
	 * FUNCTION USED TO GET COMMENTS
	 *
	 * @param : { array } { $params } { array of parameters e.g order,limit,type }
	 *
	 * @return array|bool : { array } { $results } { array of fetched comments }
	 * { $results } { array of fetched comments }
	 */
	function getComments($params=NULL)
	{
		global $db;
		$order = $params['order'];
		$limit = $params['limit'];
		$type = $params['type'];
		$cond = '';
		if(!empty($params['videoid'])) {
			$cond .= 'type_id='.$params['videoid'];
			$cond .= ' AND ';
		}
		if(empty($type)) {
			$type = 'v';
		}
		$cond .= tbl('comments.type')." = '".$type."'";
		if($params['type_id'] && $params['sectionTable']) {
			if($cond != ""){
				$cond .= " AND ";
            }
			$cond .= tbl('comments.type_id').' = '.tbl($params['sectionTable'].'.'.$params['type_id']);
		}
				
		if($params['cond']) {
			if($cond != ''){
				$cond .= ' AND ';
            }
			$cond .= $params['cond'];
		}

        $query = 'SELECT * , '.tbl('comments.userid').' AS c_userid FROM '.tbl('comments'.($params['sectionTable']?','.$params['sectionTable']:NULL));

        if($cond) {
            $query .= ' WHERE '.$cond;
        }
        if($order) {
            $query .= ' ORDER BY '.$order;
        }
        if($limit) {
            $query .= ' LIMIT '.$limit;
        }
		if(!$params['count_only']) {
            $result = db_select($query);
        }

		if($params['count_only']) {
			$cond = tbl('comments.type')."= '". $params['type'] ."'";
			$result = $db->count(tbl('comments'),'*',$cond);
		}

		if($result) {
			return $result;
		}
		return false;
	}

    /**
     * Fetches comments using params, built for smarty
     *
     * @param $params
     *
     * @return bool|mixed
     * @uses : { class : $myquery } { function : getComments }
     */
    function getSmartyComments($params)
	{
		global $myquery;
		$comments = $myquery->getComments($params);
		if($params['assign']) {
			assign($params['assign'],$comments);
		} else {
			return $comments;
		}
	}

	/**
	 * FUNCTION USED TO GET ADVERTISMENT
	 *
	 * @param : { array } { $params } { array of parameters }
	 *
	 * @return string
	 */
	function getAd($params)
	{
		global $adsObj;
		$data = '';
		if(isset($params['style']) || isset($params['class']) || isset($params['align'])){
			$data .= '<div style="'.$params['style'].'" class="'.$params['class'].'" align="'.$params['align'].'">';
        }
		$data .= ad($adsObj->getAd($params['place']));
		if(isset($params['style']) || isset($params['class']) || isset($params['align'])){
			$data .= '</div>';
        }
		return $data;
	}

	/**
	 * FUNCTION USED TO GET THUMBNAIL, MADE FOR SMARTY
	 *
	 * @param : { array } { $params } { array of parameters }
	 *
	 * @return mixed
	 */
	function getSmartyThumb($params)
	{
		return get_thumb($params['vdetails'],$params['num'],$params['multi'],$params['count_only'],true,true,$params['size']);
	}

	/**
	 * FUNCTION USED TO MAKE TAGS MORE PERFECT
	 * @author : Arslan Hassan <arslan@clip-bucket.com,arslan@labguru.com>
	 *
	 * @param : { string } { $tags } { text unformatted }
	 * @param string $sep
	 *
	 * @return string : { string } { $tagString } { text formatted }
	 */
	function genTags($tags,$sep=','): string
    {
		//Remove fazool spaces
		$tags = preg_replace(array('/ ,/','/, /'),',',$tags);
		$tags = preg_replace( '`[,]+`' , ',', $tags);
		$tag_array = explode($sep,$tags);
        $newTags = [];
		foreach($tag_array as $tag) {
			if(isValidtag($tag)) {
				$newTags[] = $tag;
			}
		}
		//Creating new tag string
		if(is_array($newTags)) {
			return implode(',',$newTags);
		}
        return 'no-tag';
	}

	/**
	 * FUNCTION USED TO VALIDATE TAG
	 * @author : Arslan Hassan <arslan@clip-bucket.com,arslan@labguru.com>
	 *
	 * @param { string } { $tag } { tag to be validated }
	 *
	 * @return bool : { boolean } { true or false }
	 */
	function isValidtag($tag): bool
    {
		$disallow_array = array('of','is','no','on','off','a','the','why','how','what','in');
		if(!in_array($tag,$disallow_array) && strlen($tag)>2) {
			return true;
		}
		return false;
	}

	/**
	 * FUNCTION USED TO GET CATEGORY LIST
	 *
	 * @param array $params
	 *
	 * @return array|bool|string : { array } { $cats } { array of categories }
	 * @internal param $ : { array } { $params } { array of parameters e.g type } { $params } { array of parameters e.g type }
	 */
	function getCategoryList($params=[])
	{
		global $cats;
		$cats = '';
		$type = $params['type'];
		switch($type)
		{
			default:
			    cb_call_functions('categoryListing',$params);
				break;
			
			case 'video':
			case 'videos':
			case 'v':
				global $cbvid;
				$cats = $cbvid->cbCategories($params);
				break;
				
			case 'users':
			case 'user':
			case 'u':
			case 'channels':
				global $userquery;
				$cats = $userquery->cbCategories($params);
				break;

			case 'collection':
			case 'collections':
			case 'cl':
				global $cbcollection;
				$cats = $cbcollection->cbCategories($params);
				break;
		}
		return $cats;
	}

	/**
	 * Get list of categories from smarty
	 * @uses { function : getCategoryList }
	 *
	 * @param $params
	 *
	 * @return array|bool|string
	 */
	function getSmartyCategoryList($params)
	{
		return getCategoryList($params);
	}

	/**
	 * Function used to insert data in database
	 * @uses : { class : $db } { function : dbInsert }
	 *
	 * @param      $tbl
	 * @param      $flds
	 * @param      $vls
	 * @param null $ep
	 */
	function dbInsert($tbl,$flds,$vls,$ep=NULL)
	{
		global $db ;
		$db->insert($tbl,$flds,$vls,$ep);
	}

    /**
     * An easy function for errors and messages (e is basically short form of exception)
     * I don't want to use the whole Trigger and Exception code, so e pretty works for me :D
     *
     * @param null   $msg
     * @param string $type
     * @param bool   $secure
     *
     * @return null
     * @internal param $ { string } { $msg } { message to display }
     * @internal param $ { string } { $type } { e for error and m for message }
     * @internal param $ { integer } { $id } { Any Predefined Message ID }
     */
	function e($msg=NULL,$type='e',$secure=true)
	{
		global $eh;
		if(!empty($msg)) {
			return $eh->e($msg,$type,$secure);
		}
	}

	/**
	 * Print an array in pretty way
	 *
	 * @param : { string / array } { $text } { Element to be printed }
	 * @param bool $pretty
	 */
	function pr($text,$pretty=false)
	{
		if(!$pretty) {
			$dump = print_r($text, true);
            echo display_clean($dump);
		} else {
			echo '<pre>';
			$dump = print_r($text, true);
            echo display_clean($dump);
			echo '</pre>';
		}
	}

	/**
	 * This function is used to call function in smarty template
	 * This wont let you pass parameters to the function, but it will only call it
	 *
	 * @param $params
	 */
	function FUNC($params)
	{
		$func=$params['name'];
		if(function_exists($func)){
			$func();
        }
	}
	
	/**
	* Function used to get userid anywhere 
	* if there is no user_id it will return false
	* @uses : { class : $userquery } { var : userid }
	*/
	function user_id()
	{
		global $userquery;
		if($userquery->userid !='' && $userquery->is_login){
			return $userquery->userid;
        }
		return false;
	}
	
	/**
	* Get current user's userid
	* @uses : { function : user_id }
	*/
	function userid(){
		return user_id();
	}
	
	/**
	* Function used to get username anywhere 
	* if there is no usern_name it will return false
	* @uses : { class : $userquery } { var : $username }
	*/
	function user_name()
	{
		global $userquery;
		if($userquery->user_name) {
			return $userquery->user_name;
		}
		return $userquery->get_logged_username();
	}

	/**
	 * Function used to check weather user access or not
	 * @uses : { class : $userquery } { function : login_check }
	 *
	 * @param      $access
	 * @param bool $check_only
	 * @param bool $verify_logged_user
	 *
	 * @return bool
	 */
	function has_access($access,$check_only=TRUE,$verify_logged_user=true)
	{
		global $userquery;
		return $userquery->login_check($access,$check_only,$verify_logged_user);
	}

	/**
	 * Function used to return mysql time
	 * @return false|string : { current time }
	 * @author : Fwhite
	 */
	function NOW() {
		return date('Y-m-d H:i:s', time());
	}

	/**
	 * Function used to get Regular Expression from database
	 *
	 * @param : { string } { $code } { code to be filtered }
	 *
	 * @return bool
	 */
	function get_re($code)
	{
		global $db;
		$results = $db->select(tbl('validation_re'),'*'," re_code='$code'");
		if(count($results)>0) {
			return $results[0]['re_syntax'];
		}
		return false;
	}

	/**
	 * Function used to check weather input is valid or not
	 * based on preg_match
	 *
	 * @param $syntax
	 * @param $text
	 *
	 * @return bool
	 */
	function check_re($syntax,$text)
	{
		preg_match('/'.$syntax.'/i',$text,$matches);
		if(!empty($matches[0])) {
			return true;
		}
		return false;
	}

	/**
	 * Check regular expression
	 * @uses: { function : check_re }
	 *
	 * @param $code
	 * @param $text
	 *
	 * @return bool
	 */
	function check_regular_expression($code,$text) {
		return check_re($code,$text); 
	}

	/**
	 * Function used to check field directly
	 * @uses : { function : check_regular_expression }
	 *
	 * @param $code
	 * @param $text
	 *
	 * @return bool
	 */
	function validate_field($code,$text)
	{
		$syntax = get_re($code);
		if(empty($syntax)) {
			return true;
		}
		return check_regular_expression($syntax,$text);
	}

	/**
	 * Check if syntax is valid
	 * @uses : { function : validate_field }
	 *
	 * @param $code
	 * @param $text
	 *
	 * @return bool
	 */
	function is_valid_syntax($code,$text)
	{
		if(DEV_INGNORE_SYNTAX) {
			return true;
		}
		return validate_field($code,$text);
	}

	/**
	 * Function used to apply function on a value
	 *
	 * @param $func
	 * @param $val
	 *
	 * @return bool
	 */
	function is_valid_value($func,$val)
	{
		if(!function_exists($func)) {
			return true;
		}
		if(!$func($val)) {
			return false;
		}
		return true;
	}

	/**
	 * Calls an array of functions with parameters
	 *
	 * @param : { array } { $func } { array with functions to be called }
	 * @param : { string } { $val } { parameters for functions }
	 *
	 * @return mixed
	 */
	function apply_func($func,$val)
	{
		if(is_array($func)) {
			foreach($func as $f) {
				if(function_exists($f)) {
					$val = $f($val);
				}
			}
		} else {
			$val = $func($val);
		}
		return $val;
	}

	/**
	 * Function used to validate YES or NO input
	 *
	 * @param : { string } { $input } { field to be checked }
	 *
	 * @param $return
	 *
	 * @return string
	 */
	function yes_or_no($input, $return = 'yes')
	{
		$input = strtolower($input);
		if($input != 'yes' && $input != 'no') {
			return $return;
		}
		return $input;
	}

	/**
	 * Function used to validate collection category
	 * @uses : { class : $cbcollection } { function : validate_collection_category }
	 *
	 * @param null $array
	 *
	 * @return bool
	 */
	function validate_collection_category($array=NULL)
	{
		global $cbcollection;
		return $cbcollection->validate_collection_category($array);
	}

	/**
	 * Function used to get user avatar
	 *
	 * @param { array } { $param } { array with parameters }
	 * @params_in_$param : details, size, uid
	 *
	 * @uses : { class : $userquery } { function : avatar }
	 * @return string
	 */
	function avatar($param)
	{
		global $userquery;
		$udetails = $param['details'];
		$size = $param['size'];
		$uid = $param['uid'];
		return $userquery->avatar($udetails,$size,$uid);
	}

	/**
	 * This function used to call function dynamically in smarty
	 *
	 * @param : { array } { $param } { array with parameters e.g $param['name'] }
	 *
	 * @return mixed
	 */
	function load_form($param)
	{
		$func = $param['name'];
		if(function_exists($func)) {
			return $func($param);
		}
	}
	
	/**
	* Function used to get PHP Path
	*/
	function php_path()
	{
		if(PHP_PATH !='') {
			return PHP_PATH;
		}
		return "/usr/bin/php";
	 }

	/**
	 * Function used to get binary paths
	 *
	 * @param : { string } { $path } { element to get path for }
	 *
	 * @return string
	 */
	function get_binaries($path)
	{
		$type = '';
		if(is_array($path)) {
			 $type = $path['type'];
			 $path = $path['path'];
		}

		$path = strtolower($path);
		if($type=='' || $type=='user')
		{
			switch($path)
			{
				case 'php':
					$software_path = php_path();
					break;

				case 'media_info':
					$software_path = config('media_info');
					break;

				case 'ffprobe_path':
					$software_path = config('ffprobe_path');
					break;

				case 'ffmpeg':
					$software_path = config('ffmpegpath');
					break;

				default:
					$software_path = '';
					break;
			}

			if( $software_path != '' ){
				return $software_path;
			}
		}

		switch($path)
		{
			case 'php':
				$return_path = shell_output('which php');
				if($return_path) {
					return $return_path;
				}
				return 'Unable to find PHP path';

			case 'media_info':
				$return_path = shell_output('which mediainfo');
				if($return_path) {
					return $return_path;
				}
				return 'Unable to find media_info path';

			case 'ffprobe_path':
				$return_path = shell_output('which ffprobe');
				if($return_path) {
					return $return_path;
				}
				return 'Unable to find ffprobe path';

			case 'ffmpeg':
				$return_path = shell_output('which ffmpeg');
				if($return_path) {
					return $return_path;
				}
				return 'Unable to find ffmpeg path';

			default:
				return 'Unknown path : '.$path;
		}
	}

	/**
	 *
	 * @param : { string } { $string } { string to decode }
	 *
	 * @return string
	 */
	function unhtmlentities($string)
	{
		$trans_tbl =get_html_translation_table (HTML_ENTITIES );
		$trans_tbl =array_flip ($trans_tbl );
		return strtr ($string ,$trans_tbl );
	}

	/**
	 * Function used to get array value
	 * if you know partial value of array and wants to know complete
	 * value of an array, this function is being used then
	 *
	 * @param $needle
	 * @param $haystack
	 *
	 * @return mixed : { string / int } { item if it is found }
	 * @internal param $ : { string / int } { $needle } { element to find } { $needle } { element to find }
	 * @internal param $ : { array / string }  { $haystack } { element to do search in }  { $haystack } { element to do search in }
	 */
	function array_find($needle, $haystack)
	{
	   foreach ($haystack as $item) {
		  if (strpos($item, $needle) !== FALSE) {
			 return $item;
		  }
	   }
	}

	/**
	 * Function used to give output in proper form
	 *
	 * @param : { array } { $params } { array of parameters e.g $params['input'] }
	 *
	 * @return mixed : { string } { string value depending on input type }
	 * { string value depending on input type }
	 */
	function input_value($params)
	{
		$input = $params['input'];
		$value = $input['value'];
		if($input['value_field'] == 'checked') {
			$value = $input['checked'];
		}
			
		if($input['return_checked']) {
			return $input['checked'];
		}
			
		if(function_exists($input['display_function'])) {
			return $input['display_function']($value);
		}

		if($input['type'] == 'dropdown')
		{
			if($input['checked']) {
				return $value[$input['checked']];
			}
			return $value[0];
		}
		return $input['value'];
	}
	
	/**
	* Function used to convert input to categories
	* @param { string / array } { $input } { categories to be converted e.g #12# }
	*/
	function convert_to_categories($input)
	{
		if(is_array($input))
		{
			foreach($input as $in)
			{
				if(is_array($in))
				{
					foreach($in as $i)
					{
						if(is_array($i))
						{
							foreach($i as $info) {
								$cat_details = get_category($info);
								$cat_array[] = array($cat_details['categoryid'],$cat_details['category_name']);
							}
						} elseif (is_numeric($i)){
							$cat_details = get_category($i);
							$cat_array[] = array($cat_details['categoryid'],$cat_details['category_name']);
						}
					}
				} elseif (is_numeric($in)){
					$cat_details = get_category($in);
					$cat_array[] = array($cat_details['categoryid'],$cat_details['category_name']);
				}
			}
		} else {
			preg_match_all('/#([0-9]+)#/',$default['category'],$m);
			$cat_array = array($m[1]);
			foreach($cat_array as $i) {
				$cat_details = get_category($i);
				$cat_array[] = array($cat_details['categoryid'],$cat_details['category_name']);
			}
		}
		$count = 1;
		if(is_array($cat_array))
		{
			foreach($cat_array as $cat) {
				echo '<a href="'.$cat[0].'">'.$cat[1].'</a>';
				if($count!=count($cat_array))
					echo ', ';
				$count++;
			}
		}
	}

	/**
	 * Function used to get categorie details
	 * @uses : { class : $myquery } { function : get_category }
	 *
	 * @param $id
	 *
	 * @return
	 */
	function get_category($id)
	{
		global $myquery;
		return $myquery->get_category($id);
	}

	/**
	 * Sharing OPT displaying
	 *
	 * @param $input
	 *
	 * @return int|string
	 */
	function display_sharing_opt($input)
	{
		foreach($input as $key => $i) {
			return $key;
		}
	}

	/**
	 * Function used to get number of videos uploaded by user
	 * @uses : { class : $userquery } { function : get_user_vids }
	 *
	 * @param      $uid
	 * @param null $cond
	 * @param bool $count_only
	 *
	 * @return array|bool|int
	 */
	function get_user_vids($uid,$cond=NULL,$count_only=false)
	{
		global $userquery;
		return $userquery->get_user_vids($uid,$cond,$count_only);
	}

	function error_list()
	{
		global $eh;
		return $eh->get_error();
	}

    function warning_list()
    {
        global $eh;
        return $eh->get_warning();
    }


    function msg_list()
	{
		global $eh;
		return $eh->get_message();
	}

	/**
	 * Function used to add template in display template list
	 *
	 * @param : { string } { $file } { file of the template }
	 * @param bool $folder
	 * @param bool $follow_show_page
	 */
	function template_files($file,$folder=false,$follow_show_page=true)
	{
		global $ClipBucket;
		if(!$folder) {
			$ClipBucket->template_files[] = array('file' => $file,'follow_show_page'=>$follow_show_page);
		} else {
			$ClipBucket->template_files[] = array('file'=>$file,'folder'=>$folder,'follow_show_page'=>$follow_show_page);
		}
	}

	/**
	 * Function used to include file
	 *
	 * @param : { array } { $params } { paramets inside array e.g $param['file'] }
	 *
	 * @action : { displays template }
	 */
	function include_template_file($params)
	{
		$file = $params['file'];
		if(file_exists(LAYOUT.DIRECTORY_SEPARATOR.$file)) {
			Template($file);
		} elseif(file_exists($file)) {
			Template($file,false);
		}
	}

	/**
	 * Function used to validate username
	 *
	 * @param : { string } { $username } { username to be checked }
	 *
	 * @return bool : { boolean } { true or false depending on situation }
	 */
	function username_check($username)
	{
		global $Cbucket;
		$banned_words = $Cbucket->configs['disallowed_usernames'];
		$banned_words = explode(',',$banned_words);
		foreach($banned_words as $word) {
			preg_match("/$word/Ui",$username,$match);
			if(!empty($match[0]))
				return false;
		}
		//Checking if its syntax is valid or not
		$multi = config('allow_unicode_usernames');
		
		//Checking Spaces
		if(!config('allow_username_spaces')) {
			preg_match('/ /',$username,$matches);
		}
		if(!is_valid_syntax('username',$username) && $multi!='yes' || $matches) {
			e(lang("class_invalid_user"));
		}
		if(!preg_match('/^[A-Za-z0-9_.]+$/', $username)){
			return false;
		}
		return true;
	}

	/**
	 * Function used to check weather username already exists or not
	 * @uses : { class : $userquery } { function : username_exists }
	 *
	 * @param $user
	 *
	 * @return bool
	 */
	function user_exists($user)
	{
		global $userquery;
		return $userquery->username_exists($user);
	}

	/**
	 * Function used to check weather email already exists or not
	 *
	 * @param : { string } { $user } { email address to check }
	 *
	 * @uses : { class : $userquery } { function : duplicate_email }
	 * @return bool
	 */
	function email_exists($user)
	{
		global $userquery;
		return $userquery->duplicate_email($user);
	}

	function check_email_domain($email)
	{
		global $userquery;
		return $userquery->check_email_domain($email);
	}

	/**
	 * Function used to check weather error exists or not
	 *
	 * @param string $param
	 *
	 * @return array|bool
	 */
	function error($param = 'array')
	{
        global $eh;
        $error = $eh->get_error();
		if( count($error) > 0 )
		{
			if($param!='array') {
				if($param=='single') {
					$param = 0;
				}
				return $error[$param];
			}
			return $error;
		}
		return false;
	}

	/**
	 * Function used to check weather msg exists or not
	 *
	 * @param string $param
	 *
	 * @return array|bool
	 */
	function msg($param='array')
	{
        global $eh;
        $message = $eh->get_message();
        if( count($message) > 0 )
        {
            if($param!='array') {
                if($param=='single') {
                    $param = 0;
                }
                return $message[$param];
            }
            return $message;
        }
        return false;
	}
	
	/**
	* Function used to load plugin
	*/
	function load_plugin()
	{
		global $cbplugin;
	}

	/**
	 * Function used to create limit function from current page & results
	 *
	 * @param $page
	 * @param $result
	 *
	 * @return string
	 */
	function create_query_limit($page,$result)
	{
		$page = mysql_clean($page);
		$result = mysql_clean($result);
		$limit = $result;
		if(empty($page) || $page == 0 || !is_numeric($page)) {
			$page = 1;
		}
		$from = $page - 1;
		$from = $from*$limit;
		return $from.','.$result;
	}

	/**
	 * Function used to get value from $_GET
	 *
	 * @param : { string } { $val } { value to fetch from $_GET }
	 * @param bool $filter
	 *
	 * @return bool|string
	 */
	function get_form_val( $val, bool $filter=false)
	{
		if($filter) {
			return isset($_GET[$val]) ? display_clean($_GET[$val]) : false;
		}
		return $_GET[$val];
	}

	/**
	 * Function used to get value from $_GET
	 * @uses : { function : get_form_val }
	 *
	 * @param $val
	 *
	 * @return bool|string
	 */
	function get($val){
		return get_form_val($val);
	}

	/**
	 * Function used to get value from $_POST
	 *
	 * @param : { string } { $val } { value to fetch from $_POST }
	 * @param bool $filter
	 *
	 * @return string
	 */
	function post_form_val($val,$filter=false)
	{
		if($filter) {
			return display_clean($_POST[$val]);
		}
		return $_POST[$val];
	}

	/**
	 * Function used to return LANG variable
	 *
	 * @param      $var
	 * @param bool $sprintf
	 *
	 * @return mixed|string
	 */
	function lang($var,$sprintf=false)
	{
		if( $var == '' ){
			return '';
        }

		global $LANG;
		$array_str = array( '{title}');
		$array_replace = array( 'Title' );
		if(isset($LANG[$var])) {
			$phrase = str_replace($array_str,$array_replace,$LANG[$var]);
		} else {
			$phrase = str_replace($array_str,$array_replace,$var);
		}
		
		if($sprintf)
		{
			$sprints = explode(',',$sprintf);
			if(is_array($sprints))
			{
				foreach($sprints as $sprint) {
					$phrase = sprintf($phrase,$sprint);
				}
			}
		}

		if($LANG != null && !isset($LANG[$var]))
		{
			error_log('[LANG] Missing translation for "'.$var.'"');
			if( in_dev() ){
				error_log(print_r(debug_backtrace(), TRUE));
            }
		}

		return $phrase;
	}

	function get_current_language(){
        global $lang_obj;
	    return $lang_obj->get_default_language()['language_code'];
    }

	/**
	 * Fetch lang value from smarty using lang code
	 *
	 * @param : { array } { $param } { array of parameters }
	 *
	 * @uses : { function lang() }
	 * @return mixed|string
	 */
	function smarty_lang($param)
	{
		if(getArrayValue($param, 'assign')=='') {
			return lang($param['code'],getArrayValue($param, 'sprintf'));
		}
		assign($param['assign'],lang($param['code'],$param['sprintf'] ?? false));
	}

	/**
	 * Get an array element by key
	 *
	 * @param array $array
	 * @param bool  $key
	 *
	 * @return bool|mixed : { value / false } { element value if found, else false }
	 * @internal param $ : { array } { $array } { array to check for element } { $array } { array to check for element }
	 * @internal param $ : { string / integeger } { $key } { element name or key } { $key } { element name or key }
	 */
	function getArrayValue($array = array(), $key = false)
	{
		if(!empty($array) && $key){
			if(isset($array[$key])){
				return $array[$key];
			}
			return false;
		}
		return false;
	}

	/**
	 * Fetch value of a constant
	 *
	 * @param bool $constantName
	 *
	 * @return bool|mixed : { val / false } { constant value if found, else false }
	 * @internal param $ : { string } { $constantName } { false by default, name of constant } { $constantName } { false by default, name of constant }
	 * @ref: { http://php.net/manual/en/function.constant.php }
	 */
	function getConstant($constantName = false)
	{
		if($constantName && defined($constantName))  {
			return constant($constantName);
		}
		return false;
	}

    /**
     * Function used to assign link
     *
     * @param : { array } { $params } { an array of parameters }
     * @param bool $fullurl
     *
     * @return string : { string } { buils link }
     */
	function cblink($params, $fullurl = false)
	{
		global $ClipBucket;
		$name = getArrayValue($params, 'name');
		if($name=='category') {
			return category_link($params['data'],$params['type']);
		}
		if($name=='sort') {
			return sort_link($params['sort'],'sort',$params['type']);
		}
		if($name=='time') {
			return sort_link($params['sort'],'time',$params['type']);
		}
		if($name=='tag') {
			return '/search_result.php?query='.urlencode($params['tag']).'&type='.$params['type'];
		}
		if($name=='category_search') {
			return '/search_result.php?category[]='.$params['category'].'&type='.$params['type'];
		}

		$val = 1;
		if (defined('SEO') && SEO !='yes') {
			$val = 0;
		}

		if( $fullurl ){
		    $link = BASEURL;
        } else {
		    $link = '';
        }

        if (isset($ClipBucket->links[$name]))
        {
            if( strpos(get_server_protocol(),$ClipBucket->links[$name][$val]) !== false ) {
                $link .= $ClipBucket->links[$name][$val];
            } else {
                $link .= '/'.$ClipBucket->links[$name][$val];
            }
        } else {
            $link = false;
        }
		
		$param_link = '';
		if(!empty($params['extra_params']))
		{
			preg_match('/\?/',$link,$matches);
			if(!empty($matches[0])) {
				$param_link = '&'.$params['extra_params'];
			} else {
				$param_link = '?'.$params['extra_params'];
			}
		}
		
		if(isset($params['assign'])) {
			assign($params['assign'],$link.$param_link);
		} else {
			return $link.$param_link;
		}
	}

	/**
	 * Function used to show rating
	 *
	 * @param : { array } { $params } { array of parameters }
	 *
	 * @return string
	 */
	function show_rating($params)
	{
		$class 	    = $params['class'] ? $params['class'] : 'rating_stars';
		$rating 	= $params['rating'];
		$ratings 	= $params['ratings'];
		$total 		= $params['total'];
		$style		= $params['style'];
		if(empty($style)){
			$style = config('rating_style');
        }

        if($total<=10){
            $total = 10;
        }
        $perc = $rating*100/$total;
        $disperc = 100 - $perc;
        if($ratings <= 0 && $disperc == 100){
            $disperc = 0;
        }

		$perc = $perc.'%';
		$disperc = $disperc.'%';
		switch($style)
		{
			case 'percentage':
			case 'percent':
			case 'perc':
			default:
				$likeClass = 'UserLiked';
				if(str_replace('%','',$perc) < '50') {
					$likeClass = 'UserDisliked';	
				}
				$ratingTemplate = '<div class="'.$class.'">
									<div class="ratingContainer">
										<span class="ratingText">'.$perc.'</span>';
				if($ratings > 0) {
					$ratingTemplate .= ' <span class="'.$likeClass.'">&nbsp;</span>';										
				}
				$ratingTemplate .='</div></div>';
				break;
			
			case 'bars':
			case 'Bars':
			case 'bar':
				$ratingTemplate = '<div class="'.$class.'">
					<div class="ratingContainer">
						<div class="LikeBar" style="width:'.$perc.'"></div>
						<div class="DislikeBar" style="width:'.$disperc.'"></div>
					</div>
				</div>';
				break;
			
			case 'numerical':
            case 'numbers':
			case 'number':
            case 'num':
				$likes = round($ratings*$perc/100);
				$dislikes = $ratings - $likes;
				$ratingTemplate = '<div class="'.$class.'">
					<div class="ratingContainer">
						<div class="ratingText">
							<span class="LikeText">'.$likes.' Likes</span>
							<span class="DislikeText">'.$dislikes.' Dislikes</span>
						</div>
					</div>
				</div>';
				break;
			
			case 'custom':
			case 'own_style':
				$file = LAYOUT.DIRECTORY_SEPARATOR.$params['file'];
				if(!empty($params['file']) && file_exists($file)) {
					// File exists, lets start assign things
					assign('perc',$perc); assign('disperc',$disperc);
					// Likes and Dislikes
					$likes = floor($ratings*$perc/100);
					$dislikes = $ratings - $likes;
					assign('likes',$likes);	assign('dislikes',$dislikes);
					Template($file,FALSE);										
				} else {
					$params['style'] = 'percent';
					return show_rating($params);	
				}
				break;
		}
		return $ratingTemplate;
	}

	/**
	 * Function used to display an ad
	 *
	 * @param $in
	 *
	 * @return string
	 */
	function ad($in)
	{
		return stripslashes($in);
	}

	/**
	 * Function used to get available function list
	 * for special place , read docs.clip-bucket.com
	 *
	 * @param $name
	 *
	 * @return bool|array
	 */
	function get_functions($name)
	{
		global $Cbucket;
		if(isset($Cbucket->$name)){
			$funcs = $Cbucket->$name;
			if(is_array($funcs) && count($funcs)>0) {
				return $funcs;
			}
			return false;
		}
	}

	/**
	 * Function used to add js in ClipBuckets JSArray
	 * @uses { class : $Cbucket } { function : addJS }
	 *
	 * @param $files
	 */
	function add_js($files)
	{
		global $Cbucket;
		$Cbucket->addJS($files);
	}

	/**
	 * Function add_header()
	 * this will be used to add new files in header array
	 * this is basically for plugins
	 * for specific page array('page'=>'file')
	 * ie array('uploadactive'=>'datepicker.js')
	 *
	 * @uses : { class : $Cbucket } { function : add_header }
	 *
	 * @param $files
	 */
	function add_header($files)
	{
		global $Cbucket;
		$Cbucket->add_header($files);
	}

	/**
	 * Adds admin header
	 * @uses : { class : $Cbucket } { function : add_admin_header }
	 *
	 * @param $files
	 */
	function add_admin_header($files)
	{
		global $Cbucket;
		$Cbucket->add_admin_header($files);
	}

	/**
	* Functions used to call functions when users views a channel
	* @param : { array } { $u } { array with details of user }
	*/
	function call_view_channel_functions($u)
	{
		$funcs = get_functions('view_channel_functions');
		if(is_array($funcs) && count($funcs)>0) {
			foreach($funcs as $func) {
				if(function_exists($func)) {
					$func($u);
				}
			}
		}
		increment_views($u['userid'],'channel');
	}

	/**
	* Functions used to call functions when users views a collection
	* @param : { array } { $cdetails } { array with details of collection }
	*/
	function call_view_collection_functions($cdetails)
	{
		$funcs = get_functions('view_collection_functions');
		if(is_array($funcs) && count($funcs)>0) {
			foreach($funcs as $func) {
				if(function_exists($func)) {
					$func($cdetails);
				}
			}
		};
		increment_views($cdetails['collection_id'],'collection');
	}

	/**
	 * Function used to increment views of an object
	 *
	 * @param      $id
	 * @param null $type
	 *
	 * @internal param $ : { integer } { $id } { id of element to update views for } { $id } { id of element to update views for }
	 * @internal param $ : { string } { $type } { type of object e.g video, user } { $type } { type of object e.g video, user }
	 * @action : database updating
	 */
	function increment_views($id,$type=NULL)
	{
		global $db;
		switch($type)
		{
			case 'v':
			case 'video':
			default:
				if(!isset($_COOKIE['video_'.$id])) {
                    $currentTime = time();
                    $views = (int)$videoViewsRecord['video_views'] + 1;
                    $db->update( tbl( 'video_views' ), array( 'video_views', 'last_updated' ), array( $views, $currentTime ), " video_id='$id' OR videokey='$id'" );
                    $query = "UPDATE " . tbl( "video_views" ) . " SET video_views = video_views + 1 WHERE video_id = {$id}";
                    $db->Execute( $query );
                    set_cookie_secure( 'video_' . $id, 'watched' );
                }
				break;

			case 'u':
			case 'user':
			case 'channel':
				if(!isset($_COOKIE['user_'.$id])) {
					$db->update(tbl("users"),array('profile_hits'),array('|f|profile_hits+1')," userid='$id'");
                    set_cookie_secure('user_'.$id,'watched');
				}
				break;

			case 'c':
			case 'collect':
			case 'collection':
				if(!isset($_COOKIE['collection_'.$id])) {
					$db->update(tbl('collections'),array('views'),array('|f|views+1')," collection_id = '$id'");
                    set_cookie_secure('collection_'.$id,'viewed');
				}
				break;
			
			case 'photos':
			case 'photo':
			case 'p':
				if(!isset($_COOKIE['photo_'.$id])) {
					$db->update(tbl('photos'),array('views','last_viewed'),array('|f|views+1',NOW())," photo_id = '$id'");
                    set_cookie_secure('photo_'.$id,'viewed');
				}
				break;
		}
		
	}

	/**
	 * Function used to increment views of an object
	 *
	 * @param      $id
	 * @param null $type
	 *
	 * @internal param $ : { integer } { $id } { id of element to update views for } { $id } { id of element to update views for }
	 * @internal param $ : { string } { $type } { type of object e.g video, user } { $type } { type of object e.g video, user }
	 * @action : database updating
	 */
	function increment_views_new($id,$type=NULL)
	{
		global $db;
		switch($type)
		{
			case 'v':
			case 'video':
			default:
				if(!isset($_COOKIE['video_'.$id]))
				{
					$vdetails = get_video_details($id);
					// Cookie life time at least 1 hour else if video duration is bigger set at video time.
					$cookieTime = ($vdetails['duration'] > 3600) ? $vdetails['duration'] : $cookieTime = 3600;
					$db->update(tbl('video'),array('views', 'last_viewed'),array('|f|views+1','|f|NOW()')," videoid='$id' OR videokey='$id'");
                    set_cookie_secure('video_'.$id,'watched');

					$userid = userid();
					if( $userid ){
						$log_array = array(
							'success'=> 'NULL',
							'action_obj_id' => $id,
							'userid' => $userid,
							'details'=> $vdetails['title']
						);
						insert_log('Watch a video',$log_array);
					}
				}
				break;

			case 'u':
			case 'user':
			case 'channel':
				if(!isset($_COOKIE['user_'.$id])) {
					$db->update(tbl('users'),array('profile_hits'),array('|f|profile_hits+1')," userid='$id'");
                    set_cookie_secure('user_'.$id,'watched');
				}
				break;

			case 'c':
			case 'collect':
			case 'collection':
				if(!isset($_COOKIE['collection_'.$id])) {
					$db->update(tbl('collections'),array('views'),array('|f|views+1')," collection_id = '$id'");
                    set_cookie_secure('collection_'.$id,'viewed');
				}
				break;
			
			case 'photos':
			case 'photo':
			case 'p':
				if(!isset($_COOKIE['photo_'.$id])) {
					$db->update(tbl('photos'),array('views','last_viewed'),array('|f|views+1',NOW())," photo_id = '$id'");
                    set_cookie_secure('photo_'.$id,'viewed');
				}
				break;
		}
		
	}

	/**
	 * Function used to get post var
	 *
	 * @param : { string } { $var } { variable to get value for }
	 *
	 * @return mixed
	 */
	function post($var) {
		return $_POST[$var];
	}

	/**
	* Function used to show flag form
	* @param : { array } { $array } { array of parameters }
	*/
	function show_share_form($array)
	{
		assign('params',$array);

		global $userquery;
		$contacts = $userquery->get_contacts(userid(),0);
		assign('contacts', $contacts);
		Template('blocks/common/share.html');
	}
	
	/**
	* Function used to show flag form
	* @param : { array } { $array } { array of parameters }
	*/
	function show_flag_form($array)
	{
		assign('params',$array);
        Template('blocks/common/report.html');
	}
	
	/**
	* Function used to show playlist form
	* @param : { array } { $array } { array of parameters }
	*/
	function show_playlist_form($array) {
		global $cbvid;
		
		assign('params',$array);
		assign('type',$array['type']);
		// decides to show all or user only playlists
		// depending on the parameters passed to it
		if (!empty($array['user'])) {
			$playlists = $cbvid->action->get_playlists($array);
		} else if (userid()) {
			$playlists = $cbvid->action->get_playlists();
		}
		assign('playlists',$playlists);
        Template('blocks/common/playlist.html');
	}

	/**
	 * Function used to show collection form
	 * @internal param $ : { array } { $params } { array with parameters }
	 */
	function show_collection_form() {
		global $db,$cbcollection;
		$brace = 1;
		if(!userid()) {
			$loggedIn = 'not';
		} else {		
			$collectArray = array('order'=>' collection_name ASC','type'=>'videos','user'=>userid(),'public_upload'=>'yes');
			$collections = $cbcollection->get_collections($collectArray,$brace);           
            $contributions = $cbcollection->get_contributor_collections(userid());
            if($contributions) {
                if(!$collections) {
                    $collections = $contributions;
                } else {
                    $collections = array_merge($collections,$contributions);
                }
            }
			assign('collections',$collections);
			assign('contributions',$contributions);
		}
		Template('/blocks/collection_form.html');
	}

	/**
	 * Convert timestamp to date
	 *
	 * @param null $format
	 * @param null $timestamp
	 *
	 * @return false|string : { string } { time formatted into date }
	 * @internal param $ : { string } { $format } { current format of date } { $format } { current format of date }
	 * @internal param $ : { string } { $timestamp } { time to be converted to date } { $timestamp } { time to be converted to date }
	 */
	function cbdate($format=NULL,$timestamp=NULL)
	{
		if(!$format){
			$format = DATE_FORMAT;
        }

		if( is_string($timestamp) ){
			$timestamp = strtotime($timestamp);
        }

		if( $timestamp < 0 ){
		    return 'N/A';
        }

		if(!$timestamp){
			return date($format);
        }

		return date($format,$timestamp);
	}

	function cbdatetime($format=NULL,$timestamp=NULL)
	{
		if(!$format){
			$format = DATE_FORMAT.' h:m:s';
        }

		return cbdate($format,$timestamp);
	}

	/**
	 * Function used to count pages and return total divided
	 *
	 * @param $total
	 * @param $count
	 *
	 * @return float : { integer } { $total_pages }
	 * @internal param $ { integer } { $total } { total number of pages }
	 * @internal param $ { integer } { $count } { number of pages to be displayed }
	 */
	function count_pages($total,$count) {
		if($count<1){
			$count = 1;
        }
		$records = $total/$count;
		return (int)round($records+0.49,0);
	}

	/**
	 * Fetch user level against a given userid
	 * @uses : { class : $userquery } { function : usr_levels() }
	 *
	 * @param $id
	 *
	 * @return
	 */
	function get_user_level($id) {
		global $userquery;
		return $userquery->usr_levels[$id];
	}

	/**
	 * This function used to check weather user is online or not
	 *
	 * @param : { string } { $time } { last active time }
	 * @param string $margin
	 *
	 * @return string : { string  }{ status of user e.g online or offline }
	 */
	function is_online($time,$margin='5') {
		$margin = $margin*60;
		$active = strtotime($time);
		$curr = time();
		$diff = $curr - $active;
		if($diff > $margin) {
			return lang('offline');
		}
		return lang('online');
	}

	/**
	 * ClipBucket Form Validator
	 * this function controls the whole logic of how to operate input
	 * validate it, generate proper error
	 *
	 * @param $input
	 * @param $array
	 *
	 * @internal param $ : { array } { $input } { array of form values } { $input } { array of form values }
	 * @internal param $ : { array } { $array } { array of form fields } { $array } { array of form fields }
	 */
	function validate_cb_form($input,$array)
	{
		//Check the Collpase Category Checkboxes 
		if(is_array($input))
		{
			foreach($input as $field)
			{
				$field['name'] = formObj::rmBrackets($field['name']);
				$title = $field['title'];
				$val = $array[$field['name']];
				$req = $field['required'];
				$invalid_err = $field['invalid_err'];
				$function_error_msg = $field['function_error_msg'];
				if(is_string($val)) {
					if(!isUTF8($val)){
						$val = utf8_decode($val);
                    }
					$length = strlen($val);
				}
				$min_len = $field['min_length'] ?? 0;
				$max_len = $field['max_length'] ;
				$rel_val = $array[$field['relative_to']];
				
				if(empty($invalid_err)) {
					$invalid_err = sprintf("Invalid %s : '%s'",$title,$val);
				}
				if(is_array($array[$field['name']])) {
					$invalid_err = '';
				}
					
				//Checking if its required or not
				if($req == 'yes') {
					if(empty($val) && !is_array($array[$field['name']])) {
						e($invalid_err);
						$block = true;
					} else {
						$block = false;
					}
				}
				$funct_err = is_valid_value($field['validate_function'],$val);
				if($block!=true)
				{
					//Checking Syntax
					if(!$funct_err) {
						if(!empty($function_error_msg)) {
							e($function_error_msg);
						} elseif(!empty($invalid_err)) {
							e($invalid_err);
						}
					}
					
					if(!is_valid_syntax($field['syntax_type'],$val)) {
						if(!empty($invalid_err)) {
							e($invalid_err);
						}
					}
					if(isset($max_len)) {
						if($length > $max_len || $length < $min_len) {
							e(sprintf(lang('please_enter_val_bw_min_max'),$title,$min_len,$field['max_length']));
						}
					}
					if(function_exists($field['db_value_check_func'])) {
						$db_val_result = $field['db_value_check_func']($val);
						if($db_val_result != $field['db_value_exists']) {
							if(!empty($field['db_value_err'])) {
								e($field['db_value_err']);
							} elseif(!empty($invalid_err)) {
								e($invalid_err);
							}
						}	
					}
                    if(isset($field['constraint_func']) && function_exists($field['constraint_func'])) {
                        if( !$field['constraint_func']($val) ){
                            e($field['constraint_err']);
                        }
                    }
					if($field['relative_type']!='')
					{
						switch($field['relative_type'])
						{
							case 'exact':
								if($rel_val != $val) {
									if(!empty($field['relative_err'])) {
										e($field['relative_err']);
									} elseif(!empty($invalid_err)) {
										e($invalid_err);
									}
								}
								break;
						}
					}
				}	
			}
		}
	}

	/**
	 * Function used to count age from date
	 *
	 * @param : { string } { $input } { date to count age }
	 *
	 * @return float : { integer } { $iYears } { years old }
	 */
	function get_age($input) { 
		$time = strtotime($input);
		$iMonth = date('m',$time);
		$iDay = date('d',$time);
		$iYear = date('Y',$time);
		$iTimeStamp = (mktime() - 86400) - mktime(0, 0, 0, $iMonth, $iDay, $iYear); 
		$iDays = $iTimeStamp / 86400;  
		return floor($iDays / 365 );
	}

	/**
	 * Function used to check time span a time difference function that outputs the
	 * time passed in facebook's style: 1 day ago, or 4 months ago. I took andrew dot
	 * macrobert at gmail dot com function and tweaked it a bit. On a strict enviroment
	 * it was throwing errors, plus I needed it to calculate the difference in time between
	 * a past date and a future date
	 * thanks to yasmary at gmail dot com
	 *
	 * @param : { string } { $date } { date to be converted in nicetime }
	 * @param bool $istime
	 *
	 * @return string
	 * @uses : { function : lang() }
	 */
	function nicetime($date,$istime=false)
	{
		if(empty($date)) {
			return lang('no_date_provided');
		}
		$periods = array(lang('second'), lang('minute'), lang('hour'), lang('day'), lang('week'), lang('month'), lang('year'), lang('decade'));
		$lengths = array(60,60,24,7,4.35,12,10);
		$now = time();
		if(!$istime) {
			$unix_date = strtotime($date);
		} else {
	   		$unix_date = $date;
		}
		   // check validity of date
		if(empty($unix_date)  || $unix_date<1) {   
			return lang('bad_date');
		}
		// is it future date or past date
		if($now > $unix_date) {   
			//time_ago
			$difference = $now - $unix_date;
			$tense = 'time_ago';
		   
		} else {
			//from_now
			$difference = $unix_date - $now;
			$tense = 'from_now';
		}
		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);
	   
		if($difference > 1) {
			// *** Dont apply plural if terms ending by a "s". Typically, french word for "month" is "mois".
			if(substr($periods[$j], -1) != "s") {
				$periods[$j] .= 's';
			}
		}
		return sprintf(lang($tense),$difference,$periods[$j]);
	}

	/**
	 * Function used to format outgoing link
	 *
	 * @param : { string } { $out } { link to some webpage }
	 *
	 * @return string : { string } { HTML anchor tag with link in place }
	 */
	function outgoing_link($out)
	{
		preg_match("/http/",$out,$matches);
		if(empty($matches[0])) {
			$out = "http://".$out;
		}
		return '<a href="'.$out.'" target="_blank">'.$out.'</a>';
	}

	/**
	 * Function used to get country via country code
	 *
	 * @param : { string } { $code } { country code name }
	 *
	 * @return bool|string : { string } { country name of flag }
	 */
	function get_country($code)
	{
		global $db;
		$result = $db->select(tbl("countries"),"name_en,iso2"," iso2='$code' OR iso3='$code'");
		if(count($result)>0) {
			$flag = '';
			$result = $result[0];
			if(SHOW_COUNTRY_FLAG) {
				$flag = '<img src="/images/icons/country/'.strtolower($result['iso2']).'.png" alt="" border="0">&nbsp;';
			}
			return $flag.$result['name_en'];
		}
		return false;
	}

	/**
	 * function used to get collections
	 * @uses : { class : $cbcollection } { function : get_collections }
	 *
	 * @param $param
	 *
	 * @return array|bool
	 */
	function get_collections($param)
	{
		global $cbcollection;
		return $cbcollection->get_collections($param);
	}

	/**
	 * function used to get users
	 * @uses : { class : $userquery } { function : get_users }
	 *
	 * @param $param
	 *
	 * @return bool|mixed
	 */
	function get_users($param)
	{
		global $userquery;
		return $userquery->get_users($param);
	}

	/**
	 * Function used to call functions
	 *
	 * @param      $in
	 * @param null $params
	 *
	 * @internal param $ : { array } { $in } { array with functions to be called } { $in } { array with functions to be called }
	 * @internal param $ : { array } { $params } { array with parameters for functions } { $params } { array with parameters for functions }
	 */
	function call_functions($in,$params=NULL)
	{
		if(is_array($in))
		{
			foreach($in as $i)
			{
				if(function_exists($i))
				{
					if(!$params) {
						$i();
					} else {
						$i($params);
					}
				}
			}
		} else {
			if(function_exists($in))
			{
				if(!$params) {
					$in();
				} else {
					$in($params);
				}
			}
					
		}
	}

	/**
	 * Category Link is used to return category based link
	 *
	 * @param $data
	 * @param $type
	 *
	 * @return string : { string } { sorting link }
	 * @internal param $ : { array } { $data } { array with category details } { $data } { array with category details }
	 * @internal param $ : { string } { $type } { type of category e.g videos } { $type } { type of category e.g videos }
	 */
	function category_link($data,$type): string
    {
		$sort = '';
		$time = '';
		$seo_cat_name = '';

		if(SEO=='yes')
		{
			if(isset($_GET['sort']) && $_GET['sort'] != ''){
				$sort = '/'.$_GET['sort'];
            }
			if( isset($_GET['time']) && $_GET['time'] != ''){
				$time = '/'.$_GET['time'];
            }
		} else {
			if(isset($_GET['sort']) && $_GET['sort'] != ''){
				$sort = '&sort='.$_GET['sort'];
            }
			if( isset($_GET['time']) && $_GET['time'] != ''){
				$time = '&time='.$_GET['time'];
            }
			if( isset($_GET['seo_cat_name']) && $_GET['seo_cat_name'] != ''){
				$time = '&seo_cat_name='.$_GET['seo_cat_name'];
            }
		}

		switch($type)
		{
			case 'video':
            case 'videos':
			case 'v':
				if(SEO=='yes') {
					return '/videos/'.$data['category_id'].'/'.SEO($data['category_name']).$sort.$time.'/';
				}
				return '/videos.php?cat='.$data['category_id'].$sort.$time.$seo_cat_name;
			
			case 'channels':
            case 'channel':
            case 'c':
            case 'user':
				if(SEO=='yes') {
					return '/channels/'.$data['category_id'].'/'.SEO($data['category_name']).$sort.$time.'/';
				}
				return '/channels.php?cat='.$data['category_id'].$sort.$time.$seo_cat_name;

			default:
				if(THIS_PAGE=='photos') {
					$type = 'photos';
				}

				if(defined("IN_MODULE")) {
					global $prefix_catlink;
					$url = 'cat='.$data['category_id'].$sort.$time.'&page='.$_GET['page'].$seo_cat_name;
					$url = $prefix_catlink.$url;
					$rm_array = array('cat','sort','time','page','seo_cat_name');
					if($prefix_catlink) {
						$rm_array[] = 'p';
					}
					$plugURL = queryString($url,$rm_array);
					return $plugURL;
				}
								
				if(SEO=='yes') {
					return '/'.$type.'/'.$data['category_id'].'/'.SEO($data['category_name']).$sort.$time.'/';
				}
				return '/'.$type.'.php?cat='.$data['category_id'].$sort.$time.$seo_cat_name;
		}
	}

	/**
	 * Sorting Links is used to return Sorting based link
	 *
	 * @param        $sort
	 * @param string $mode
	 * @param        $type
	 *
	 * @return string : { string } { sorting link }
	 * @internal param $ : { string } { $sort } { specifies sorting style } { $sort } { specifies sorting style }
	 * @internal param $ : { string } { $mode } { element to sort e.g time } { $mode } { element to sort e.g time }
	 * @internal param $ : { string } { $type } { type of element to sort e.g channels } { $type } { type of element to sort e.g channels }
	 */
	function sort_link($sort,$mode,$type)
	{
		switch($type) {
			case 'video':
			case 'videos':
			case 'v':
				if(!isset($_GET['cat'])){
					$_GET['cat'] = 'all';
                }
				if(!isset($_GET['time'])){
					$_GET['time'] = 'all_time';
                }
				if(!isset($_GET['sort'])){
					$_GET['sort'] = 'most_recent';
                }
				if(!isset($_GET['page'])){
					$_GET['page'] = 1;
                }
				if(!isset($_GET['seo_cat_name'])){
					$_GET['seo_cat_name'] = 'All';
                }
				
				$_GET['page'] = 1;
				if($mode == 'sort') {
					$sorting = $sort;
				} else {
					$sorting = $_GET['sort'];
				}
				if($mode == 'time') {
					$time = $sort;
				} else {
					$time = $_GET['time'];
				}
					
				if (SEO=='yes') {
					return '/videos/'.$_GET['cat'].'/'.$_GET['seo_cat_name'].'/'.$sorting.'/'.$time.'/'.$_GET['page'];
				}
				return '/videos.php?cat='.$_GET['cat'].'&sort='.$sorting.'&time='.$time.'&page='.$_GET['page'].'&seo_cat_name='.$_GET['seo_cat_name'];
			
			case 'channels':
			case 'channel':
				if(!isset($_GET['cat'])){
					$_GET['cat'] = 'all';
                }
				if(!isset($_GET['time'])){
					$_GET['time'] = 'all_time';
                }
				if(!isset($_GET['sort'])){
					$_GET['sort'] = 'most_recent';
                }
				if(!isset($_GET['page'])){
					$_GET['page'] = 1;
                }
				if(!isset($_GET['seo_cat_name'])){
					$_GET['seo_cat_name'] = 'All';
                }
				
				if($mode == 'sort') {
					$sorting = $sort;
				} else {
					$sorting = $_GET['sort'];
				}
				if($mode == 'time') {
					$time = $sort;
				} else {
					$time = $_GET['time'];
				}
					
				if(SEO=='yes') {
					return '/channels/'.$_GET['cat'].'/'.$_GET['seo_cat_name'].'/'.$sorting.'/'.$time.'/'.$_GET['page'];
				}
				return '/channels.php?cat='.$_GET['cat'].'&sort='.$sorting.'&time='.$time.'&page='.$_GET['page'].'&seo_cat_name='.$_GET['seo_cat_name'];


			default:
				if(!isset($_GET['cat'])){
					$_GET['cat'] = 'all';
                }
				if(!isset($_GET['time'])){
					$_GET['time'] = 'all_time';
                }
				if(!isset($_GET['sort'])){
					$_GET['sort'] = 'most_recent';
                }
				if(!isset($_GET['page'])){
					$_GET['page'] = 1;
                }
				if(!isset($_GET['seo_cat_name'])){
					$_GET['seo_cat_name'] = 'All';
                }
				
				if($mode == 'sort') {
					$sorting = $sort;
				} else {
					$sorting = $_GET['sort'];
				}
				if($mode == 'time') {
					$time = $sort;
				} else {
					$time = $_GET['time'];
				}
				
				if(THIS_PAGE=='photos') {
					$type = 'photos';
				}
				
				if(defined("IN_MODULE")) {
					$url = 'cat='.$_GET['cat'].'&sort='.$sorting.'&time='.$time.'&page='.$_GET['page'].'&seo_cat_name='.$_GET['seo_cat_name'];
					$plugURL = queryString($url,array("cat","sort","time","page","seo_cat_name"));
					return $plugURL;
				}
				
				if(SEO=='yes') {
					return '/'.$type.'/'.$_GET['cat'].'/'.$_GET['seo_cat_name'].'/'.$sorting.'/'.$time.'/'.$_GET['page'];
				}
				return '/'.$type.'.php?cat='.$_GET['cat'].'&sort='.$sorting.'&time='.$time.'&page='.$_GET['page'].'&seo_cat_name='.$_GET['seo_cat_name'];
		}
	}

	/**
	* Function used to get flag options
	* @uses : { class : $action } { var : $report_opts }
	*/
	function get_flag_options()
	{
		$action = new cbactions();
		$action->init();
		return $action->report_opts;
	}

	/**
	 * Function used to display flag type
	 * @uses : { get_flag_options() function }
	 *
	 * @param $id
	 *
	 * @return
	 */
	function flag_type($id)
	{
		$flag_opts = get_flag_options();
		return $flag_opts[$id];
	}

	/**
	* Function used to load captcha field
	* @uses : { class : $Cbucket }  { var : $captchas }
	*/
	function get_captcha()
	{
		global $Cbucket;
		if(count($Cbucket->captchas)>0) {   
			return $Cbucket->captchas[0];
		}
		return false;
	}
	
	/**
	* Function used to load captcha
	* @param : { array } { $params } { an array of parametrs }
	*/
	define('GLOBAL_CB_CAPTCHA','cb_captcha');
	function load_captcha($params)
	{
		global $total_captchas_loaded;
		switch($params['load']) {
			case 'function':
				if($total_captchas_loaded!=0){
					$total_captchas_loaded = $total_captchas_loaded+1;
                } else {
					$total_captchas_loaded = 1;
                }
				$_SESSION['total_captchas_loaded'] = $total_captchas_loaded;
				if(function_exists($params['captcha']['load_function'])) {
					return $params['captcha']['load_function']().'<input name="cb_captcha_enabled" type="hidden" id="cb_captcha_enabled" value="yes" />';
				}
				break;

			case 'field':
				echo '<input type="text" '.$params['field_params'].' name="'.GLOBAL_CB_CAPTCHA.'" />';
				break;
		}
	}
	
	/**
	* Function used to verify captcha
	*/
	function verify_captcha()
	{
		$var = post('cb_captcha_enabled');
		if($var == 'yes') {
			$captcha = get_captcha();
			$val = $captcha['validate_function'](post(GLOBAL_CB_CAPTCHA));
			return $val;
		}
		return true;
	}

	/**
	 * Adds title for ClipBucket powered website
	 *
	 * @param bool $params
	 *
	 * @internal param $ : { string } { $title } { title to be given to page } { $title } { title to be given to page }
	 */
	function cbtitle($params=false)
	{
		global $cbsubtitle;
		$sub_sep = getArrayValue($params, 'sub_sep');
		if(!$sub_sep) {
			$sub_sep = '-';
		}
		//Getting Subtitle
		if(!$cbsubtitle) {
			echo display_clean(TITLE.' - '.SLOGAN);
		} else {
			echo display_clean($cbsubtitle.' '.$sub_sep.' '.TITLE);
		}
	}
	
	/**
	* Adds subtitle for any given page
	* @param : { string } { $title } { title to be given to page }
	*/
	function subtitle($title)
	{
		global $cbsubtitle;
		$cbsubtitle = $title;
	}

	/**
	 * Extract user's name using userid
	 * @uses : { class : $userquery } { function : get_username }
	 *
	 * @param $uid
	 *
	 * @return
	 */
	function get_username($uid)
	{
		global $userquery;
		return $userquery->get_username($uid);
	}

	/**
	 * Extract collection's name using Collection's id
	 * function is mostly used via Smarty template engine
	 *
	 * @uses : { class : $cbcollection } { function : get_collection_field }
	 *
	 * @param        $cid
	 * @param string $field
	 *
	 * @return bool
	 */
	function get_collection_field($cid,$field='collection_name')
	{
		global $cbcollection;
		return $cbcollection->get_collection_field($cid,$field);
	}

	/**
	 * Deletes all photos found inside of given collection
	 * function is used when whole collection is being deleted
	 *
	 * @param : { array } { $details } { an array with collection's details }
	 *
	 * @action: makes photos orphan
	 */
	function delete_collection_photos($details)
	{
		global $cbphoto;
		$type = $details['type'];
		if($type == 'photos')
		{
			$ps = $cbphoto->get_photos(array("collection"=>$details['collection_id']));
			if(!empty($ps))
			{
				foreach($ps as $p) {
					$cbphoto->make_photo_orphan($details,$p['photo_id']);	
				}
				unset($ps); // Empty $ps. Avoiding the duplication prob
			}
		}
	}

	/**
	 * Get ClipBucket's header menu
	 * @uses : { class : $Cbucket } { function : head_menu }
	 *
	 * @param null $params
	 *
	 * @return array
	 */
	function head_menu($params=NULL)
	{
		global $Cbucket;
		return $Cbucket->head_menu($params);
	}

	/**
	 * Get ClipBucket's menu
	 * @uses : { class : $Cbucket } { function : cbMenu }
	 *
	 * @param null $params
	 *
	 * @return array|string
	 */
	function cbMenu($params=NULL)
	{
		global $Cbucket;
		return $Cbucket->cbMenu($params);
	}

	/**
	 * Get ClipBucket's footer menu
	 * @uses : { class : $Cbucket } { function : foot_menu }
	 *
	 * @param null $params
	 *
	 * @return array
	 */
	function foot_menu($params=NULL)
	{
		global $Cbucket;
		return $Cbucket->foot_menu($params);
	}

	/**
	 * Converts given array XML into a PHP array
	 *
	 * @param : { array } { $array } { array to be converted into XML }
	 * @param int    $get_attributes
	 * @param string $priority
	 * @param bool   $is_url
	 *
	 * @return array|bool : { string } { $xml } { array converted into XML }
	 */
	function xml2array($url, $get_attributes = 1, $priority = 'tag',$is_url=true)
	{
		$contents = "";

		if($is_url)
		{
			$fp = @ fopen($url, 'rb');
			if( $fp )
			{
				while(!feof($fp))
				{
					$contents .= fread($fp, 8192);
				}
			} else {
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_USERAGENT,
				'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/3.0.0.2');
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0); 
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, 600);
				$contents = curl_exec($ch);
				curl_close($ch);
			}
			fclose($fp);

			if(!$contents)
				return false;
		} else {
			$contents = $url;
		}

		if (!function_exists('xml_parser_create')) {
			return false;
		}
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);
		if (!$xml_values) {
			return false;
		}
		$xml_array = array ();

		$current = & $xml_array;
		$repeated_tag_index = array ();
		foreach ($xml_values as $data)
		{
			unset ($attributes, $value);
			extract($data);
			$result = array ();
			$attributes_data = array ();
			if (isset ($value))
			{
				if ($priority == 'tag') {
					$result = $value;
				} else {
					$result['value'] = $value;
				}
			}
			if (isset ($attributes) and $get_attributes)
			{
				foreach ($attributes as $attr => $val)
				{
					if ($priority == 'tag') {
						$attributes_data[$attr] = $val;
					} else{
						$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
					}
				}
			}
			if ($type == "open")
			{
				$parent[$level -1] = & $current;
				if (!is_array($current) or (!in_array($tag, array_keys($current))))
				{
					$current[$tag] = $result;
					if ($attributes_data) {
						$current[$tag . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level] = 1;
					$current = & $current[$tag];
				} else {
					if (isset ($current[$tag][0])) {
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					} else {
						$current[$tag] = array (
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag . '_' . $level] = 2;
						if (isset ($current[$tag . '_attr'])) {
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current = & $current[$tag][$last_item_index];
				}
			} elseif ($type == "complete") {
				if (!isset ($current[$tag])) {
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $attributes_data)
						$current[$tag . '_attr'] = $attributes_data;
				} else {
					if (isset ($current[$tag][0]) and is_array($current[$tag])) {
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						if ($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					} else {
						$current[$tag] = array (
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes)
						{
							if (isset ($current[$tag . '_attr'])) {
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset ($current[$tag . '_attr']);
							}
							if ($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
					}
				}
			} elseif ($type == 'close') {
				$current = & $parent[$level -1];
			}
		}
		return ($xml_array);
	}

	/**
	 * Converts given array into valid XML
	 *
	 * @param : { array } { $array } { array to be converted into XML }
	 * @param int $level
	 *
	 * @return string : { string } { $xml } { array converted into XML }
	 */
	function array2xml($array, $level=1)
	{
		$xml = '';
		foreach ($array as $key=>$value)
		{
			$key = strtolower($key);
			if (is_object($value)) // convert object to array
			{
				$value=get_object_vars($value);
			}
			
			if (is_array($value))
			{
				$multi_tags = false;
				foreach($value as $key2=>$value2)
				{
					if (is_object($value2)) // convert object to array
					{
				 		$value2=get_object_vars($value2);
					}
					if (is_array($value2))
					{
						$xml .= str_repeat("\t",$level)."<$key>\n";
						$xml .= array2xml($value2, $level+1);
						$xml .= str_repeat("\t",$level)."</$key>\n";
						$multi_tags = true;
					} else {
						if (trim($value2)!='') {
							if (htmlspecialchars($value2)!=$value2)
							{
								$xml .= str_repeat("\t",$level).
										"<$key2><![CDATA[$value2]]>". // changed $key to $key2... didn't work otherwise.
										"</$key2>\n";
							} else {
								$xml .= str_repeat("\t",$level).
										"<$key2>$value2</$key2>\n"; // changed $key to $key2
							}
						}
						$multi_tags = true;
					}
				}
				if (!$multi_tags and count($value)>0) {
					$xml .= str_repeat("\t",$level)."<$key>\n";
					$xml .= array2xml($value, $level+1);
					$xml .= str_repeat("\t",$level)."</$key>\n";
				}
			
			} else {
				if (trim($value)!='')
				{
					echo "value=$value<br>";
					if (htmlspecialchars($value)!=$value)
					{
						$xml .= str_repeat("\t",$level)."<$key>".
								"<![CDATA[$value]]></$key>\n";
					} else {
						$xml .= str_repeat("\t",$level).
								"<$key>$value</$key>\n";
					}
				}
			}
		}
		return $xml;
	}

	/**
	 * This function used to include headers in <head> tag
	 * it will check weather to include the file or not
	 * it will take file and its type as an array
	 * then compare its type with THIS_PAGE constant
	 * if header has TYPE of THIS_PAGE then it will be inlucded
	 *
	 * @param : { array } { $params } { parameters array e.g file, type }
	 *
	 * @return bool : { false }
	 */
	function include_header($params)
	{
		$file = getArrayValue($params, 'file');
		$type = getArrayValue($params, 'type');
		if($file == 'global_header') {
			Template(BASEDIR.'/styles/global/head.html',false);
			return false;
		}
		if($file == 'admin_bar')
		{
			if(has_access('admin_access',TRUE))
			{
				Template(BASEDIR.'/styles/global/admin_bar.html',false);
				return false;
			}
		}
		if(!$type) {
			$type = "global";
		}
		if(is_includeable($type)) {
			Template($file,false);
		}		
		return false;
	}

	/**
	 * Function used to check weather to include given file or not
	 * it will take array of pages if array has ACTIVE PAGE or has GLOBAL value
	 * it will return true otherwise FALSE
	 *
	 * @param : { array } { $array } { array with files to include }
	 *
	 * @return bool : { boolean } { true or false depending on situation }
	 */
	function is_includeable($array)
	{
		if(!is_array($array)) {
			$array = array($array);
		}
		if(in_array(THIS_PAGE,$array) || in_array('global',$array)) {
			return true;
		}
		return false;
	}
	
	/**
	* This function works the same way as include_header
	* but the only difference is , it is used to include
	* JS files only
	*
	* @param : { array } { $params } { array with parameters e.g  file, type}
	* @return : { string } { javascript tag with file in src }
	*/
	$the_js_files = array();
	function include_js($params)
	{
		global $the_js_files;
		$file = $params['file'];
		$type = $params['type'];
		if(!in_array($file,$the_js_files))
		{
			$the_js_files[] = $file;
			if($type == 'global') {
				return '<script src="'.JS_URL.'/'.$file.'" type="text/javascript"></script>';
			}
			if($type == 'plugin') {
                return '<script src="'.PLUG_URL.'/'.$file.'" type="text/javascript"></script>';
            }
			if(is_array($type)) {
				foreach($type as $t) {
					if($t == THIS_PAGE){
						return '<script src="'.JS_URL.'/'.$file.'" type="text/javascript"></script>';
                    }
				}
			} else if($type == THIS_PAGE) {
				return '<script src="'.JS_URL.'/'.$file.'" type="text/javascript"></script>';
			}
		}
		return false;
	}

	function get_ffmpeg_codecs($type)
	{
		switch($type)
		{
			case 'audio':
				$codecs = array(
					'aac',
					'aac_latm',
					'libfaac',
					'libvo_aacenc',
					'libxvid',
					'libmp3lame'
				);
				break;

			case 'video':
			default:
				$codecs = array(
					'libx264',
					'libtheora'
				);
				break;
		}

		$codec_installed = array();
		foreach($codecs as $codec)
		{
			$get_codec = shell_output(  get_binaries('ffmpeg').' -codecs 2>/dev/null | grep "'.$codec.'"');
			if( $get_codec ){
				$codec_installed[] = $codec;
            }
		}

		return $codec_installed;
	}

	/**
	 * Check if a module is installed on server or not using path
	 *
	 * @param : { array } { $params } { array with parameters including path }
	 *
	 * @return array|bool|string : { string / boolean } { path if found, else false }
	 */
	function check_module_path($params)
	{
		$path = $params['path'];
		if($path['get_path'])
			$path = get_binaries($path);
		$array = array();
		$result = shell_output($path." -version");
		if ($result) {
			if(strstr($result,'error') || strstr(($result),'No such file or directory')) {
				$error['error'] = $result;
				if($params['assign']) {
					assign($params['assign'],$error);
				}
				return false;
			}		
			if($params['assign']) {
				$array['status'] = 'ok';
				$array['version'] = parse_version($params['path'],$result);
				assign($params['assign'],$array);
				return $array;
			}
			return $result;
		}
        if($params['assign']) {
            assign($params['assign']['error'],"error");
        } else {
            return false;
        }
	}

	/**
	 * Check if FFMPEG is installed by extracting its version
	 *
	 * @param : { string } { $path } { path to FFMPEG }
	 *
	 * @return bool|mixed|string : { string } { version if found, else false }
	 */
	function check_ffmpeg($path)
	{
		$path = get_binaries($path);
		$matches = array();
		$result = shell_output($path." -version");
		if($result)
		{
			if (preg_match("/git/i", $result)) {
				preg_match('@^(?:ffmpeg version)?([^C]+)@i',$result, $matches);
				$host = $matches[1];
				return $host;
			}

            preg_match("/(?:ffmpeg\\s)(?:version\\s)?(\\d\\.\\d\\.(?:\\d|[\\w]+))/i", strtolower($result), $matches);
            if(count($matches) > 0) {
                $version = array_pop($matches);
                return $version;
            }
			return false;
		}
		return false;
	}

	/**
	 * Check if PHP_CLI is installed by extracting its version
	 *
	 * @param : { string } { $path } { path to PHP_CLI }
	 *
	 * @return bool|mixed : { string } { version if found, else false }
	 */
	function check_php_cli($path)
	{
		$path = get_binaries($path);
		$matches = array();
		$result = shell_output($path." --version");
		if($result) {
			preg_match("/(?:php\\s)(?:version\\s)?(\\d\\.\\d\\.(?:\\d|[\\w]+))/i", strtolower($result), $matches);
			if(count($matches) > 0) {
				$version = array_pop($matches);
				return $version;
			}
			return false;
		}
		return false;
	}

	/**
	 * Check if MediaInfo is installed by extracting its version
	 *
	 * @param : { string } { $path } { path to MediaInfo }
	 *
	 * @return  : { string } { version if found, else false }
	 */
	function check_media_info($path)
	{
		$path = get_binaries($path);
		$result = shell_output($path." --version");
		$media_info_version  = explode('v', $result);
		return $media_info_version[1];
	}

	/**
	 * Check if FFPROBE is installed by extracting its version
	 *
	 * @param : { string } { $path } { path to FFPROBE }
	 *
	 * @return array|string : { string } { version if found, else false }
	 */
	function check_ffprobe_path($path)
	{
		$path = get_binaries($path);
		$result = shell_output($path." -version");
		$result = explode(" ", $result);
		$result = $result[2];
		return $result;
	}

	/**
	 * Function used to parse versions from info
	 *
	 * @param : { string } { $path } { tool to check }
	 * @param : { string } { $result } { data to parse version from }
	 *
	 * @return bool
	 */
	function parse_version($path,$result)
	{
		switch($path)
		{
			case 'ffmpeg':
				//Get FFMPEG SVN version
				preg_match("/svn-r([0-9]+)/i",strtolower($result),$matches);
				if(is_numeric(floatval($matches[1])) && $matches[1]) {
					return 'Svn '.$matches[1];
				}
				//Get FFMPEG version
				preg_match("/FFmpeg version ([0-9.]+),/i",strtolower($result),$matches);
				if(is_numeric(floatval($matches[1])) && $matches[1]) {
					return  $matches[1];
				}
				//Get FFMPEG GIT version
				preg_match("/ffmpeg version n\-([0-9]+)/i",strtolower($result),$matches);
				if(is_numeric(floatval($matches[1])) && $matches[1]) {
					return 'Git '.$matches[1];
				}
				break;

			case 'php':
				return phpversion(); 
		}
	}

	/**
	 * Calls ClipBucket footer into the battlefield
	 */
	function footer()
	{
		$funcs = get_functions('clipbucket_footer');
		if(is_array($funcs) && count($funcs)>0) {
			foreach($funcs as $func) {
				if(function_exists($func)) {
					$func();
				}
			}
		}
	}

	/**
	 * Function used to generate RSS FEED links
	 *
	 * @param : { array } { $params } { array with parameters }
	 *
	 * @return mixed
	 */
	function rss_feeds($params)
	{
		/**
		* setting up the feeds arrays..
		* if you want to call em in your functions..simply call the global variable $rss_feeds
		*/
		$rss_link = cblink(array("name"=>"rss"));
		$rss_feeds = array();
		$rss_feeds[] = array("title"=>"Recently added videos","link"=>$rss_link."recent");
		$rss_feeds[] = array("title"=>"Most Viewed Videos","link"=>$rss_link."views");
		$rss_feeds[] = array("title"=>"Top Rated Videos","link"=>$rss_link."rating");
		$rss_feeds[] = array("title"=>"Videos Being Watched","link"=>$rss_link."watching");
		
		$funcs = get_functions('rss_feeds');
		if(is_array($funcs)) {
			foreach($funcs as $func) {
				return $func($params);
			}
		}

		if($params['link_tag']) {
			foreach($rss_feeds as $rss_feed) {
				echo "<link rel=\"alternate\" type=\"application/rss+xml\"
				title=\"".$rss_feed['title']."\" href=\"".$rss_feed['link']."\" />\n";
			}
		}
	}

	/**
	 * Function used to insert Log
	 * @uses { class : $cblog } { function : insert }
	 *
	 * @param $type
	 * @param $details
	 */
	function insert_log($type,$details)
	{
		global $cblog;
		$cblog->insert($type,$details);
	}
	
	/**
	* Function used to get database size
	* @return int : { $dbsize }
	*/
	function get_db_size()
	{
		global $db;
		$results = $db->_select("SHOW TABLE STATUS");
		$dbsize = 0;
		foreach($results as $row) {
			$dbsize += $row[ "Data_length" ] + $row[ "Index_length" ];
		}
		return $dbsize;
	}

	/**
	 * Function used to check weather user has marked comment as spam or not
	 *
	 * @param : { array } { $comment } { array with all details of comment }
	 *
	 * @return bool : { boolean } { true if marked as spam, else false }
	 */
	function marked_spammed($comment)
	{
		$spam_voters = explode("|",$comment['spam_voters']);
		$spam_votes = $comment['spam_votes'];
		$admin_vote = in_array('1',$spam_voters);
		if(userid() && in_array(userid(),$spam_voters)) {
			return true;
		}

		if($admin_vote) {
			return true;
		}
		return false;
	}

	/**
	 * Function used to get object type from its code
	 *
	 * @param : { string } { $type } { shortcode of type ie v=>video }
	 *
	 * @return string : { string } { complete type name }
	 */
	function get_obj_type($type)
	{
		if( $type == 'v' ){
			return "video";
        }
	}

	/**
	 * Check installation of ClipBucket
	 *
	 * @param : { string } { $type } { type of check e.g before, after }
	 *
	 * @return bool
	 */
    function check_install($type)
	{
    	if( in_dev() ){
    		return true;
        }

		global $Cbucket;
		switch($type)
		{
			case "before":
				if(file_exists('files/temp/install.me') && !file_exists('includes/dbconnect.php') && !file_exists('includes/config.php') ) {
					header('Location: '.get_server_url().'/cb_install');
					die();
				}
				break;
			
			case "after":
				if(file_exists('files/temp/install.me')) {
					$Cbucket->configs['closed'] = 1;
				}
				break;
		}
    }

	/**
	 * Function to get server URL
	 * @return string { string } { url of server }
	 * @internal param $ : { none }
	 */
    function get_server_url()
	{
        $DirName = dirname($_SERVER['PHP_SELF']);
        if(preg_match('/admin_area/i', $DirName)) {
            $DirName = str_replace('/admin_area','',$DirName);
        }

		if(preg_match('/cb_install/i', $DirName)) {
            $DirName = str_replace('/cb_install','',$DirName);
        }
        return get_server_protocol().$_SERVER['HTTP_HOST'].$DirName;
    }

	/**
	 * Get current protocol of server that CB is running on
	 * @return mixed|string { string } { $protocol } { http or https }
	 */
    function get_server_protocol()
	{
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return 'https://';
        }

		$protocol = preg_replace('/^([a-z]+)\/.*$/', '\\1', strtolower($_SERVER['SERVER_PROTOCOL']));
		$protocol .= '://';
		return $protocol;
    }

	/**
	* Returns <kbd>true</kbd> if the string or array of string is encoded in UTF8.
	*
	* Example of use. If you want to know if a file is saved in UTF8 format :
	* <code> $array = file('one file.txt');
	* $isUTF8 = isUTF8($array);
	* if (!$isUTF8) --> we need to apply utf8_encode() to be in UTF8
	* else --> we are in UTF8 :)
	* </code>
	* @param mixed A string, or an array from a file() function.
	* @return boolean
	*/
	function isUTF8($string)
	{
		if (is_array($string)) {
			$enc = implode('', $string);
			return @!((ord($enc[0]) != 239) && (ord($enc[1]) != 187) && (ord($enc[2]) != 191));
		}
		return (utf8_encode(utf8_decode($string)) == $string);
	}

	/**
	 * Generate embed code of provided video
	 *
	 * @param : { array } { $vdetails } { all details of video }
	 *
	 * @return string : { string } { $code } { embed code for video }
	 */
	function embeded_code($vdetails)
	{
		$code = '';
		$code .= '<object width="'.EMBED_VDO_WIDTH.'" height="'.EMBED_VDO_HEIGHT.'">';
		$code .= '<param name="allowFullScreen" value="true">';
		$code .= '</param><param name="allowscriptaccess" value="always"></param>';
		//Replacing Height And Width
		$h_w_p = array("{Width}","{Height}");
		$h_w_r = array(EMBED_VDO_WIDTH,EMBED_VDO_HEIGHT);	
		$embed_code = str_replace($h_w_p,$h_w_r,$vdetails['embed_code']);
		$code .= unhtmlentities($embed_code);
		$code .= '</object>';
		return $code;
	}

	/**
	 * function used to convert input to proper date created format
	 *
	 * @param : { string } { date in string }
	 *
	 * @return string : { string } { proper date format }
	 */
	function datecreated($in): string
    {
	    if( !empty($in) ){
	        $datecreated = DateTime::createFromFormat(DATE_FORMAT, $in);
	        if( $datecreated ){
                return $datecreated->format('Y-m-d');
            }
            return $in;
        }
        return '2000-01-01';
	}

	/**
	 * Check if website is using SSL or not
	 * @return bool { boolean } { true if SSL, else false }
	 * @internal param $ { none }
	 * @since 2.6.0
	 */
	function is_ssl()
	{
		if (isset($_SERVER['HTTPS'])) {
			if ('on' == strtolower($_SERVER['HTTPS'])) {
				return true;
			}
			if ('1' == $_SERVER['HTTPS']) {
				return true;
			}
		}
		if ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
			return true;
		}
		return false;
	}

	/**
	 * This will update stats like Favorite count, Playlist count
	 *
	 * @param string $type
	 * @param string $object
	 * @param : { string } { $type } { favorite by default, type of stats to update }
	 * @param string $op
	 *
	 * @action : database updation
	 */
	function updateObjectStats($type,$object,$id,$op='+')
	{
		global $db;
		switch($type) {
			case "favorite":  case "favourite":
			case "favorites": case "favourties":
			case "fav":
				switch($object)
				{
					case "video": 
					case "videos":
					case "v":
						$db->update(tbl('video'),array('favourite_count'),array("|f|favourite_count".$op."1")," videoid = '".$id."'");
						break;
					
					case "photo":
					case "photos":
					case "p":
						$db->update(tbl('photos'),array('total_favorites'),array("|f|total_favorites".$op."1")," photo_id = '".$id."'");
						break;
				}
				break;
			
			case "playlist":
			case "playList":
			case "plist":
				switch($object)
				{
					case "video":
					case "videos":
					case "v":
						$db->update(tbl('video'),array('playlist_count'),array("|f|playlist_count".$op."1")," videoid = '".$id."'");
						break;
				}
				break;
		}
	}

	/**
	 * Function used to check weather conversion lock exists or not
	 * if conversion log exists it means no further conersion commands will be executed
	 * @return bool { boolean } { true if conversion lock exists, else false }
	 * { true if conversion lock exists, else false }
	 */
	function conv_lock_exists()
	{
		if(file_exists(TEMP_DIR.'/conv_lock.loc')) {
			return true;
		}
		return false;
	}

	/**
	 * Function used to return a well-formed queryString
	 * for passing variables to url
	 * @input variable_name
	 *
	 * @param bool $var
	 * @param bool $remove
	 *
	 * @return string
	 */
	function queryString($var=false,$remove=false)
	{
		$queryString = $_SERVER['QUERY_STRING'];
		if($var) {
			$queryString = preg_replace("/&?$var=([\w+\s\b\.?\S]+|)/","",$queryString);
		}
		
		if($remove)
		{
			if(!is_array($remove)) {
				$queryString = preg_replace("/&?$remove=([\w+\s\b\.?\S]+|)/","",$queryString);
			} else {
				foreach($remove as $rm) {
					$queryString = preg_replace("/&?$rm=([\w+\s\b\.?\S]+|)/","",$queryString);
				}
			}
		}
		
		if($queryString) {
			$preUrl = "?$queryString&";
		} else {
			$preUrl = "?";
		}
		$preUrl = preg_replace(array("/(\&{2,10})/","/\?\&/"),array("&","?"),$preUrl);
		return $preUrl.$var;
	}

	/**
	 * Download a remote file and store in given directory
	 *
	 * @param      $snatching_file
	 * @param      $destination
	 * @param      $dest_name
	 * @param bool $rawdecode
	 *
	 * @return string
	 * @internal param $ : { string } { $snatching_file } { file to be downloaded }
	 * @internal param $ : { string } { $destination } { where to save the downloaded file }
	 * @internal param $ : { string } { $dest_name } { new name for file }
	 *
	 */
	function snatch_it($snatching_file,$destination,$dest_name,$rawdecode=true)
	{
		if($rawdecode==true)
		$snatching_file= rawurldecode($snatching_file);
		if(PHP_OS == "Linux") {
			$destination.'/'.$dest_name;
			$saveTo = $destination.'/'.$dest_name;
		} elseif (PHP_OS == "WINNT") {
			$destination.'\\'.$dest_name;
			$saveTo = $destination.'/'.$dest_name;
		}
		cURLdownload($snatching_file, $saveTo);
		return $saveTo;
	}

	/**
	 * This Function gets a file using curl method in php
	 *
	 * @param : { string } { $url } { file to be downloaded }
	 * @param : { string } { $file } { where to save the downloaded file }
	 *
	 * @return string
	 */
	function cURLdownload($url, $file)
	{
		$ch = curl_init(); 
		if($ch) 
		{ 
		    $fp = fopen($file, "w"); 
		    if($fp) 
		    { 
			    if( !curl_setopt($ch, CURLOPT_URL, $url) )
			    {
			        fclose($fp); // to match fopen() 
			        curl_close($ch); // to match curl_init() 
			        return "FAIL: curl_setopt(CURLOPT_URL)"; 
			    } 
			    if( !curl_setopt($ch, CURLOPT_FILE, $fp) ){
			    	return "FAIL: curl_setopt(CURLOPT_FILE)";
                }
			    if( !curl_setopt($ch, CURLOPT_HEADER, 0) ){
			    	return "FAIL: curl_setopt(CURLOPT_HEADER)";
                }
			    if( !curl_exec($ch) ){
			        return "FAIL: curl_exec()";
                }
			    curl_close($ch); 
			   	fclose($fp); 
			    return "SUCCESS: $file [$url]"; 
		    }
			return "FAIL: fopen()";
		}
		return "FAIL: curl_init()";
	}

	/**
	 * Checks if CURL is installed on server
	 * @return bool : { boolean } { true if curl found, else false }
	 * @internal param $ : { none }
	 */
	function isCurlInstalled()
	{
		if  (in_array('curl',get_loaded_extensions())) {
			return true;
		}
		return false;
	}

	/**
	 * Load configuration related files for uploader (video, photo)
	 */
	function uploaderDetails()
	{
		$uploaderDetails = array(
			'uploadSwfPath' => JS_URL.'/uploadify/uploadify.swf',
			'uploadScriptPath' => '/actions/file_uploader.php',
		);
		
		$photoUploaderDetails = array(
			'uploadSwfPath' => JS_URL.'/uploadify/uploadify.swf',
			'uploadScriptPath' => '/actions/photo_uploader.php',
		);
		
		assign('uploaderDetails',$uploaderDetails);	
		assign('photoUploaderDetails',$photoUploaderDetails);		
		//Calling Custom Functions
		cb_call_functions('uploaderDetails');
	}

	/**
	 * Checks if given section is enabled or not e.g videos, photos
	 *
	 * @param : { string } { $input } { section to check }
	 * @param bool $restrict
	 *
	 * @return bool : { boolean } { true of false depending on situation }
	 */
	function isSectionEnabled($input,$restrict=false)
	{
		global $Cbucket;
		$section = $Cbucket->configs[$input.'Section'];
		if(!$restrict){
			return $section == 'yes';
        }

		if($section =='yes' || THIS_PAGE=='cb_install') {
			return true;
		}

        template_files('blocked.html');
        display_it();
        exit();
	}

	/**
	* Updates last commented data - helps cache refresh
	* @param : { string } { $type } { type of comment e.g video, channel }
	* @param : { integer } { $id } { id of element to update }
	* @action : database updation
	*/
	function update_last_commented($type,$id)
	{
		global $db;
		if($type && $id)
		{
			switch($type)
			{
				case "v":
				case "video":
				case "vdo":
				case "vid":
				case "videos":
					$db->update(tbl("video"),array('last_commented'),array(now()),"videoid='$id'");
					break;
				
				case "c":
				case "channel":
				case "user":
				case "u":
				case "users":
				case "channels":
					$db->update(tbl("users"),array('last_commented'),array(now()),"userid='$id'");
					break;
				
				case "cl":
				case "collection":
				case "collect":
				case "collections":
				case "collects":
					$db->update(tbl("collections"),array('last_commented'),array(now()),"collection_id='$id'");
					break;
				
				case "p":
				case "photo":
				case "photos":
				case "picture":
				case "pictures":
					$db->update(tbl("photos"),array('last_commented'),array(now()),"photo_id='$id'");
					break;
			}
		}
	}

	/**
	* Inserts new feed against given user
	*
	* @param : { array } { $array } { array with all details of feed e.g userid, action etc }
	* @action : inserts feed into database 
	*/
	function addFeed($array)
	{
		global $cbfeeds;
		$action = $array['action'];
		if($array['uid']) {
			$userid = $array['uid'];
		} else {
			$userid = userid();
		}

		switch($action)
		{
			default:
				return;

			case "upload_photo":
				$feed['object'] = 'photo';
				break;

			case "add_comment":
				$feed['object'] = $array['object'];
				break;

			case "upload_video":
			case "add_favorite":
				$feed['object'] = 'video';
				break;

			case "signup":
				$feed['object'] = 'signup';
				break;

			case "add_friend":
				$feed['object'] = 'friend';
				break;

			case "add_collection":
				$feed['object'] = 'collection';
				break;
		}
		$feed['uid'] = $userid;
		$feed['object_id'] = $array['object_id'];
		$feed['action'] = $action;
		$cbfeeds->addFeed($feed);
	}

	/**
	 * Fetch directory of a plugin to make it dynamic
	 *
	 * @param : { string } { $pluginFile } { false by default, main file of plugin }
	 *
	 * @return string :    { string } { basename($pluginFile) } { directory path of plugin }
	 */
	function this_plugin($pluginFile=NULL)
	{
		if(!$pluginFile){
			global $pluginFile;
        }
		return basename(dirname($pluginFile));
	}

	/**
	 * Fetch browser details for current user
	 *
	 * @param : { string } { $in } { false by default, HTTP_USER_AGENT }
	 * @param bool $assign
	 *
	 * @return array : { array } { $array } { array with all details of user }
	 */
	function get_browser_details($in=NULL,$assign=false)
	{
		//Checking if browser is firefox
		if(!$in) {
			$in = $_SERVER['HTTP_USER_AGENT'];
		}
		$u_agent = $in;
		$bname = 'Unknown';
		$platform = 'Unknown';
	
		//First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		} elseif (preg_match('/iPhone/i', $u_agent)) {
			$platform = 'iphone';
		} elseif (preg_match('/iPad/i', $u_agent)) {
			$platform = 'ipad';
		} elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		} elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}
	   
		// Next get the name of the useragent yes seperately and for good reason
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		} elseif(preg_match('/Firefox/i',$u_agent)) {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		} elseif(preg_match('/Chrome/i',$u_agent)) {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		} elseif(preg_match('/Safari/i',$u_agent)) {
			$bname = 'Apple Safari';
			$ub = "Safari";
		} elseif(preg_match('/Opera/i',$u_agent)) {
			$bname = 'Opera';
			$ub = "Opera";
		} elseif(preg_match('/Netscape/i',$u_agent)) {
			$bname = 'Netscape';
			$ub = "Netscape";
		} elseif(preg_match('/Googlebot/i',$u_agent)) {
			$bname = 'Googlebot';
			$ub = "bot";
		} elseif(preg_match('/msnbot/i',$u_agent)) {
			$bname = 'MSNBot';
			$ub = "bot";
		} elseif(preg_match('/Yahoo\! Slurp/i',$u_agent)) {
			$bname = 'Yahoo Slurp';
			$ub = "bot";
		}

		// finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) .')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!@preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}
	   
		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1)
		{
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			} else {
				$version= $matches['version'][1];
			}
		} else {
			$version= $matches['version'][0];
		}
	   
		// check if we have a number
		if ($version==null || $version=="")
			$version="?";
	   
		$array= array(
			'userAgent' => $u_agent,
			'name'      => $bname,
			'version'   => $version,
			'platform'  => $platform,
			'bname'		=> strtolower($ub),
			'pattern'   => $pattern
		);
		
		if($assign){
			assign($assign,$array);
        } else {
			return $array;
        }
	}

	function update_user_voted($array,$userid=NULL)
	{
		global $userquery;
		return $userquery->update_user_voted($array,$userid);	
	}
	
	/**
	* Deletes a video from a video collection
	* @param : { array } { $vdetails } { video details of video to be deleted }
	* @action : { calls function from video class }
	*/
	function delete_video_from_collection($vdetails) {
		global  $cbvid;
		$cbvid->collection->deleteItemFromCollections($vdetails['videoid']);
	}

	/**
	 * Check if a remote file exists or not via curl without downloading it
	 *
	 * @param : { string } { $url } { URL of file to check }
	 *
	 * @return bool : { boolean } { true if file exists, else false }
	 */
	function checkRemoteFile($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		// don't download content
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		if($result!==FALSE) {
			return true;
		}
		return false;
	}

	/**
	 * Fetch total count for videos, photos and channels
	 *
	 * @param $section
	 * @param $query
	 *
	 * @return bool : { integer } { $select[0]['counts'] } { count for requested field }
	 * @internal param $ : { string } { $section } { section to select count for }
	 * @internal param $ : { string } { $query } { query to fetch data against }
	 */
	function get_counter($section,$query)
	{
		global $db;

		if(!config('use_cached_pagin')) {
			return false;	
		}
		$timeRefresh = config('cached_pagin_time');
		$timeRefresh = $timeRefresh*60;
		$validTime = time()-$timeRefresh;
		unset($query['order']);
		$je_query = json_encode($query);
		$query_md5 = md5($je_query);
		$select = $db->select(tbl('counters'),"*","section='$section' AND query_md5='$query_md5' 
		AND '$validTime' < date_added");
		if(count($select)>0) {
			return $select[0]['counts'];
		}
		return false;
	}

	/**
	 * Updates total count for videos, photos and channels
	 *
	 * @param $section
	 * @param $query
	 * @param $counter
	 *
	 * @internal param $ : { string } { $section } { section to update counter for }
	 * @internal param $ : { string } { $query } { query to run for updating }
	 * @internal param $ : { integer } { $counter } { count to update }
	 */
	function update_counter($section,$query,$counter)
	{
		global $db;
		unset($query['order']);
		$je_query = json_encode($query);
		$query_md5 = md5($je_query);
		$count = $db->count(tbl('counters'),"*","section='$section' AND query_md5='$query_md5'");
		if($count) {
			$db->update(tbl('counters'),array('counts','date_added'),array($counter,strtotime(now())),
			"section='$section' AND query_md5='$query_md5'");
		} else {
			$db->insert(tbl('counters'),array('section','query','query_md5','counts','date_added'),
			array($section,'|no_mc|'.$je_query,$query_md5,$counter,strtotime(now())));
		}
	}

	/**
	 * function used to verify user age
	 *
	 * @param : { string } { $dob } { date of birth of user }\
	 *
	 * @return bool : { boolean } { true / false depending on situation }
	 */
    function verify_age($dob)
	{
        $allowed_age = config('min_age_reg');
        if($allowed_age < 1){
        	return true;
        }
        $age_time = strtotime($dob);
        $diff = time() - $age_time;
        $diff = $diff / 60 / 60 / 24 / 364;
        if($diff >= $allowed_age ){
        	return true;
        }
        return false;
    }

    /**
     * Checks development mode
     *
     * @return Boolean
     */
    function in_dev()
	{
        if(defined('DEVELOPMENT_MODE')) {
            return DEVELOPMENT_MODE;
        }
		return false;
    }

    /**
     * Dumps data in pretty format [ latest CB prefers pr() instead ]
     *
     * @param array $data
     * @param bool  $die
     */
    function dump($data = [], $die = false)
	{
    	echo '<pre>';
    	var_dump($data);
    	echo '</pre>';

    	if( $die ){
    	    die();
        }
    }

    /**
    * Displays a code error in developer friendly way [ used with PHP exceptions ]
    *
    * @param { Object } { $e } { complete current object }
    */
    function show_cb_error($e)
	{
        echo $e->getMessage();
        echo '<br>';
        echo 'On Line Number ';
        echo $e->getLine();
        echo '<br>';
        echo 'In file ';
        echo $e->getFile();
    }

	/**
	 * Returns current page name or boolean for the given string
	 *
	 * @param string $name
	 *
	 * @return bool|mixed|string : { string / boolean } { page name if found, else false }
	 * @internal param $ { string } { $name } { name of page to check against } { $name } { name of page to check against }
	 */
	function this_page($name='')
	{
	    if(defined('THIS_PAGE')) {
	        $page = THIS_PAGE;
	        if($name) {
	            if($page==$name) {
	                return true; 
	            }
				return false;
	        }
	        return $page;
	    }
	    return false;
	}

	/**
	 * Returns current page's parent name or boolean for the given string
	 *
	 * @param string $name
	 *
	 * @return bool|mixed|string : { string / boolean } { page name if found, else false }
	 * @internal param $ { string } { $name } { name of page to check against } { $name } { name of page to check against }
	 */
	function parent_page($name='')
	{
	    if(defined('PARENT_PAGE'))
	    {
	        $page = PARENT_PAGE;
	        if($name)
	        {
	            if($page==$name){
	                return true;
                }
				return false;
	        }
	        return $page;
	    }
	    return false;
	}

	/**
	 * Function used for building sort links that are used
	 * on main pages such as videos.php, photos.php etc
	 * @return array : { array } { $array } { an array with all possible sort sorts }
	 * @internal param $ : { none }
	 */
	function sorting_links(): array
    {
		if(!isset($_GET['sort'])){
			$_GET['sort'] = 'most_recent';
        }
		if(!isset($_GET['time'])){
			$_GET['time'] = 'all_time';
        }

		return array(
			'view_all'		=> lang('all'),
			'most_recent' 	=> lang('most_recent'),
		 	'most_viewed'	=> lang('mostly_viewed'),
		 	'featured'		=> lang('featured'),
		 	'top_rated'		=> lang('top_rated'),
		 	'most_commented'=> lang('most_comments')
		);
	}

	/**
	 * Function used for building time links that are used
	 * on main pages such as videos.php, photos.php etc
	 * @return array : { array } { $array } { an array with all possible time sorts }
	 * @internal param $ : { none }
	 */
	function time_links(): array
    {
		return array(
		    'all_time' 	 => lang('alltime'),
            'today'		 => lang('today'),
            'yesterday'	 => lang('yesterday'),
            'this_week'	 => lang('thisweek'),
            'last_week'	 => lang('lastweek'),
            'this_month' => lang('thismonth'),
            'last_month' => lang('lastmonth'),
            'this_year'	 => lang('thisyear'),
            'last_year'	 => lang('lastyear')
		 );
	}

	/**
	* Calls $lang_obj variable and returns a string
	* @return String
	*/
	function get_locale()
	{
		global $lang_obj;
		return $lang_obj->lang_iso;
	}

	/*assign results to template for load more buttons on all_videos.html*/
	function template_assign($results,$limit,$total_results,$template_path,$assigned_variable_smarty)
	{
	  	if($limit <$total_results)    
	  	{
	   		$html = "";
	   		$count = $limit;
	   		foreach ($results as $key => $result) 
	   		{
	    		assign("$assigned_variable_smarty",$result);
	    		$html .= Fetch($template_path);
	   		}
			$arr = array("template"=>$html, 'count' => $count, 'total' => $limit);
	  	} else {
	   		$arr = 'limit_exceeds';
	  	}
	  	return $arr;
	}

	/**
	 * function uses to parse certain string from bulk string
	 * @author : Awais Tariq
	 *
	 * @param $needle_start
	 * @param $needle_end
	 * @param $results
	 *
	 * @return array|bool {bool/string/int} {true/$return_arr}
	 * {true/$return_arr}
	 * @internal param $ : {string} {$needle_start} { string from where the parse starts} {$needle_start} { string from where the parse starts}
	 * @internal param $ : {string} {$needle_end} { string from where the parse end} {$needle_end} { string from where the parse end}
	 * @internal param $ : {string} {$results} { total string in which we search} {$results} { total string in which we search}
	 */
	function find_string($needle_start,$needle_end,$results)
	{
		if(empty($results)||empty($needle_start)||empty($needle_end)) {
			return false;
		}
		$start = strpos($results, $needle_start);	
		$end = strpos($results, $needle_end);
		if(!empty($start)&&!empty($end))
		{
			$results = substr($results, $start,$end);
			$end = strpos($results, $needle_end);
			if(empty($end)) {
				return false;
			}
			$results = substr($results, 0,$end);
			return explode(':', $results);
		}
		return false;
	}

	/*
	* Function used to check server configs
	* Checks : MEMORY_LIMIT, UPLOAD_MAX_FILESIZE, POST_MAX_SIZE, MAX_EXECUTION_TIME
	* If any of these configs are less than required value, warning is shown
    */
	function check_server_confs()
	{
		define('POST_MAX_SIZE', ini_get('post_max_size'));
	    define('MEMORY_LIMIT', ini_get('memory_limit'));
	    define('UPLOAD_MAX_FILESIZE', ini_get('upload_max_filesize'));
	    define('MAX_EXECUTION_TIME', ini_get('max_execution_time'));

		if ( getBytesFromFileSize(POST_MAX_SIZE) < getBytesFromFileSize('50M') || getBytesFromFileSize(MEMORY_LIMIT) < getBytesFromFileSize('128M') || getBytesFromFileSize(UPLOAD_MAX_FILESIZE) < getBytesFromFileSize('50M') && MAX_EXECUTION_TIME < 7200 ) {
			e('You must update <strong>"Server Configurations"</strong>. Click here <a href=/admin_area/cb_server_conf_info.php>for details</a>','w', false);
		}
	}

	/**
	 * Get part of a string between two characters
	 *
	 * @param $str
	 * @param $from
	 * @param $to
	 *
	 * @return string : { string } { requested part of stirng }
	 * { requested part of stirng }
	 * @internal param $ : { string } { $str } { string to read } { $str } { string to read }
	 * @internal param $ : { string } { $from } { character to start cutting } { $from } { character to start cutting }
	 * @internal param $ : { string } { $to } { character to stop cutting } { $to } { character to stop cutting }
	 * @since : 3rd March, 2016 ClipBucket 2.8.1
	 * @author : Saqib Razzaq
	 */
	function getStringBetween($str,$from,$to): string
    {
	    $sub = substr($str, strpos($str,$from)+strlen($from),strlen($str));
	    return substr($sub,0,strpos($sub,$to));
	}

	/**
	 * Convert default youtube api timestamp in usable CB time
	 *
	 * @param : { string } { $time } { youtube time stamp }
	 *
	 * @return bool|string : { integer } { $total } { video duration in seconds }
	 * @since : 3rd March, 2016 ClipBucket 2.8.1
	 * @author : Saqib Razzaq
	 */
	function yt_time_convert($time)
	{
		if (!empty($time))
		{
			$str = $time;
			$str = str_replace("P", "", $str);
			$from = "T";
			$to = "H";
			$hours = getStringBetween($str,$from,$to);
			$from = "H";
			$to = "M";
			$mins = getStringBetween($str,$from,$to);
			$from = "M";
			$to = "S";
			$secs = getStringBetween($str,$from,$to);

			$hours = $hours * 3600;
			$mins = $mins * 60;
			$total = $hours + $mins + $secs;
			if (is_numeric($total)) {
				return $total;
			}
			return false;
		}
		return false;
	}

	function fetch_action_logs($params)
	{
		global $db;
		$cond = array();
		if ($params['type']) {
			$type = $params['type'];
			$cond['action_type'] = $type;
		}

		if ($params['user'])
		{
			$user = $params['user'];
			if (is_numeric($user)) {
				$cond['action_userid'] = $user;
			} else {
				$cond['action_username'] = $user;
			}
		}

		if ($params['umail']) {
			$mail = $params['umail'];
			$cond['action_usermail'] = $mail;
		}

		if ($params['ulevel']) {
			$level = $params['ulevel'];
			$cond['action_userlevel'] = $level;
		}

		if ($params['limit']) {
			$limit = $params['limit'];
		} else {
			$limit = 20;
		}

		if (isset($_GET['page'])) {
			$page = $_GET['page'];
			$start = $limit * $page - $limit;
		} else {
			$start = 0;
		}

		$count = 0;
		$final_query = '';
		foreach ($cond as $field => $value)
		{
			if ($count > 0) {
				$final_query .= " AND `$field` = '$value' ";
			} else {
				$final_query .= " `$field` = '$value' ";
			}
			$count++;
		}
		if (!empty($cond)) {
			$final_query .= " ORDER BY `action_id` DESC LIMIT $start,$limit";
			$logs = $db->select(tbl("action_log"),"*","$final_query");
		} else {
			$final_query = " `action_id` != '' ORDER BY `action_id` DESC LIMIT $start,$limit";
			$logs = $db->select(tbl("action_log"),"*", "$final_query");
		}
		if (is_array($logs)) {
			return $logs;
		}
		return false;
	}

	/**
	 * Fetch user's geolocation related data
	 *
	 * @param : { string } { $ip } { ip address to perform checks against }
	 * @param string $purpose
	 * @param bool   $deep_detect
	 *
	 * @return array|null|string
	 * @since : 11th April, 2016 ClipBucket 2.8.1
	 *
	 * @author: manuelbcd [http://stackoverflow.com/users/3518053/manuelbcd]
	 */
	function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE)
	{
	    $output = NULL;
	    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE)
	    {
	        $ip = $_SERVER["REMOTE_ADDR"];
	        if ($deep_detect) {
	            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
	                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
	                $ip = $_SERVER['HTTP_CLIENT_IP'];
	        }
	    }
	    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
	    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
	    $continents = array(
	        "AF" => "Africa",
	        "AN" => "Antarctica",
	        "AS" => "Asia",
	        "EU" => "Europe",
	        "OC" => "Australia (Oceania)",
	        "NA" => "North America",
	        "SA" => "South America"
	    );
	    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support))
	    {
	        $ipdat = @json_decode(cb_curl("http://www.geoplugin.net/json.gp?ip=" . $ip));
	        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2)
	        {
	            switch ($purpose)
				{
	                case "location":
	                    $output = array(
	                        "city"           => @$ipdat->geoplugin_city,
	                        "state"          => @$ipdat->geoplugin_regionName,
	                        "country"        => @$ipdat->geoplugin_countryName,
	                        "country_code"   => @$ipdat->geoplugin_countryCode,
	                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
	                        "continent_code" => @$ipdat->geoplugin_continentCode
	                    );
	                    break;

	                case "address":
	                    $address = array($ipdat->geoplugin_countryName);
	                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
	                        $address[] = $ipdat->geoplugin_regionName;
	                    if (@strlen($ipdat->geoplugin_city) >= 1)
	                        $address[] = $ipdat->geoplugin_city;
	                    $output = implode(", ", array_reverse($address));
	                    break;

	                case "city":
	                    $output = @$ipdat->geoplugin_city;
	                    break;

	                case "state":
                    case "region":
	                    $output = @$ipdat->geoplugin_regionName;
	                    break;

	                case "country":
	                    $output = @$ipdat->geoplugin_countryName;
	                    break;

	                case "countrycode":
	                    $output = @$ipdat->geoplugin_countryCode;
	                    break;
	            }
	        }
	    }
	    return $output;
	}

	/**
	 * Checks if a user has rated a video / photo and returns rating status
	 *
	 * @param      $userid
	 * @param      $itemid
	 * @param bool $type
	 *
	 * @return bool|string : { string / boolean } { rating status if found, else false }
	 * @internal param $ : { integer } { $userid } { id of user to check rating by } { $userid } { id of user to check rating by }
	 * @internal param $ : { integer } { $itemid } { id of item to check rating for } { $itemid } { id of item to check rating for }
	 * @internal param $ : { boolean } { false by default, type of item [video / photo] } { false by default, type of item [video / photo] }
	 *
	 * @example : has_rated(1,1033, 'video') // will check if userid 1 has rated video with id 1033
	 * @since : 12th April, 2016 ClipBucket 2.8.1
	 * @author : Saqib Razzaq
	 */
	function has_rated($userid, $itemid, $type = false)
	{
		global $db;
		switch ($type)
		{
			case 'video':
				$toselect = 'videoid';
				break;

			case 'photo':
				$toselect = 'photo_id';
				break;
			
			default:
				$type = 'video';
				$toselect = 'videoid';
				break;
		}
		$raw_rating = $db->select(tbl($type),'voter_ids',"$toselect = $itemid");
		$ratedby_json = $raw_rating[0]['voter_ids'];
		$ratedby_cleaned = json_decode($ratedby_json,true);
		foreach ($ratedby_cleaned as $key => $rating_data)
		{
			if ($rating_data['userid'] == $userid)
			{
				if ($rating_data['rating'] == 0) {
					return 'disliked';
				}
				return 'liked';
			}
		}
		return false;
	}

	/**
	 * Fetches max quality thumbnail of a youtube video
	 *
	 * @param : { string / array } { $video } { youtube video id or json decoded api content }
	 * @param bool $thumbarray
	 *
	 * @return array : { array } { $toreturn } { width, height and thumb url }
	 * @since : 14th April, 2016 ClipBucket 2.8.1
	 * @author : Saqib Razzaq
	 */
	function maxres_youtube($video, $thumbarray = false)
	{
		if (is_array($video) || $thumbarray)
		{
			$content = $video;
			if (!is_array($thumbarray)) {
				$thumbs_array = $content['items'][0]['snippet']['thumbnails'];
			} else {
				$thumbs_array = $thumbarray;
			}
			$maxres = $thumbs_array['maxres'];
			$standard = $thumbs_array['standard'];
			$high = $thumbs_array['high'];
			$medium = $thumbs_array['medium'];
			$default = $thumbs_array['default'];

			$all_qualities = array($maxres, $standard, $high, $medium, $default);

			foreach ($all_qualities as $key => $value)
			{
				if (!empty($value['url'])) {
					$toreturn = array();
					$toreturn['width'] = $value['width'];
					$toreturn['height'] = $value['height'];
					$toreturn['thumb'] = $value['url'];
					return $toreturn;
				}
			}
		} else {
			$maxres = $thumbs_array['maxres'];
			$standard = $thumbs_array['standard'];
			$high = $thumbs_array['high'];
			$medium = $thumbs_array['medium'];
			$default = $thumbs_array['default'];

			$all_qualities = array($maxres, $standard, $high, $medium, $default);

			foreach ($all_qualities as $key => $value)
			{
				if (!empty($value['url'])) {
					$toreturn = array();
					$toreturn['width'] = $value['width'];
					$toreturn['height'] = $value['height'];
					$toreturn['thumb'] = $value['url'];
					return $toreturn;
				}
			}
		}
	}

	/**
	* Takes thumb file and generates upto 5 possible qualities from it
	* @param : { array } { $params } { an array of parameters }
	* @since : 14th April, 2016 ClipBucket 2.8.1
	* @author : Saqib Razzaq
	*/
	function thumbs_black_magic($params)
	{
		global $imgObj,$Upload;
		$files_dir = $params['files_dir'];
		$file_name = $params['file_name'];
		$filepath = $params['filepath'];
		$width = $params['width'];
		$height = $params['height'];
		$ms = $params['ms'];
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);
		
		$thumbs_settings_28 = thumbs_res_settings_28();
		foreach ($thumbs_settings_28 as $key => $thumbs_size)
		{
			$file_num = $Upload->get_available_file_num($file_name);
			$height_setting = $thumbs_size[1];
			$width_setting = $thumbs_size[0];
			if ( $key != 'original' ){
				$dimensions = implode('x',$thumbs_size);
			} else {
				$dimensions = 'original';
				$width_setting  = $width;
				$height_setting = $height;
			}

			if (!$ms) {
				$outputFilePath = THUMBS_DIR.'/'.$files_dir.'/'.$file_name.'-'.$dimensions.'-'.$file_num.'.'.$ext;	
			} else {
				$outputFilePath = $files_dir.'/'.$file_name.'-'.$dimensions.'-'.$file_num.'.'.$ext;	
			}

			$imgObj->CreateThumb($filepath,$outputFilePath,$width_setting,$ext,$height_setting,false);
		}
		unlink($filepath);
	}

	/**
	* Assigns smarty values to an array
	* @param : { array } { $vals } { an associative array to assign vals }
	*/
	function array_val_assign($vals)
	{
		if (is_array($vals)) {
			foreach ($vals as $name => $value) {
				assign($name, $value);
			}
		}
	}

	function build_sort($sort, $vid_cond)
	{
		if (!empty($sort))
		{
			switch($sort)
			{
				case 'most_recent':
				default:
					$vid_cond['order'] = ' date_added DESC ';
					break;

				case 'most_viewed':
					$vid_cond['order'] = 'views DESC ';
					$vid_cond['date_span_column'] = 'last_viewed';
					break;

				case 'featured':
					$vid_cond['featured'] = 'yes';
					break;

				case 'top_rated':
					$vid_cond['order'] = ' rating DESC, rated_by DESC';
					break;

				case 'most_commented':
					$vid_cond['order'] = ' comments_count DESC';
					break;
			}
			return $vid_cond;
		}
	}

	function build_sort_photos($sort, $vid_cond)
	{
		if (!empty($sort))
		{
			switch($sort)
			{
				case 'most_recent':
				default:
					$vid_cond['order'] = ' date_added DESC ';
					break;

				case 'most_viewed':
					$vid_cond['order'] = ' views DESC ';
					$vid_cond['date_span_column'] = 'last_viewed';
					break;

				case 'featured':
					$vid_cond['featured'] = 'yes';
					break;

				case 'top_rated':
					$vid_cond['order'] = ' rating DESC';
					break;

				case 'most_commented':
					$vid_cond['order'] = ' comments_count DESC';
					break;
			}
			return $vid_cond;
		}
	}

    function get_website_logo_path()
    {
        $logo_name = config('logo_name');
        if( $logo_name && $logo_name != '' ){
            return LOGOS_URL.DIRECTORY_SEPARATOR.$logo_name;
        }
        if( defined('TEMPLATEURLFO') ){
            return TEMPLATEURLFO.'/theme'.'/images/logo.png';
        }
        return TEMPLATEURL.'/theme'.'/images/logo.png';
    }

    function get_website_favicon_path()
    {
        $favicon_name = config('favicon_name');
        if( $favicon_name && $favicon_name != '' ){
            return LOGOS_URL.DIRECTORY_SEPARATOR.$favicon_name;
        }
        if( defined('TEMPLATEURLFO') ){
            return TEMPLATEURLFO.'/theme'.'/images/favicon.png';
        }
        return TEMPLATEURL.'/theme'.'/images/favicon.png';
    }

    function upload_image($type = 'logo')
    {
        if( !in_array($type, array('logo','favicon')) ){
            e(lang('Wrong logo type !'));
            return;
        }
        global $Cbucket;

        $filename = $_FILES['fileToUpload']['name'];
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        $file_basename = pathinfo($filename, PATHINFO_FILENAME);
        $filesize = $_FILES['fileToUpload']['size'];
        $allowed_file_types = explode(',', $Cbucket->configs['allowed_photo_types']);

        if (in_array($file_ext,$allowed_file_types) && ($filesize < 4000000))
        {
            // Rename file
            $logo_path = LOGOS_DIR.DIRECTORY_SEPARATOR.$file_basename.'-'.$type.'.'.$file_ext;
            unlink($logo_path);
            move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $logo_path);

            $myquery = new myquery();
            $myquery->Set_Website_Details($type.'_name',$file_basename.'-'.$type.'.'.$file_ext);

            e(lang('File uploaded successfully.'),'m');
        } elseif (empty($filename)) {
            // file selection error
            e(lang('Please select a file to upload.'));
        } elseif ($filesize > 4000000) {
            // file size error
            e(lang('The file you are trying to upload is too large.'),"e");
        } else {
            e(lang('Only these file types are allowed for upload: '.implode(', ',$allowed_file_types)),"e");
            unlink($_FILES['fileToUpload']['tmp_name']);
        }
    }

	function AutoLinkUrls($str,$popup = FALSE)
	{
	    if (preg_match_all("#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)#i", $str, $matches))
	    {
			$pop = ($popup == TRUE) ? " target=\"_blank\" " : "";
			for ($i = 0; $i < count($matches['0']); $i++)
			{
				$period = '';
				if (preg_match("|\.$|", $matches['6'][$i])){
					$period = '.';
					$matches['6'][$i] = substr($matches['6'][$i], 0, -1);
				}
				$str = str_replace($matches['0'][$i],
					$matches['1'][$i].'<a href="http'.
					$matches['4'][$i].'://'.
					$matches['5'][$i].
					$matches['6'][$i].'"'.$pop.'>http'.
					$matches['4'][$i].'://'.
					$matches['5'][$i].
					$matches['6'][$i].'</a>'.
					$period, $str);
			}
	    }
	    return $str;
	}

	function cb_curl($url)
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	/**
		* Check for the content mime type of a file provided
		* @param : { FILE } { $mainFile } { File to run check against }
		* @author : Fahad Abbas
		* @since : 10 January, 2018
		* @todo : will Check for the content mime type of a file provided
		* @return : { string/boolean } { type or false }
		* @example : N/A
    */
	function get_mime_type($file, $offset = 0)
	{
		$raw_content_type = mime_content_type($file);
        $cont_type = substr($raw_content_type, $offset,strpos($raw_content_type, '/'));
        if ($cont_type){
        	return $cont_type;
        }
        return false;
	}

	function get_date_js()
	{
		$date_format_php = config('date_format');
		$search = array('Y', 'm', 'd');
		$replace = array('yy', 'mm', 'dd');

		return str_replace($search, $replace, $date_format_php);
	}

	function isset_check($input_arr,$key_name,$mysql_clean=false)
	{
	    if(isset($input_arr[$key_name])&&!empty($input_arr[$key_name]))
	    {
	        if(!is_array($input_arr[$key_name])&&!is_numeric($input_arr[$key_name])&&$mysql_clean) {
	            $input_arr[$key_name] = mysql_clean($input_arr[$key_name]);
            }
	        
	        return $input_arr[$key_name]; 
	    }
	    return false;
	}

	/**
	* [generic_curl use to send curl with post method]
	* @author Awais Tariq
	* @param [string] $call_bk [url where curl will be sent]
	* @param [array] $array [date to be send ]
	* @param [string] $follow_redirect [ redirect follow option for 301 status ]
	* @param [array] $header_arr [ header's parameters are sent through this array ]
	* @param [bool] $read_response_headers [ parse/fetch the response headers ]
	* @return [array] [return code and result of curl]
	*/
	function generic_curl($input_arr = array())
	{
		$call_bk = isset_check($input_arr,'url');
		
		$array = isset_check($input_arr,'post_arr'); 
		$file = isset_check($input_arr,'file');
		$follow_redirect = isset_check($input_arr,'redirect');
		$full_return_info = isset_check($input_arr,'full_return_info');
		$header_arr = isset_check($input_arr,'headers');
		$curl_timeout = isset_check($input_arr,'curl_timeout');
		$methods = strtoupper(isset_check($input_arr,'method'));
		$curl_connect_timeout = isset_check($input_arr,'curl_connect_timeout');
		$curl_connect_timeout = (int)trim($curl_connect_timeout);
		$curl_timeout = (int)trim($curl_timeout);
		$read_response_headers = isset_check($input_arr,'response_headers');
		$return_arr = array();
		if(!empty($call_bk))
		{
			$ch = curl_init($call_bk);

			if(!empty($file))
			{
				foreach ($file as $key => $value) 
				{
					if(file_exists($value)){
						$array[$key] = curl_file_create( $value, mime_content_type($value), basename($value));
                    }
				}
			}

			if($methods){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "{$methods}");
            }

			if(!empty($array)) {
				curl_setopt($ch,CURLOPT_POST,true);
				curl_setopt($ch,CURLOPT_POSTFIELDS,$array);
			}

			if($read_response_headers===true) {
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLOPT_HEADER, 1);
			}

			if(empty($header_arr)){
				$header_arr = array('Expect:');
            }

			if(empty($curl_timeout)||$curl_timeout==0){
				$curl_timeout = 3;
            }
			
			if($curl_timeout>0){
				curl_setopt($ch, CURLOPT_TIMEOUT, $curl_timeout);
            }
			
			if(empty($curl_connect_timeout)||$curl_connect_timeout==0){
				$curl_connect_timeout = 2;
            }

			if($curl_connect_timeout>0){
				curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $curl_connect_timeout);
            }

			curl_setopt($ch,CURLOPT_HTTPHEADER,$header_arr);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			if($follow_redirect){
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            }

			$result = curl_exec($ch); 
			$error_msg = curl_error($ch); 
			$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if($full_return_info){
				$return_arr['full_info'] = curl_getinfo($ch);
            }

			$return_arr['code'] = $returnCode;
			$errorNO = curl_errno($ch);
			if($errorNO) {
				$return_arr['curl_error_no'] = $errorNO;
			}

			if(!empty($error_msg)) {
				$return_arr['error_curl'] = $error_msg;
			}

			$return_arr['result'] = $result;
			curl_close($ch);
		} else{
			$return_arr['error'] = "False no callback url present! {$call_bk}";
		}

		return $return_arr;

	}

	/**
	* This function is used to clean a string removing all special chars
	* @author Mohammad Shoaib
	* @param string
	* @return string
	*/ 
	function cleanString($string) {
		$string = str_replace("â€™", "'", $string);
	    return preg_replace('/[^A-Za-z0-9 !@#$%^&*()_?<>|{}\[\].,+-;\/:"\'\-]/', "'", $string);
	}

	function display_changelog($version, $title = null)
    {
        if( !is_array($version) ){
            $filepath = __DIR__.'/../changelog/'.$version.'.json';
            if( !file_exists($filepath) ) {
                echo lang('error_occured').'<br/>';
                echo 'File don\' exists :'.$filepath;
                return;
            }
            $content_json = json_decode(file_get_contents($filepath), true);
        } else {
            $content_json = $version;
        }
        echo '<div class="well changelog">';
        if( is_null($title) ){
            echo '<h3>'.$content_json['version'].' Changelog - '.ucfirst($content_json['status']).'</h3>';
        } else {
            echo '<h3>'.$title.'</h3>';
        }
        foreach($content_json['detail'] as $detail){
            echo '<b>'.$detail['title'].'</b>';
            if( !isset($detail['description']) ){
                continue;
            }
            echo '<ul>';
            foreach($detail['description'] as $description){
                echo '<li>'.$description.'</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    function display_changelog_diff($current, $new)
    {
        $detail_current = $current['detail'];
        $detail_new = $new['detail'];
        $diff = [
            'version' => $new['version']
            ,'revision' => $new['revision']
            ,'status' => $new['status']
            ,'detail' => []
        ];

        foreach($detail_new as $categ){
            $categ_exists = false;
            foreach($detail_current as $categ_current){
                if( $categ['title'] != $categ_current['title']){
                    continue;
                }

                foreach($categ['description'] as $element){
                    $element_exists = false;
                    foreach($categ_current['description'] as $element_current){
                        if( $element == $element_current ){
                            $element_exists = true;
                            break;
                        }
                    }

                    if( !$element_exists ){
                        $element_diff_exists = false;
                        foreach($diff['detail'] as &$element_diff){
                            if( $element_diff['title'] == $categ_current['title'] ){
                                $element_diff['description'][] = $element;
                                $element_diff_exists = true;
                                break;
                            }
                        }

                        if( !$element_diff_exists ){
                            $diff['detail'][] = [
                                'title' => $categ_current['title']
                                ,'description' => [$element]
                            ];
                        }
                    }

                }

                $categ_exists = true;
            }

            if( !$categ_exists ){
                $diff['detail'][] = $categ;
            }
        }

        if( empty($diff['detail']) ){
            echo 'The new revision has the same changelog';
        } else {
            display_changelog($diff, 'Additions from your current version');
        }
    }

    /**
     * @return array|null
     */
    function get_proxy_settings(string $format = '')
    {
	    switch($format){
            default:
            case 'file_get_contents':
                $context = null;
                if( config('proxy_enable') == 'yes' ){
                    $context = [
                        'http' => [
                            'proxy'           => 'tcp://'.config('proxy_url').':'.config('proxy_port'),
                            'request_fulluri' => true
                        ]
                    ];

                    if( config('proxy_auth') == 'yes' ){
                        $context['http']['header'] = 'Proxy-Authorization: Basic '.base64_encode(config('proxy_username').':'.config('proxy_password'));
                    }
                }
                return $context;
        }
    }

    /**
     * @param bool
     * @return string|void
     */
    function get_update_status($only_flag = false)
    {
        if( config('enable_update_checker') != 1 ){
            return '';
        }

        if( !ini_get('allow_url_fopen') ) {
            if( $only_flag ){
                return 'red';
            }
            return '<div class="well changelog"><h5>'.lang('dashboard_php_config_allow_url_fopen').'</h5></div>';
        }

        $base_url = 'https://raw.githubusercontent.com/MacWarrior/clipbucket-v5/master/upload/changelog';
        $current_version = VERSION;
        $current_status = strtolower(STATE);
        $current_revision = REV;

        $context = get_proxy_settings('file_get_contents');

        $versions_url = $base_url.'/latest.json';
        $versions = json_decode(file_get_contents($versions_url, false, $context), true);
        if( !isset($versions[$current_status]) ){
            if( $only_flag ){
                return 'red';
            }
            echo lang('error_occured').'<br/>';
            echo lang('error_file_download').' : '.$versions_url;
            return;
        }

        $changelog_url = $base_url.'/'.$versions[$current_status].'.json';
        $changelog = json_decode(file_get_contents($changelog_url, false, $context), true);
        if( !isset($changelog['version']) ){
            if( $only_flag ){
                return 'red';
            }
            echo lang('error_occured').'<br/>';
            echo lang('error_file_download').' : '.$changelog_url;
            return;
        }

        if( !$only_flag ) {
            echo '<div class="well changelog"><h5>Current version : <b>' . $current_version . '</b> - Revision <b>'.$current_revision.'</b> <i>('.ucfirst( $current_status ).')</i><br/>';
            echo 'Latest version <i>('.ucfirst( $current_status).')</i> : <b>'.$changelog['version'].'</b> - Revision <b>'.$changelog['revision'].'</b></h5></div>';
        }

        $is_new_version = $current_version > $changelog['version'];
        $is_new_revision = $is_new_version || $current_revision > $changelog['revision'];

        if( $current_version == $changelog['version'] && $current_revision == $changelog['revision'] ) {
            if ( $only_flag ) {
                return 'green';
            }
            echo '<h3 style="text-align:center;">Your Clipbucket seems up-to-date !</h3>';
        } else if( $is_new_version || $is_new_revision ) {
            if ( $only_flag ) {
                return 'green';
            }

            echo '<h3 style="text-align:center;">Keep working on this new version ! :)</h3>';
        } else {
            if( $only_flag ){
                return 'orange';
            }
            echo '<h3 style="text-align:center;">Update <b>'.$changelog['version'].'</b> - Revision <b>'.$changelog['revision'].'</b> is available !</h3>';

            if( $current_version != $changelog['version'] ){
                display_changelog($changelog);
            } else {
                $current_changelog = json_decode(file_get_contents(realpath(__DIR__.'/../changelog').'/'.CHANGELOG.'.json'), true);
                display_changelog_diff($current_changelog, $changelog);
            }
        }

        if( $current_status == 'dev' ){
            echo '<div class="well changelog"><h5>Thank you for using the developpement version of Clipbucket !<br/>Please create an <a href="https://github.com/MacWarrior/clipbucket-v5/issues" target="_blank">issue</a> if you encounter any bug.</h5></div>';
        }
    }

    include('functions_db.php');
    include('functions_filter.php');
    include('functions_player.php');
    include('functions_template.php');
    include('functions_helper.php');
    include('functions_video.php');
    include('functions_user.php');
    include('functions_photo.php');
    include('functions_actions.php');
    include('functions_playlist.php');
