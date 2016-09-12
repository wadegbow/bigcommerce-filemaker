<?php
header("Content-Type:text/plain");
require 'bigcommerce.php';
use Bigcommerce\Api\Client as Bigcommerce;
use Bigcommerce\Api\Resources\ProductImage as ProductImage;
use Bigcommerce\Api\Resources\ProductVideo as ProductVideo;

//setup api
include 'bigcommerce-config.php';

if (isset($_POST)) {

	$product_id = $_POST['ProductID'];
	unset($_POST['ProductID']);
	$recID = $_POST['recid'];
	unset($_POST['recid']);
	$filenames = isset($_POST['filenames']) ? $_POST['filenames'] : NULL;
	unset($_POST['filenames']);
	$image_descriptions = isset($_POST['image_descriptions']) ? $_POST['image_descriptions'] : NULL;
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

	//check for free shipping
	$price_hidden = isset($_POST['is_price_hidden']) ? $_POST['is_price_hidden'] : NULL;
	if ($price_hidden == "True") {
		$_POST['is_price_hidden'] = true;
	} elseif ($price_hidden == "False") {
		$_POST['is_price_hidden'] = false;
	}

	$_POST['is_visible'] = true;

	$response = Bigcommerce::updateProduct($product_id, $_POST);

	if (!$response) {
		echo "Error: Product was not updated\n";

		print_r($_POST);
	} else {
		echo "Success: Product was updated\n";
		$product_url = $response->custom_url;
		echo $product_url."\n";

		//only process images if filenames are posted
		if ($filenames) {
			//check to see if the product already has images
			$product_images = Bigcommerce::getProductImages($product_id);

			//if the product being updated has images, we are going to delete them
			if ($product_images) {
				foreach ($product_images as $image) {
					$image_delete = Bigcommerce::deleteResource('/products/' . $product_id . '/images/' . $image->id);
				}
			}

			$i = 0;
			foreach ($filenames as $filename) {
				$url = "http://35.9.51.36/lister_images/RC_Data_FMS/Lister_Images/lister_images/".$filename;
				$image = new ProductImage();
				$image->product_id = $product_id;
				$image->image_file = $url;
				$image->description = $image_descriptions[$i];
				$imageResponse = $image->create();

				if (!$imageResponse) {
					$error = "image error";
				} else {
					echo $imageResponse->image_file."\n";
				}

				$i++;
			}

			if (isset($error)) {
				echo $error;

				$hideResponse = Bigcommerce::updateProduct($product_id, array("is_visible" => false));
			} else {
				echo "images updated successfully\n";
			}
		}

		if ($video_urls) {
			//check to see if the product already has videos
			$product_videos = Bigcommerce::getProductVideos($product_id);

			//if the product being updated has video, we are going to delete them
			if ($product_videos) {
				foreach ($product_videos as $product_video) {
					$video_delete = Bigcommerce::deleteResource('/products/' . $product_id . '/videos/' . $product_video->id);
				}
			}

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
	}
}

?>
