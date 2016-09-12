<?php
header("Content-Type:text/plain");
require 'bigcommerce.php';
use Bigcommerce\Api\Client as Bigcommerce;
use Bigcommerce\Api\Resources\ProductImage as ProductImage;
use Bigcommerce\Api\Resources\ProductVideo as ProductVideo;

//setup api
include 'bigcommerce-config.php';

if (isset($_POST)) {

	$recID = $_POST['recid'];
	unset($_POST['recid']);
	$filenames = $_POST['filenames'];
	unset($_POST['filenames']);
	$image_descriptions = $_POST['image_descriptions'];
	unset($_POST['image_descriptions']);
	$video_urls = isset($_POST['videos']) ? $_POST['videos'] : NULL;
	unset($_POST['videos']);

	//check for free shipping
	$freeship = isset($_POST['freeship']) ? $_POST['freeship'] : NULL;
	if ($freeship == "True") {
		$_POST['is_free_shipping'] = true;
	} elseif ($freeship == "False") {
		$_POST['is_free_shipping'] = false;
	}
	unset($_POST['freeship']);

	//check for is price hidden
	$price_hidden = isset($_POST['is_price_hidden']) ? $_POST['is_price_hidden'] : NULL;
	if ($price_hidden == "True") {
		$_POST['is_price_hidden'] = true;
	} elseif ($price_hidden == "False") {
		$_POST['is_price_hidden'] = false;
	}

	$_POST['is_visible'] = true;


	$response = Bigcommerce::createProduct($_POST);

	if (!$response) {
		echo "Error: Product was not created\n";
		print_r($_POST);
	} else {
		$product_id = $response->id;
		$product_url = $response->custom_url;
		echo $product_id."\n";
		echo $product_url."\n";

		$i = 0;
		foreach ($filenames as $filename) {
			$url = "http://35.9.51.36/lister_images/RC_Data_FMS/Lister_Images/lister_images/".$filename;
			$image = new ProductImage();
			$image->product_id = $product_id;
			$image->image_file = $url;
			$image->description = $image_descriptions[$i];
			$imageResponse = $image->create();

			if (!$imageResponse) {
				$error = "error\n";
			} else {
				echo $imageResponse->image_file."\n";
			}

			$i++;
		}

		if ($video_urls) {
			$i = 0;
			foreach ($video_urls as $video_url) {
				$video = new ProductVideo();
				$video->product_id = $product_id;
				$video->url = $video_url;
				$videoResponse = $video->create();

				if (!$videoResponse) {
					$error = "error\n";
				} else {
					echo $videoResponse->url."\n";
				}

				$i++;
			}
		}

		if (isset($error)) {
			echo $error;
			$hideResponse = Bigcommerce::updateProduct($product_id, array("is_visible" => false));
		}
	}
}

?>
