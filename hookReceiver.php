<?php
require_once('..\filemaker_api\server_data_pos.php');
require_once('..\filemaker_api\FileMaker.php');
require 'bigcommerce.php';
use Bigcommerce\Api\Client as Bigcommerce;

//get payload from bigcommerce
if (file_get_contents('php://input')) {
    $json = file_get_contents('php://input');
}

//setup api
include 'bigcommerce-config.php';

//connect to filemaker database
$fm = new FileMaker(FM_DATABASE, FM_IP, FM_USERNAME, FM_PASSWORD);

if(isset($json)) {
  $payload = json_decode($json);

    $toDB['OnlineOrderNumber'] = $payload->data->id;

    //get the order details
    $orderDetails = Bigcommerce::getOrder($payload->data->id);

    if ($orderDetails) {
        $status = $orderDetails->status;
        $phase01 = false;

        if ($status == 'Completed' || $status == 'Awaiting Payment' ) {

          $toOrderDB['OrderID'] = $orderDetails->id;
          $toOrderDB['Status'] = $orderDetails->status;
          $toOrderDB['PaymentProviderID'] = $orderDetails->payment_provider_id;
          $toOrderDB['PaymentMethod'] = $orderDetails->payment_method;		  
          $toOrderDB['ShippingCostExTax'] = $orderDetails->shipping_cost_ex_tax;
          $toOrderDB['ShippingCostTax'] = $orderDetails->shipping_cost_tax;
          $toOrderDB['TotalExTax'] = $orderDetails->total_ex_tax;
          $toOrderDB['TotalTax'] = $orderDetails->total_tax;
          $toOrderDB['CouponDiscount'] = $orderDetails->coupon_discount;

          $newAdd = $fm->newAddCommand('Util_ListingProductOrder', $toOrderDB);
          $orderResult = $newAdd->execute();

          if (FileMaker::isError($orderResult)) {
                    echo "Phase 01 - Failed\n";
                    echo $orderResult->getMessage();
                    $phase01 = false;
          } else {
                    echo "Phase 01 - Success\n";
                    $phase01 = true;
                }

        }


        //check the status to see if order requires further action
        if ($phase01 && $status == 'Completed' || $status == 'Awaiting Payment' ) {
            $find = $fm->newFindCommand('Util_ListingProduct');

            //$toDB['Payment_Provider_ID'] = $orderDetails->payment_provider_id;

            foreach($orderDetails->products as $product) {
                $productID = $product->product_id;

                //find with store's product id
                $find->addFindCriterion('_Online_Product_ID', $productID);
                $result = $find->execute();

                if (FileMaker::isError($result)) {
                    echo "Phase 02 - Failed\n";
                    echo $result->getMessage();
                } else {
                    echo "Phase 02 - Success\n";


                    $records = $result->getRecords();
                    $productPK = $records[0]->getField('zz__ID');

                    //setup table for filemaker import
                    $toDB['zz_ListingProductID'] = $productPK;
                    $toDB['OnlineProductNumber'] = $productID;
                    $toDB['Payment_Provider_ID'] = $orderDetails->payment_provider_id;
					$toOrderDB['PaymentMethod'] = $orderDetails->payment_method;							
                    $toDB['ListingQuantity'] = -1 * $product->quantity;
                    $toDB['ListingQuantityDate'] = date("n/d/Y");
                    $toDB['Sold_Amount'] = $product->quantity * $product->price_inc_tax;

                    //import table
                    $newAdd = $fm->newAddCommand('webListingQuantityHistory', $toDB);
                    $result = $newAdd->execute();

                    if (FileMaker::isError($result)) {
                        echo "Phase 03 - Failed\n";
                        echo $result->getMessage();
                    } else {
                        echo "Phase 03 - Success\n";
                    }
                }
            }
        }
    }
}

?>