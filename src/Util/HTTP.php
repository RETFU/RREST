<?php


namespace RREST\Util;

class HTTP
{
    /**
     * Return the protocol (http or https) used.
     *
     * @return string
     */
    public static function getProtocol()
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
            !empty($_SERVER['HTTP_X_FORWARDED_SSL']) &&
            $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            $isSecure = true;
        }

        return $isSecure ? 'HTTPS' : 'HTTP';
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public static function getHeader($name)
    {
        $name = strtolower($name);
        $headers = array_change_key_case(\getallheaders(), CASE_LOWER);
        if (empty($headers)) {
            $headers = array_change_key_case($_SERVER, CASE_LOWER);
        }
        if (isset($headers[$name])) {
            return $headers[$name];
        }

        return null;
    }
}