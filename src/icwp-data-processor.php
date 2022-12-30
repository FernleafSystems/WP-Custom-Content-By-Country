<?php

if ( class_exists( 'ICWP_CCBC_DataProcessor', false ) ) {
	return;
}

class CCBC_DP {

	/**
	 * @var ICWP_CCBC_DataProcessor
	 */
	protected static $oInstance = null;

	/**
	 * @var string
	 */
	protected static $IpAddress;

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

		if ( empty( self::$IpAddress ) ) {
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
						self::$IpAddress = $ip;
						return self::$IpAddress;
					}
				}
			}
		}

		return self::$IpAddress;
	}

	/**
	 * @param string $ip
	 * @return bool
	 */
	public static function IsAddressInPublicIpRange( $ip ) {
		return function_exists( 'filter_var' ) && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE );
	}

	/**
	 * @param array      $array
	 * @param string     $key The array key to fetch
	 * @param mixed      $default
	 * @param ?|callable $sanitizeCallback
	 * @return mixed|null
	 */
	public static function ArrayFetch( $array, $key, $default = null, $sanitizeCallback = null ) {
		$value = ( is_array( $array ) && isset( $array[ $key ] ) ) ? $array[ $key ] : $default;
		return ( !is_null( $value ) && is_callable( $sanitizeCallback ) ) ? call_user_func( $sanitizeCallback, $value ) : $value;
	}

	/**
	 * @param string $key The $_COOKIE key
	 * @param mixed  $default
	 * @param ?|callable $sanitizeCallback
	 * @return mixed|null
	 */
	public static function FetchCookie( $key, $default = null, $sanitizeCallback = null ) {
		return self::ArrayFetch( $_COOKIE, $key, $default, $sanitizeCallback );
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @param ?|callable $sanitizeCallback
	 * @return mixed|null
	 */
	public static function FetchEnv( $key, $default = null, $sanitizeCallback = null ) {
		return self::ArrayFetch( $_ENV, $key, $default, $sanitizeCallback );
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @param ?|callable $sanitizeCallback
	 * @return mixed|null
	 */
	public static function FetchGet( $key, $default = null, $sanitizeCallback = null ) {
		return self::ArrayFetch( $_GET, $key, $default, $sanitizeCallback );
	}

	/**
	 * @param string $key The $_POST key
	 * @param mixed  $default
	 * @param ?|callable $sanitizeCallback
	 * @return mixed|null
	 */
	public static function FetchPost( $key, $default = null, $sanitizeCallback = null ) {
		return self::ArrayFetch( $_POST, $key, $default, $sanitizeCallback );
	}

	/**
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed|null
	 */
	public static function FetchServer( $key, $default = null, $sanitizeCallback = null ) {
		return self::ArrayFetch( $_SERVER, $key, $default, $sanitizeCallback );
	}
}

class ICWP_CCBC_DataProcessor extends CCBC_DP {

}