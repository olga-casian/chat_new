<?php
class Anti_Exception extends RuntimeException
{
    public function __construct($message, $code = 0, array $values = null)
    {
        if (!empty($values)) {
            $message = vsprintf($message, $values);
        }
        parent::__construct($message, $code);
    }
}