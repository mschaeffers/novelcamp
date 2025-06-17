<?php

namespace App\DataModel\DataAccessAttributes;

use Attribute;

#[Attribute]
class FieldType
{
    // Database Assigned Identity Field - Field gets ignored during Insert 
    const IDENTITY = "Identity";
    // Natural Primary Key - Field gets included during Insert 
    const KEY = "Key";
    // Field gets ignored during Insert and Update operations
    const SKIP = "Skip";

    public $Value;

    public function __construct(string $value)
    {
        $this->Value = $value;
    }
}

#[\Attribute]
class FieldName
{
    public $Value;
    public function __construct(string $value)
    {
        $this->Value = $value;
    }
}

#[Attribute]
class TableName
{
    public $Value;

    public function __construct(string $value)
    {
        $this->Value = $value;
    }
}