<?php

use FernleafSystems\Wordpress\Plugin\CCBC\GeoIP\RetrieveCountryForVisitor;

class ICWP_CCBC_Processor_GeoLocation {

	const CbcDataCountryNameCookie = 'cbc_country_name';
	const CbcDataCountryCodeCookie = 'cbc_country_code';

	protected $oDbCountryData;

	/**
	 * @var bool
	 */
	protected $fHtmlOffMode = false;

	/**
	 * @var bool
	 */
	protected $fW3tcCompatibilityMode = false;

	/**
	 * @var bool
	 */
	protected $fDeveloperMode = false;

	/**
	 * @var string
	 */
	protected $sWpOptionPrefix = '';

	public function __construct() {
	}

	/**
	 * @var ICWP_CCBC_Processor_GeoLocation
	 */
	protected static $oInstance = null;

	/**
	 * @return ICWP_CCBC_Processor_GeoLocation
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @param bool $fHtmlOff
	 * @return $this
	 */
	public function setModeHtmlOff( $fHtmlOff ) {
		$this->fHtmlOffMode = (bool)$fHtmlOff;
		return $this;
	}

	/**
	 * @param bool $fOn
	 * @return $this
	 */
	public function setModeW3tcCompatibility( $fOn ) {
		$this->fW3tcCompatibilityMode = (bool)$fOn;
		return $this;
	}

	/**
	 * @param string $prefix
	 * @return $this
	 */
	public function setWpOptionPrefix( $prefix ) {
		$this->sWpOptionPrefix = (string)$prefix;
		return $this;
	}

	public function initShortCodes() {

		if ( function_exists( 'add_shortcode' ) ) {
			foreach (
				[
					'CBC'         => [ $this, 'sc_printContentByCountry' ],
					'CBC_COUNTRY' => [ $this, 'sc_printVisitorCountryName' ],
					'CBC_CODE'    => [ $this, 'sc_printVisitorCountryCode' ],
					'CBC_IP'      => [ $this, 'sc_printVisitorIpAddress' ],
					//			'CBC_HELP'  => [ $this, 'printHelp' ],
				] as $shortcode => $callback
			) {
				if ( is_callable( $callback ) ) {
					add_shortcode( $shortcode, $callback );
				}
			}
		}
	}

