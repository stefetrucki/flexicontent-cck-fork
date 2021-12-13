<?php

/**
 * Placement configuration
 */
$tab_fields       = $this->placementConf['tab_fields'];
$tab_titles       = $this->placementConf['tab_titles'];
$tab_icocss       = $this->placementConf['tab_icocss'];
$all_tab_fields   = $this->placementConf['all_tab_fields'];
$coreprop_missing = $this->placementConf['coreprop_missing'];


/**
 * Compatibility, if 'vstate' is present but not specified in configuration then append 'vstate' after 'state'
 */
if (!isset($all_tab_fields['vstate']) && isset($captured['vstate']))
{
	if (isset($all_tab_fields['state']))
	{
		$captured['state'] = isset($captured['state'])
			? $captured['state'] . $captured['vstate']
			: $captured['vstate'];
		unset($captured['vstate']);
	}
}


/**
 * Compatibility, if 'category' is present but not specified in configuration then place 'category' inside 'categories'
 */
if (!isset($all_tab_fields['category']) && isset($captured['category']))
{
	if (isset($all_tab_fields['categories']))
	{
		$captured['categories'] = isset($captured['categories'])
			? str_replace('<!--__FC_CATEGORY_BOX__-->', $captured['category'], $captured['categories'])
			: $captured['category'];
		unset($captured['category']);
	}
}



/**
 * Compatibility, if 'lang' is present but not specified in configuration then place 'lang' inside 'lang_assocs' (*which renamed from legacy name 'language')
 */
if (!isset($all_tab_fields['lang']) && isset($captured['lang']))
{
	if (isset($all_tab_fields['lang_assocs']))
	{
		$captured['lang_assocs'] = isset($captured['lang_assocs'])
			? str_replace('<!--__FC_LANGUAGE_BOX__-->', $captured['lang'], $captured['lang_assocs'])
			: $captured['lang'];
		unset($captured['lang']);
	}
}




/**
 * ANY field not found inside the 'captured' ARRAY,
 * must be a field not configured to be displayed
 */

$displayed_at_tab = array();
foreach($tab_fields as $tabname => $fieldnames)
{
	//echo "$tabname <br/>  %% " . print_r($fieldnames, true) . "<br/>";

	foreach($fieldnames as $fn => $i)
	{
		//echo " -- $fn <br/>";

		// Array used to count duplicate placement of fields
		if (isset($rendered[$fn]))
		{
			$displayed_at_tab[$fn][] = $tabname;
		}

		// If a field is assigned to multiple places then unset its excess placements (except the 1st placement)
		if (isset($shown[$fn]))
		{
			unset( $tab_fields[$tabname][$fn] );
			continue;
		}
		$shown[$fn] = 1;

		// If we did not captured the display of field
		// because field was skipped due to ACL or due to configuration,
		// then removed the field from its placement positions
		if (!isset($captured[$fn]))
		{
			unset( $tab_fields[$tabname][$fn] );
			continue;
		}
	}
}
//echo '<pre>'; print_r($displayed_at_tab); echo '</pre>'; exit;



/**
 * CONFIGURATION WARNING, fields that are displayed twice
 */
$field_n_places = array();
foreach($displayed_at_tab as $fieldname => $_places)
{
	if ( count($_places) > 1 ) $field_n_places[] = "<b>".$fieldname."</b>" . " at [".implode(', ', $_places)."]";
}

if ($field_n_places)
{
	$msg = JText::sprintf( 'FLEXI_FORM_FIELDS_DISPLAYED_TWICE', implode('<br>', $field_n_places) );
	echo sprintf( $alert_box, '', 'error', '', $msg );
}



// ************
// ABOVE TABSET
// ************
if ( count($tab_fields['above']) ) :

