<?php
/**
 * PageCarton Content Management System
 *
 * LICENSE
 *
 * @category   PageCarton CMS
 * @package    Application_Settings_Abstract
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Abstract.php 5.7.2012 11.53 ayoola $
 */

/**
 * @see Ayoola_Abstract_Playable
 */
 
require_once 'Ayoola/Abstract/Playable.php';


/**
 * @category   PageCarton CMS
 * @package    Application_Settings_Abstract
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

abstract class Application_Settings_Abstract extends Ayoola_Abstract_Table
{
	
    /**
     * Whether class is playable or not
     *
     * @var boolean
     */
	protected static $_playable = true;
	
    /**
     * Access level for player
     *
     * @var boolean
     */
	protected static $_accessLevel = array( 99, 98 );
	
    /**
     * Settings
     * 
     * @var array
     */
	protected static $_settings;
	
    /**
     * Default Database Table
     *
     * @var string
     */
	protected $_tableClass = 'Application_Settings';
	
    /**
     * Identifier for the column to edit
     * 
     * @var array
     */
	protected $_identifierKeys = array( 'settingsname_name' );
	
    /**
     * Calls this after every successful settings change
     * 
     */ 
	public static function callback()
    {

	}
	
	
	
    /**
     * Sets and Returns the setting
     * 
     */
	public static function retrieve( $key = null )
    {
		$class = get_called_class();
		$settings = new Application_Settings_SettingsName();
		if( $settingsNameInfo = $settings->selectOne( null, array( 'class_name' => $class ) ) )
		{
			$settingsNameToUse = $settingsNameInfo['settingsname_name'];
			return self::getSettings( $settingsNameToUse, $key );
		}
		elseif( $extensionInfo = $table->selectOne( null,  array( 'settings_class' => $class ) ) )
		{
			return self::getSettings( $extensionInfo['extension_name'], $key );
		}
//	var_export(  $settingsNameToUse );
	}
	
    /**
     * Sets and Returns the setting
     * 
     */
	public static function getSettings( $settingsName, $key = null )
    {
		if( is_null( @self::$_settings[$settingsName] ) )
		{
			$settings = Application_Settings::getInstance();
			$settings = $settings->selectOne( null, array( 'settingsname_name' => $settingsName ) );
			if( ! isset( $settings['settings'] ) )
			{ 

				//	Not found in site settings. 
				//	Now lets look in the extension settings
				$table = Ayoola_Extension_Import_Table::getInstance();
		//		var_export( $table->select() );
		//		var_export( $settingsName );
				if( ! $extensionInfo = $table->selectOne( null,  array( 'extension_name' => $settingsName ) ) )
				{
					self::$_settings[$settingsName]  = false;
				//	return false; 
				}
			//	var_export( $settingsName );
			//	var_export( $extensionInfo );
				if( empty( $extensionInfo['settings'] ) )
				{
				//	settings getting lost in the subdomains with username
				//	workaround till we find lasting solution
					$domainSettings = Ayoola_Application::getDomainSettings();
					if( ! empty( $domainSettings['main_domain'] ) && $domainSettings['main_domain'] != $domainSettings['domain_name'] )
					{
						$settings = Application_Settings::getInstance()->selectOne( null, array( 'settingsname_name' => $settingsName ), array( 'disable_cache' => true ) );
						if( ! empty( $settings['settings'] ) )
						{
							static::$_settings[$settingsName] = unserialize( $settings['settings'] );
						//	self::v( static::$_settings );
						}
						else
						{
							static::$_settings[$settingsName] = false;
						}
					}
				//	self::v( $settings );
				}
				else
				{
					static::$_settings[$settingsName] =  $extensionInfo['settings'];
				}
				
			}
			else
			{
				static::$_settings[$settingsName] = unserialize( $settings['settings'] );
			}
			
		}
		if( static::$_settings[$settingsName] && is_string( static::$_settings[$settingsName] ) )
		{
			static::$_settings[$settingsName] = unserialize( static::$_settings[$settingsName] );  
		}

//		self::v( self::$_settings );
	//	if( is_array( self::$_settings[$settingsName] ) && array_key_exists( $key, self::$_settings[$settingsName] ) )
		if( ! is_null( $key ) )
		{
			return @self::$_settings[$settingsName][$key];
		}
		else
		{
			return self::$_settings[$settingsName];
		}
    } 
	
    /**
     * creates the form for creating and editing
     * 
     * param string The Value of the Submit Button
     * param string Value of the Legend
     * param array Default Values
     */
	public function createForm( $submitValue = null, $legend = null, Array $values = null )
    {
        $form = new Ayoola_Form( array( 'name' => $this->getObjectName() ) );
		$form->setParameter( array( 'no_fieldset' => true ) );
		
//		var_export( $values );
		
	//	$form->oneFieldSetAtATime = true;
		do
		{
			$formAvailable = false;
			//	self::v( $values );

			if( empty( $values['class_name'] ) || ! class_exists( $values['class_name'] ) )
			{
				break;
			}
			$player = new $values['class_name'];
			if( $player instanceof Application_Settings_Interface )
			{
				$fieldsets = $player::getFormFieldsets( $values );
			}
			else
			{
				$player->createForm( null, null, $values );
				$fieldsets = $player->getForm()->getFieldsets();
			}
		//	self::v( $form );   
			foreach( $fieldsets as $fieldset ){ $form->addFieldset( $fieldset ); }
			$formAvailable = true;
			$form->submitValue = 'Save';
			return $this->setForm( $form );
		}
		while( false );
 		$this->setForm( $form );
    } 
	// END OF CLASS
}
