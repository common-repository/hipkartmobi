<?php 
/*
***********************************************************************************************************
***********************************************************************************************************
********** Registering Ajax functions which are called from Plugin's Page during User's Actions. **********
***********************************************************************************************************
***********************************************************************************************************
*/


/*
* @HipStoreLoginData is called via Ajax and post data to hipkart to get store linked with hipKart store.
*/
function HipStoreLoginData(){
	global $woocommerce;
	$WocommerceCurrency = get_woocommerce_currency( $currency );
	
	$url = 'https://www.hipkart.com/hipCommerce/login';
	$data = array();
	$username = $_POST['Username'];
	if(strpos($username,'@') !== false){
		$data['username'] 	= sanitize_email($_POST['Username']);
	}else{
		$data['username'] 	= sanitize_text_field($_POST['Username']);
	}
	$data['password'] 	= sanitize_text_field($_POST['UserPassword']);
	$data['domain'] 	= sanitize_text_field($_POST['domain']);
	$data['StoreCurrency'] = sanitize_text_field($WocommerceCurrency);
	if($data['username'] != '' && $data['password'] != '' && $data['domain'] != ''){
		$response = Hipkrt_callCurl($url,$data);
		$result = (array)json_decode($response);
		$Settings = array('0'=>'PUSH','1'=>'SYNC');
		
		if($result['CODE'] == 100){
			$storeId = $result['RESPONSE']->storeId;
			$storeName = $result['RESPONSE']->storeName;
			$storeURL = $result['RESPONSE']->storeUrl;
			$hipCommerceKey = $result['RESPONSE']->hipCommerceKey;
			if($storeId != ''){
				update_option( 'hipCommerceSettingsVal', json_encode($Settings), '', '' );
				update_option( 'hipCommerceKey', $hipCommerceKey, '', '' );
				update_option( 'hipCommerceStoreID', $storeId, '', '' );
				update_option( 'hipCommerceStoreName', $storeName, '', '' );
				update_option( 'hipCommerceStoreURL', $storeURL, '', '' );
			}
		}
		echo $response;
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_HipStoreLoginData", "HipStoreLoginData");
add_action("wp_ajax_nopriv_HipStoreLoginData", "HipStoreLoginData");

/*
* @SaveHipkartSettings is used to save user's settings.
*/
function SaveHipkartSettings(){
	$SettingsVal = sanitize_text_field($_POST['SettingsVal']);
	$SettingsVal = json_encode($SettingsVal);
	if(!empty($SettingsVal) && $SettingsVal != 'null' && $SettingsVal != null){
		update_option( 'hipCommerceSettingsVal',$SettingsVal, '', '' );
	}else{
		update_option( 'hipCommerceSettingsVal',0, '', '' );
	}
	echo 1;
	die;
}
add_action("wp_ajax_SaveHipkartSettings", "SaveHipkartSettings");
add_action("wp_ajax_nopriv_SaveHipkartSettings", "SaveHipkartSettings");

/*
* @UnlinkHipkartStore is used to unlink user's hipkart store.
*/
function UnlinkHipkartStore(){
	delete_option('hipCommerceKey');
	delete_option('hipCommerceStoreID');
	delete_option('hipCommerceStoreName');
	$url = 'https://www.hipkart.com/hipCommerce/UnlinkHipkartStore';
	
	$data = sanitize_text_field($_POST['hipCommerceKey']);
	$response = Hipkrt_callCurl($url,$data);
	echo 1;
	die;
}
add_action("wp_ajax_UnlinkHipkartStore", "UnlinkHipkartStore");
add_action("wp_ajax_nopriv_UnlinkHipkartStore", "UnlinkHipkartStore");

/*
* @GetHipCategories fetch categories from hipKart.
*/
function GetHipCategories(){
	$url = 'https://www.hipkart.com/hipCommerce/gethipcategories';
	
	$data = sanitize_text_field($_POST['hipCommerceKey']);
	if($data != ''){
		$response = Hipkrt_callCurl($url,$data);
		$result = (array)json_decode($response);
		$AllCategories = array();
		$i = 0;
		if($result['CODE'] == 100){
			$categories = $result['RESPONSE']->categories;
			foreach($categories as $cat){
				$AllCategories[$i]['id'] = $cat->category->id;
				$AllCategories[$i]['name'] = $cat->category->name;
				$i++;
			}
		}
		echo json_encode($AllCategories);
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_GetHipCategories", "GetHipCategories");
add_action("wp_ajax_nopriv_GetHipCategories", "GetHipCategories");


/*
* @GetHipSubCategories fetch Sub categories of a Main category from hipKart.
*/
function GetHipSubCategories(){
	$url = 'https://www.hipkart.com/hipCommerce/gethipsubcategories';
	$data['hipCommerceKey'] = sanitize_text_field($_POST['hipCommerceKey']);
	$data['categoryid'] = sanitize_text_field($_POST['categoryid']);
	if($data['hipCommerceKey'] != '' && $data['categoryid'] != ''){
		$response = Hipkrt_callCurl($url,$data);
		$result = (array)json_decode($response);
		echo json_encode($result);
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_GetHipSubCategories", "GetHipSubCategories");
add_action("wp_ajax_nopriv_GetHipSubCategories", "GetHipSubCategories");

/*
* @GetHipThirdSubCategories fetch Sub categories of a Sub category from hipKart.
*/
function GetHipThirdSubCategories(){
	$url = 'https://www.hipkart.com/hipCommerce/gethipsubcategories';
	$data['hipCommerceKey'] = sanitize_text_field($_POST['hipCommerceKey']);
	$data['categoryid'] = sanitize_text_field($_POST['categoryid']);
	if($data['hipCommerceKey'] != '' && $data['categoryid'] != ''){
		$response = Hipkrt_callCurl($url,$data);
		$result = (array)json_decode($response);
		echo json_encode($result);
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_GetHipThirdSubCategories", "GetHipThirdSubCategories");
add_action("wp_ajax_nopriv_GetHipThirdSubCategories", "GetHipThirdSubCategories");

/*
* @SaveHipcommerceCategory saves linking of WooCommerce categories and hipKart categories.
*/
function SaveHipcommerceCategory(){
	$option_name 		= 'HipkartCatgs';
	$hipCommerceKey 	= sanitize_text_field($_POST['hipCommerceKey']);
	$WocommerceProCat 	= sanitize_text_field($_POST['WocommerceProCat']);
	$HipkartMainCat 	= sanitize_text_field($_POST['HipkartMainCat']);
	$HipkartSubCat 		= sanitize_text_field($_POST['HipkartSubCat']);
	$HipkartSubCat2 	= sanitize_text_field($_POST['HipkartSubCat2']);
	if($hipCommerceKey != '' && $WocommerceProCat != ''){
		if($HipkartMainCat == '' || $HipkartMainCat == null){
			$HipkartMainCat = 0;
		}
		if($HipkartSubCat == '' || $HipkartSubCat == null){
			$HipkartSubCat = 0;
		}
		if($HipkartSubCat2 == '' || $HipkartSubCat2 == null){
			$HipkartSubCat2 = 0;
		}
		$CategoryArr = array();
		$NewOptionAdded = array();
		if (get_option( $option_name ) !== false) {
			$OptionData = get_option($option_name);
			$CategoryArr = json_decode($OptionData,true);
		}
		$CategoryArr[$WocommerceProCat] = array();
		$CategoryArr[$WocommerceProCat][] = $HipkartMainCat;
		$CategoryArr[$WocommerceProCat][] = $HipkartSubCat;
		$CategoryArr[$WocommerceProCat][] = $HipkartSubCat2;
		
		$updatedoption = update_option( $option_name, json_encode($CategoryArr));
		echo $updatedoption;
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_SaveHipcommerceCategory", "SaveHipcommerceCategory");
add_action("wp_ajax_nopriv_SaveHipcommerceCategory", "SaveHipcommerceCategory");

/*
* @DeleteHipcommerceCategory unlink WooCommerce category with hipKart category.
*/
function DeleteHipcommerceCategory(){
	$option_name		= 'HipkartCatgs';
	$WocommerceCatID	= sanitize_text_field($_POST['WocommerceCatID']);
	if($WocommerceCatID != ''){
		if (get_option( $option_name ) !== false) {
			$OptionData = get_option($option_name);
			$CategoryArr = json_decode($OptionData,true);
		}
		if (array_key_exists($WocommerceCatID,$CategoryArr)){
			unset($CategoryArr[$WocommerceCatID]);
		}
		if(empty($CategoryArr)){
			delete_option($option_name);
		}else{
			$updatedoption = update_option( $option_name, json_encode($CategoryArr));
		}
		echo $updatedoption;
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_DeleteHipcommerceCategory", "DeleteHipcommerceCategory");
add_action("wp_ajax_nopriv_DeleteHipcommerceCategory", "DeleteHipcommerceCategory");

/*
* @GETHIPKARTAPPINFO Retreive information of Store App of linked store.
*/
function GETHIPKARTAPPINFO(){
	$url = 'https://www.hipkart.com/hipCommerce/getStoreAppInfo';
	$data = array();
	$data['HipcommerceKey'] = sanitize_text_field($_POST['HipcommerceKey']);
	$data['storeid'] 		= sanitize_text_field($_POST['hipCommerceStoreID']);
	if($data['HipcommerceKey'] != '' && $data['storeid'] != ''){
		$response 	= Hipkrt_callCurl($url,$data);
		$result 	= (array)json_decode($response);
		if($result['CODE'] == 100){
			echo $response;
		}else{
			echo 0;
		}
	}else{
		echo 0;
	}
	die;
}
add_action("wp_ajax_GETHIPKARTAPPINFO", "GETHIPKARTAPPINFO");
add_action("wp_ajax_nopriv_GETHIPKARTAPPINFO", "GETHIPKARTAPPINFO");

/*
***********************************************************************************************************
***********************************************************************************************************
********************************* Registering of Ajax functions Completed. ********************************
***********************************************************************************************************
***********************************************************************************************************
*/


/*
*
* @AddProductToHip is a hook when user add/update any product to his store and that will be pushed to his hipKart store be listed or update on his Mobile App. Same hook is used when any Order is updated to push status of that order to hipKart.
*
*
* This will Push Product to hipKart only if user has enabled the option from settings.
*
*/

add_action('save_post', 'AddProductToHip',10,1);
function AddProductToHip($post_id){
	$hipCommerceSettingsVal = json_decode(get_option('hipCommerceSettingsVal'));
	if('product' == sanitize_text_field($_POST['post_type'])){
		if(in_array('PUSH',$hipCommerceSettingsVal)){
			global $product;
			$_product = wc_get_product( $post_id );
			$PRiCE = '';
			if($_product->is_type( 'simple' )){
				$PRiCE = $_product->price;
			}else if($_product->is_type( 'variable' )){
				$available_variations = $_product->get_available_variations();
				if(!empty($available_variations)){
					foreach($available_variations as $key=>$val){ 
						if($PRiCE == ''){
							$variation_id = $available_variations[$key]['variation_id'];
							$variable_product1 = new WC_Product_Variation( $variation_id );
							$regular_price = $variable_product1 ->regular_price;
							$sales_price = $variable_product1 ->sale_price;
							if($sales_price != ''){
								$PRiCE = $sales_price;
							}else if($regular_price != ''){
								$PRiCE = $regular_price;
							}
						}
					}
				}
			}
			
			if ($PRiCE!=''){
				$option_name 	= 'hipproduct_'.$post_id;
				$HIP_ProductId 	= get_option($option_name);
				$myPost = get_post($post_id);
				if($HIP_ProductId == '' || $HIP_ProductId == null || $HIP_ProductId == 'undefined'){
					$PostType = 1; // Insert post First Time
					$HIP_ProductId = 0;
				}else{
					$PostType = 0; //// Updated Post 
				}
				
				$finalProduct = array();
				$url = 'https://www.hipkart.com/hipCommerce/pushProductToHip/';
				$hipCommerceKey = get_option('hipCommerceKey');
				$finalProduct['productId']= $post_id;
				$finalProduct['hipCommerceKey']= $hipCommerceKey;
				$finalProduct['insert']= $PostType;
				$finalProduct['hipproductId']= $HIP_ProductId;
				$hipproductId = Hipkrt_callCurl($url,$finalProduct);
				update_option($option_name,$hipproductId);
			}
		}
	}elseif('shop_order' == sanitize_text_field($_POST['post_type'])){
		$url = 'https://www.hipkart.com/hipCommerce/pushOrderStatusToHip/';
		$hipCommerceKey = get_option('hipCommerceKey');
		$finalProduct['orderId']= $post_id;
		$order = new WC_Order( $post_id );
		$order_status = $order->get_status();    
		$finalProduct['hipCommerceKey']= $hipCommerceKey;
		$finalProduct['order_status']= $order_status;
		$RESPONSE = Hipkrt_callCurl($url,$finalProduct);
		//echo '<pre>';print_r($RESPONSE);die;
	}
}


/*
* @Hipkrt_callCurl basic function to make cURL Calls.
*/
function Hipkrt_callCurl($url,$data){
	$args = array(
		'body' => $data,
		'timeout' => '30',
		'redirection' => '30',
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(),
		'cookies' => array()
	);
 
	$response = wp_remote_post( $url, $args );
	return $response['body'];
	
}


/*
* @Hipkrt_my_sort basic function for sorting.
*/
function Hipkrt_my_sort($a,$b){
	return $b['ShippingCost'] - $a['ShippingCost'];
}

/*
* @Hipkrt_freeshippingCoupons to get all coupons which are enabled for Free Shipping.
*/
function Hipkrt_freeshippingCoupons(){
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'asc',
		'post_type'        => 'shop_coupon',
		'post_status'      => 'publish',
	);
		
	$Allcoupons = get_posts( $args );
	$finalCoupons = array();
	foreach($Allcoupons as $Coupn){
		$Detailcoupon = new WC_Coupon($Coupn->post_title);
		$finalCoupon = array();
		if($Detailcoupon->get_free_shipping() == '1'){
			$finalCoupon['code'] = $Detailcoupon->get_code();
			$finalCoupons[] = $finalCoupon;
		}
	}
	return $finalCoupons;
}


/*
* @Hipkrt_GetShippingDetails fetches shipping cost information of any product by passing Product id into parameters.
*/
function Hipkrt_GetShippingDetails($ProductID){
	$product = wc_get_product($ProductID);
	$shipping_class_id = $product->get_shipping_class_id();
	
	$shipping_class= $product->get_shipping_class();
	
	$delivery_zones = WC_Shipping_Zones::get_zones();
	$AllZones = array();
	$j = 0;
	foreach((array) $delivery_zones as $key => $the_zone ) {
		$AllZones[$j]['id'] = $the_zone['id'];
		$AllZones[$j]['zone_name'] = $the_zone['zone_name'];
		$AllZones[$j]['zone_locations'] = $the_zone['zone_locations'];
		$AllZones[$j]['zone_id'] = $the_zone['zone_id'];
		$z = 0;
		foreach($the_zone['shipping_methods'] as $shipping_methods){
			$shippingMTH[$z]['id'] = $shipping_methods->id;
			$shippingMTH[$z]['method_title'] = $shipping_methods->method_title;
			$shippingMTH[$z]['instance_settings'] = $shipping_methods->instance_settings;
			$shippingMTH[$z]['instance_settings']['enabled'] = $shipping_methods->enabled;
			$z++;
		}
		$AllZones[$j]['shipping_methods'] = $shippingMTH;
		$j++;
	}
	$AllShippingClasses = WC()->shipping->get_shipping_classes();
	$FinalShippingClass = array();
	$i = 0;
	foreach($AllShippingClasses as $ShipClass){
		$FinalShippingClass[$i]['termid'] = $ShipClass->term_id;
		$FinalShippingClass[$i]['name'] = $ShipClass->name;
		$FinalShippingClass[$i]['slug'] = $ShipClass->slug;
		$FinalShippingClass[$i]['term_taxonomy_id'] = $ShipClass->term_taxonomy_id;
		$i++;
	}
	$shipping_methods = WC()->shipping->get_shipping_methods();
	
	$AllShippingMTHD = array();
	$h = 0;
	foreach($shipping_methods as $ShipMthds){
		$AllShippingMTHD[$h]['id'] = $ShipMthds->id;
		$AllShippingMTHD[$h]['title'] = $ShipMthds->title;
		$AllShippingMTHD[$h]['instance_settings'] = $ShipMthds->instance_settings;
		$h++;
	}
	$instance_settings = array();
	$GetZone = array();
	$AllRequiredZones = array();
	foreach($AllZones as $Zones){
		$ShippingZoneMethods = $Zones['shipping_methods'];
		foreach($ShippingZoneMethods as $method){
			$shippingMethod = $method['id'];
			if($shippingMethod == 'flat_rate'){
				if(isset($method['instance_settings']['class_cost_'.$shipping_class_id]) && $method['instance_settings']['class_cost_'.$shipping_class_id] != ''){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['class_cost_'.$shipping_class_id];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}else if(isset($method['instance_settings']['no_class_cost']) && $method['instance_settings']['no_class_cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['no_class_cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}else if(isset($method['instance_settings']['cost']) && $method['instance_settings']['cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}
			}else if($shippingMethod == 'free_shipping'){
				if(isset($method['instance_settings'])){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod'] = $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost'] = '0.00';
					$GetZone['requires'] = $method['instance_settings']['requires'];
					if($GetZone['requires'] == '' || $GetZone['requires'] == 'coupon'){
						unset($GetZone['Min_amount']);
					}else{
						$GetZone['Min_amount']= $method['instance_settings']['min_amount'];
					}
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Type']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}else if(isset($method['instance_settings']['no_class_cost']) && $method['instance_settings']['no_class_cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['no_class_cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}else if(isset($method['instance_settings']['cost']) && $method['instance_settings']['cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}
			}else if($shippingMethod == 'local_pickup'){
				if(isset($method['instance_settings'])){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['cost'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					unset($GetZone['Type']);
					$AllRequiredZones[$Zones['zone_name']][]= $GetZone;
				}else if(isset($method['instance_settings']['no_class_cost']) && $method['instance_settings']['no_class_cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['no_class_cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}else if(isset($method['instance_settings']['cost']) && $method['instance_settings']['cost'] != '' && $shipping_class_id == 0){
					$GetZone['zone_name'] = $Zones['zone_name'];
					$GetZone['ShippingMethod']= $method['id'];
					$GetZone['zone_Code'] = $Zones['zone_locations'][0]->code;
					$GetZone['zone_type'] = $Zones['zone_locations'][0]->type;
					$GetZone['ShippingCost']= $method['instance_settings']['cost'];
					$GetZone['Type']= $method['instance_settings']['type'];
					$GetZone['enabled']= $method['instance_settings']['enabled'];
					unset($GetZone['Min_amount']);
					unset($GetZone['requires']);
					$AllRequiredZones[$Zones['zone_name']][] = $GetZone;
				}
			}
		}
	}
	
	$finalShipping = array();
	foreach($AllRequiredZones as $Key => $RequiredShipping){
		$Zonecode = $RequiredShipping[0]['zone_Code'];
		usort($RequiredShipping,'Hipkrt_my_sort');
		$finalShipping[$Zonecode] = $RequiredShipping[0]['ShippingCost'];
	}
	return $finalShipping;
}






?>