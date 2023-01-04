<?php

if ( class_exists( 'ICWP_CCBC_Wordpress_Plugin_V1' ) ) {
	return;
}

abstract class ICWP_CCBC_Wordpress_Plugin_V1 {

	const ViewDir = 'views';
	const SrcDir = 'src';

	/**
	 * @var string
	 */
	protected static $LoggingEnabled;

	/**
	 * @var string
	 */
	protected static $ParentSlug = 'icwp';

	/**
	 * @var string
	 */
	protected static $PluginSlug;

	/**
	 * @var string
	 */
	protected static $Version;

	/**
	 * @var string
	 */
	protected static $HumanName;

	/**
	 * @var string
	 */
	protected static $MenuTitle;

	/**
	 * @var string
	 */
	protected static $TextDomain;

	/**
	 * @var string
	 */
	protected static $sBasePermissions = 'manage_options';

	/**
	 * @var string
	 */
	protected static $sWpmsNetworkAdminOnly = true;

	/**
	 * @var string
	 */
	protected static $sRootFile;

	/**
	 * @var string
	 */
	protected static $fAutoUpgrade = false;

	/**
	 * @var string
	 */
	protected static $aFeatures;

	/**
	 * @return string
	 */
	public function getAdminMenuTitle() {
		return self::$MenuTitle;
	}

	/**
	 * @return string
	 */
	public function getBasePermissions() {
		return self::$sBasePermissions;
	}

	/**
	 * @param string $glue
	 * @return string
	 */
	public function getFullPluginPrefix( $glue = '-' ) {
		return sprintf( '%s%s%s', self::$ParentSlug, $glue, self::$PluginSlug );
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getFeatures() {
		return self::$aFeatures;
	}

	/**
	 * @param string $suffix
	 * @return string
	 */
	public function getOptionStoragePrefix( $suffix = '' ) {
		return $this->getFullPluginPrefix( '_' ).'_'.( empty( $suffix ) ? '' : $suffix );
	}

	/**
	 * @return string
	 */
	public function getHumanName() {
		return self::$HumanName;
	}

	/**
	 * @return string
	 */
	public function getIsLoggingEnabled() {
		return self::$LoggingEnabled;
	}

	/**
	 * @return string
	 */
	public function getIsWpmsNetworkAdminOnly() {
		return self::$sWpmsNetworkAdminOnly;
	}

	/**
	 * @return string
	 */
	public function getParentSlug() {
		return self::$ParentSlug;
	}

	/**
	 * @return string
	 */
	public function getPluginSlug() {
		return self::$PluginSlug;
	}

	/**
	 * get the root directory for the plugin with the trailing slash
	 *
	 * @return string
	 */
	public function getRootDir() {
		return trailingslashit( dirname( $this->getRootFile() ) );
	}

	/**
	 * @return string
	 */
	public function getRootFile() {
		return self::$sRootFile;
	}

	/**
	 * get the directory for the plugin view with the trailing slash
	 *
	 * @return string
	 */
	public function getSourceDir() {
		return trailingslashit( path_join( $this->getRootDir(), self::SrcDir ) );
	}

	/**
	 * @return string
	 */
	public static function GetTextDomain() {
		return self::$TextDomain;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return self::$Version;
	}

	/**
	 * get the directory for the plugin view with the trailing slash
	 *
	 * @return string
	 */
	public function getViewDir() {
		return trailingslashit( path_join( $this->getRootDir(), self::ViewDir ) );
	}

	/**
	 * get the directory for the plugin view with the trailing slash
	 * @return string
	 */
	public function getHandleBarTemplateDir() {
		return trailingslashit( path_join( $this->getRootDir(), self::ViewDir.'/hb' ) );
	}
}