<?php

namespace FernleafSystems\Wordpress\Plugin\CCBC\GeoIP;

class Base {

	protected $pathDB;

	public function __construct( $pathToDB ) {
		$this->pathDB = $pathToDB;
	}
}