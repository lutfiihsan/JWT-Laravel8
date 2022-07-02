<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use \Exception;

class JwtApi
{
    /**
     * RETURN VISITORS REAL IP
     * @return string
     */
    public static function getIp(){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
        if (array_key_exists($key, $_SERVER) === true){
            foreach (explode(',', $_SERVER[$key]) as $ip){
            $ip = trim($ip); // just to be safe
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                return $ip;
            }
            }
        }
        }
        return \Request::ip(); //No IP found, return Laravel default
    }

    public static function getUserAgent(){
        //this is a good place to detect mobile devices etc.
        return $_SERVER['HTTP_USER_AGENT'];
    }

}