	/**
	 * Meat and Potatoes of the CBC plugin
	 * By default, $content will be "shown" for whatever countries are specified.
	 * Alternatively, set to 'n' if you want to hide.
	 * Logic is: if visitor is coming from a country in the 'country' list and show='y', then show the content.
	 * OR
	 * If the visitor is not from a country in the 'country' list and show='n', then show the content.
	 * Otherwise, display 'message' if defined.
	 * 'message' is displayed where the content isn't displayed.
	 * @param array  $params
	 * @param string $content
	 * @return string
	 */
	public function sc_printContentByCountry( $params = [], $content = '' ) {
		$params = shortcode_atts( [
			'message' => '',
			'show'    => 'y',
			'country' => '',
			'ip'      => '',
		], $params );

		$params[ 'country' ] = str_replace( ' ', '', strtolower( $params[ 'country' ] ) );
		$params[ 'ip' ] = str_replace( ' ', '', strtolower( $params[ 'ip' ] ) );

		if ( empty( $params[ 'country' ] ) && empty( $params[ 'ip' ] ) ) {
			$output = do_shortcode( $content );
		}
		else {
			if ( !empty( $params[ 'country' ] ) ) {
				$selectedCountries = array_map(
					function ( $country ) {
						return trim( strtolower( $country ) );
					},
					explode( ',', $params[ 'country' ] )
				);
				if ( in_array( 'uk', $selectedCountries ) ) {
					$selectedCountries[] = 'gb'; // FIX for use "iso_code_2" db column instead of "code"
				}

				$isVisitorMatched = in_array( $this->getVisitorCountryCode(), $selectedCountries );
			}
			else { // == !empty( $params[ 'ip' ]
				$selectedIPs = array_map(
					function ( $ip ) {
						return trim( strtolower( $ip ) );
					},
					explode( ',', $params[ 'ip' ] )
				);
				$isVisitorMatched = in_array( CCBC_DP::GetVisitorIpAddress(), $selectedIPs );
			}

			$isShowVisitorContent = strtolower( $params[ 'show' ] ) != 'n'; // defaults to show content
			$isShowContent = $isShowVisitorContent === $isVisitorMatched;

			$this->def( $params, 'class', 'cbc_content' );
			$output = $this->printShortCodeHtml( $params, do_shortcode( $isShowContent ? $content : $params[ 'message' ] ) );
		}

		return $output;
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function sc_printVisitorCountryCode( $params = [] ) {
		$params = shortcode_atts(
			[
				'class' => 'cbc_countrycode',
				'case'  => 'lower',
			],
			$params
		);
		$code = $this->getVisitorCountryCode();
		if ( strtolower( $params[ 'case' ] ) === 'upper' ) {
			$code = strtoupper( $code );
		}
		return $this->printShortCodeHtml( $params, $code );
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function sc_printVisitorCountryName( $params = [] ) {
		$params = shortcode_atts( [ 'class' => 'cbc_country' ], $params );
		return $this->printShortCodeHtml( $params, $this->getVisitorCountryName() );
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function sc_printVisitorIpAddress( $params = [] ) {
		$params = shortcode_atts( [ 'class' => 'cbc_ip' ], $params );
		return $this->printShortCodeHtml( $params, $this->loadDataProcessor()->GetVisitorIpAddress() );
	}

	/**
	 * @param        $params
	 * @param string $content
	 * @return string
	 */
	private function printShortCodeHtml( &$params, $content ) {
		$this->handleW3tcCompatibiltyMode();

		$this->def( $params, 'html' );
		$this->def( $params, 'id' );
		$this->def( $params, 'style' );
		$this->noEmptyElement( $params, 'id' );
		$this->noEmptyElement( $params, 'style' );
		$this->noEmptyElement( $params, 'class' );

		if ( $this->getHtmlIsOff( $params[ 'html' ] ) || empty( $content ) ) {
			$output = $content;
		}
		else {
			$params[ 'html' ] = empty( $params[ 'html' ] ) ? 'span' : $params[ 'html' ];
			$output = '<'.$params[ 'html' ]
					  .$params[ 'style' ]
					  .$params[ 'class' ]
					  .$params[ 'id' ].'>'.$content.'</'.$params[ 'html' ].'>';
		}

		return trim( $output );
	}

	/**
	 * @return string
	 */
	public function getVisitorCountryCode() {
		$code = null;

		$codeRegEx = '/^[a-z]{2}$/i';

		if ( $this->loadDataProcessor()->GetVisitorIpAddress() == '127.0.0.1' ) {
			$code = 'localhost';
		}
		else {
			$data = $this->getDataFromPlugin_GeoIpDetect();
			if ( is_object( $data ) && isset( $data->country )
				 && !empty( $data->country->isoCode ) && preg_match( $codeRegEx, $data->country->isoCode ) ) {
				$code = $data->country->isoCode;
			}

			if ( empty( $code ) ) {

				$cfCountry = CCBC_DP::FetchServer( 'HTTP_CF_IPCOUNTRY', null, 'sanitize_key' );
				if ( preg_match( $codeRegEx, $cfCountry ) ) {
					$code = $cfCountry;
				}
				else {
					try {
						$code = $this->getMMCountry()->country->isoCode;
					}
					catch ( Exception $e ) {
					}
				}
			}
		}

		return empty( $code ) ? 'us' : strtolower( sanitize_key( $code ) );
	}

	/**
	 * @return string
	 */
	public function getVisitorCountryName() {
		$country = '';

		if ( $this->loadDataProcessor()->GetVisitorIpAddress() == '127.0.0.1' ) {
			$country = 'localhost';
		}
		else {
			$data = $this->getDataFromPlugin_GeoIpDetect();
			if ( is_object( $data ) && isset( $data->country ) && !empty( $data->country->names ) && is_array( $data->country->names ) ) {
				$names = $data->country->names;
				$country = empty( $names[ 'en' ] ) ? array_shift( $names ) : $names[ 'en' ];
			}

			if ( empty( $country ) ) {
				try {
					$country = $this->getMMCountry()->country->name;
				}
				catch ( Exception $e ) {
				}
			}
		}

		return empty( $country ) ? 'Unknown' : $country;
	}

	/**
	 * https://wordpress.org/plugins/geoip-detect/
	 * @return null|object
	 */
	private function getDataFromPlugin_GeoIpDetect() {
		$data = null;
		$ip = $this->loadDataProcessor()->GetVisitorIpAddress();

		if ( !empty( $ip ) && function_exists( 'geoip_detect2_get_info_from_ip' ) ) {
			$data = geoip_detect2_get_info_from_ip( $ip );
		}
		if ( ( empty( $data ) || !isset( $data->country ) || empty( $data->country->isoCode ) )
			 && function_exists( 'geoip_detect2_get_info_from_current_ip' ) ) {
			$data = geoip_detect2_get_info_from_current_ip( $ip );
		}

		return $data;
	}

	/**
	 * @return \GeoIp2\Model\Country
	 * @throws Exception
	 */
	public function getMMCountry() {
		$this->requireLib();
		$pathToDB = path_join( \ICWP_CustomContentByCountry_Plugin::GetInstance()->getRootDir(),
			'resources/MaxMind/GeoLite2-Country.mmdb' );
		return ( new RetrieveCountryForVisitor( $pathToDB ) )
			->lookupIP( $this->DP()->GetVisitorIpAddress() );
	}

	protected function requireLib() {
		require_once( path_join(
			\ICWP_CustomContentByCountry_Plugin::GetInstance()->getRootDir(), 'vendor/autoload.php'
		) );
	}

	/**
	 * @return ICWP_CCBC_DataProcessor
	 * @deprecated 3.2
	 */
	public function loadDataProcessor() {
		if ( method_exists( $this, 'DP' ) ) {
			return $this->DP();
		}
		if ( !class_exists( 'ICWP_CCBC_DataProcessor' ) ) {
			require_once( __DIR__.'/icwp-data-processor.php' );
		}
		return ICWP_CCBC_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_CCBC_DataProcessor
	 */
	public function DP() {
		if ( !class_exists( 'ICWP_CCBC_DataProcessor' ) ) {
			require_once( __DIR__.'/icwp-data-processor.php' );
		}
		return ICWP_CCBC_DataProcessor::GetInstance();
	}

	/**
	 * @param string $sKey
	 * @return mixed
	 */
	protected function getOption( $sKey ) {
		return get_option( $this->sWpOptionPrefix.$sKey );
	}

	/**
	 * @param array  $src
	 * @param string $key
	 * @param string $value
	 */
	protected function def( &$src, $key, $value = '' ) {
		if ( is_array( $src ) && !isset( $src[ $key ] ) ) {
			$src[ $key ] = $value;
		}
	}

	/**
	 * Takes an array, an array key and an element type. If value is empty, sets the html element
	 * string to empty string, otherwise forms a complete html element parameter.
	 * E.g. noEmptyElement( aSomeArray, sSomeArrayKey, "style" )
	 * will return String: style="aSomeArray[sSomeArrayKey]" or empty string.
	 * @param array  $aArgs
	 * @param string $sAttrKey
	 * @param string $sElement
	 */
	protected function noEmptyElement( &$aArgs, $sAttrKey, $sElement = '' ) {
		$sAttrValue = $aArgs[ $sAttrKey ];
		$sElement = ( $sElement == '' ) ? $sAttrKey : $sElement;
		$aArgs[ $sAttrKey ] = empty( $sAttrValue ) ? '' : sprintf( ' %s="%s"', $sElement, $sAttrValue );
	}

	private function handleW3tcCompatibiltyMode() {
		if ( $this->fW3tcCompatibilityMode && !defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}

	/**
	 * @param string $htmlVar
	 * @return bool
	 */
	private function getHtmlIsOff( $htmlVar = '' ) {
		return ( strtolower( $htmlVar ) == 'none' ) || $this->fHtmlOffMode;
	}
}