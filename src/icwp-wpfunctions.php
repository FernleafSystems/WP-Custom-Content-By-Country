<?php

if ( class_exists( 'ICWP_WpFunctions_CBC' ) ) {
	return;
}

class ICWP_WpFunctions_CBC {

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