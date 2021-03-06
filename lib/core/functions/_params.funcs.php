<?php
/**
 * Balupton's Resource Library (balPHP)
 * Copyright (C) 2008 Benjamin Arthur Lupton
 * http://www.balupton.com/
 *
 * This file is part of Balupton's Resource Library (balPHP).
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Balupton's Resource Library (balPHP).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package balphp
 * @subpackage core
 * @version 0.1.1-final, November 11, 2009
 * @since 0.1.0-final, April 21, 2008
 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
 * @copyright Copyright (c) 2008, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 */

require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_general.funcs.php');
require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'_strings.funcs.php');

if ( function_compare('explode_querystring', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function explode_querystring ( $query_string, $amp = '&amp;' ) {
		$query_string = explode($amp, $query_string);
		$params = array();
		for($i = 0, $n = sizeof($query_string); $i < $n; ++$i) {
			$param = explode('=', $query_string[$i]);
			if ( sizeof($param) === 2 ) {
				$key = $param[0];
				$value = $param[1];
				$params[$key] = $value;
			}
		}
		return $params;
	}
}

if ( function_compare('implode_querystring', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1.1, June 24, 2010 - added urlencode
	 * @since 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function implode_querystring ( $query_string, $amp = '&amp;' ) {
		$params = '';
		foreach ( $query_string as $key => $value ) {
			$params .= urlencode($key) . '=' . urlencode($value) . $amp;
		}
		return $params;
	}
}

if ( function_compare('regenerate_params', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Do something
	 *
	 * @version 1
	 *
	 * @todo figure out what the hell this does
	 *
	 */
	function regenerate_params ( $display = 'form', $params = NULL ) {
		if ( $params === NULL )
			$params = array_merge($_GET, $_POST);
		elseif ( gettype($params) === 'string' ) {
			$params = explode_querystring($params);
		}
		
		$result = '';
		
		switch ( $display ) {
			case 'form' :
				foreach ( $params as $key => $value ) {
					$result .= '<input type="hidden" name="' . $key . '" value="' . $value . '"  />';
				}
				break;
			
			default :
				die('Unknown regenerate params display: ' . $display);
				break;
		}
		
		return $result;
	}
}

if ( function_compare('hydrate_request_init', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Rebuild and hydrate $_REQUEST
	 * @version 1, January 06, 2010
	 */
	function hydrate_request_init ( $once = true ) {
		# Init
		if ( defined('REQUEST_HYDRATED') ) {
			if ( $once ) return;
		} else
			define('REQUEST_HYDRATED', 1);
	
		# Prepare
		$stripslashes = ini_get('magic_quotes_gpc') ? true : false;
		
		# Prepare
		$_POST_HYDRATED		= $_POST;
		$_GET_HYDRATED		= $_GET;
		$_FILES_HYDRATED	= array();
		
		# Clean
		array_clean_form($_POST_HYDRATED);
		array_clean_form($_GET_HYDRATED);
		array_clean_form($_FILES_HYDRATED);
		
		# Hydrate
		hydrate_value($_POST_HYDRATED,	array('stripslashes'=>$stripslashes));
		hydrate_value($_GET_HYDRATED,	array('stripslashes'=>$stripslashes));
		hydrate_value($_FILES_HYDRATED);
		
		# Liberate
		liberate_files($_FILES_HYDRATED);
		unset_empty_files($_FILES_HYDRATED);
		
		# Merge
		$_REQUEST = array_merge_recursive_keys(false, $_FILES_HYDRATED, $_GET_HYDRATED, $_POST_HYDRATED);
		
		# Done
		return true;
	}
}

if ( function_compare('get_param', 2, true, __FILE__, __LINE__) ) {

	/**
	 * Get a unhydrated param
	 * @version 3, May 05, 2010
	 * @since 2
	 * @param string $name
	 * @param array $options [optional]
	 */
	function get_param ( $name, $default = null, $stripslashes = null) {
		# Prepare
		if ( $stripslashes === null )
			$stripslashes = get_magic_quotes_gpc() ? true : false;
		
		# Fetch
		$value = delve($_POST,$name,delve($_GET,$name,delve($_FILES,$name,$default)));
		
		# Stripslashes
		if ( $stripslashes && is_string($value) ) {
			$value = stripslashes($value);
		}
		
		# Return value
		return $value;
	}
}


if ( function_compare('fetch_param', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Get a hydrated param
	 * @version 1, January 06, 2010
	 */
	function fetch_param ( $name, $default = null ) {
		# Prepare
		hydrate_request_init();
		
		# Fetch
		$value = delve($_REQUEST, $name, $default);
		
		# Return value
		return $value;
	}
}

if ( function_compare('has_param', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Check to see if the param exists
	 * @version 1, January 06, 2010
	 */
	function has_param ( $name ) {
		# Prepare
		hydrate_request_init();
		
		# Fetch
		$exists = delve($_REQUEST, $name) !== null;
		
		# Return exists
		return $exists;
	}
}


if ( function_compare('liberate_subfiles', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Liberate subfiles
	 * @version 1, January 06, 2010
	 */
	function liberate_subfiles ( &$where, $prefix, $suffix, $subvalue ) {
		# Prepare
		$prefix = trim($prefix, '.');
		$suffix = trim($suffix, '.');
	
		# Handle
		if ( !is_array($subvalue) ) {
			# We have reached the bottom
			$name = $prefix.'.'.$suffix;
			array_apply($where, $name, $subvalue, true); // when setting to false, PHP memory reference error occurs...
		}
		else {
			# More sub files
			foreach ( $subvalue as $key => $value ) {
				liberate_subfiles($where, $prefix.'.'.$key, $suffix, $value);
			}
		}
	
		# Done
		return true;
	}
}


if ( function_compare('liberate_files', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Liberate files
	 * The purpose of this is when using $_FILE with param arrays, we want to be able to do this $_FILE['user']['avatar']['tmpname'] instead of $_FILE['user']['tmpname']['avatar']
	 * @version 1, January 06, 2010
	 */
	function liberate_files ( &$where ) {
		# Handle
		foreach ( $_FILES as $fileKey => $fileValue ) {
			foreach ( $fileValue as $filePartKey => $filePartValue ) {
				if ( is_array($filePartValue) ) {
					# We have a multiple file
					liberate_subfiles($where, $fileKey, $filePartKey, $filePartValue);
				}
			}
		}
	}
}

if ( function_compare('unset_empty_files', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Unset empty files
	 * The purpose of this function is to unset empty files (error==4)
	 * @version 1, February 22, 2010
	 */
	function unset_empty_files ( &$files, $current = null, $prefix = '', $last = '' ) {
		# Prepare
		$prefix = trim($prefix, '.');
		if ( $prefix === '' && $current === null ) $current = $files;
		
		# Handle
		if ( is_array($current) ) {
			# Deeper
			foreach ( $current as $key => $value ) {
				if ( is_array($value) ) {
					unset_empty_files($files, $value, $prefix.'.'.$key, $key);
				}
				elseif ( ($key === 'error' && $value === 4) || ($key === 'size' && !$value) ) {
					array_unapply($files, $prefix);
				}
			}
		}
		
		# Done
		return true;
	}
}

if ( function_compare('fix_magic_quotes', 1, true, __FILE__, __LINE__) ) {

	/**
	 * Fix magic quotes
	 * @version 2, August 22, 2009
	 * @since 1, Unknown
	 * @package BalPHP {@link http://www.balupton/projects/balphp}
	 * @author Benjamin "balupton" Lupton <contact@balupton.com> - {@link http://www.balupton.com/}
	 * @copyright Copyright (c) 2008-2010, Benjamin Arthur Lupton - {@link http://www.balupton.com/}
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 */
	function fix_magic_quotes ( ) {
		if ( ini_get('magic_quotes_gpc') ) {
			$_POST = array_map('stripslashes_deep', $_POST);
			$_GET = array_map('stripslashes_deep', $_GET);
			$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
			ini_set('magic_quotes_gpc', 0);
		}
	}
}
