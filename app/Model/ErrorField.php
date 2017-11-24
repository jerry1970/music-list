<?php

namespace Model;


trait ErrorField
{
    /**
     * Error info in a json
     *
     * @var string
     */
    public $errorField = '';

    public function getErrors()
    {
        if (!$this->errorField) {
            return [];
        }
        return json_decode($this->errorField, true);
    }

    public function addError($error)
    {
        $errors = $this->getErrors();
        $errors[] = $error;
        $this->errorField = json_encode($errors);
    }
}
