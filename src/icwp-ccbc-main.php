<?php

include_once( dirname( __FILE__ ).'/worpit-plugins-base.php' );
include_once( dirname( __FILE__ ).'/icwp-data-processor.php' );

class ICWP_CustomContentByCountry extends ICWP_Plugins_Base_CBC {

	protected $m_aPluginOptions_EnableSection;

	protected $m_aPluginOptions_AffTagsSection;

	/**
	 * @var ICWP_CCBC_Processor_GeoLocation
	 */
	protected $oProcessorGeoLocation;

	/**
	 * @param ICWP_CustomContentByCountry_Plugin $oPluginVo
	 */
	public function __construct( ICWP_CustomContentByCountry_Plugin $oPluginVo ) {
		parent::__construct( $oPluginVo );

		register_activation_hook( __FILE__, [ $this, 'onWpActivatePlugin' ] );
		register_deactivation_hook( __FILE__, [ $this, 'onWpDeactivatePlugin' ] );

		$this->sPluginUrl = plugins_url( '/', $this->oPluginVo->getRootFile() );
	}

	public function onWpLoaded() {
		if ( $this->getOption( 'enable_content_by_country' ) === 'Y' || $this->getOption( 'enable_amazon_associate' ) === 'Y' ) {
			$this->loadGeoLocationProcessor()->initShortCodes();
		}
	}

	protected function createPluginSubMenuItems() {
		$this->m_aPluginMenu = [
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
			$this->adminNoticeVersionUpgrade();
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

		$this->m_aPluginOptions_EnableSection = [
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
				[
					'enable_amazon_associate',
					'',
					'N',
					'checkbox',
					'Amazon Associates',
					'Enable Amazon Associates Feature',
					"Provides the shortcode to use Amazon Associate links based on visitor's location."
				],
			]
		];

		$this->m_aPluginOptions_AffTagsSection = [
			'section_title'   => 'Amazon Associate Tags by Region',
			'section_options' => [
				[
					'afftag_amazon_region_us',
					'',
					'',
					'text',
					'US Associate Tag',
					'Specify your Amazon.com Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_canada',
					'',
					'',
					'text',
					'Canada Associate Tag',
					'Specify your Amazon.ca Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_uk',
					'',
					'',
					'text',
					'U.K. Associate Tag',
					'Specify your Amazon.co.uk Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_france',
					'',
					'',
					'text',
					'France Associate Tag',
					'Specify your Amazon.fr Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_germany',
					'',
					'',
					'text',
					'Germany Associate Tag',
					'Specify your Amazon.de Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_italy',
					'',
					'',
					'text',
					'Italy Associate Tag',
					'Specify your Amazon.it Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_spain',
					'',
					'',
					'text',
					'Spain Associate Tag',
					'Specify your Amazon.es Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_japan',
					'',
					'',
					'text',
					'Japan Associate Tag',
					'Specify your Amazon.co.jp Associate Tag here:',
					''
				],
				[
					'afftag_amazon_region_china',
					'',
					'',
					'text',
					'China Associate Tag',
					'Specify your Amazon.cn Associate Tag here:',
					''
				],
			]
		];

		$this->m_aAllPluginOptions = [
			&$this->m_aPluginOptions_EnableSection,
			&$this->m_aPluginOptions_AffTagsSection
		];
		return true;
	}

	/** BELOW IS SPECIFIC TO THIS PLUGIN **/
	protected function handlePluginFormSubmit() {
		if ( $this->isWorpitPluginAdminPage() && isset( $_POST[ $this->oPluginVo->getOptionStoragePrefix().'all_options_input' ] ) ) {
			//Don't need to run isset() because previous function does this
			if ( $_GET[ 'page' ] === $this->getSubmenuId( 'main' ) ) {
				$this->handleSubmit_main();
			}
		}
	}

	protected function handleSubmit_main() {
		$this->updatePluginOptionsFromSubmit( $_POST[ $this->oPluginVo->getOptionStoragePrefix().'all_options_input' ] );
	}

	/**
	 * For each display, if you're creating a form, define the form action page and the form_submit_id
	 * that you can then use as a guard to handling the form submit.
	 */
	public function onDisplayCbcMain() {

		//populates plugin options with existing configuration
		$this->readyAllPluginOptions();

		//Specify what set of options are available for this page
		$aAvailableOptions = [ &$this->m_aPluginOptions_EnableSection, &$this->m_aPluginOptions_AffTagsSection ];

		$sAllInputOptions = $this->collateAllFormInputsForOptionsSection( $this->m_aPluginOptions_EnableSection );
		$sAllInputOptions .= ','.$this->collateAllFormInputsForOptionsSection( $this->m_aPluginOptions_AffTagsSection );

		$this->display( 'worpit_cbc_main', [
			'plugin_url'        => $this->sPluginUrl,
			'var_prefix'        => $this->oPluginVo->getOptionStoragePrefix(),
			'aAllOptions'       => $aAvailableOptions,
			'all_options_input' => $sAllInputOptions,
			'form_action'       => 'admin.php?page='.$this->getFullParentMenuId().'-main'
		] );
	}

	private function adminNoticeOptionsUpdated() {

		//Admin notice for Main Options page submit.
		if ( isset( $_GET[ 'ccbc_options_updated' ] ) ) {

			if ( $this->m_fUpdateSuccessTracker ) {
				$sNotice = '<p>Updating CBC Plugin Options was a <strong>Success</strong>.</p>';
				$sClass = 'updated';
			}
			else {
				$sNotice = '<p>Updating CBC Plugin Options <strong>Failed</strong>.</p>';
				$sClass = 'error';
			}
			$this->getAdminNotice( $sNotice, $sClass, true );
		}
	}

	private function adminNoticeVersionUpgrade() {

		global $current_user;
		$user_id = $current_user->ID;

		$sCurrentVersion = get_user_meta( $user_id, $this->oPluginVo->getOptionStoragePrefix().'current_version', true );

		if ( $sCurrentVersion !== $this->oPluginVo->getVersion() ) {
			$sNotice = '
					<form method="post" action="admin.php?page='.$this->getFullParentMenuId().'">
						<p><strong>Custom Content By Country</strong> plugin has been updated. Worth checking out the latest docs.
						<input type="hidden" value="1" name="'.$this->oPluginVo->getOptionStoragePrefix().'hide_update_notice" id="'.$this->oPluginVo->getOptionStoragePrefix().'hide_update_notice">
						<input type="hidden" value="'.$user_id.'" name="worpit_user_id" id="worpit_user_id">
						<input type="submit" value="Okay, show me and hide this notice" name="submit" class="button-primary">
						</p>
					</form>
			';

			$this->getAdminNotice( $sNotice, 'updated', true );
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