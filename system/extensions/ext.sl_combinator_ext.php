<?php

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

/**
 * Combine and compress JavaScript and CSS files for faster downloading.
 *
 * @package   	SL Combinator
 * @version   	2.1.1
 * @author    	Stephen Lewis <addons@experienceinternet.co.uk>
 * @copyright 	Copyright (c) 2008-2010, Stephen Lewis
 * @link      	http://experienceinternet.co.uk/software/sl-combinator/
 */

class Sl_combinator_ext {
	
	/**
	 * Description.
	 *
	 * @access	public
	 * @var		string
	 */
	var $description = 'Settings for the SL Combinator add-on.';
	
	/**
	 * Documentation URL.
	 *
	 * @access	public
	 * @var		string
	 */
	var $docs_url = 'http://experienceinternet.co.uk/software/sl-combinator/';
	
	
	/**
	 * Extension name.
	 *
	 * @access	public
	 * @var		string
	 */
	var $name = 'SL Combinator';
	
	/**
	 * Settings
	 *
	 * @access	public
	 * @var		string
	 */
	var $settings = array();
	
	/**
	 * Does the extension have a settings form?
	 *
	 * @access	public
	 * @var		string
	 */
	var $settings_exist = 'y';
	
	/**
	 * Version.
	 *
	 * @access	public
	 * @var		string
	 */
	var $version = '2.1.1';
	
	
	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */

	/**
	 * PHP4 constructor.
	 *
	 * @see		__construct
	 * @access	public
	 * @param	mixed	$settings	Previously-saved extension settings.
	 * @return	void
	 */
	function Sl_combinator_ext($settings = '')
	{
		$this->__construct($settings);
	}
	
	
	/**
	 * PHP5 constructor
	 *
	 * @access	public
	 * @param 	mixed 	$settings 	Previously-saved extension settings.
	 * @return 	void
	 */
	function __construct($settings='')
	{
		global $PREFS, $DB, $REGX;
		
		$settings = FALSE;
		
		$query = $DB->query("SELECT settings
			FROM exp_extensions
			WHERE enabled = 'y'
			AND class = '" . get_class($this) . "' LIMIT 1");
		
		if ($query->num_rows == 1)
		{
			$settings = $REGX->array_stripslashes(unserialize($query->row['settings']));
		}
		
		$this->settings = $settings;
	}
	
	
	/**
	 * Registers a new addon.
	 *
	 * @access	public
	 * @param	array 		$addons			The existing addons.
	 * @return 	array
	 */
	function lg_addon_update_register_addon($addons)
	{
		global $EXT;
		
		// Retrieve the data from the previous call, if applicable.
		if ($EXT->last_call !== FALSE)
		{
			$addons = $EXT->last_call;
		}
		
		// Register a new addon.
		if ($this->settings['update_check'] == 'y')
		{
			$addons[$this->name] = $this->version;
		}
		
		return $addons;
	}
	
	
	/**
	 * Registers a new addon source.
	 *
	 * @access	public
	 * @param	array 		$sources		The existing sources.
	 * @return	array
	 */
	function lg_addon_update_register_source($sources)
	{
		global $EXT;
		
		// Retrieve the data from the previous call, if applicable.
		if ($EXT->last_call !== FALSE)
		{
			$sources = $EXT->last_call;
		}
		
		// Register a new source.
		if ($this->settings['update_check'] == 'y')
		{
			$sources[] = 'http://experienceinternet.co.uk/addon-versions.xml';
		}
		
		return $sources;
	}
	
	
	/**
	 * Activate the extension.
	 *
	 * @access	public
	 * @return 	void
	 */
	function activate_extension()
	{
		global $DB;
		
		$hooks = array(
			'lg_addon_update_register_source'	=> 'lg_addon_update_register_source',
			'lg_addon_update_register_addon'	=> 'lg_addon_update_register_addon'
		);
			
		foreach ($hooks AS $hook => $method)
		{
			$sql[] = $DB->insert_string(
				'exp_extensions',
				array(
					'extension_id' => '',
					'class'        => get_class($this),
					'method'       => $method,
					'hook'         => $hook,
					'settings'     => '',
					'priority'     => 10,
					'version'      => $this->version,
					'enabled'      => 'y'
				)
			);
		}
		
		// Run all the SQL queries.
		foreach ($sql AS $query)
		{
			$DB->query($query);
		}
	}


	/**
	 * Updates the extension.
	 *
	 * @access	public
	 * @param 	string 		$current 	The current extension version.
	 * @return 	bool
	 */
	function update_extension($current='')
	{
		global $DB;

		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < $this->version)
		{
			$DB->query("UPDATE exp_extensions
				SET version = '" .$DB->escape_str($this->version) ."' 
				WHERE class = '" .get_class($this) ."'");
		}
	}


	/**
	 * Disables the extension, and deletes settings from the database.
	 *
	 * @access	public
	 * @return 	void
	 */
	function disable_extension()
	{
		global $DB;
		
		$DB->query("DELETE FROM exp_extensions
			WHERE class = '" .get_class($this) ."'");
	}
	
	
	/**
	 * Get the extension settings from the extensions database table. Used by
	 * the SL Combinator plugin.
	 *
	 * @access	public
	 * @return 	array|bool
	 */
	function get_settings()
	{
		global $DB, $SESS, $REGX;
		
		$ret = FALSE;
		$cache_id = strtolower(get_class($this));
		
		if (isset($SESS->cache[$cache_id]['settings']) === FALSE)
		{
			$query = $DB->query("SELECT settings
				FROM exp_extensions
				WHERE enabled = 'y'
				AND class = '" .get_class($this) ."' LIMIT 1");
			
			// If we've got some settings, save them to the cache.
			if ($query->num_rows == 1)
			{
				$SESS->cache[$cache_id]['settings'] = $REGX->array_stripslashes(unserialize($query->row['settings']));
			}
		}
		
		if (empty($SESS->cache[$cache_id]['settings']) !== TRUE)
		{
			$ret = $SESS->cache[$cache_id]['settings'];
		}
		
		return $ret;
	}
	
	
	/**
	 * Enables the user to specify the Extension settings.
	 *
	 * @access	public
 	 * @return 	void
	 */
	function settings_form($current)
	{	
		global $DSP, $LANG;
		
		$DSP->crumbline = TRUE;
		
		$DSP->title = $LANG->line('extension_settings');
		$DSP->crumb = $DSP->anchor(BASE . AMP . 'C=admin' . AMP . 'P=utilities', $LANG->line('utilities'));
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE . AMP . 'C=admin' . AMP . 'M=utilities' . AMP . 'P=extensions_manager', $LANG->line('extensions_manager')));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('extension_name'));
		
		// Start writing out the body.
		$DSP->body = '';
		
		// Main title.
		$DSP->body .= $DSP->heading($LANG->line('extension_name') . " <small>v{$this->version}</small>");
		
		// Open the form.		
		$DSP->body .= $DSP->form_open(
			array(
				'action' => 'C=admin' . AMP . 'M=utilities' . AMP . 'P=save_extension_settings',
				'id'	=> 'sl_combinator',
				'name'	=> 'sl_combinator'
			),
			array('name' => strtolower(get_class($this)))		/* Must be lowercase. */
		);
			
		// Cache path settings.
		$DSP->body .= $DSP->table_open(
			array(
				'class' => 'tableBorder',
				'border' => '0',
				'style' => 'width : 100%; margin-top : 1em;',
			)
		);
			
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableHeading', '', '2');
		$DSP->body .= $LANG->line('min_url_title');
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
		
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('', '', '2');
		$DSP->body .= "<div class='box' style='border-width : 0 0 1px 0; margin : 0; padding : 10px 5px'><p>" . $LANG->line('min_url_info'). "</p></div>";
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();	
		
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableCellOne', '40%');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('min_url_label'));
		$DSP->body .= $DSP->td_c();
		
		$min_url = isset($this->settings['min_url']) ? $this->settings['min_url'] : '';
		
		$DSP->body .= $DSP->td('tableCellOne', '60%');
		$DSP->body .= $DSP->input_text('min_url', $min_url);
		$DSP->body .= $DSP->td_c();
		
		$DSP->body .= $DSP->tr_c();
		$DSP->body .= $DSP->table_c();		
		
		// Automatic update?
		$DSP->body .= $DSP->table_open(
			array(
				'class' => 'tableBorder',
				'border' => '0',
				'style' => 'width : 100%; margin-top : 1em;',
			)
		);
			
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableHeading', '', '2');
		$DSP->body .= $LANG->line('update_check_title');
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
		
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('', '', '2');
		$DSP->body .= "<div class='box' style='border-width : 0 0 1px 0; margin : 0; padding : 10px 5px'><p>" . $LANG->line('update_check_info'). "</p></div>";
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();	
		
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableCellOne', '40%');
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('update_check_label'));
		$DSP->body .= $DSP->td_c();
		
