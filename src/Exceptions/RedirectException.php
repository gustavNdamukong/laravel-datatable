<?php
namespace Gustocoder\LaravelDatatable\Exceptions;

use Exception;

class RedirectException extends Exception
{
    public $redirectTo;

    public function __construct($redirectTo, $message = "", $code = 0, Exception $previous = null)
    { 
        $this->redirectTo = $redirectTo;
        $message = $message = "" ? $message : "Redirecting to: $redirectTo";
        parent::__construct($message, $code, $previous);
    }

    public function getRedirectUrl()
    {
        return $this->redirectTo;
    }
}