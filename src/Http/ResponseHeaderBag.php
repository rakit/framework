<?php namespace Rakit\Framework\Http;

use Rakit\Framework\Bag;

class ResponseHeaderBag extends Bag {

    public function resolveKey($key)
    {
        return strtoupper(str_replace('-', ' ', $key));
    }    

    public function unresolveKey($key)
    {
        $key = $this->resolveKey($key);

        $not_ucwords = array(
            'P3P' => 'P3P', 
            'X XSS PROTECTION' => 'X-XSS-Protection', 
            'X UA COMPATIBLE', 'X-UA-Compatible', 
            'X WEBKIT CSP' => 'X-WebKit-CSP',
            'WWW AUTHENTICATE' => 'WWW-Authenticate'
        );

        if(array_key_exists($key, $not_ucwords)) {
            return $not_ucwords[$key];
        } else {
            $key = ucwords($key);
            $key = str_replace(' ', '-', $key);

            return $key;
        }
    }

}