$total_fields = count($tab_fields['above']);
$use_flexbox  = $total_fields > 1;
$n = 0;
?>
<div class="fcform_tabs_above">

	<?php
	echo $use_flexbox ? '
	<div class="fc_form_flex_box">
		<div class="fc_form_flex_box_item">
	' : '';

	foreach($tab_fields['above'] as $fn => $i) :
		$n++;

		echo $use_flexbox && ($n - 1 == ceil($total_fields / 2)) ? '
			</div><div class="fc_form_flex_box_item">' : '';
		echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
			</div><div class="fc_form_flex_box_item" style="width: 100%;">
		' : '';
		if (!is_array($captured[$fn])) :
			echo $captured[$fn]; unset($captured[$fn]);
		else:
			foreach($captured[$fn] as $n => $html) : ?>
				<div class="fcclear"></div>
				<?php echo $html;
			endforeach;
			unset($captured[$fn]);
		endif;
	endforeach;

	echo $use_flexbox ? '
		</div>
	</div>
	' : '';
	?>

</div>
<?php endif;




// ***
// *** MAIN TABSET START
// ***
array_push($tabSetStack, $tabSetCnt);
$tabSetCnt = ++$tabSetMax;
$tabCnt[$tabSetCnt] = 0;
?>

<!-- tabber start -->
<div class="fctabber fields_tabset" id="fcform_tabset_<?php echo $tabSetCnt; ?>">


<?php
// ***
// *** DESCRIPTION TAB
// ***
if ( count($tab_fields['tab01']) ) :
	$tab_lbl = isset($tab_titles['tab01']) ? $tab_titles['tab01'] : JText::_( 'FLEXI_DESCRIPTION' );
	$tab_ico = isset($tab_icocss['tab01']) ? $tab_icocss['tab01'] : 'icon-file-2';
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php foreach($tab_fields['tab01'] as $fn => $i) : ?>
			<div class="fcclear"></div>

			<?php if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;
		?>

	</div> <!-- end tab -->
<?php endif;



// ***
// *** CUSTOM FIELDS TAB (via TYPE)
// ***
if ( count($tab_fields['tab02']) ) :
	$tab_lbl = isset($tab_titles['tab02']) ? $tab_titles['tab02'] : $this->type_lbl; // __TYPE_NAME__
	$tab_ico = isset($tab_icocss['tab02']) ? $tab_icocss['tab02'] : 'icon-tree-2';
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php foreach($tab_fields['tab02'] as $fn => $i) : ?>
			<div class="fcclear"></div>

			<?php if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;
		?>

	</div> <!-- end tab -->
<?php endif;




// ***
// *** JOOMLA IMAGE/URLS TAB
// ***
if ( count($tab_fields['tab02a']) ) : ?>
	<?php
	if (isset($tab_fields['tab02a']['jimages']) && isset($tab_fields['tab02a']['jurls'])) {
		$fsetname = 'FLEXI_COM_CONTENT_IMAGES_AND_URLS';
		$fseticon = 'icon-pencil-2';
	} else if (isset($tab_fields['tab02a']['jimages'])) {
		$fsetname = 'FLEXI_IMAGES';
		$fseticon = 'icon-images';
	} else if (isset($tab_fields['tab02a']['jurls'])) {
		$fsetname = 'FLEXI_LINKS';
		$fseticon = 'icon-link';
	} else {
		$fsetname = 'FLEXI_COMPATIBILITY';
		$fseticon = 'icon-pencil-2';
	}

	$tab_lbl = isset($tab_titles['jimage_jurls']) && $tab_titles['jimage_jurls'] !== '_DEFAULT_' ? $tab_titles['jimage_jurls'] : JText::_($fsetname);
	$tab_ico = isset($tab_icocss['jimage_jurls']) && $tab_icocss['jimage_jurls'] !== '_default_'? $tab_icocss['jimage_jurls'] : $fseticon;

	$total_fields = count($tab_fields['tab02a']);
	$use_flexbox  = $total_fields > 1;
	$n = 0;
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php
		echo $use_flexbox ? '
		<div class="fc_form_flex_box">
			<div class="fc_form_flex_box_item">
		' : '';

		foreach($tab_fields['tab02a'] as $fn => $i) :
			$n++;

			echo $use_flexbox && ($n - 1 == ceil($total_fields / 2)) ? '
				</div><div class="fc_form_flex_box_item">' : '';
			echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
				</div><div class="fc_form_flex_box_item" style="width: 100%;">
			' : '';
			if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;

		echo $use_flexbox ? '
			</div>
		</div>
		' : '';
		?>

	</div> <!-- end tab -->
