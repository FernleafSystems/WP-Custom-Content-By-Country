<?php
include_once( dirname( __FILE__ ).'/worpit_options_helper.php' );
include_once( dirname( __FILE__ ).'/widgets/worpit_widgets.php' );
?>
<div class="wrap">
	<div class="bootstrap-wpadmin">

		<?php printOptionsPageHeader( 'Main Options' ); ?>

		<div class="row">
			<div class="span9">
				<form method="post" action="<?php echo $worpit_form_action; ?>" class="form-horizontal">
					<?php
		  printAllPluginOptionsForm( $worpit_aAllOptions, $worpit_var_prefix, 1 );
		  ?>
					<div class="form-actions">
						<input type="hidden" name="<?php echo $worpit_var_prefix.'all_options_input'; ?>"
						       value="<?php echo $worpit_all_options_input; ?>" />
						<?php echo $worpit_form_nonce ?>
						<button type="submit" class="btn btn-primary" name="submit">Save All Settings</button>
					</div>
				</form>
			</div><!-- / span9 -->
			<div class="span3" id="side_widgets">
	  			<?php include( __DIR__.'/widgets/side-widget.php' ); ?>
			</div>
		</div>
	</div><!-- / bootstrap-wpadmin -->
	<?php include_once( dirname( __FILE__ ).'/worpit_options_js.php' ); ?>
</div><!-- / wrap -->
