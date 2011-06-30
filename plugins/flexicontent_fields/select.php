<?php
/**
 * @version 1.0 $Id$
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.select
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');

class plgFlexicontent_fieldsSelect extends JPlugin
{
	function plgFlexicontent_fieldsSelect( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		JPlugin::loadLanguage('plg_flexicontent_fields_select', JPATH_ADMINISTRATOR);
	}
	function onAdvSearchDisplayField(&$field, &$item) {
		plgFlexicontent_fieldsSelect::onDisplayField($field, $item);
	}
	function onDisplayField(&$field, &$item)
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'select') return;
		$field->label = JText::_($field->label);
		// some parameter shortcuts
		$sql_mode			= $field->parameters->get( 'sql_mode', 0 ) ;
		$empty_option		= $field->parameters->get( 'empty_option', 1 ) ;
		$field_elements		= $field->parameters->get( 'field_elements' ) ;
		$default_value		= $field->parameters->get( 'default_value' ) ;
						
		$required 			= $field->parameters->get( 'required', 0 ) ;
		$required 	= $required ? ' required' : '';

		// initialise property
		if($item->version < 2 && $default_value) {
			$field->value = array();
			$field->value[0] = $default_value;
		} else if (!$field->value) {
			$field->value = array();
			$field->value[0] = '';
		}

		if ($sql_mode) { // SQL mode
			
			$db =& JFactory::getDBO();
		
			$query = preg_match('#^select#i', $field_elements) ? $field_elements : '';
			$db->setQuery($query);
			$results = $db->loadObjectList();
			
			if (!$results) {
				$field->html = JText::_('FLEXI_FIELD_INVALID_QUERY');
			
			} else {
			
				$options = array();
				if ($empty_option) $options[] = JHTML::_('select.option', '', JText::_('FLEXI_PLEASE_SELECT'));
				foreach($results as $result) {
					$options[] = JHTML::_('select.option', $result->value, JText::_($result->text));
				}
				$field->html	= JHTML::_('select.genericlist', $options, $field->name, 'class="'.$required.'"', 'value', 'text', $field->value);
			}
		} else { // Elements mode
			$listelements = explode("%% ", $field_elements);
			$listarrays = array();
			foreach ($listelements as $listelement) {
				$listarrays[] = explode("::", $listelement);
			}

			$options = array(); 
			$options[] = JHTML::_('select.option', '', JText::_('FLEXI_PLEASE_SELECT'));
			$i = 1;
			$display = "";
			foreach ($listarrays as $listarray) {
				$options[] = JHTML::_('select.option', $listarray[0], JText::_($listarray[1])); 
				if ($field->value[0] == $listarray[0]) {
					$display = JText::_($listarray[1]);
				}
				$i++;
			}
			$field->html	= JHTML::_('select.genericlist', $options, $field->name, 'class="'.$required.'"', 'value', 'text', $field->value);
			$field->display	= $display ? $display:JText::_( 'FLEXI_NO_VALUE' );
		}
	}


	function onBeforeSaveField( $field, &$post, &$file )
	{
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'select') return;
		if(!$post) return;
		
		// create the fulltext search index
		$searchindex = '';
		
		$pretext			= $field->parameters->get( 'pretext', '' ) ;
		$posttext			= $field->parameters->get( 'posttext', '' ) ;
		$field_elements		= $field->parameters->get( 'field_elements', '' ) ;
		$sql_mode			= $field->parameters->get( 'sql_mode', 0 ) ;
		$remove_space		= $field->parameters->get( 'remove_space', 0 ) ;

		if($pretext) 	{ $pretext 	= $remove_space ? '' : $pretext . ' '; }
		if($posttext) 	{ $posttext	= $remove_space ? '' : ' ' . $posttext; }

		if ($sql_mode) { // SQL mode
			
			$db =& JFactory::getDBO();
		
			$query = preg_match('#^select#i', $field_elements) ? $field_elements : '';
			$db->setQuery($query);
			$results = $db->loadObjectList();
			
			if (!$results) {
				$field->{$prop} = '';
			
			} else {

				foreach($results as $result) {
					if ($result->value == $post) {
						$searchindex	= $pretext . JText::_($result->text) . $posttext;
					}
				}
				
				$searchindex .= ' | ';

				$field->search = $field->issearch ? $searchindex : '';
			}

		} else { // Elements mode

			$listelements = explode("%% ", $field_elements);
			$listarrays = array();
		
			foreach ($listelements as $listelement) {
				$listarrays[] = explode("::", $listelement);
			}

			foreach ($listarrays as $listarray) {
				if ($post == $listarray[0]) {
					$searchindex = $listarray[1];
				} 
			}
			
		$searchindex .= ' | ';

		$field->search = $field->issearch ? $searchindex : '';
		
		}
	}


	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		$field->label = JText::_($field->label);
		// execute the code only if the field type match the plugin type
		if($field->field_type != 'select') return;
		
		$values = $values ? $values : $field->value ;

		// some parameter shortcuts
		$remove_space		= $field->parameters->get( 'remove_space', 0 ) ;
		$pretext			= $field->parameters->get( 'pretext', '' ) ;
		$posttext			= $field->parameters->get( 'posttext', '' ) ;
		$field_elements		= $field->parameters->get( 'field_elements', '' ) ;
		$sql_mode			= $field->parameters->get( 'sql_mode', 0 ) ;
		$text_or_value		= $field->parameters->get( 'text_or_value', 1 ) ;
						
		if($pretext) 	{ $pretext 	= $remove_space ? $pretext : $pretext . ' '; }
		if($posttext) 	{ $posttext	= $remove_space ? $posttext : ' ' . $posttext; }

		if ($sql_mode) { // SQL mode
			
			$db =& JFactory::getDBO();
		
			$query = preg_match('#^select#i', $field_elements) ? $field_elements : '';
			$db->setQuery($query);
			$results = $db->loadObjectList();
			
			if (!$results) {
				$field->{$prop} = '';
			
			} else {

				if ($values) {
					foreach($results as $result) {
						if ($result->value == $values[0]) {
							$field->{$prop}	= $pretext . JText::_($text_or_value ? $result->text : $result->value) . $posttext;
						}
					}
				}
			}

		} else { // Elements mode

			// initialise property
			$listelements = explode("%% ", $field_elements);
			$listarrays = array();

			foreach ($listelements as $listelement) {
				$listarrays[] = explode("::", $listelement);
			}

			$i = 1;
			$display = "";
			if ($values) {
				foreach ($listarrays as $listarray) {
					if ($values[0] == $listarray[0]) {
						$display = $pretext . JText::_($text_or_value ? $listarray[1] : $listarray[0]) . $posttext;
					}
				$i++;
				}			
			}
			$field->{$prop}	= $display ? $display : '';
		}
	}


	function onDisplayFilter(&$filter, $value='')
	{
		// execute the code only if the field type match the plugin type
		if($filter->field_type != 'select') return;

		// some parameter shortcuts
		$field_elements		= $filter->parameters->get( 'field_elements' ) ;
		$sql_mode			= $filter->parameters->get( 'sql_mode', 0 ) ;
		$label_filter 		= $filter->parameters->get( 'display_label_filter', 0 ) ;
		if ($label_filter == 2) $text_select = $filter->label; else $text_select = JText::_('All');
		$field->html = '';
		
		if ($sql_mode) { // SQL mode
			
			$db =& JFactory::getDBO();
		
			$query = preg_match('#^select#i', $field_elements) ? $field_elements : '';
			$db->setQuery($query);
			$results = $db->loadObjectList();
			
			if (!$results) {
				$field->html = '';
			
			} else {
			
				$options = array();
				$options[] = JHTML::_('select.option', '', '-'.$text_select.'-');
				foreach($results as $result) {
					$options[] = JHTML::_('select.option', $result->value, JText::_($result->text));
				}
				if ($label_filter == 1) $filter->html  .= $filter->label.': ';
				$filter->html	.= JHTML::_('select.genericlist', $options, 'filter_'.$filter->id, 'onchange="document.getElementById(\'adminForm\').submit();"', 'value', 'text', $value);
			}

		} else { // Elements mode

		$listelements = explode("%% ", $field_elements);
		$listarrays = array();
		foreach ($listelements as $listelement) {
			$listarrays[] = explode("::", $listelement);
			}

		$options = array(); 
		
		$options[] = JHTML::_('select.option', '', '-'.$text_select.'-');
		foreach ($listarrays as $listarray) {
			$options[] = JHTML::_('select.option', $listarray[0], $listarray[1]); 
			}			
			if ($label_filter == 1) $filter->html  .= $filter->label.': ';
		$filter->html	.= JHTML::_('select.genericlist', $options, 'filter_'.$filter->id, 'onchange="document.getElementById(\'adminForm\').submit();"', 'value', 'text', $value);
		}
	}
}
