<?php

class AuditTrail{
    public $weekday;
    public $product;
    public $change;
    
    function __construct($weekday, $product, $change)
    {
        $this->weekday = $weekday;
        $this->product = $product;
        $this->change = $change;
    }
}

class AuditLog{
    public $attribute;
    public $oldValue;
    public $newValue;
    
    function __construct($attribute, $oldValue, $newValue)
    {
        $this->attribute = $attribute;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
}