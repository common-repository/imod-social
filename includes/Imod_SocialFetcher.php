<?php

class Imod_SocialFetcher
{
    private static function _get($url)
    {
        $response = wp_remote_get($url);
        $api_response = wp_remote_retrieve_body($response);
        return $api_response;
    }

    private static function _getJSON($url)
    {
        $api_response = json_decode(self::_get($url));
        return $api_response;
    }

    public static function facebook($url)
    {
        $url = urlencode($url);
        $json = self::_getJSON("http://graph.facebook.com/?id=$url");
        $total = 0;
        if(isset($json->share) && isset($json->share->share_count)){
            $total = (int)$json->share->share_count;
        }
        return $total;
    }

    public static function google($url)
    {
        $url = urlencode($url);
        $res = self::_get("https://plusone.google.com/_/+1/fastbutton?url=$url");

        $count = array();
        if (preg_match('/window\.__SSR\s\=\s\{c:\s(\d+?)\./', $res, $count)) {
            return (int)$count[1];
        }
        return 0;
    }

    public static function pinterest($url)
    {
        $url = urlencode($url);
        $return_data = self::_get("http://api.pinterest.com/v1/urls/count.json?url=$url");
        $json_string = preg_replace('/^receiveCount\((.*)\)$/', "\\1", $return_data);
        $json = json_decode($json_string);
        return isset($json->count) ? (int)$json->count : 0;
    }

    public static function linkedIn($url)
    {
        $url = urlencode($url);
        $json = self::_getJSON("http://www.linkedin.com/countserv/count/share?url=$url&format=json");
        return isset($json->count) ? (int)$json->count : 0;
    }

}