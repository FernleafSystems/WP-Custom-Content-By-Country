<?php

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
	 * @param bool $fOn
	 * @return $this
	 */
	public function setModeDeveloper( $fOn ) {
		$this->fDeveloperMode = (bool)$fOn;
		return $this;
	}

	/**
	 * @param string $sPrefix
	 * @return $this
	 */
	public function setWpOptionPrefix( $sPrefix ) {
		$this->sWpOptionPrefix = (string)$sPrefix;
		return $this;
	}

	public function initShortCodes() {

		$aShortCodeMapping = [
			'CBC'         => 'sc_printContentByCountry',
			'CBC_COUNTRY' => 'sc_printVisitorCountryName',
			'CBC_CODE'    => 'sc_printVisitorCountryCode',
			'CBC_IP'      => 'sc_printVisitorIpAddress',
			'CBC_AMAZON'  => 'sc_printAmazonLinkByCountry'
			//			'CBC_HELP'		=>	'printHelp',
		];

		if ( function_exists( 'add_shortcode' ) && !empty( $aShortCodeMapping ) ) {
			foreach ( $aShortCodeMapping as $sShortCode => $sCallbackFunction ) {
				if ( is_callable( [ $this, $sCallbackFunction ] ) ) {
					add_shortcode( $sShortCode, [ $this, $sCallbackFunction ] );
				}
			}
		}
	}

	/**
	 * The Shortcode function for CBC_AMAZON
	 * @param array  $aAtts
	 * @param string $sContent
	 * @return string
	 */
	public function sc_printAmazonLinkByCountry( $aAtts = [], $sContent = '' ) {
		$aAtts = shortcode_atts(
			[
				'item'    => '',
				'text'    => $sContent,
				'asin'    => '',
				'country' => '',
			],
			$aAtts
		);

		if ( !empty( $aAtts[ 'asin' ] ) ) {
			$sAsinToUse = $aAtts[ 'asin' ];
		}
		else {
			$aAtts[ 'item' ] = strtolower( $aAtts[ 'item' ] );

			if ( array_key_exists( $aAtts[ 'item' ], $this->m_aPreselectedAffItems ) ) {
				$sAsinToUse = $this->m_aPreselectedAffItems[ $aAtts[ 'item' ] ];
			}
			else {
				return ''; //ASIN is undefined or the "item" does not exist.
			}
		}

		if ( empty( $aAtts[ 'country' ] ) ) {
			$sLink = $this->buildAffLinkFromAsinOnly( $sAsinToUse );
		}
		else {
			$sLink = $this->buildAffLinkFromCountryCode( $sAsinToUse, $aAtts[ 'country' ] );
		}

		$sOutputText = '<a class="cbc_amazon_link" href="%s" target="_blank">%s</a>';
		return sprintf( $sOutputText, $sLink, do_shortcode( $aAtts[ 'text' ] ) );
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
				$isVisitorMatched = in_array( $this->loadDataProcessor()->GetVisitorIpAddress( false ), $selectedIPs );
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
		$params = shortcode_atts( [ 'class' => 'cbc_countrycode' ], $params );
		return $this->printShortCodeHtml( $params, $this->getVisitorCountryCode() );
	}

	/**
	 * @param array $aParams
	 * @return string
	 */
	public function sc_printVisitorCountryName( $aParams = [] ) {
		$aParams = shortcode_atts( [ 'class' => 'cbc_country' ], $aParams );
		return $this->printShortCodeHtml( $aParams, $this->getVisitorCountryName() );
	}

	/**
	 * @param array $aParams
	 * @return string
	 */
	public function sc_printVisitorIpAddress( $aParams = [] ) {
		$aParams = shortcode_atts( [ 'class' => 'cbc_ip' ], $aParams );
		return $this->printShortCodeHtml( $aParams, $this->loadDataProcessor()->GetVisitorIpAddress( false ) );
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
		$DP = $this->loadDataProcessor();

		$theCode = 'us';  //defaults to US.

		// Get the CloudFlare country if it's set
		$cfCode = $DP->FetchServer( 'HTTP_CF_IPCOUNTRY' );
		if ( !empty( $cfCode ) ) {
			$theCode = $cfCode;
		}
		elseif ( !$this->fDeveloperMode ) {
			// Use Cookies if developer mode is off.
			$code = $DP->FetchCookie( self::CbcDataCountryCodeCookie );
			if ( !empty( $code ) ) {
				$theCode = $code;
			}
		}
		elseif ( $DP->GetVisitorIpAddress( false ) == '127.0.0.1' ) {
			$theCode = 'localhost';
		}
		else {
			$data = $this->loadVisitorCountryData();
			if ( !empty( $data->iso_code_2 ) ) {
				$theCode = $data->iso_code_2;
			}
		}

		return strtolower( (string)$theCode );
	}

	/**
	 * @return null|string
	 */
	public function getVisitorCountryName() {

		$oDp = $this->loadDataProcessor();

		if ( $oDp->GetVisitorIpAddress( false ) == '127.0.0.1' ) {
			return 'localhost';
		}

		if ( !$this->fDeveloperMode ) {
			$sCookieCountry = $oDp->FetchCookie( self::CbcDataCountryNameCookie );
			if ( !empty( $sCookieCountry ) ) {
				return $sCookieCountry;
			}
		}

		$oData = $this->loadVisitorCountryData();
		if ( isset( $oData->country ) ) {
			return $oData->country;
		}
		return null;
	}

	/**
	 * @return object
	 */
	protected function loadVisitorCountryData() {

		if ( isset( $this->oDbCountryData ) ) {
			return $this->oDbCountryData;
		}

		$oDp = $this->loadDataProcessor();
		$sIpAddress = $oDp->GetVisitorIpAddress( false );

		$sSqlQuery = "
			SELECT `c`.`country`, `c`.`code`, `c`.`iso_code_2`
			FROM `ip2nationCountries` AS `c`
			INNER JOIN ip2nation AS `i`
				ON `c`.`code` = `i`.`country`
			WHERE `i`.`ip` < INET_ATON( '%s' )
			ORDER BY `i`.`ip` DESC
			LIMIT 1
		";
		$sSqlQuery = sprintf( $sSqlQuery, $sIpAddress );

		global $wpdb;
		$this->oDbCountryData = $wpdb->get_row( $sSqlQuery );
		return $this->oDbCountryData;
	}

	/**
	 * @param object|null $oCountryData
	 */
	public function setCountryDataCookies( $oCountryData = null ) {

		if ( is_null( $oCountryData ) ) {
			$oCountryData = $this->loadVisitorCountryData();
		}

		$oDp = $this->loadDataProcessor();
		$nTimeToExpire = $oDp->GetRequestTime() + DAY_IN_SECONDS;

		//set the cookie for future reference if it hasn't been set yet.
		if ( !$oDp->FetchCookie( self::CbcDataCountryNameCookie ) && isset( $oCountryData->country ) ) {
			setcookie( self::CbcDataCountryNameCookie, $oCountryData->country, $nTimeToExpire, COOKIEPATH, COOKIE_DOMAIN, false );
			$_COOKIE[ self::CbcDataCountryNameCookie ] = $oCountryData->country;
		}

		//set the cookie for future reference if it hasn't been set yet.
		if ( !$oDp->FetchCookie( self::CbcDataCountryCodeCookie ) && isset( $oCountryData->code ) ) {
			setcookie( self::CbcDataCountryCodeCookie, $oCountryData->code, $nTimeToExpire, COOKIEPATH, COOKIE_DOMAIN, false );
			$_COOKIE[ self::CbcDataCountryCodeCookie ] = $oCountryData->code;
		}
	}

	/**
	 * @return ICWP_CCBC_DataProcessor
	 */
	public function loadDataProcessor() {
		if ( !class_exists( 'ICWP_CCBC_DataProcessor' ) ) {
			require_once( dirname( __FILE__ ).'/icwp-data-processor.php' );
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

	/**
	 */
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