<?php endif;




// ***
// *** Joomla custom fields (1 TAB: 'fields-0') and custom field groups (1 TAB per group: fields-n)
// ***

$fieldSets = $this->form->getFieldsets();
foreach ($fieldSets as $name => $fieldSet) :
	if (substr($name, 0, 7) !== 'fields-') continue;

	$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_FLEXICONTENT_'.$name.'_FIELDSET_LABEL';
	if ( JText::_($label)=='COM_FLEXICONTENT_'.$name.'_FIELDSET_LABEL' ) $label = 'COM_CONTENT_'.$name.'_FIELDSET_LABEL';

	$icon_class = 'icon-pencil-2';
	//echo JHtml::_('sliders.panel', JText::_($label), $name.'-options');
	//echo "<h2>".$label. "</h2> " . "<h3>".$name. "</h3> ";
	?>
	<!-- CUSTOM parameters TABs -->
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $icon_class; ?>">
		<h3 class="tabberheading"> <?php echo JText::_($label); ?> </h3>

		<div class="fc_tabset_inner">
			<?php foreach ($this->form->getFieldset($name) as $field) : ?>

				<?php if ($field->hidden): ?>
					<span style="display:none !important;">
						<?php echo $field->input; ?>
					</span>
				<?php else :
					echo ($field->getAttribute('type') === 'separator' || $field->hidden || !$field->label) ? $field->input : '
					<div class="control-group">
						<div class="control-label" id="jform_attribs_'.$field->fieldname.'-lbl-outer">
							' . str_replace('class="', 'class="' . $lbl_class . ' label-fcinner ', str_replace(' for="', ' data-for="', $field->label)) . '
						</div>
						<div class="controls container_fcfield">
							' . $field->input . '
						</div>
					</div>
					';
				endif; ?>

			<?php endforeach; ?>
		</div>

	</div> <!-- end tab -->
<?php endforeach;




// ***
// *** ASSIGNMENTS TAB (Multi-category assignments  -- and --  Item language associations)
// ***
if ( count($tab_fields['tab03']) ) :
	$tab_lbl = isset($tab_titles['tab03']) ? $tab_titles['tab03'] : JText::_( 'FLEXI_ASSIGNMENTS' );
	$tab_ico = isset($tab_icocss['tab03']) ? $tab_icocss['tab03'] : 'icon-signup';

	$total_fields = count($tab_fields['tab03']);
	$use_flexbox  = $total_fields > 1;
	$n = 0;
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>" >
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php
		echo $use_flexbox ? '
		<div class="fc_form_flex_box">
			<div class="fc_form_flex_box_item">
		' : '';

		foreach($tab_fields['tab03'] as $fn => $i) :
			$n++;

			echo $use_flexbox && ($n - 1 == ceil($total_fields / 2)) ? '
				</div><div class="fc_form_flex_box_item">' : '';
			echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
				</div><div class="fc_form_flex_box_item" style="width: 100%;">
			' : '';
			if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;

		echo $use_flexbox ? '
			</div>
		</div>
		' : '';
		?>

	</div>
<?php endif;


//echo "<pre>"; print_r(array_keys($this->form->getFieldsets('attribs'))); echo "</pre>";
//echo "<pre>"; print_r(array_keys($this->form->getFieldsets())); echo "</pre>";

