<?php

/**
 * @package SL Combinator
 * @version 2.1.0
 * @author Stephen Lewis (http://experienceinternet.co.uk/)
 * @copyright Copyright (c) 2008-2009, Stephen Lewis
 * @license http://creativecommons.org/licenses/by-sa/3.0 Creative Commons Attribution-Share Alike 3.0 Unported
 * @link http://experienceinternet.co.uk/resources/details/sl-combinator/
*/

if ( ! defined('SL_CMB_version'))
{
	define('SL_CMB_version', '2.1.0');
	define('SL_CMB_docs_url', 'http://experienceinternet.co.uk/resources/details/sl-combinator/');
	define('SL_CMB_addon_id', 'SL Combinator');
	define('SL_CMB_extension_class', 'Sl_combinator_ext');	
}


/**
 * Plugin information used by EE.
 * @global array $plugin_info
 */
$plugin_info = array(
		'pi_name' 				=> 'SL Combinator',
		'pi_version' 			=> SL_CMB_version,
		'pi_author' 			=> 'Stephen Lewis',
		'pi_author_url' 	=> 'http://experienceinternet.co.uk/',
		'pi_description' 	=> 'Combines and compresses JavaScript or CSS files for faster downloading.',
		'pi_usage'				=> Sl_combinator::usage()
);


class Sl_combinator {

	/**
	* Data returned from the plugin.
	* @var array
	*/
	var $return_data = '';	
	
	
	/**
	 * PHP4 constructor.
	 * @see __construct
	 */
	function Sl_combinator()
	{
		$this->__construct();
	}
	
	
	/**
	 * PHP5 constructor.
	 */
	function __construct()
	{
		if  ( ! class_exists('Sl_combinator_ext'))
		{
			include(PATH_EXT . 'ext.sl_combinator_ext.php');
		}
		
		// Retrieve the extension settings.
		$slc = new Sl_combinator_ext;
		$this->settings = $slc->get_settings();
		
		// Tidy up the settings, if required.
		if (isset($this->settings['min_url']))
		{
			$str = trim($this->settings['min_url']);
			if (substr($str, -1) != '/')
				$this->settings['min_url'] = $str . '/';
			
			// Note: this is required in order to work with the "Include" index.php removal
			// technique on some servers.
			$this->settings['min_url'] .= 'index.php';
		}
	}
	
	
	/**
	 * Combines and minifies the specified JavaScript files.
	 */
	function combine_js()
	{
		return $this->_process('js');
	}
	
	
	/**
	 * Combines and minifies the specified JavaScript files.
	 */
	function combine_css()
	{
		return $this->_process('css');
	}
	
	
	/**
	 * Extracts source file paths from the supplied string.
	 * @param string $haystack The string to search.
	 * @param string $type The type of files to search for ('css' or 'js').
	 * @return array An array of extracted source file paths, or FALSE if no file paths were found.
	 */
	function _extract_files_to_process($haystack, $type)
	{
		$ret = FALSE;
		
		switch(strtolower($type))
		{
			case 'css':
				$pat = "/<link{1}.*?href=['|\"']{1}(.*?)['|\"]{1}/i";
				break;
				
			case 'js':
				$pat = "/<script{1}.*?src=['|\"]{1}(.*?)['|\"]{1}/i";
				break;
				
			default:
				return FALSE;
				break;
		}
		
		if (preg_match_all($pat, $haystack, $matches, PREG_PATTERN_ORDER))
			$ret = $matches[1];
		
		return $ret;
	}
	
	
	/**
	 * Processes the tag.
	 * @param string $type The type of files to be compressed ('css' or 'js').
	 * @return string The String to output.
   */
	function _process($type='')
	{
		global $TMPL;
		
		// Retrieve the parameters.
		$disable = strtolower($TMPL->fetch_param('disable'));
		$debug = strtolower($TMPL->fetch_param('debug'));
		
		// Disabled?
		if ($disable == 'yes' OR $disable == 'true')
			return $TMPL->tagdata;
			
		// Check we have the required path to the Minifier script.
		if ( ! isset($this->settings['min_url']))
			return $TMPL->tagdata;
			
		// Check we have a valid $type.
		$type = strtolower($type);
		if ($type != 'css' && $type != 'js')
			return $TMPL->tagdata;
		
		// Make a note of whether we're in debug mode.
		$debug = ($debug == 'yes' OR $debug == 'true');
		
		if ($type == 'css')
		{
			$source_files = $this->_extract_files_to_process($TMPL->tagdata, 'css');
			
			// Also need to retrieve the media type.
			$pat = "/<link{1}.*?media=['|\"']{1}(.*?)['|\"]{1}/i";
			$media = 'all';
			
			if (preg_match_all($pat, $TMPL->tagdata, $matches, PREG_PATTERN_ORDER))
			{
				// Check that all the media types are the same.
				$media = $matches[1][0];
				foreach ($matches[1] as $match_media)
				{						
					if ($media != $match_media)
						return $TMPL->tagdata;
				}
			}
		}
		else
		{
			$source_files = $this->_extract_files_to_process($TMPL->tagdata, 'js');
		}
		
		// If we have no files to process, just return the original template tagdata.
		if ($source_files === FALSE)
			return $TMPL->tagdata;
			
		// Loop through the files to process, constructing the query string.
		$qs = '?f=';
		
		foreach ($source_files as $file)
		{
			$file = str_replace('&#47;', '/', $file);
			$file = ltrim($file, '/');
			$qs .= $file . ',';
		}
		
		$qs = rtrim($qs, ',');
		if ($debug === TRUE)
			$qs .= '&amp;debug=1';
			
		// Create the full URL to the Minify that will do the donkey work for us (yay!)
		$src = $this->settings['min_url'] . $qs;
		
		if ($type == 'css')
			return '<link rel="stylesheet" type="text/css" media="' . $media . '" href="' . $src . '" />';
		else
			return '<script type="text/javascript" src="' . $src . '"></script>';
	}
	
	
	/**
	 * Displays usage instructions in the EE control panel.
	 */
	function usage()
	{
		ob_start(); 
	  ?>
		---
		METHOD: combine
		---
		Combine the specified files into a single document.

		Example usage:
		{exp:sl_combinator:combine_js}
			<script type="text/javascript" src="/js/lib/common.js"></script>
			<script type="text/javascript" src="js/custom/nice.js"></script>
			<script type="text/javascript" src="../errant.js"></script>
		{/exp:sl_combinator:combine_js}
		
		{exp:sl_combinator:combine_css}
			<link rel="stylesheet" type="text/css" media="screen" href="/css/basic.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="css/fancy.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="../errant.css" />
		{/exp:sl_combinator:combine_css}
		
	  <?php
	  	$buffer = ob_get_contents();
	  	ob_end_clean(); 
	  	return $buffer;
	}

}

?>