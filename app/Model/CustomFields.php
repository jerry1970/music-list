<?php

namespace Model;


trait CustomFields
{
    /**
     * All other data in a json
     *
     * @var string
     */
    public $customFields;

    public function setFields($data = [])
    {
        $this->customFields = json_encode($data);
    }

    public function setField($key, $value, $isArray = false)
    {
        $fields = $this->getFields();
        if ($isArray) {
            $fields[$key][] = $value;
        } else {
            $fields[$key] = $value;
        }
        $this->setFields($fields);
        return $this;
    }

    public function getFields()
    {
        $fields = json_decode($this->customFields, true);
        if (!is_array($fields)) {
            $this->setFields([]);
        }
        return json_decode($this->customFields, true);
    }

    public function getField($key)
    {
        if (!is_numeric($key) && !is_string($key)) {
            print 'getField() cannot accept: ' . var_export($key, true);
        }
        $fields = $this->getFields();
        if ($fields && is_array($fields) && array_key_exists($key, $fields)) {
            return $fields[$key];
        }
        return null;
    }

}
