<?php

include_once( dirname( __FILE__ ).'/worpit-plugins-base.php' );
include_once( dirname( __FILE__ ).'/icwp-data-processor.php' );

class ICWP_CustomContentByCountry extends ICWP_Plugins_Base_CBC {

	private $pluginOptions_CCBC;

	/**
	 * @var ICWP_CCBC_Processor_GeoLocation
	 */
	protected $oProcessorGeoLocation;

	/**
	 * @param ICWP_CustomContentByCountry_Plugin $pluginVO
	 */
	public function __construct( ICWP_CustomContentByCountry_Plugin $pluginVO ) {
		parent::__construct( $pluginVO );

		register_activation_hook( __FILE__, [ $this, 'onWpActivatePlugin' ] );
		register_deactivation_hook( __FILE__, [ $this, 'onWpDeactivatePlugin' ] );

		$this->sPluginUrl = plugins_url( '/', $this->oPluginVo->getRootFile() );
	}

	public function onWpLoaded() {
		if ( $this->getOption( 'enable_content_by_country' ) === 'Y' ) {
			$this->loadGeoLocationProcessor()->initShortCodes();
		}
	}

	protected function createPluginSubMenuItems() {
		$this->menu = [
			//Menu Page Title => Menu Item name, page ID (slug), callback function for this page - i.e. what to do/load.
			$this->getSubmenuPageTitle( 'Content by Country' ) => [
				'Content by Country',
				$this->getSubmenuId( 'main' ),
				'onDisplayCbcMain'
			],
		];
	}

	public function onWpAdminNotices() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->adminNoticeOptionsUpdated();
		}
	}

	public function onWpDeactivatePlugin() {
		if ( !$this->initPluginOptions() ) {
			return;
		}
		$this->deleteAllPluginDbOptions();
	}

	/**
	 * Override for specify the plugin's options
	 */
	protected function initPluginOptions() {
		$this->pluginOptions_CCBC = [
			'section_title'   => 'Enable Content By Country Plugin Options',
			'section_options' => [
				[
					'enable_content_by_country',
					'',
					'N',
					'checkbox',
					'Content By Country',
					'Enable Content by Country Feature',
					"Provides the shortcodes for showing/hiding content based on visitor's location."
				],
				[
					'enable_html_off_mode',
					'',
					'N',
					'checkbox',
					'HTML Off',
					'HTML Off mode turns off HTML printing by default',
					"When enabled, the HTML that is normally output is disabled.  Normally the output is surrounded by html SPAN tags, but these are then removed."
				],
				[
					'enable_w3tc_compatibility_mode',
					'',
					'N',
					'checkbox',
					'W3TC Compatibility Mode',
					'Turns off page caching for shortcodes',
					"When enabled, 'Custom Content by Country' plugin will turn off page caching for pages that use these shortcodes."
				],
			]
		];

		$this->allPluginOptions = [
			&$this->pluginOptions_CCBC,
		];
		return true;
	}

	protected function handlePluginFormSubmit() {
		if ( $this->isWorpitPluginAdminPage()
			 && isset( $_POST[ $this->oPluginVo->getOptionStoragePrefix( 'all_options_input' ) ] ) ) {
			if ( CCBC_DP::FetchGet( 'page', null, 'sanitize_key' ) === $this->getSubmenuId( 'main' ) ) {
				$this->handleSubmit_main();
				$this->allPluginOptions = null;
			}
		}
	}

	protected function handleSubmit_main() {
		if ( !current_user_can( $this->oPluginVo->getBasePermissions() ) ) {
			wp_die( 'Invalid user permissions' );
		}
		check_admin_referer( $this->oPluginVo->getOptionStoragePrefix( 'main_submit' ) );

		$this->updatePluginOptionsFromSubmit(
			CCBC_DP::FetchPost( $this->oPluginVo->getOptionStoragePrefix( 'all_options_input' ), '', 'sanitize_text_field' )
		);
	}

	public function onDisplayCbcMain() {
		$this->display( 'worpit_cbc_main', array_merge( $this->getCommonDisplayVars(), [
			'plugin_url'        => $this->sPluginUrl,
			'var_prefix'        => $this->oPluginVo->getOptionStoragePrefix(),
			'allOptions'        => $this->getAllPluginOptions(),
			'all_options_input' => implode( ',', array_map(
				function ( $optionSection ) {
					return $this->collateAllFormInputsForOptionsSection( $optionSection, ',' );
				}, $this->getAllPluginOptions() ) ),
			'form_action'       => 'admin.php?page='.$this->getFullParentMenuId().'-main',
			'form_nonce'        => wp_nonce_field( $this->oPluginVo->getOptionStoragePrefix( 'main_submit' ), '_wpnonce', true, false ),
		] ) );
	}

	private function adminNoticeOptionsUpdated() {
		if ( CCBC_DP::FetchGet( 'ccbc_options_updated', '', 'sanitize_key' ) ) {
			$this->getAdminNotice(
				sprintf( 'Updating CBC Plugin Options: %s', $this->updateSuccess ? 'Successful' : 'Failure' ),
				$this->updateSuccess ? 'updated' : 'error',
				true
			);
		}
	}

	/**
	 * @return ICWP_CCBC_Processor_GeoLocation
	 */
	protected function loadGeoLocationProcessor() {
		if ( !isset( $this->oProcessorGeoLocation ) ) {
			require_once( dirname( __FILE__ ).'/icwp-ccbc-processor.php' );
			$this->oProcessorGeoLocation = new ICWP_CCBC_Processor_GeoLocation();
			$this->oProcessorGeoLocation
				->setModeHtmlOff( $this->getOption( 'enable_html_off_mode' ) == 'Y' )
				->setModeW3tcCompatibility( $this->getOption( 'enable_w3tc_compatibility_mode' ) == 'Y' )
				->setWpOptionPrefix( $this->oPluginVo->getOptionStoragePrefix() );
		}
		return $this->oProcessorGeoLocation;
	}
}