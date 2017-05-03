<?php

namespace Split;

use Exception;

class JsonValidationException extends Exception
{
    /**
     * @var array
     */
    protected $errors;
    
    /**
     * JsonValidationException constructor.
     *
     * @param string         $message
     * @param array          $errors
     * @param Exception|null $previous
     */
    public function __construct($message, $errors = array(), Exception $previous = null)
    {
        $this->errors = $errors;
        
        parent::__construct($message, 0, $previous);
    }
    
    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
