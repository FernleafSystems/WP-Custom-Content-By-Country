<?php

if ( class_exists( 'ICWP_CCBC_DataProcessor', false ) ) {
	return;
}

class ICWP_CCBC_DataProcessor {

	/**
	 * @var ICWP_CCBC_DataProcessor
	 */
	protected static $oInstance = null;

	/**
	 * @var string
	 */
	protected static $sIpAddress;

	/**
	 * @var integer
	 */
	protected static $nRequestTime;

	/**
	 * @return ICWP_CCBC_DataProcessor
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @return int
	 */
	public static function GetRequestTime() {
		if ( empty( self::$nRequestTime ) ) {
			self::$nRequestTime = time();
		}
		return self::$nRequestTime;
	}

	/**
	 * @return string
	 */
	public static function GetVisitorIpAddress() {

		if ( empty( self::$sIpAddress ) ) {
			$sourceOptions = [
				'HTTP_CF_CONNECTING_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_X_REAL_IP',
				'HTTP_X_SUCURI_CLIENTIP',
				'HTTP_INCAP_CLIENT_IP',
				'HTTP_FORWARDED',
				'HTTP_CLIENT_IP',
				'REMOTE_ADDR'
			];
			$fCanUseFilter = function_exists( 'filter_var' ) && defined( 'FILTER_FLAG_NO_PRIV_RANGE' ) && defined( 'FILTER_FLAG_IPV4' );

			foreach ( $sourceOptions as $opt ) {

				$ipToTest = self::FetchServer( $opt );
				if ( empty( $ipToTest ) ) {
					continue;
				}

				$addresses = array_map( 'trim', explode( ',', $ipToTest ) ); //sometimes a comma-separated list is returned
				foreach ( $addresses as $ip ) {

					$ipParts = explode( ':', $ip );
					$ip = $ipParts[ 0 ];

					if ( $fCanUseFilter && !self::IsAddressInPublicIpRange( $ip ) ) {
						continue;
					}
					else {
						self::$sIpAddress = $ip;
						return self::$sIpAddress;
					}
				}
			}
		}

		return self::$sIpAddress;
	}

	/**
	 * @param string $ip
	 * @return bool
	 */
	public static function IsAddressInPublicIpRange( $ip ) {
		return function_exists( 'filter_var' ) && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE );
	}

	/**
	 * @param array  $aArray
	 * @param string $sKey The array key to fetch
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function ArrayFetch( &$aArray, $sKey, $mDefault = null ) {
		if ( empty( $aArray ) || !isset( $aArray[ $sKey ] ) ) {
			return $mDefault;
		}
		return $aArray[ $sKey ];
	}

	/**
	 * @param string $sKey The $_COOKIE key
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchCookie( $sKey, $mDefault = null ) {
		return self::ArrayFetch( $_COOKIE, $sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchEnv( $sKey, $mDefault = null ) {
		return self::ArrayFetch( $_ENV, $sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchGet( $sKey, $mDefault = null ) {
		return self::ArrayFetch( $_GET, $sKey, $mDefault );
	}

	/**
	 * @param string $sKey The $_POST key
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchPost( $sKey, $mDefault = null ) {
		return self::ArrayFetch( $_POST, $sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param bool   $bIncludeCookie
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchRequest( $sKey, $bIncludeCookie = true, $mDefault = null ) {
		$mFetchVal = self::FetchPost( $sKey );
		if ( is_null( $mFetchVal ) ) {
			$mFetchVal = self::FetchGet( $sKey );
			if ( is_null( $mFetchVal && $bIncludeCookie ) ) {
				$mFetchVal = self::FetchCookie( $sKey );
			}
		}
		return is_null( $mFetchVal ) ? $mDefault : $mFetchVal;
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed|null
	 */
	public static function FetchServer( $sKey, $mDefault = null ) {
		return self::ArrayFetch( $_SERVER, $sKey, $mDefault );
	}
}