		$update_check = isset($this->settings['update_check']) ? $this->settings['update_check'] : 'y';
		
		$DSP->body .= $DSP->td('tableCellOne', '60%');
		$DSP->body .= $DSP->input_select_header('update_check', '', 3, '', 'id="update_check"');
		$DSP->body .= $DSP->input_select_option('y', 'Yes', ($update_check == 'y' ? 'selected' : ''));
		$DSP->body .= $DSP->input_select_option('n', 'No', ($update_check == 'n' ? 'selected' : ''));
		$DSP->body .= $DSP->input_select_footer();
		$DSP->body .= $DSP->td_c();
		
		$DSP->body .= $DSP->tr_c();
		$DSP->body .= $DSP->table_c();
		
		// Form submission.
		$DSP->body .= $DSP->qdiv(
			'itemWrapperTop',
			$DSP->input_submit($LANG->line('save_settings'), 'save_settings', 'id="save_settings"')
		);
		
		$DSP->body .= $DSP->form_c();
	}
	
	
	/**
	 * Saves the Extension settings.
	 *
	 * @access	public
	 * @return 	void
	 */
	function save_settings()
	{
		global $DB, $REGX;
		
		// Initialise the settings array.
		$this->settings = array(
			'min_url'		=> isset($_POST['min_url']) ? $_POST['min_url'] : '',
			'update_check'	=> isset($_POST['update_check']) ? $_POST['update_check'] : ''
		);
		
		// Serialise the settings, and save them to the database.
		$sql = "UPDATE exp_extensions SET settings = '"
			.addslashes(serialize($this->settings)) ."' WHERE class = '"
			.get_class($this) ."'";
		
		$DB->query($sql);
	}
}

/* End of file		: ext.sl_combinator_ext.php */
/* File location	: /system/extensions/ext.sl_combinator_ext.php */