<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: FormText.php 18951 2009-11-12 16:26:19Z alexander $
 */


/**
 * Abstract class for extension
 */
require_once 'Zend/View/Helper/FormElement.php';


/**
 * Helper to generate a "text" element
 *
 * @category   Zend
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_FormDoctrine extends Zend_View_Helper_FormElement
{
	/**
	 * The View in use
	 * @var Zend_View_Interface
	 */
	public $view;
	
	/**
	 * Apply View
	 * @param Zend_View_Interface $view
	 */
	public function setView (Zend_View_Interface $view) {
		# Set
		$this->view = $view;
		
		# Chain
		return $this;
	}
	
    /**
     * Generates a 'text' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are used in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function formDoctrine( $name, $value = null, $attribs = null, $options = null, $listsep = null ) {
		# Prepare
		$Locale = Bal_App::getLocale();
		$result = '';
		$custom_args_keys = array(
			'name','value',
			'target','source',
			'table','field',
			'type','notnull','notblank','auto','length','relationStatus'
		);
		
		# --------------------------
		
		# Attempt to Extract Custom Info
		if ( is_array($name) ) {
			$custom_args = array_keys_keep($name, $custom_args_keys);
		}
		elseif ( is_array($attribs) ) {
			$custom_args = array_keys_keep($attribs, $custom_args_keys);
		}
		else {
			$custom_args = array();
		}
		
		# Fetch Info
		$info = array_merge(
			$this->_getInfo($name, $value, $attribs, $options, $listsep),
				/* ^ generates: name, id, value, attribs, options, [listsep, disable, escape] */
			$custom_args
		);
		$info = array_keys_ensure($info, $custom_args_keys);
        extract($info);
		
		# --------------------------
		
		# Handle Target Shortcut
		// perhaps instead of passing in name and value, we pass target and source
		if ( !empty($target) ) {
			# Name <- Target
			if ( empty($name) ) {
				$name = make_field_name($target);
			}
			# Value <- Target, Source
			if ( !empty($source)) {
				$value = delve($source,$target);
			}
			# Table, Field <- Target
			if ( empty($table) && empty($field) && count($target) === 2 ) {
				$table = $target[0];
				$field = $target[1];
			}
		}
		
		# --------------------------
		
		# Fetch Table Information
		$Table = Bal_Doctrine_Core::getTable($table);
		if ( $type === null )		$type = Bal_Doctrine_Core::getFieldType($Table,$field);
		$properties = $Table->getDefinitionOf($field);
		array_keys_ensure($properties, array('length'), null);
		
		# Prepare lowers for il8ns indexes
		$tableLower = strtolower($table);
		$fieldLower = strtolower($field);
		
		# --------------------------
		
		# Extract Field Properties
		if ( $notblank === null )			$notblank = delve($properties,'notblank');
		if ( $notnull === null )			$notnull = delve($properties,'notnull');
		if ( $auto === null )				$auto = delve($properties,'auto');
		if ( $length === null )				$length = delve($properties,'length');
		if ( $relationStatus === null )		$relationStatus = delve($properties,'relationStatus');
		
		# Prepare Attributes
		array_keys_ensure($attribs, array('class'), '');
		
		# --------------------------
		
		# Prepare value
		if ( is_array($value) || is_object($value) ) {
			if ( is_numeric(delve($value,'id')) ) { // for some reason a doctrine collection actually returns an ID value
				$value = delve($value,'id');
			}
			elseif ( is_traversable($value) ) {
				$values = array();
				foreach ( $value as $_value ) {
					$values[] = delve($_value,'id',$_value);
				}
				$value = $values;
			}
			else {
				throw new Bal_Exception(array(
					'Could not convert the high level value into a series of low level values that ZF can understand',
					'value' => $value,
					'field' => $name
				));
			}
		}
		
		# --------------------------
		
		# Handle
		switch ( $type ) {
			case 'relation':
				# Prepare
				$Relation = $Table->getRelation($field);
				$RelationTable = $Relation->getTable();
				
				# Determine
				$text_field = Bal_Doctrine_Core::getTableLabelFieldName($RelationTable);
				
				# Fetch
				try {
					$relations = $RelationTable->createQuery()->select('id, '.$text_field.' as text')->setHydrationMode(Doctrine::HYDRATE_ARRAY)->execute();
				}
				catch ( Exception $Exception ) {
					$relations = array();
					$Relations = delve($Table,$field);
					if ( $Relations ) foreach ( $Relations as $relation ) {
						$relations[] = array('id'=>$relation['id'],'text'=>$relation[$text_field]);
					}
				}
				
				# Options
				if ( empty($options) ) {
					# Pprepare
					$options = array();
				
					# Options: Empty Value
					if ( !$notnull ) {
						$options['null'] = $Locale->translate('select-empty');
					}
				
					# Options: Relations
					foreach ( $relations as $relation ) {
						$options[$relation['id']] = $relation['text'];
					}
				}
				
				# Display
				if ( empty($options) ) {
					$result .= '<span class="form-empty">'.$Locale->translate('none').'</span>';
				}
				else {
					if ( count($options) === 1 ) {
						$attribs['disabled'] = $attribs['readonly'] = true;
					}
					elseif ( $Relation->getType() === Doctrine_Relation::MANY ) {
						$attribs['multiple'] = true;
						unset($options['']);
					}
					$result .= $this->view->formSelect($name, $value, $attribs, $options);
				}
				break;
				
			case 'enum':
				# Enum Values
				$enumValues = $Table->getEnumValues($field);
				$enumValues = array_flip($enumValues);
				
				# Translate Enum VAlues
				foreach ( $enumValues as $enum => &$text ) {
					$text = $Locale->translate_default($tableLower.'-'.$fieldLower.'-'.$enum, array(), ucfirst($enum));
				}
				
				# Options
				if ( empty($options) ) {
					# Prepare
					$options = array();
				
					# Options: Empty Value
					if ( !$notnull && empty($attribs['multiple']) ) {
						$options['null'] = $Locale->translate('select-empty');
					}
				
					# options: Enum Values
					$options = array_merge($options, $enumValues);
				}
				
				# Handle Options
				if ( count($options) === 1 ) {
					$attribs['disabled'] = $attribs['readonly'] = true;
				}
			
				# Display
				$result .= $this->view->formSelect($name, $value, $attribs, $options);
				break;
			
			case 'bool':
			case 'boolean':
				$result .= $this->view->formBoolean($name, $value, $attribs);
				break;
			
			case 'timestamp':
			case 'datetime':
				$result .= $this->view->formDatetime($name, $value, $attribs);
				break;
				
			case 'date':
				$result .= $this->view->formDate($name, $value, $attribs);
				break;
				
			case 'time':
				$result .= $this->view->formTime($name, $value, $attribs);
				break;
			
			case 'currency':
				$result .= $this->view->formCurrency($name, $value, $attribs);
				break;
				
			case 'integer':
			case 'decimal':
			case 'float':
				$result .= $this->view->formNumber($name, $value, $attribs);
				break;
				
			case 'password':
				# Prepare
				$value = null; // We never want to output a password
				
				# Handle
				if ( $length && $length <= 255 ) {
					$attribs['maxlength'] = $length;
				}
				$attribs['autocomplete'] = 'off';
				$attribs['class'] .= ' sparkle-password';
				$result .= $this->view->formPassword($name, $value, $attribs);
				break;
			
			case 'text':
			case 'string':
				if ( $length && $length <= 255 ) {
					$attribs['maxlength'] = $length;
					$result .= $this->view->formText($name, $value, $attribs);
					break;
				}
			case 'textarea':
				$attribs['class'] .= ' autogrow';
				$result .= $this->view->formTextarea($name, $value, $attribs);
				break;
			
			case 'csv':
				$result .= $this->view->formCsv($name, $value, $attribs);
				break;
							
			case 'rating':
				$result .= $this->view->formRating($name, $value, $attribs);
				break;
				
			case 'hidden':
				$result .= $this->view->formHidden($name, $value, $attribs);
				break;
				
			default:
				throw new Zend_Exception('error-unkown_input_type['.$type.']['.$field.']');
				break;
		}
		
		# Done
		return $result;
    }
}
