<?php

if ( !class_exists('ICWP_WpFunctions_CBC') ):

class ICWP_WpFunctions_CBC {

	/**
	 * @var string
	 */
	protected $sWpVersion;
	
	public function __construct() {}

	/**
	 * @param string $sPluginFile
	 * @return false|stdClass
	 */
	public function getIsPluginUpdateAvailable( $sPluginFile ) {
		$aUpdates = $this->getWordpressUpdates();
		if ( empty( $aUpdates ) ) {
			return false;
		}
		if ( isset( $aUpdates[ $sPluginFile ] ) ) {
			return $aUpdates[ $sPluginFile ];
		}
		return false;
	}

	public function getPluginUpgradeLink( $insPluginFile ) {
		$sUrl = self_admin_url( 'update.php' ) ;
		$aQueryArgs = array(
			'action' 	=> 'upgrade-plugin',
			'plugin'	=> urlencode( $insPluginFile ),
			'_wpnonce'	=> wp_create_nonce( 'upgrade-plugin_' . $insPluginFile )
		);
		return add_query_arg( $aQueryArgs, $sUrl );
	}
	
	public function getWordpressUpdates() {
		$oCurrent = $this->getTransient( 'update_plugins' );
		return $oCurrent->response;
	}
	
	/**
	 * The full plugin file to be upgraded.
	 * 
	 * @param string $insPluginFile
	 * @return boolean
	 */
	public function doPluginUpgrade( $insPluginFile ) {

		if ( !$this->getIsPluginUpdateAvailable($insPluginFile)
			|| ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'update.php' ) ) {
			return true;
		}
		$sUrl = $this->getPluginUpgradeLink( $insPluginFile );
		wp_redirect( $sUrl );
		exit();
	}
	/**
	 * @param string $insKey
	 * @return object
	 */
	protected function getTransient( $insKey ) {
	
		// TODO: Handle multisite
	
		if ( version_compare( $this->getWordPressVersion(), '2.7.9', '<=' ) ) {
			return get_option( $insKey );
		}
	
		if ( function_exists( 'get_site_transient' ) ) {
			return get_site_transient( $insKey );
		}
	
		if ( version_compare( $this->getWordPressVersion(), '2.9.9', '<=' ) ) {
			return apply_filters( 'transient_'.$insKey, get_option( '_transient_'.$insKey ) );
		}
	
		return apply_filters( 'site_transient_'.$insKey, get_option( '_site_transient_'.$insKey ) );
	}
	
	/**
	 * @return string
	 */
	public function getWordPressVersion() {
		global $wp_version;
		
		if ( empty( $this->sWpVersion ) ) {
			$sVersionFile = ABSPATH.WPINC.'/version.php';
			$sVersionContents = file_get_contents( $sVersionFile );
			
			if ( preg_match( '/wp_version\s=\s\'([^(\'|")]+)\'/i', $sVersionContents, $aMatches ) ) {
				$this->sWpVersion = $aMatches[1];
			}
		}
		return $this->sWpVersion;
	}

	public static function GetWpOption( $sKey, $mDefault = false ) {
		return get_option( $sKey, $mDefault );
	}
	public static function AddWpOption( $sKey, $mValue ) {
		return add_option( $sKey, $mValue );
	}
	public static function UpdateWpOption( $sKey, $mValue ) {
		return update_option( $sKey, $mValue );
	}
	public static function DeleteWpOption( $sKey ) {
		return delete_option( $sKey );
	}
}

endif;