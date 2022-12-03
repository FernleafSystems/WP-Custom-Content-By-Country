<?php

include_once( dirname( __FILE__ ).'/icwp-wpfunctions.php' );

class ICWP_Plugins_Base_CBC {

	const ParentTitle = 'Worpit';
	const ParentName = 'Custom Content';
	const ParentMenuId = 'worpit';
	const VariablePrefix = 'worpit';

	/**
	 * @var string
	 */
	protected $sPluginBaseFile;

	/**
	 * @var string
	 */
	protected $sPluginUrl;

	/**
	 * @var ICWP_CustomContentByCountry_Plugin
	 */
	protected $oPluginVo;

	const ViewDir = 'views';

	protected $m_aPluginMenu;

	protected $m_aAllPluginOptions;

	protected $m_fUpdateSuccessTracker;

	protected $m_aFailedUpdateOptions;

	public function __construct( ICWP_CustomContentByCountry_Plugin $oPluginVo ) {

		$this->oPluginVo = $oPluginVo;

		add_action( 'plugins_loaded', [ $this, 'onWpPluginsLoaded' ] );
		add_action( 'init', [ $this, 'onWpInit' ], 1 );
		add_action( 'init', [ $this, 'onWpLoaded' ], 1 );
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'onWpAdminInit' ] );
			add_action( 'admin_notices', [ $this, 'onWpAdminNotices' ] );
			add_action( 'admin_menu', [ $this, 'onWpAdminMenu' ] );
			add_action( 'plugin_action_links', [ $this, 'onWpPluginActionLinks' ], 10, 4 );
		}
		add_filter( 'auto_update_plugin', [ $this, 'onWpAutoUpdatePlugin' ], 1000, 2 );
		/**
		 * We make the assumption that all settings updates are successful until told otherwise
		 * by an actual failing update_option call.
		 */
		$this->m_fUpdateSuccessTracker = true;
		$this->m_aFailedUpdateOptions = [];
	}

	/**
	 * This is the path to the main plugin file relative to the WordPress plugins directory.
	 *
	 * @return string
	 */
	public function getPluginBaseFile() {
		if ( !isset( $this->sPluginBaseFile ) ) {
			$this->sPluginBaseFile = plugin_basename( $this->oPluginVo->getRootFile() );
		}
		return $this->sPluginBaseFile;
	}

	/**
	 * Returns this unique plugin prefix
	 *
	 * @param string $sGlue
	 * @return string
	 */
	public function getPluginPrefix( $sGlue = '-' ) {
		return $this->oPluginVo->getFullPluginPrefix( $sGlue );
	}

	/**
	 * @param boolean $fUpdate
	 * @param         $oPluginInfo
	 * @return bool
	 */
	public function onWpAutoUpdatePlugin( $fUpdate, $oPluginInfo ) {

		// Only supports WordPress 3.8.2+
		if ( !is_object( $oPluginInfo ) || !isset( $oPluginInfo->new_version ) || !isset( $oPluginInfo->plugin ) ) {
			return $fUpdate;
		}

		if ( $oPluginInfo->plugin === $this->getPluginBaseFile() ) {
			$aCurrentParts = explode( '-', $this->oPluginVo->getVersion(), 2 );
			$aUpdateParts = explode( '-', $oPluginInfo->new_version, 2 );
			// We only return true (i.e. update if and when the update is a minor version
			return ( $aUpdateParts[ 0 ] === $aCurrentParts[ 0 ] );
		}
		return $fUpdate;
	}

	protected function getFullParentMenuId() {
		return self::ParentMenuId.'-'.$this->oPluginVo->getPluginSlug();
	}

	protected function display( $insView, $inaData = [] ) {
		$sFile = $this->oPluginVo->getViewDir().$insView.'.php';

		if ( !is_file( $sFile ) ) {
			echo "View not found: ".$sFile;
			return false;
		}

		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, self::VariablePrefix );
		}

		ob_start();
		include( $sFile );
		$sContents = ob_get_contents();
		ob_end_clean();

		echo $sContents;
		return true;
	}

	protected function getImageUrl( $img ) {
		return $this->sPluginUrl.'resources/images/'.$img;
	}

	protected function getCssUrl( $css ) {
		return $this->sPluginUrl.'resources/css/'.$css;
	}

	protected function getJsUrl( $js ) {
		return $this->sPluginUrl.'resources/js/'.$js;
	}

	protected function getSubmenuPageTitle( $title ) {
		return self::ParentTitle.' - '.$title;
	}

	protected function getSubmenuId( $ID ) {
		return $this->getFullParentMenuId().'-'.$ID;
	}

	public function onWpPluginsLoaded() {
		if ( is_admin() ) {
			$this->handlePluginUpgrade();
		}
	}

	public function onWpInit() {
		$this->handlePluginFormSubmit();
	}

	public function onWpLoaded() {
	}

	public function onWpAdminInit() {

		//Do Plugin-Specific Work
		if ( $this->isWorpitPluginAdminPage() ) {

			//Links up CSS styles for the plugin itself (set the admin bootstrap CSS as a dependency also)
			$this->enqueueBootstrapAdminCss();
			$this->enqueuePluginAdminCss();
		}
	}

	public function onWpAdminMenu() {

		$sFullParentMenuId = $this->getFullParentMenuId();

		add_menu_page( self::ParentTitle, self::ParentName, $this->oPluginVo->getBasePermissions(), $sFullParentMenuId, [
			$this,
			'onDisplayMainMenu'
		], $this->getImageUrl( 'worpit_16x16.png' ) );

		//Create and Add the submenu items
		$this->createPluginSubMenuItems();
		if ( !empty( $this->m_aPluginMenu ) ) {
			foreach ( $this->m_aPluginMenu as $sMenuTitle => $aMenu ) {
				list( $sMenuItemText, $sMenuItemId, $sMenuCallBack ) = $aMenu;
				add_submenu_page( $sFullParentMenuId, $sMenuTitle, $sMenuItemText, $this->oPluginVo->getBasePermissions(), $sMenuItemId, [
					$this,
					$sMenuCallBack
				] );
			}
		}

		$this->fixSubmenu();
	}//onWpAdminMenu

	protected function createPluginSubMenuItems() {
		/* Override to create array of sub-menu items
		 $this->m_aPluginMenu = array(
		 		//Menu Page Title => Menu Item name, page ID (slug), callback function onLoad.
		 		$this->getSubmenuPageTitle( 'Content by Country' ) => array( 'Content by Country', $this->getSubmenuId('main'), 'onDisplayCbcMain' ),
		 );
		*/
	}//createPluginSubMenuItems

	protected function fixSubmenu() {
		global $submenu;
		$sFullParentMenuId = $this->getFullParentMenuId();
		if ( isset( $submenu[ $sFullParentMenuId ] ) ) {
			$submenu[ $sFullParentMenuId ][ 0 ][ 0 ] = 'Dashboard';
		}
	}

	/**
	 * The callback function for the main admin menu index page
	 */
	public function onDisplayMainMenu() {
		$this->display( 'worpit_'.$this->oPluginVo->getPluginSlug().'_index', [
			'plugin_url'    => $this->sPluginUrl,
			'icwp_logo_url' => $this->sPluginUrl.'resources/images/icwp_logo-250.png',
		] );
	}

	/**
	 * The Action Links in the main plugins page. Defaults to link to the main Dashboard page
	 *
	 * @param $aActionLinks
	 * @param $sPluginFile
	 */
	public function onWpPluginActionLinks( $aActionLinks, $sPluginFile ) {
		if ( $sPluginFile == $this->getPluginBaseFile() ) {
			$sSettingsLink = sprintf( '<a href="%s">%s</a>', admin_url( "admin.php" ).'?page='.$this->getFullParentMenuId(), 'Settings' );;
			array_unshift( $aActionLinks, $sSettingsLink );
		}
		return $aActionLinks;
	}

	/**
	 * Override this method to handle all the admin notices
	 */
	public function onWpAdminNotices() {
	}

	/**
	 * This is called from within onWpAdminInit. Use this solely to manage upgrades of the plugin
	 */
	protected function handlePluginUpgrade() {
	}

	protected function handlePluginFormSubmit() {
	}

	protected function enqueueBootstrapAdminCss() {
		wp_register_style( 'worpit_bootstrap_wpadmin_css', $this->getCssUrl( 'bootstrap-wpadmin.css' ), false, $this->oPluginVo->getVersion() );
		wp_enqueue_style( 'worpit_bootstrap_wpadmin_css' );
		wp_register_style( 'worpit_bootstrap_wpadmin_css_fixes', $this->getCssUrl( 'bootstrap-wpadmin-fixes.css' ), 'worpit_bootstrap_wpadmin_css', $this->oPluginVo->getVersion() );
		wp_enqueue_style( 'worpit_bootstrap_wpadmin_css_fixes' );
	}

	protected function enqueuePluginAdminCss() {
		$iRand = rand();
		wp_register_style( 'icwp_plugin_css'.$iRand, $this->getCssUrl( 'plugin.css' ), false, $this->oPluginVo->getVersion() );
		wp_enqueue_style( 'icwp_plugin_css'.$iRand );
	}

	/**
	 * Provides the basic HTML template for printing a WordPress Admin Notices
	 *
	 * @param $insNotice       - The message to be displayed.
	 * @param $insMessageClass - either error or updated
	 * @param $infPrint        - if true, will echo. false will return the string
	 * @return boolean|string
	 */
	protected function getAdminNotice( $insNotice = '', $insMessageClass = 'updated', $infPrint = false ) {

		$sFullNotice = '
			<div id="message" class="'.$insMessageClass.'">
				<style>
					#message form { margin: 0px; }
				</style>
				'.$insNotice.'
			</div>
		';

		if ( $infPrint ) {
			echo $sFullNotice;
			return true;
		}
		else {
			return $sFullNotice;
		}
	}

	protected function redirect( $insUrl, $innTimeout = 1 ) {
		echo '
			<script type="text/javascript">
				function redirect() {
					window.location = "'.$insUrl.'";
				}
				var oTimer = setTimeout( "redirect()", "'.( $innTimeout*1000 ).'" );
			</script>';
	}

	/**
	 * A little helper function that populates all the plugin options arrays with DB values
	 */
	protected function readyAllPluginOptions() {
		$this->initPluginOptions();
		$this->populateAllPluginOptions();
	}

	/**
	 * Override to create the plugin options array.
	 *
	 * Returns false if nothing happens - i.e. not over-rided.
	 */
	protected function initPluginOptions() {
		return false;
	}

	/**
	 * Reads the current value for ALL plugin option from the WP options db.
	 *
	 * Assumes the standard plugin options array structure. Over-ride to change.
	 *
	 * NOT automatically executed on any hooks.
	 */
	protected function populateAllPluginOptions() {

		if ( empty( $this->m_aAllPluginOptions ) && !$this->initPluginOptions() ) {
			return;
		}

		foreach ( $this->m_aAllPluginOptions as &$aOptionsSection ) {
			$this->populatePluginOptionsSection( $aOptionsSection );
		}
	}//populateAllPluginOptions

	/**
	 * Reads the current value for each plugin option in an options section from the WP options db.
	 * Called from within on admin_init
	 * NOT automatically executed on any hooks.
	 *
	 * @param array $inaOptionsSection
	 */
	protected function populatePluginOptionsSection( &$inaOptionsSection ) {

		if ( empty( $inaOptionsSection ) ) {
			return;
		}

		foreach ( $inaOptionsSection[ 'section_options' ] as &$aOptionParams ) {
			list( $sOptionKey, $sOptionCurrent, $sOptionDefault ) = $aOptionParams;
			$sCurrentOptionVal = $this->getOption( $sOptionKey );
			$aOptionParams[ 1 ] = ( $sCurrentOptionVal == '' ) ? $sOptionDefault : $sCurrentOptionVal;
		}
	}

	/**
	 * @param $allOptionsInput - comma separated list of all the input keys to be processed from the $_POST
	 * @return bool
	 */
	protected function updatePluginOptionsFromSubmit( $allOptionsInput ) {

		if ( empty( $allOptionsInput ) ) {
			return true;
		}

		foreach ( explode( ',', $allOptionsInput ) as $inputKey ) {
			$input = explode( ':', $inputKey );
			list( $optionType, $optionKey ) = $input;

			$optionValue = $this->getAnswerFromPost( $optionKey );
			if ( is_null( $optionValue ) ) {

				if ( $optionType == 'text' ) { //if it was a text box, and it's null, don't update anything
					continue;
				}
				elseif ( $optionType == 'checkbox' ) { //if it was a checkbox, and it's null, it means 'N'
					$optionValue = 'N';
				}
			}
			$this->updateOption( $optionKey, $optionValue );
		}
		return true;
	}

	protected function collateAllFormInputsForAllOptions( $aAllOptions, $sInputSeparator = ',' ) {

		if ( empty( $aAllOptions ) ) {
			return '';
		}
		$iCount = 0;
		$sCollated = '';
		foreach ( $aAllOptions as $aOptionsSection ) {

			if ( $iCount == 0 ) {
				$sCollated = $this->collateAllFormInputsForOptionsSection( $aOptionsSection, $sInputSeparator );
			}
			else {
				$sCollated .= $sInputSeparator.$this->collateAllFormInputsForOptionsSection( $aOptionsSection, $sInputSeparator );
			}
			$iCount++;
		}
		return $sCollated;
	}

	/**
	 * Returns a comma seperated list of all the options in a given options section.
	 * @param        $aOptionsSection
	 * @param string $sInputSeparator
	 * @return string
	 */
	protected function collateAllFormInputsForOptionsSection( $aOptionsSection, $sInputSeparator = ',' ) {

		if ( empty( $aOptionsSection ) ) {
			return '';
		}
		$iCount = 0;
		$sCollated = '';
		foreach ( $aOptionsSection[ 'section_options' ] as $aOption ) {

			list( $sKey, $fill1, $fill2, $sType ) = $aOption;

			if ( $iCount == 0 ) {
				$sCollated = $sType.':'.$sKey;
			}
			else {
				$sCollated .= $sInputSeparator.$sType.':'.$sKey;
			}
			$iCount++;
		}
		return $sCollated;
	}//collateAllFormInputsForOptionsSection

	protected function isWorpitPluginAdminPage() {

		$sSubPageNow = isset( $_GET[ 'page' ] ) ? $_GET[ 'page' ] : '';
		if ( is_admin() && !empty( $sSubPageNow ) && ( strpos( $sSubPageNow, $this->getFullParentMenuId() ) === 0 ) ) { //admin area, and the 'page' begins with 'worpit'
			return true;
		}

		return false;
	}

	protected function deleteAllPluginDbOptions() {

		if ( current_user_can( 'manage_options' ) ) {

			if ( empty( $this->m_aAllPluginOptions ) && !$this->initPluginOptions() ) {
				return;
			}

			foreach ( $this->m_aAllPluginOptions as &$aOptionsSection ) {
				foreach ( $aOptionsSection[ 'section_options' ] as &$aOptionParams ) {
					if ( isset( $aOptionParams[ 0 ] ) ) {
						$this->deleteOption( $aOptionParams[ 0 ] );
					}
				}
			}
		}
	}

	protected function getAnswerFromPost( $insKey, $insPrefix = null ) {
		if ( is_null( $insPrefix ) ) {
			$insKey = $this->oPluginVo->getOptionStoragePrefix().$insKey;
		}
		return ( isset( $_POST[ $insKey ] ) ? $_POST[ $insKey ] : 'N' );
	}

	/**
	 * @param       $sOptionName
	 * @param mixed $mDefault
	 * @return mixed
	 */
	public function getOption( $sOptionName, $mDefault = false ) {
		return ICWP_WpFunctions_CBC::GetWpOption( $this->getOptionKey( $sOptionName ), $mDefault );
	}

	/**
	 * @param $sOptionName
	 * @param $sValue
	 * @return mixed
	 */
	public function addOption( $sOptionName, $sValue ) {
		return ICWP_WpFunctions_CBC::AddWpOption( $this->getOptionKey( $sOptionName ), $sValue );
	}

	/**
	 * @param $sOptionName
	 * @param $sValue
	 * @return bool
	 */
	public function updateOption( $sOptionName, $sValue ) {
		if ( $this->getOption( $sOptionName ) == $sValue ) {
			return true;
		}
		$fResult = ICWP_WpFunctions_CBC::UpdateWpOption( $this->getOptionKey( $sOptionName ), $sValue );
		if ( !$fResult ) {
			$this->m_fUpdateSuccessTracker = false;
			$this->m_aFailedUpdateOptions[] = $this->getOptionKey( $sOptionName );
		}
		return true;
	}

	/**
	 * @param $insKey
	 * @return mixed
	 */
	public function deleteOption( $insKey ) {
		return ICWP_WpFunctions_CBC::DeleteWpOption( $this->getOptionKey( $insKey ) );
	}

	/**
	 * @param string $sOptionName
	 * @return string
	 */
	public function getOptionKey( $sOptionName ) {
		return $this->oPluginVo->getOptionStoragePrefix().$sOptionName;
	}

	public function onWpActivatePlugin() {
	}

	public function onWpDeactivatePlugin() {
	}

	public function onWpUninstallPlugin() {

		//Do we have admin priviledges?
		if ( current_user_can( 'manage_options' ) ) {
			$this->deleteAllPluginDbOptions();
		}
	}

	/**
	 * Takes an array, an array key, and a default value. If key isn't set, sets it to default.
	 */
	protected function def( &$aSrc, $insKey, $insValue = '' ) {
		if ( !isset( $aSrc[ $insKey ] ) ) {
			$aSrc[ $insKey ] = $insValue;
		}
	}

	/**
	 * Takes an array, an array key and an element type. If value is empty, sets the html element
	 * string to empty string, otherwise forms a complete html element parameter.
	 *
	 * E.g. noEmptyElement( aSomeArray, sSomeArrayKey, "style" )
	 * will return String: style="aSomeArray[sSomeArrayKey]" or empty string.
	 */
	protected function noEmptyElement( &$inaArgs, $insAttrKey, $insElement = '' ) {
		$sAttrValue = $inaArgs[ $insAttrKey ];
		$insElement = ( $insElement == '' ) ? $insAttrKey : $insElement;
		$inaArgs[ $insAttrKey ] = ( empty( $sAttrValue ) ) ? '' : ' '.$insElement.'="'.$sAttrValue.'"';
	}
}//Worpit_Plugins_Base Class
