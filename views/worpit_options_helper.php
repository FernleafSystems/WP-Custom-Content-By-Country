<?php

function printOptionsPageHeader( $title = '' ) {
	echo '<div class="page-header">';
	echo '<h2><a id="pluginlogo_32" class="header-icon32" href="https://icwp.io/2k" target="_blank"></a>';

	$baseTitle = sprintf( 'Custom Content By Country (from %s)', '<a href="https://icwp.io/3a" target="_blank">iControlWP</a>' );
	echo empty( $title ) ? $baseTitle : sprintf( '%s :: %s', esc_js( $title ), $baseTitle );

	echo '</h2></div>';
}

function getIsHexColour( $colour ) {
	return preg_match( '/^#[a-fA-F0-9]{3,6}$/', $colour );
}

function printAllPluginOptionsForm( $allOptions, $prefix = '', $optsPerRow = 1 ) {

	if ( empty( $allOptions ) ) {
		return;
	}

	$optionWidth = 8/$optsPerRow; //8 spans.

	//Take each Options Section in turn
	foreach ( $allOptions as $optSection ) {

		$rowID = str_replace( ' ', '', $optSection[ 'section_title' ] );
		//Print the Section Title
		echo '
				<div class="row" id="'.$rowID.'">
					<div class="col-9" style="margin-left:0">
						<fieldset>
							<legend>'.$optSection[ 'section_title' ].'</legend>
		';

		$rowCount = 1;
		$optCount = 0;
		//Print each option in the option section
		foreach ( $optSection[ 'section_options' ] as $option ) {

			$optCount = $optCount%$optsPerRow;

			if ( $optCount == 0 ) {
				echo '
				<div class="row row_number_'.$rowCount.'">';
			}

			echo getPluginOptionSpan( $option, $optionWidth, $prefix );

			$optCount++;

			if ( $optCount == $optsPerRow ) {
				echo '
				</div> <!-- / options row -->';
				$rowCount++;
			}
		}

		echo '
					</fieldset>
				</div>
			</div>
		';
	}
}

function getPluginOptionSpan( $option, $spanSize, $varPrefix = '' ) {

	list( $optKey, $optSaved, $optDef, $optType, $optName, $optTitle, $optHelp ) = $option;

	if ( $optKey == 'spacer' ) {
		$html = '<div class="col-'.$spanSize.'"></div>';
	}
	else {
		$spanId = 'span_'.$varPrefix.$optKey;
		$html = '
			<div class="col-'.$spanSize.'" id="'.$spanId.'">
				<div class="control-group">
					<label class="control-label" for="'.$varPrefix.$optKey.'">'.$optName.'<br /></label>
					<div class="controls">
					  <div class="option_section'.( ( $optSaved == 'Y' ) ? ' selected_item' : '' ).'" id="option_section_'.$varPrefix.$optKey.'">
						<label>
		';
		$sAdditionalClass = '';
		$sTextInput = '';
		$checked = '';
		$sHelpSection = '';

		if ( $optType === 'checkbox' ) {

			$checked = ( $optSaved == 'Y' ) ? 'checked="checked"' : '';

			$html .= '
				<input '.$checked.'
						type="checkbox"
						name="'.$varPrefix.$optKey.'"
						value="Y"
						class="'.$sAdditionalClass.'"
						id="'.$varPrefix.$optKey.'" />
						'.$optTitle;

			$optHelp = '<p class="help-block">'.$optHelp.'</p>';
		}
		elseif ( $optType === 'text' ) {
			$sTextInput = esc_attr( $optSaved );
			$html .= '
				<p>'.$optTitle.'</p>
				<input type="text"
						name="'.$varPrefix.$optKey.'"
						value="'.$sTextInput.'"
						placeholder="'.$sTextInput.'"
						id="'.$varPrefix.$optKey.'" />';

			$optHelp = '<p class="help-block">'.$optHelp.'</p>';
		}
		elseif ( is_array( $optType ) ) { //it's a select, or radio

			$sInputType = array_shift( $optType );

			if ( $sInputType == 'select' ) {
				$html .= '<p>'.$optTitle.'</p>
				<select id="'.$varPrefix.$optKey.'" name="'.$varPrefix.$optKey.'">';
			}

			foreach ( $optType as $aInput ) {

				$html .= '
					<option value="'.$aInput[ 0 ].'" id="'.$varPrefix.$optKey.'_'.$aInput[ 0 ].'"'.( ( $optSaved == $aInput[ 0 ] ) ? ' selected="selected"' : '' ).'>'.$aInput[ 1 ].'</option>';
			}

			if ( $sInputType == 'select' ) {
				$html .= '
				</select>';
			}

			$optHelp = '<p class="help-block">'.$optHelp.'</p>';
		}
		elseif ( strpos( $optType, 'less_' ) === 0 ) {    //dealing with the LESS compiler options class_exists(HLT_BootstrapLess) is implied

			if ( empty( $optSaved ) ) {
				$optSaved = $optDef;
			}

			$html .= '<input class="col-2'.$sAdditionalClass.'"
						type="text"
						placeholder="'.esc_attr( $optSaved ).'"
						name="'.$varPrefix.$optKey.'"
						value="'.esc_attr( $optSaved ).'"
						id="'.$varPrefix.$optKey.'" />';

			$sToggleTextInput = '';

			$sHelpSection = '
					<div class="help_section">
						<span class="label label-less-name">@'.str_replace( HLT_BootstrapLess::$LESS_PREFIX, '', $optKey ).'</span>
						'.$sToggleTextInput.'
						<span class="label label-less-name">'.$optDef.'</span>
					</div>';
		}
		else {
			echo 'we should never reach this point';
		}

		$html .= '
						</label>
						'.$optHelp.'
					  </div>
					</div><!-- controls -->'
				 .$sHelpSection.'
				</div><!-- control-group -->
			</div>
		';
	}

	return $html;
}//getPluginOptionSpan