$fieldSets = $this->form->getFieldsets();
foreach ($fieldSets as $name => $fieldSet) :
	if (substr($name, 0, 7) == 'params-' || substr($name, 0, 7) == 'fields-' || $name=='themes' || $name=='item_associations') continue;

	$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_FLEXICONTENT_'.$name.'_FIELDSET_LABEL';
	if ( JText::_($label)=='COM_FLEXICONTENT_'.$name.'_FIELDSET_LABEL' ) $label = 'COM_CONTENT_'.$name.'_FIELDSET_LABEL';

	if ($name == 'metafb')
		$icon_class = 'icon-users';
	else
		$icon_class = '';
	?>
	<!-- CUSTOM parameters TABs -->
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $icon_class; ?>">
		<h3 class="tabberheading"> <?php echo JText::_($label); ?> </h3>

		<div class="fc_tabset_inner">
			<?php foreach ($this->form->getFieldset($name) as $field) : ?>

				<?php if ($field->hidden): ?>
					<span style="display:none !important;">
						<?php echo $field->input; ?>
					</span>
				<?php else :
					echo ($field->getAttribute('type')=='separator' || $field->hidden || !$field->label) ? $field->input : '
					<div class="control-group">
						<div class="control-label" id="jform_attribs_'.$field->fieldname.'-lbl-outer">
							' . str_replace('class="', 'class="' . $lbl_class . ' label-fcinner ', str_replace(' for="', ' data-for="', $field->label)) . '
						</div>
						<div class="controls container_fcfield">
							' . $this->getFieldInheritedDisplay($field, $this->iparams) . '
						</div>
					</div>
					';
				endif; ?>

			<?php endforeach; ?>
		</div>

	</div> <!-- end tab -->
<?php endforeach; ?>



<?php

// ***
// *** PUBLISHING TAB
// ***
// J2.5 requires Edit State privilege while J1.5 requires Edit privilege
if ( count($tab_fields['tab04']) ) : ?>
	<?php
	$tab_lbl = isset($tab_titles['tab04']) ? $tab_titles['tab04'] : JText::_( 'FLEXI_PUBLISHING' );
	$tab_ico = isset($tab_icocss['tab04']) ? $tab_icocss['tab04'] : 'icon-calendar';

	$has_i_screen = !isset($tab_icocss['tab04']['item_screen']);
	$total_fields = count($tab_fields['tab04']);
	$use_flexbox  = $total_fields > 1;
	$n = 0;
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>" >
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php
		echo $use_flexbox ? '
		<div class="fc_form_flex_box">
			<div class="fc_form_flex_box_item">
		' : '';

		foreach($tab_fields['tab04'] as $fn => $i) :
			$n++;

			echo $fn === 'item_screen' || (!$has_i_screen && $use_flexbox && ($n - 1 == ceil($total_fields / 2))) ? '
				</div><div class="fc_form_flex_box_item">' : '';
			echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
				</div><div class="fc_form_flex_box_item" style="width: 100%;">
			' : '';
			if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;

		echo $use_flexbox ? '
			</div>
		</div>
		' : '';
		?>

	</div> <!-- end tab -->
<?php endif;



// ***
// *** META / SEO TAB
// ***
if ( count($tab_fields['tab05']) ) : ?>
	<?php
	$tab_lbl = isset($tab_titles['tab05']) ? $tab_titles['tab05'] : JText::_( 'FLEXI_META_SEO' );
	$tab_ico = isset($tab_icocss['tab05']) ? $tab_icocss['tab05'] : 'icon-bookmark';

	$total_fields = count($tab_fields['tab05']);
	$use_flexbox  = $total_fields > 1;
	$n = 0;
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>" >
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php
		echo $use_flexbox ? '
		<div class="fc_form_flex_box">
			<div class="fc_form_flex_box_item">
		' : '';

		foreach($tab_fields['tab05'] as $fn => $i) :
			$n++;

			echo $use_flexbox && ($n - 1 == ceil($total_fields / 2)) ? '
				</div><div class="fc_form_flex_box_item">' : '';
			echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
				</div><div class="fc_form_flex_box_item" style="width: 100%;">
			' : '';
			if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;

		echo $use_flexbox ? '
			</div>
		</div>
		' : '';
		?>

	</div> <!-- end tab -->
<?php endif;




// ***
// *** DISPLAYING PARAMETERS TAB
// ***
if ( count($tab_fields['tab06']) ) : ?>
	<?php
	$tab_lbl = isset($tab_titles['tab06']) ? $tab_titles['tab06'] : JText::_( 'FLEXI_DISPLAYING' );
	$tab_ico = isset($tab_icocss['tab06']) ? $tab_icocss['tab06'] : 'icon-eye-open';
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php foreach($tab_fields['tab06'] as $fn => $i) : ?>
			<div class="fcclear"></div>

			<?php if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;
		?>

	</div> <!-- end tab -->
