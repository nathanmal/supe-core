<?php

if( ! function_exists('debug') )
{
   
    function debug( $data )
    {
        if( is_object($data) OR is_array($data) )
        {
            error_log(print_r($data, true), 3, WP_DEBUG_LOG);
        }
        else if( is_string($data) OR is_numeric($data) )
        {
            error_log($data, 3, WP_DEBUG_LOG);
        }
    }
}
