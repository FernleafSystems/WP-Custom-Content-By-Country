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

	protected $menu;

	protected $allPluginOptions = null;

	protected $updateSuccess;

	protected $failedUpdateOptions;

	public function __construct( ICWP_CustomContentByCountry_Plugin $pluginVO ) {

		$this->oPluginVo = $pluginVO;

		add_action( 'plugins_loaded', [ $this, 'onWpPluginsLoaded' ] );
		add_action( 'init', [ $this, 'onWpInit' ], 1 );
		add_action( 'init', [ $this, 'onWpLoaded' ], 1 );
		if ( is_admin() ) {
			add_action( 'admin_init', [ $this, 'onWpAdminInit' ] );
			add_action( 'admin_notices', [ $this, 'onWpAdminNotices' ] );
			add_action( 'admin_menu', [ $this, 'onWpAdminMenu' ] );
			add_action( 'plugin_action_links', [ $this, 'onWpPluginActionLinks' ], 10, 4 );
		}
		/**
		 * We make the assumption that all settings updates are successful until told otherwise
		 * by an actual failing update_option call.
		 */
		$this->updateSuccess = true;
		$this->failedUpdateOptions = [];
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
	 * @param string $glue
	 * @return string
	 */
	public function getPluginPrefix( $glue = '-' ) {
		return $this->oPluginVo->getFullPluginPrefix( $glue );
	}

	protected function getFullParentMenuId() {
		return self::ParentMenuId.'-'.$this->oPluginVo->getPluginSlug();
	}

	protected function display( $view, $data = [] ) {
		$file = $this->oPluginVo->getViewDir().$view.'.php';

		if ( !is_file( $file ) ) {
			echo 'View not found: '.esc_html( $file );
			return false;
		}

		if ( count( $data ) > 0 ) {
			extract( $data, EXTR_PREFIX_ALL, self::VariablePrefix );
		}

		ob_start();
		include( $file );
		$contents = ob_get_contents();
		ob_end_clean();

		echo $contents;
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
		if ( $this->isWorpitPluginAdminPage() ) {
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
		if ( !empty( $this->menu ) ) {
			foreach ( $this->menu as $menuTitle => $aMenu ) {
				list( $sMenuItemText, $sMenuItemId, $menuCallBack ) = $aMenu;
				add_submenu_page( $sFullParentMenuId, $menuTitle, $sMenuItemText, $this->oPluginVo->getBasePermissions(), $sMenuItemId, [
					$this,
					$menuCallBack
				] );
			}
		}

		$this->fixSubmenu();
	}

	protected function createPluginSubMenuItems() {
		/* Override to create array of sub-menu items
		 $this->m_aPluginMenu = array(
		 		//Menu Page Title => Menu Item name, page ID (slug), callback function onLoad.
		 		$this->getSubmenuPageTitle( 'Content by Country' ) => array( 'Content by Country', $this->getSubmenuId('main'), 'onDisplayCbcMain' ),
		 );
		*/
	}

	protected function fixSubmenu() {
		global $submenu;
		$fullParentMenuID = $this->getFullParentMenuId();
		if ( isset( $submenu[ $fullParentMenuID ] ) ) {
			$submenu[ $fullParentMenuID ][ 0 ][ 0 ] = 'Dashboard';
		}
	}

	/**
	 * The callback function for the main admin menu index page
	 */
	public function onDisplayMainMenu() {
		$this->display( 'worpit_'.$this->oPluginVo->getPluginSlug().'_index', $this->getCommonDisplayVars() );
	}

	protected function getCommonDisplayVars() {
		return [
			'plugin_url'         => $this->sPluginUrl,
			'icwp_logo_url'      => $this->sPluginUrl.'resources/images/icwp_logo-250.png',
			'worpdrive_logo_url' => $this->sPluginUrl.'resources/images/worpdrive-plugin-logo.png',
		];
	}

	/**
	 * The Action Links in the main plugins page. Defaults to link to the main Dashboard page
	 *
	 * @param array $actionLinks
	 * @param       $pluginFile
	 * @return array
	 */
	public function onWpPluginActionLinks( $actionLinks, $pluginFile ) {
		if ( $pluginFile == $this->getPluginBaseFile() ) {
			$settingsLink = sprintf( '<a href="%s">%s</a>', admin_url( "admin.php" ).'?page='.$this->getFullParentMenuId(), 'Settings' );
			array_unshift( $actionLinks, $settingsLink );
		}
		return $actionLinks;
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
		wp_register_style( 'worpit_bootstrap_wpadmin_css', $this->getCssUrl( 'bootstrap.min.css' ), false, $this->oPluginVo->getVersion() );
		wp_enqueue_style( 'worpit_bootstrap_wpadmin_css' );
	}

	protected function enqueuePluginAdminCss() {
		$rand = rand();
		wp_register_style( 'icwp_plugin_css'.$rand, $this->getCssUrl( 'plugin.css' ), false, $this->oPluginVo->getVersion() );
		wp_enqueue_style( 'icwp_plugin_css'.$rand );
	}

	/**
	 * Provides the basic HTML template for printing a WordPress Admin Notices
	 *
	 * @param $notice - The message to be displayed.
	 * @param $class  - either error or updated
	 * @param $echo   - if true, will echo. false will return the string
	 * @return string
	 */
	protected function getAdminNotice( $notice = '', $class = 'updated', $echo = false ) {
		$fullNotice = '
			<div id="message" class="'.esc_attr( $class ).'">
				<style>
					#message form { margin: 0; }
				</style>
				'.esc_html( $notice ).'
			</div>
		';

		if ( $echo ) {
			echo $fullNotice;
		}
		return $fullNotice;
	}

	/**
	 * A little helper function that populates all the plugin options arrays with DB values
	 * @deprecated 3.2
	 */
	protected function readyAllPluginOptions() {
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
	 * @deprecated 3.2
	 */
	protected function populateAllPluginOptions() {
	}

	/**
	 * @param $allOptionsInput - comma separated list of all the input keys to be processed from the $_POST
	 * @return bool
	 */
	protected function updatePluginOptionsFromSubmit( $allOptionsInput ) {
		foreach ( explode( ',', $allOptionsInput ) as $inputKey ) {
			if ( strpos( $inputKey, ':' ) ) {
				$input = explode( ':', $inputKey );
				list( $optionType, $optionKey ) = $input;
				/** currently all checkboxes so default to 'N' */
				$this->updateOption( $optionKey, $this->getInputFromPost( $optionKey, 'N' ) );
			}
		}
		return true;
	}

	/**
	 * Returns a comma seperated list of all the options in a given options section.
	 * @param array $optSection
	 * @return string
	 */
	protected function collateAllFormInputsForOptionsSection( $optSection ) {
		return implode( ',', array_map(
			function ( $option ) {
				return sprintf( '%s:%s', $option[ 'type' ], $option[ 'slug' ] );
			},
			empty( $optSection[ 'section_options' ] ) ? [] : $optSection[ 'section_options' ]
		) );
	}

	protected function isWorpitPluginAdminPage() {
		$pageNow = CCBC_DP::FetchGet( 'page', null, 'sanitize_key' );
		//admin area, and the 'page' begins with 'worpit'
		return ( is_admin() && !empty( $pageNow ) && ( strpos( $pageNow, $this->getFullParentMenuId() ) === 0 ) );
	}

	protected function deleteAllPluginDbOptions() {
		if ( current_user_can( 'manage_options' ) ) {
			foreach ( $this->getAllPluginOptions() as $optionsSection ) {
				foreach ( $optionsSection[ 'section_options' ] as $option ) {
					if ( !empty( $option[ 'slug' ] ) ) {
						$this->deleteOption( $option[ 'slug' ] );
					}
				}
			}
		}
	}

	/**
	 * @return array[]
	 */
	protected function &getAllPluginOptions() {
		if ( !is_array( $this->allPluginOptions ) ) {

			$this->initPluginOptions();
			if ( !is_array( $this->allPluginOptions ) ) {
				$this->allPluginOptions = [];
			}

			foreach ( $this->allPluginOptions as &$section ) {
				foreach ( $section[ 'section_options' ] as &$optionParam ) {
					$currentOptVal = $this->getOption( $optionParam[ 'slug' ] );
					$optionParam[ 'value' ] = is_null( $currentOptVal ) ? $optionParam[ 'default' ] : $currentOptVal;
				}
			}
		}
		return $this->allPluginOptions;
	}

	/**
	 * @return array
	 */
	protected function getAllPluginOptionKeys() {
		$keys = [];
		foreach ( $this->getAllPluginOptions() as $optionsSection ) {
			foreach ( $optionsSection[ 'section_options' ] as $param ) {
				if ( !empty( $option[ 'slug' ] ) ) {
					$keys[] = $option[ 'slug' ];
				}
			}
		}
		return $keys;
	}

	/**
	 * Current all form input types are checkbox (which is text) or text.
	 * @param string $key
	 * @return string
	 */
	protected function getInputFromPost( $key, $default = null ) {
		$value = CCBC_DP::FetchPost( $this->oPluginVo->getOptionStoragePrefix( $key ), null, 'sanitize_text_field' );
		return is_null( $value ) ? $default : $value;
	}

	/**
	 * @param       $optionKey
	 * @param mixed $default
	 * @return mixed
	 */
	public function getOption( $optionKey, $default = false ) {
		return $this->isValidPluginOptionKey( $optionKey ) ?
			ICWP_WpFunctions_CBC::GetWpOption( $this->getOptionKey( $optionKey ), $default )
			: null;
	}

	/**
	 * @param $optionKey
	 * @param $value
	 * @return bool
	 */
	public function updateOption( $optionKey, $value ) {
		$success = false;
		if ( $this->isValidPluginOptionKey( $optionKey ) ) {
			$success = CCBC_Functions::GetWpOption( $this->getOptionKey( $optionKey ) ) === $value
					   || CCBC_Functions::UpdateWpOption( $this->getOptionKey( $optionKey ), $value );
			if ( !$success ) {
				$this->updateSuccess = false;
				$this->failedUpdateOptions[] = $this->getOptionKey( $optionKey );
			}
		}
		return $success;
	}

	/**
	 * @param $optionKey
	 * @return bool
	 */
	public function deleteOption( $optionKey ) {
		return $this->isValidPluginOptionKey( $optionKey )
			   && ICWP_WpFunctions_CBC::DeleteWpOption( $this->getOptionKey( $optionKey ) );
	}

	/**
	 * @param string $optionKey
	 * @return string
	 */
	public function getOptionKey( $optionKey ) {
		return $this->oPluginVo->getOptionStoragePrefix( $optionKey );
	}

	/**
	 * @return bool
	 */
	public function isValidPluginOptionKey( $optionKey ) {
		return in_array( $optionKey, $this->getAllPluginOptionKeys() );
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
