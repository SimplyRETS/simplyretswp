<?php
/**
 * simply-rets-utils.php
 * SimplyRETS (C) 2015
 */

class SimplyRetsUtils {

    public static function defaultValue( &$var, $default ) {
        if( empty( $var ) ) {
            $var = $default;
        }
    }
}