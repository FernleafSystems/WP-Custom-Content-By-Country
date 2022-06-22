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
					'CBC_AMAZON'  => [ $this, 'sc_printAmazonLinkByCountry' ],
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
	 * The Shortcode function for CBC_AMAZON
	 * @param array  $attributes
	 * @param string $sContent
	 * @return string
	 */
	public function sc_printAmazonLinkByCountry( $attributes = [], $sContent = '' ) {
		$attributes = shortcode_atts(
			[
				'item'    => '',
				'text'    => $sContent,
				'asin'    => '',
				'country' => '',
			],
			$attributes
		);

		if ( !empty( $attributes[ 'asin' ] ) ) {
			$sAsinToUse = $attributes[ 'asin' ];
		}
		else {
			return ''; //ASIN is undefined or the "item" does not exist.
		}

		if ( empty( $attributes[ 'country' ] ) ) {
			$href = $this->buildAffLinkFromAsinOnly( $sAsinToUse );
		}
		else {
			$href = $this->buildAffLinkFromCountryCode( $sAsinToUse, $attributes[ 'country' ] );
		}

		$output = '<a class="cbc_amazon_link" href="%s" target="_blank">%s</a>';
		return sprintf( $output, $href, do_shortcode( $attributes[ 'text' ] ) );
	}

	/**
	 * Meat and Potatoes of the CBC plugin
	 * By default, $insContent will be "shown" for whatever countries are specified.
	 * Alternatively, set to 'n' if you want to hide.
	 * Logic is: if visitor is coming from a country in the 'country' list and show='y', then show the content.
	 * OR
	 * If the visitor is not from a country in the 'country' list and show='n', then show the content.
	 * Otherwise display 'message' if defined.
	 * 'message' is displayed where the the content isn't displayed.
	 * @param        $params
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
				$isVisitorMatched = in_array( $this->loadDataProcessor()->GetVisitorIpAddress(), $selectedIPs );
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
			$sReturnContent = $content;
		}
		else {
			$params[ 'html' ] = empty( $params[ 'html' ] ) ? 'span' : $params[ 'html' ];
			$sReturnContent = '<'.$params[ 'html' ]
							  .$params[ 'style' ]
							  .$params[ 'class' ]
							  .$params[ 'id' ].'>'.$content.'</'.$params[ 'html' ].'>';
		}

		return trim( $sReturnContent );
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
			if ( function_exists( 'geoip_detect2_get_info_from_ip' ) ) {
				$data = geoip_detect2_get_info_from_ip( $this->loadDataProcessor()->GetVisitorIpAddress() );
				if ( is_object( $data ) && isset( $data->country )
					 && !empty( $data->country->isoCode ) && preg_match( $codeRegEx, $data->country->isoCode ) ) {
					$code = $data->country->isoCode;
				}
			}

			if ( empty( $code ) ) {

				if ( !empty( $_SERVER[ 'HTTP_CF_IPCOUNTRY' ] ) && preg_match( $codeRegEx, $_SERVER[ 'HTTP_CF_IPCOUNTRY' ] ) ) {
					$code = $_SERVER[ 'HTTP_CF_IPCOUNTRY' ];
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

		return empty( $code ) ? 'us' : strtolower( $code );
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
			if ( function_exists( 'geoip_detect2_get_info_from_ip' ) ) {
				$data = geoip_detect2_get_info_from_ip( $this->loadDataProcessor()->GetVisitorIpAddress() );
				if ( is_object( $data ) && isset( $data->country )
					 && !empty( $data->country->names ) && is_array( $data->country->names ) ) {
					$names = $data->country->names;
					if ( isset( $names[ 'en' ] ) ) {
						$country = $names[ 'en' ];
					}
					else {
						$country = array_shift( $names );
					}
				}
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
	 * @return \GeoIp2\Model\Country
	 * @throws Exception
	 */
	public function getMMCountry() {
		$this->requireLib();
		$pathToDB = path_join( \ICWP_CustomContentByCountry_Plugin::GetInstance()->getRootDir(),
			'resources/MaxMind/GeoLite2-Country.mmdb' );
		return ( new RetrieveCountryForVisitor( $pathToDB ) )
			->lookupIP( $this->loadDataProcessor()->GetVisitorIpAddress() );
	}

	protected function requireLib() {
		require_once( path_join(
			\ICWP_CustomContentByCountry_Plugin::GetInstance()->getRootDir(), 'vendor/autoload.php'
		) );
	}

	/**
	 * @return ICWP_CCBC_DataProcessor
	 */
	public function loadDataProcessor() {
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

	/** AMAZON **/

	/**
	 * @param $sAsin
	 * @return string
	 */
	public function buildAffLinkFromAsinOnly( $sAsin ) {
		// Default country code to US. (amazon.com)
		return $this->buildAffLinkFromCountryCode( $sAsin, $this->getVisitorCountryCode() );
	}

	/**
	 * Given the country code and the product ASIN code, returns an Amazon link.
	 * If the country code isn't found in the country code mapping, 'global' (amazon.com) is used.
	 * @param $sAsin
	 * @param $sCountryCode
	 * @return string
	 */
	public function buildAffLinkFromCountryCode( $sAsin, $sCountryCode ) {

		$sAmazonSiteCode = 'global';    //the default: amazon.com
		$aAmazonCountryCodeToSiteMap = $this->getAmazonCountryCodeToSiteMap();
		$aAmazonSitesData = $this->getAmazonSitesData();

		if ( array_key_exists( $sCountryCode, $aAmazonCountryCodeToSiteMap ) ) {
			//special country code mapping that has been provisioned for. e.g. ie => uk amazon site
			$sAmazonSiteCode = $aAmazonCountryCodeToSiteMap[ $sCountryCode ];
		}
		elseif ( array_key_exists( $sCountryCode, $aAmazonSitesData ) ) {
			$sAmazonSiteCode = $sCountryCode;
		}

		return $this->buildAffLinkFromAmazonSite( $sAsin, $sAmazonSiteCode );
	}

	/**
	 * Give it an Amazon site (defaults to "global") and an ASIN and it will create it.
	 * @param string $sAsin
	 * @param string $sAmazonSite
	 * @return string
	 */
	public function buildAffLinkFromAmazonSite( $sAsin = '', $sAmazonSite = 'global' ) {
		$aAmazonSitesData = $this->getAmazonSitesData();

		if ( !array_key_exists( $sAmazonSite, $aAmazonSitesData ) ) {
			$sAmazonSite = 'global';
		}

		list( $sAmazonDomain, $sAssociateIdTag ) = $aAmazonSitesData[ $sAmazonSite ];
		$sAssociateIdTag = $this->getOption( $sAssociateIdTag );
		return $this->buildAffLinkAmazon( $sAsin, $sAmazonDomain, $sAssociateIdTag );
	}

	/**
	 * The most basic link builder.
	 * @param string $sAsin
	 * @param string $sAmazonDomain
	 * @param string $sAffIdTag
	 * @return string
	 */
	protected function buildAffLinkAmazon( $sAsin = '', $sAmazonDomain = 'com', $sAffIdTag = '' ) {
		return sprintf( 'https://www.amazon.%s/dp/%s/?tag=%s&creativeASIN=%s',
			$sAmazonDomain,
			$sAsin,
			$sAffIdTag,
			$sAsin
		);
	}

	/**
	 * @param string $sHtmlVar
	 * @return bool
	 */
	private function getHtmlIsOff( $sHtmlVar = '' ) {

		// Basically the local html directive will always override the plugin global setting
		if ( !empty( $sHtmlVar ) ) {
			return ( strtolower( $sHtmlVar ) == 'none' );
		}

		return $this->fHtmlOffMode;
	}

	/**
	 * @return array
	 */
	private function getAmazonCountryCodeToSiteMap() {
		return [
			//country code	//Amazon site
			'us' => 'global',    //US is the default
			'ie' => 'uk',
		];
	}

	/**
	 * @return array
	 */
	private function getAmazonSitesData() {
		return [
			'global' => [ 'com', 'afftag_amazon_region_us' ],
			'ca'     => [ 'ca', 'afftag_amazon_region_canada' ],
			'uk'     => [ 'co.uk', 'afftag_amazon_region_uk' ],
			'fr'     => [ 'fr', 'afftag_amazon_region_france' ],
			'de'     => [ 'de', 'afftag_amazon_region_germany' ],
			'it'     => [ 'it', 'afftag_amazon_region_italy' ],
			'es'     => [ 'es', 'afftag_amazon_region_spain' ],
			'jp'     => [ 'co.jp', 'afftag_amazon_region_japan' ],
			'cn'     => [ 'cn', 'afftag_amazon_region_china' ]
		];
	}
}