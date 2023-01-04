<div id="ccbc-output-body"></div>

<script id="ccbc-sidebar" type="text/x-handlebars-template">
	<?php include_once( __DIR__.'/hb/partials/sidebar.handlebars' ); ?>
</script>
<script id="ccbc-horizbar" type="text/x-handlebars-template">
	<?php include_once( __DIR__.'/hb/partials/horizbar.handlebars' ); ?>
</script>
<script id="ccbc-page_header" type="text/x-handlebars-template">
	<?php include_once( __DIR__.'/hb/partials/page_header.handlebars' ); ?>
</script>

<script id="ccbc-<?php echo esc_html( $ccbc_slug ); ?>" type="text/x-handlebars-template">
	<?php include_once( __DIR__.sprintf( '/hb/%s.handlebars', $ccbc_slug ) ); ?>
</script>

<script type="text/javascript">
	Handlebars.registerHelper( 'ifEquals', function ( arg1, arg2, options ) {
	  return (arg1 === arg2) ? options.fn( this ) : options.inverse( this );
  } );
  Handlebars.registerHelper( 'strConcat', function ( arg1, arg2, options ) {
	  return arg1 + arg2;
  } );

  Handlebars.registerPartial( 'ccbc-sidebar', Handlebars.compile( jQuery( '#ccbc-sidebar' ).html() ) );
  Handlebars.registerPartial( 'ccbc-horizbar', Handlebars.compile( jQuery( '#ccbc-horizbar' ).html() ) );
  Handlebars.registerPartial( 'ccbc-page_header', Handlebars.compile( jQuery( '#ccbc-page_header' ).html() ) );

  // Compile the template data into a function
  let templateScript = Handlebars.compile( jQuery( '#ccbc-<?php echo esc_html( $ccbc_slug ); ?>' ).html() );
  jQuery( '#ccbc-output-body' ).html(
	  templateScript( <?php echo $ccbc_context ?> )
  );
</script>