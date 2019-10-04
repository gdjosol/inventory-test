<?php
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

//Files created under development
include './Product.php';
include './Audit.php';

//Mandated Files to integrate
include '../InventoryInterface.php';
include '../OrderProcessorInterface.php';
include '../Products.php';
include '../ProductsPurchasedInterface.php';
include '../ProductsSoldInterface.php';

/*
    This class implements InventoryInterface that will provide the current stock level of each product.
*/
class Inventory implements InventoryInterface
{
    private $products = array();

    function __construct($products)
    {
        $this->products = $products;
    }

    public function getStockLevel(int $productId): int
    {
        $currentStockLevel = -1;
        $index = Product::search($this->products, $productId);
        if ($index >= 0) {
            $currentStockLevel = $this->products[$index]->currentStockLevel;
        }
        return $currentStockLevel;
    }
}

/*
    This class implements ProductsSold that will provide the units sold of each product.
*/
class ProductsSold implements ProductsSoldInterface
{
    private $products = array();

    function __construct($products)
    {
        $this->products = $products;
    }

    public function getSoldTotal(int $productId): int
    {
        $unitsSold = -1;
        $index = Product::search($this->products, $productId);
        if ($index >= 0) {
            $unitsSold = $this->products[$index]->unitsSold;
        }
        return $unitsSold;
    }
}

/*
    This class implements ProductsPurchasedInterface that will provide the units sold of each product.
*/
class ProductsPurchased implements ProductsPurchasedInterface
{
    private $products = array();

    function __construct($products)
    {
        $this->products = $products;
    }
    public function getPurchasedReceivedTotal(int $productId): int
    {
        $purchasedAndReceived = -1;
        $index = Product::search($this->products, $productId);
        if ($index >= 0) {
            $purchasedAndReceived = $this->products[$index]->purchasedAndReceived;
        }
        return $purchasedAndReceived;
    }

    public function getPurchasedPendingTotal(int $productId): int
    {
        $purchasedPendingTotal = -1;
        $index = Product::search($this->products, $productId);
        if ($index >= 0) {
            $purchasedPendingTotal = $this->products[$index]->purchasedAndPending;
        }
        return $purchasedPendingTotal;
    }
}


/*
    This class implements OrderProcessorInterface that will moderate all orders specified within the json file.
*/
class OrderProcessor implements OrderProcessorInterface
{
    public $products = array();
    public $auditTrail = array();
    private $sevenDaysData = array();
    public $daysToProcess;
    function getProducts()
    {
        return $this->products;
    }

    function __construct($products, $days)
    {
        $this->products = $products;
        $this->daysToProcess = $days;
    }

    public function processFromJson(string $filePath): void
    {
        $inventory = new Inventory($this->products);
        $productSold = new ProductsSold($this->products);
        $purchaseOrder = new ProductsPurchased($this->products);
        $weekdays = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
        $newline = "\n";
        /*
            stock < 10 = create PO for 20 units
            PO = 2days after posting 
        */
        $this->sevenDaysData = json_decode(file_get_contents($filePath));

        foreach ($this->sevenDaysData as $weekday => $dayData) {
            //dayData = all orders per day

            if ($weekday < $this->daysToProcess) {
                //echo "Day " . ($weekday + 1) . " - " . $weekdays[$weekday] . " has started!--------------------------------------------------------------" . $newline;
                foreach ($dayData as $order) {
                    //Order per day
                    foreach ($order as $item => $quantitiesOrdered) {
                        $index = Product::search($this->products, $item);
                        if ($index >= 0) {
                            $auditLog=array();
                            $productId = $this->products[$index]->id;
                            $poDays = $this->products[$index]->poCreatedStatus;
                            $preCurrentStockLevel = $inventory->getStockLevel($productId);
                            $prePurchasedAndPending = $purchaseOrder->getPurchasedPendingTotal($productId);
                            $prePurchasedAndReceived = $purchaseOrder->getPurchasedReceivedTotal($productId);
                            $preProductSold = $productSold->getSoldTotal($productId);

                            //Check if there is any PO Delivery
                            if ($poDays > 0) {
                                $this->products[$index]->poCreatedStatus = ($poDays - 1);
                            } else if ($poDays === 0) {

                                $this->products[$index]->purchasedAndPending = ($this->products[$index]->purchasedAndPending - 20);
                                $this->products[$index]->purchasedAndReceived = ($this->products[$index]->purchasedAndReceived + 20);
                                $this->products[$index]->currentStockLevel = ($this->products[$index]->currentStockLevel + 20);
                                $this->products[$index]->poCreatedStatus = (-1);

                            }

                            //Calculate New Stock based on Order
                            if($preCurrentStockLevel>=$quantitiesOrdered){
                                $this->products[$index]->currentStockLevel = ($this->products[$index]->currentStockLevel - $quantitiesOrdered); //Setting new reduced stock
                                $newStock = $inventory->getStockLevel($productId);
                            }else{
                                //not enough units left to sell
                            }
                            
                            

                            //Update Products Sold
                            $this->products[$index]->unitsSold = ($this->products[$index]->unitsSold + $quantitiesOrdered); //Setting new UnitsSold

                            //Check if PO needs to be created 
                            if ($newStock < 10 && $poDays == -1) {
                                //Create PO
                                $this->products[$index]->poCreatedStatus = (2);
                                $this->products[$index]->purchasedAndPending = ($this->products[$index]->purchasedAndPending + 20);
                                //echo "--Create PO for: " . $this->products[$index]->name . $newline;
                                //echo "--Total Pending for " . $this->products[$index]->name . ": " . $purchaseOrder->getPurchasedPendingTotal($productId) . $newline;
                            }

                            $postCurrentStockLevel = $inventory->getStockLevel($productId);
                            $postpurchasedAndPending = $purchaseOrder->getPurchasedPendingTotal($productId);
                            $postpurchasedAndReceived = $purchaseOrder->getPurchasedReceivedTotal($productId);
                            $postProductSold = $productSold->getSoldTotal($productId);

                            if($prePurchasedAndPending!=$postpurchasedAndPending){
                                array_push($auditLog,new AuditLog('PurchasedAndPending',$prePurchasedAndPending,$postpurchasedAndPending));
                            }
                            if($prePurchasedAndReceived!=$postpurchasedAndReceived){
                                array_push($auditLog,new AuditLog('PurchasedAndReceived',$prePurchasedAndReceived,$postpurchasedAndReceived));
                            }
                            if($preCurrentStockLevel!=$postCurrentStockLevel){
                                array_push($auditLog,new AuditLog('CurrentStockLevel',$preCurrentStockLevel,$postCurrentStockLevel));
                            }
                            if($preProductSold!=$postProductSold){
                                array_push($auditLog,new AuditLog('ProductSold',$preProductSold,$postProductSold));
                            }
                            array_push($this->auditTrail,new AuditTrail($weekday,$this->products[$index],$auditLog));
                        }
                    }
                }
            } else {
                break;
            }
        }
    }
}



/*
    Main Function
*/
function simulateSevenDays()
{
    $daysToProcess = 7;

    if (isset($_GET['days'])) {
        $daysToProcess = $_GET['days'];
    }

    $orderProcessor = new OrderProcessor(Product::generate(), $daysToProcess);
    $orderProcessor->processFromJson('../../orders-sample.json');

    echo json_encode($orderProcessor);
}

//Function Call
simulateSevenDays();