<?php endif;



// ***
// *** TEMPLATE TAB
// ***
if ( count($tab_fields['tab07']) ) : ?>
	<?php
	$tab_lbl = isset($tab_titles['tab07']) ? $tab_titles['tab07'] : JText::_( 'FLEXI_TEMPLATE' );
	$tab_ico = isset($tab_icocss['tab07']) ? $tab_icocss['tab07'] : 'icon-palette';
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<fieldset class="flexi_params fc_edit_container_full">

		<?php foreach($tab_fields['tab07'] as $fn => $i) : ?>
			<div class="fcclear"></div>

			<?php if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;
		?>

		</fieldset>

	</div> <!-- end tab -->
<?php endif;



// ***
// *** Versions TAB
// ***
if ( count($tab_fields['tab08']) ) : ?>
	<?php
	$tab_lbl = isset($tab_titles['tab08']) ? $tab_titles['tab08'] : JText::_( 'FLEXI_VERSIONS' );
	$tab_ico = isset($tab_icocss['tab08']) ? $tab_icocss['tab08'] : 'icon-stack';

	$total_fields = count($tab_fields['tab08']);
	$use_flexbox  = $total_fields > 1;
	$n = 0;
	?>
	<div class="tabbertab" id="fcform_tabset_<?php echo $tabSetCnt; ?>_tab_<?php echo $tabCnt[$tabSetCnt]++; ?>" data-icon-class="<?php echo $tab_ico; ?>">
		<h3 class="tabberheading"> <?php echo $tab_lbl; ?> </h3>

		<?php
		echo $use_flexbox ? '
		<div class="fc_form_flex_box">
			<div class="fc_form_flex_box_item">
		' : '';

		foreach($tab_fields['tab08'] as $fn => $i) :
			$n++;

			echo $use_flexbox && ($n - 1 == ceil($total_fields / 2)) ? '
				</div><div class="fc_form_flex_box_item">' : '';
			echo $use_flexbox && in_array($fn, array('perms', 'fields_manager')) ? '
				</div><div class="fc_form_flex_box_item" style="width: 100%;">
			' : '';
			if (!is_array($captured[$fn])) :
				echo $captured[$fn]; unset($captured[$fn]);
			else:
				foreach($captured[$fn] as $n => $html) : ?>
					<div class="fcclear"></div>
					<?php echo $html;
				endforeach;
				unset($captured[$fn]);
			endif;
		endforeach;

		echo $use_flexbox ? '
			</div>
		</div>
		' : '';
		?>

	</div> <!-- end tab -->
<?php endif; ?>





<?php
// ***
// *** MAIN TABSET END
// ***
?>
</div> <!-- end of tab set -->

<?php $tabSetCnt = array_pop($tabSetStack); ?>


<?php
// ************
// BELOW TABSET
// ************
if ( count($tab_fields['below']) || count($captured) ) : ?>
<div class="fcform_tabs_below">

	<?php foreach($tab_fields['below'] as $fn => $i) : ?>
		<div class="fcclear"></div>

		<?php if (!is_array($captured[$fn])) :
			echo $captured[$fn]; unset($captured[$fn]);
		else:
			foreach($captured[$fn] as $n => $html) : ?>
				<div class="fcclear"></div>
				<?php echo $html;
			endforeach;
			unset($captured[$fn]);
		endif;
	endforeach;
	?>

	<?php /* ALSO print any fields that were not placed above, this list may contain fields zero-length HTML which is OK */ ?>
	<?php foreach($captured as $fn => $i) : ?>
		<div class="fcclear"></div>

		<?php if (!is_array($captured[$fn])) :
			echo $captured[$fn]; unset($captured[$fn]);
		else:
			foreach($captured[$fn] as $n => $html) : ?>
				<div class="fcclear"></div>
				<?php echo $html;
			endforeach;
			unset($captured[$fn]);
		endif;
	endforeach;
	?>

</div>
<?php endif;
