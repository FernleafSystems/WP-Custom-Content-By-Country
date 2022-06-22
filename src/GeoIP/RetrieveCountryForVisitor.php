<?php

namespace FernleafSystems\Wordpress\Plugin\CCBC\GeoIP;

class RetrieveCountryForVisitor extends Base {

	/**
	 * @param string $ip
	 * @throws \Exception
	 */
	public function lookupIP( $ip ) {
		if ( !is_file( $this->pathDB ) ) {
			throw new \Exception( 'Path to DB is invalid' );
		}
		$reader = new \GeoIp2\Database\Reader( $this->pathDB );
		return $reader->country( $ip );
	}
}