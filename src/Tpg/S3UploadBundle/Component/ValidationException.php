<?php
namespace Tpg\S3UploadBundle\Component;

class ValidationException extends \RuntimeException {

    protected $errors;

    public function __construct($errors, $code, \Exception $previous = null) {
        $this->errors = $errors;
        parent::__construct('', $code, $previous);
    }

    public function getErrors() {
        return $this->errors;
    }
}