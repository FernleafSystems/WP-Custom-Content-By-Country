<?php
include_once( dirname( __FILE__ ).'/worpit_options_helper.php' );
?>

<div class="wrap">
	<div class="bootstrap-wpadmin">

		<?php printOptionsPageHeader( 'Dashboard' ); ?>

		<div class="row">
			<div class="col-12">
				<div class="alert alert-error">
					<h4 class="alert-heading">Important Notice</h4>
					You need to go to the <a href="admin.php?page=<?php echo $this->getSubmenuId( 'main' ) ?>">
						main plugin Settings page</a> to enable the plugin features.</div>
			</div>
		</div>

		<div class="row" id="tbs_docs">
		  <div class="col-6" id="tbs_docs_shortcodes">
			  <div class="well">
				<h3>Custom Content By Country Shortcodes</h3>
				<p>The following shortcodes are available:</p>
				<ol>
					<li>[ CBC ]</li>
					<li>[ CBC_COUNTRY ]</li>
					<li>[ CBC_IP ]</li>
					<li>[ CBC_CODE ]</li>
				</ol>
			  </div>
		  </div>

		  <div class="col-6" id="tbs_docs_examples">
			  <div class="well">
					<h3>Shortcode Usage Examples</h3>
					<div class="shortcode-usage">
						<p>The following are just some examples of how you can use the shortcodes with the associated HTML output</p>
						<ul>
							<li><span class="code">[CBC show="y" country="es, us"]I only appear in Spain and the U.S.[/CBC]</span>
							<p>will give the following HTML:</p>
							<p class="code">&lt;SPAN class="cbc_content"&gt;I only appear in Spain and the U.S.&lt;/SPAN&gt;</p>
							<p class="code-description">This HTML will only appear when the visitor is found to be in Spain or North America given the country codes used, 'es' and 'us'.</p>
							</li>
						</ul>
					</div>
			  </div>
		  </div>

		</div>

		<div class="row mt-5" id="worpit_promo">
		  <div class="col-12">
        <?php include( __DIR__.'/widgets/horiz-widget.php' ); ?>
		  </div>
		</div>

	</div><!-- / bootstrap-wpadmin -->

</div><!-- / wrap -->