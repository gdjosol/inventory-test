<?php


/*
    A Class to categorize each product as an object in the project's instance.
*/
class Product
{
    public $id;
    public $name;
    public $unitsSold;
    public $purchasedAndPending;
    public $purchasedAndReceived;
    public $currentStockLevel;
    public $poRemainingDeliveryDays;

    function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->unitsSold = 0;
        $this->purchasedAndPending = 0;
        $this->purchasedAndReceived = 0;
        $this->currentStockLevel = 20;
        $this->poRemainingDeliveryDays = -1;
    }

    /* 
        This static function generates an array of class Product from the values indicated in Products.
    */
    public static function generate()
    {
        $products = new Products();
        $reflectionProducts = new ReflectionClass($products);
        $arr = $reflectionProducts->getReflectionConstants();
        $productArray = array();
        foreach ($arr as $key => $value) {
            $productArray[$key] = new Product($key, $value->name);
        }
        return $productArray;
    }

    /*
        This functions returns the index of an instance in an array of the same id
    */
    public static function search($products, $id)
    {
        $index = -1;
        foreach ($products as $key => $product) {
            if ($product->id == $id) {
                $index = $key;
            }
        }
        return $index;
    }
}