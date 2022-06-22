<?php
/*
Plugin Name: Custom Content by Country (from Shield Security)
Plugin URI: https://icwp.io/db
Description: Tool for displaying/hiding custom content based on visitors country/location.
Version: 3.1.0
Author: AptoWeb
Author URI: https://icwp.io/da
*/

/**
 * Copyright (c) 2022 AptoWeb <support@getshieldsecurity.com>
 * All rights reserved.
 *
 * "Custom Content by Country" is
 * distributed under the GNU General Public License, Version 2,
 * June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once( __DIR__.'/src/icwp-base.php' );

class ICWP_CustomContentByCountry_Plugin extends ICWP_CCBC_Wordpress_Plugin_V1 {

	/**
	 * @var ICWP_CustomContentByCountry_Plugin
	 */
	public static $oInstance;

	/**
	 * @return ICWP_CustomContentByCountry_Plugin
	 */
	public static function GetInstance() {
		if ( !isset( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	protected function __construct() {
		if ( empty( self::$sRootFile ) ) {
			self::$sRootFile = __FILE__;
		}
		self::$aFeatures = [
			'plugin',
			'css',
			'less'
		];
		self::$sParentSlug = 'worpit';
		self::$sVersion = '2.19';
		self::$sPluginSlug = 'cbc';
		self::$sHumanName = 'Custom Content By Country';
		self::$sMenuTitleName = 'Content By Country';
		self::$sTextDomain = 'custom-content-by-country';
		self::$fLoggingEnabled = false;
	}
}

include_once( dirname( __FILE__ ).'/src/icwp-ccbc-main.php' );
$oICWP_CBC = new ICWP_CustomContentByCountry( ICWP_CustomContentByCountry_Plugin::GetInstance() );