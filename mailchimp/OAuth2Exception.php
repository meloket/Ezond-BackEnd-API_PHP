<?php

/**
 * OAuth2.0 draft v10 exception handling.
 *
 * @author Originally written by Naitik Shah <naitik@facebook.com>.
 * @author Update to draft v10 by Edison Wong <hswong3i@pantarei-design.com>.
 *
 * @sa <a href="https://github.com/facebook/php-sdk">Facebook PHP SDK</a>.
 */
class OAuth2Exception extends Exception
{

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    /**
     * Make a new API Exception with the given result.
     *
     * @param $result
     *   The result from the API server.
     */
    public function __construct($result)
    {
        $this->result = $result;

        $code = isset($result['code']) ? $result['code'] : 0;

        if (isset($result['error'])) {
            // OAuth 2.0 Draft 10 style
            $message = $result['error'];
        } elseif (isset($result['message'])) {
            // cURL style
            $message = $result['message'];
        } else {
            $message = 'Unknown Error. Check getResult()';
        }

        parent::__construct($message, $code);
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @returns
     *   The result from the API server.
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns the associated type for the error. This will default to
     * 'Exception' when a type is not available.
     *
     * @return
     *   The type for the error.
     */
    public function getType()
    {
        if (isset($this->result['error'])) {
            $message = $this->result['error'];
            if (is_string($message)) {
                // OAuth 2.0 Draft 10 style
                return $message;
            }
        }
        return 'Exception';
    }

    /**
     * To make debugging easier.
     *
     * @returns
     *   The string representation of the error.
     */
    public function __toString()
    {
        $str = $this->getType() . ': ';
        if ($this->code != 0) {
            $str .= $this->code . ': ';
        }
        return $str . $this->message;
    }
}
