<?php
/*
* Plugin Name: hipKartMobi
* Plugin URI: https://www.hipkart.mobi/
* Description: hipKartMobi is a powerful plugin that helps you make WooCommerce Store run on your own branded native Mobile Apps. Seamlessly.
* Version: 1.0.0
* Author: hipKart Team
* 
* Text Domain: hipKartMobi
*
* @package hipKartMobi
*/   

/*
* Include function file to main plugin file.
*/  
include( plugin_dir_path( __FILE__ ) . '/Hipfunctions.php');

/*
* Registering stylesheet and scripts files of plugin.
*/
add_action('init', 'Hipkrt_RegisterStyleScripts');

function Hipkrt_RegisterStyleScripts() {
	wp_register_style( 'HIPSTYLE', plugins_url('css/HipStyle.css', __FILE__) );
	wp_enqueue_style( 'HIPSTYLE' );
	wp_register_script('HIPJS', plugins_url( 'js/custom.js', __FILE__ ) );
	wp_enqueue_script('HIPJS');
	wp_localize_script( 'HIPJS', 'Hip_ajax_object',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ),'Domain_url' => site_url(),'hipKartMobiPluginPageURL' => admin_url( 'admin.php?page=hipKartMobi' ) ) );
}


/*
***********************************************************************************************************
***********************************************************************************************************
*************** Let's Register API Routes to Communicate with hipKart ************************************
***********************************************************************************************************
***********************************************************************************************************
*/


/*
* Register API route for bulk products fetching.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_FetchBulkProducts', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_FetchBulkProducts',
    ));
});

/*
* @Hipkrt_FetchBulkProducts is callback for "Hipkrt_FetchBulkProducts" route and used when first time app is setup for store.
*/

function Hipkrt_FetchBulkProducts( WP_REST_Request $request ) {
	$parameters = $request->get_params();
	
	$Limit = $parameters['limit'];
	$offset = $parameters['offset'];
	$ProductIds = json_decode($parameters['ProductIds']);
	
	$PostedhipCommerceKey = $parameters['hipCommerceKey'];
	$hipCommerceKey = get_option('hipCommerceKey');
	$hipCommerceStoreID = get_option('hipCommerceStoreID');
	$newFinalData = array();
	if($PostedhipCommerceKey == $hipCommerceKey && $PostedhipCommerceKey!='' && $hipCommerceKey!=''){
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $Limit,
			'offset' 		 => $offset,
			'orderby'        => 'ID',
			'post__not_in'   => $ProductIds
		);
		$loop = new WP_Query( $args );
		
		while ( $loop->have_posts() ) { 
			$loop->the_post();
		    global $woocommerce, $product, $post;
			$newFinalData[] = $post->ID;
		}
	}
	return $newFinalData;
    wp_reset_query();
	die;
}


/*
* Register API route for Pushing all categories to app.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_PostAllCategories', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_PostAllCategories',
    ));
});

/*
* @Hipkrt_PostAllCategories is callback for "Hipkrt_PostAllCategories" route and used when first time app is setup for store.
*/

function Hipkrt_PostAllCategories( WP_REST_Request $request ) {
	$parameters = $request->get_params();
	$hipCommerceKey  = $parameters['hipCommerceKey'];
	$args = array(
		'orderby'    => 'term_id',
		'parent' => 0,
		'hide_empty' => false
	);
	$S = 0;
	$product_categories = get_terms( 'product_cat', $args );
	$Allcategory = array();
	foreach($product_categories as $P_cat){
		if($S > 0){
			$Allcategory[$S]['term_id']=$P_cat->term_id;
			$Allcategory[$S]['name']=$P_cat->name;
		}
		$S++;
	}
	return $Allcategory;
	die;
}

/*
* Register API route for sync order from app to store.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_InsertHipOrder', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_InsertHipOrder',
    ));
});


/*
*@Hipkrt_InsertHipOrder is callback for "Hipkrt_InsertHipOrder" route and called to insert order in WooCommerce when new order is placed into store app.
*/
function Hipkrt_InsertHipOrder( WP_REST_Request $request ) {
	$hipCommerceSettingsVal = json_decode(get_option('hipCommerceSettingsVal'));
	if(in_array('SYNC',$hipCommerceSettingsVal)){
		$parameters = $request->get_params();
		$DetailProduct = json_decode($parameters['ProductJson'],true);
		$PostedhipCommerceKey = $parameters['hipCommerceKey'];
		$hipCommerceKey = get_option('hipCommerceKey');
		
		if($PostedhipCommerceKey == $hipCommerceKey && $PostedhipCommerceKey!=''){
			if(is_array($DetailProduct) && !empty($DetailProduct)){
				$post_date = date('Y-M-D');
				$order_data = array();
				$order_data[ 'post_type' ] = 'shop_order';
				$order_data[ 'post_status' ] = 'wc-' . apply_filters( 'woocommerce_default_order_status', 'Processing' );
				$order_data[ 'ping_status' ] = 'closed';
				$order_data[ 'post_author' ] = 1;
				$order_data[ 'post_password' ] = uniqid( 'order_' );
				$order_data[ 'post_title' ] = sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ), strtotime( $post_date ) ) );
				$order_data[ 'post_content' ]  = "";
				$order_data[ 'comment_status' ]  = "open";
				$order_data[ 'post_name' ] = sanitize_title( sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ), strtotime( $post_date) ) ) );

				$order_id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', $order_data ), true );
				if($order_id != 0){
					update_option('hipCommerceorderId_'.$order_id, $DetailProduct['orderId'], '', '' );
					$order = wc_get_order( $order_id );
					if(is_array($DetailProduct)){
						$ProductIDS = $DetailProduct['products'];
						foreach($ProductIDS as $da){
							$varients = array();
							if(!empty($da['varients'])){
								$VARIENTS = explode('_@_',$da['varients']);
								foreach($VARIENTS as $rel){
									$VAL = explode(':',$rel);
									if(count($VAL)>1){
										$varients['attribute_'.$VAL[0]] = $VAL[1];
									}
								}
								
								$varients = json_encode($varients);
								
							}
							
							$product = new WC_Product_Variable($da['ProductID']);
							
							$variations = $product->get_available_variations();
							
							$allVariations = array();
							if(!empty($variations)){
								foreach($variations as $var){
									$attributes = $var['attributes'];
									$variation_id = $var['variation_id'];
									$allVariations[$variation_id] = json_encode($attributes);
								}
								
								$VariationID = 0;
								$VariationAttr = array();
								if(!empty($allVariations)){
									foreach($allVariations as $vid => $allVariation){
										if($allVariation == $varients){
											$VariationID = $vid;
											$VariationAttr['variation'] = json_decode($varients);
										}
									}
								}
								if($VariationID > 0){
									$varProduct = new WC_Product_Variation($VariationID);
									$order->add_product($varProduct,$da['Quantity'], $VariationAttr);
								}
							}else{
								$product_item_id = $order->add_product(get_product($da['ProductID']),$da['Quantity']);
							}
						}
					}
					$addressShipping = $DetailProduct['ShippingAddress'];
					$order->set_address( $addressShipping, 'shipping' );
					
					$addressBilling = $DetailProduct['BillingAddress'];
					
					$shipping_name = 'Shipping Charges(Ordered from hipkart)';
					$shipping_amount = $DetailProduct['shipping'];
					$shipping_tax = array(); 
					$shipping_rate = new WC_Shipping_Rate('', $shipping_name, 
												  $shipping_amount, $shipping_tax, 
												  'custom_shipping_method' );
					$order->add_shipping($shipping_rate);

					
					$order->set_address( $addressBilling, 'billing' );
					$order->calculate_totals();
					echo $order_id;
				}else{
					echo 0;
				}
			}
		}
	}
	die;
}

/*
* Register API route to check stock of product.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_IsOutOfStock', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_IsOutOfStock',
    ));
});


/*
* @Hipkrt_IsOutOfStock is callback for "Hipkrt_IsOutOfStock" route and called to check stock of product when user add the product into his cart in store app.
*/

function Hipkrt_IsOutOfStock( WP_REST_Request $request ) {
	$parameters = $request->get_params();
	$ProductID = $parameters['ProductID'];
	$VarientString = $parameters['Varients'];
	$PostedhipCommerceKey = $parameters['hipCommerceKey'];
	$hipCommerceKey = get_option('hipCommerceKey');
	
	if($PostedhipCommerceKey == $hipCommerceKey && $PostedhipCommerceKey!=''){
		$varients = array();
		if($VarientString!=''){
			$VARIENTS = explode('_@_',$VarientString);
			foreach($VARIENTS as $rel){
				$VAL = explode(':',$rel);
				if(count($VAL)>1){
					$varients['attribute_'.$VAL[0]] = $VAL[1];
				}
			}
			$varients = json_encode($varients);	
		}
		$product = new WC_Product_Variable($ProductID);
		
		$variations = $product->get_available_variations();
		
		$allVariations = array();
		if(!empty($variations)){
			foreach($variations as $var){
				$attributes = $var['attributes'];
				$variation_id = $var['variation_id'];
				$allVariations[$variation_id] = json_encode($attributes);
			}
			$VariationID = 0;
			$VariationAttr = array();
			if(!empty($allVariations)){
				foreach($allVariations as $vid => $allVariation){
					if($allVariation == $varients){
						$VariationID = $vid;
						$VariationAttr['variation'] = json_decode($varients);
					}
				}
			}
			if($VariationID > 0){
				$varProduct = new WC_Product_Variation($VariationID);
				$stock_quantity =  $varProduct->get_stock_quantity();
				$stock_status =  $varProduct->stock_status;
			}
		}else{
			 $stock_status = $product->stock_status;
			 $stock_quantity = $product->stock_quantity;
		}
		$FinalIsStock = array('quantity'=>$stock_quantity,'status'=>$stock_status);
		return $FinalIsStock;
	} else {
		return false;
	}	
	die;
}


/*
* Register API route to get product Shipping details.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_Product_Shipping_Details', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_Product_Shipping_Details',
    ));
});


/*
* @Hipkrt_Product_Shipping_Details is callback for "Hipkrt_Product_Shipping_Details" route and called when user check out with this product in store app.
*/
function Hipkrt_Product_Shipping_Details(WP_REST_Request $request){
	$parameters = $request->get_params();
	$ProductIDs = json_decode($parameters['ProductIDs'],true);
	$Ship_Ctry 	= $parameters['Ship_Ctry'];
	$Order_Item_total = $parameters['Order_Item_total'];
	$PostedhipCommerceKey = $parameters['hipCommerceKey'];
	$hipCommerceKey = get_option('hipCommerceKey');
	$productshiping = array();
	if($PostedhipCommerceKey == $hipCommerceKey && $PostedhipCommerceKey!=''){
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
		
		foreach($ProductIDs as $ProductID){
			$product = wc_get_product($ProductID);
			$shipping_class_id = $product->get_shipping_class_id();
			
			$shipping_class= $product->get_shipping_class();
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
							$GetZone['ShippingMethod']= $method['id'];
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
			
			$shippingZone = array();
			foreach($AllRequiredZones as $zoneName => $RequiredZone){
				if($RequiredZone[0]['zone_Code'] == $Ship_Ctry){
					$shippingZone = $RequiredZone;
				}
			}
			
			$finalShipping = '';
			
			$FreeShippingCoupon = Hipkrt_freeshippingCoupons();
			$allCouponCode = array();
			foreach($FreeShippingCoupon as $data){
				$allCouponCode[] = $data['code']; 
			}
			
			if (!empty($shippingZone)) {
				$shippingAvailable = '0';
				if(count($shippingZone) > 1){
					foreach($shippingZone as $GetShippingAMount){
						if($GetShippingAMount['ShippingMethod'] != 'local_pickup' && $GetShippingAMount['enabled'] == 'yes'){
							if(array_key_exists('requires', $GetShippingAMount)){
								if($GetShippingAMount['requires'] == 'min_amount'){
									$min_amount = $GetShippingAMount['Min_amount'];
									$ShippingCost = $GetShippingAMount['ShippingCost'];
									if($Order_Item_total >= $min_amount){
										$finalShipping = $ShippingCost;
										$shippingAvailable = '1';
									}
								}else if($GetShippingAMount['requires'] == 'either'){
									$min_amount = $GetShippingAMount['Min_amount'];
									$ShippingCost = $GetShippingAMount['ShippingCost'];
									if($Order_Item_total >= $min_amount){
										$finalShipping = $ShippingCost;
										$shippingAvailable = '1';
									}
								}
							}else{
								if($shippingAvailable == '1'){
									if($GetShippingAMount['ShippingCost'] < $finalShipping){
										$finalShipping = $GetShippingAMount['ShippingCost'];
									}
								}else{
									$finalShipping = $GetShippingAMount['ShippingCost'];
									$shippingAvailable = '1';
								}
							}
						}
					}
				}else{
					if($shippingAvailable == '1'){
						if($shippingZone[0]['ShippingCost'] < $finalShipping){
							$finalShipping = $shippingZone[0]['ShippingCost'];
						}
					}else{
						$finalShipping = $shippingZone[0]['ShippingCost'];
						$shippingAvailable = '1';
					}
				}
			}
			$productshiping[$ProductID]['shipAvail'] = $shippingAvailable;
			$productshiping[$ProductID]['shipCost']  = $finalShipping;
		}
	}
	
	return $productshiping;	
}


/*
* Register API route to get product details.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_FetchProductDetail', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_FetchProductDetail',
    ));
});

/*
* @Hipkrt_FetchProductDetail is callback for "Hipkrt_FetchProductDetail" route and used when hipkart request for product detail.
*/
function Hipkrt_FetchProductDetail(WP_REST_Request $request){
	$parameters = $request->get_params();
	$ProductID = $parameters['ProductID'];
	$shipping = Hipkrt_GetShippingDetails($ProductID);
	$PostedhipCommerceKey = $parameters['hipCommerceKey'];
	
	$hipCommerceKey = get_option('hipCommerceKey');
	$hipCommerceStoreID = get_option('hipCommerceStoreID');
	
	if($PostedhipCommerceKey == $hipCommerceKey && $ProductID!=''  && $PostedhipCommerceKey!='' && $hipCommerceKey!=''){
		global $woocommerce, $product, $post;
		
		$terms = wp_get_post_terms( $ProductID, 'product_cat' );
		foreach ( $terms as $term ){ 
			$cats_array[] = $term->name;
		}
		$DispatchCountry = wc_get_base_location();
		$DispatchCountry = $DispatchCountry['country'];
		$product = wc_get_product($ProductID);
		$GalleryImages = array();
		$attachment_ids = $product->get_gallery_attachment_ids();
		if($attachment_ids !=''){
			foreach( $attachment_ids as $attachment_id ) {
				 $GalleryImages[] = wp_get_attachment_url( $attachment_id );
			}
		}
		$name = $product->get_title();
		
		$attribute = $product->attributes;
		$ProductPrice = '';
		if($product->get_sku() != ''){
			$ProductSKU = $product->get_sku();
		}
		$variation = array();
		
		if ($product->is_type( 'variable' )){
			$available_variations = $product->get_available_variations();
			$ProductPrice = $available_variations[0]['display_regular_price'];
			foreach ($available_variations as $key => $value){
				
				if($value['max_qty'] != ''){
					$quantity = $value['max_qty'];
				}else{
					$quantity = $value['min_qty'];
				}
				$sizeVal = '';
				$optionVal = '';
				$color = '';
				foreach($value['attributes'] as $key => $attribute){
					$key = str_replace("attribute_","",$key);
					if("color" == substr($key,0) || "color" == strtolower($key)){
						$color = $attribute;
					}
					
					if($optionVal == ''){
						$optionVal = $key;
					}else{
						$optionVal = $optionVal.'_@_'.$key;
					}
					if($sizeVal == ''){
						$sizeVal = $key.':'.$attribute;
					}else{
						$sizeVal = $sizeVal.'_@_'. $key.':'.$attribute;
					}
				}
				if($color == ''){
					$color = 'default';
				}
			
				if(!is_array($variation[$color]) || empty($variation[$color])){
					$variation[$color] = array();
					$variation[$color]['sizes'] = array();
				}
				$variation[$color]['sku']='';
				$variation[$color]['images']=$featureimage[0];
				$variation[$color]['sizes'][$sizeVal] = array(
					'enableVariant' =>'1',
					'sku' => $value['sku'],
					'price' => $value['display_regular_price'],
					'actualprice' => $value['display_price'],
					'quantity' => $quantity,
					'attribute'=> $optionVal
				);
			}
		}
		$WocommerceCurrency = get_woocommerce_currency( $currency );
		$ProductStock = $product->get_stock_quantity();
		$price = $product->price;
		$featureimage = wp_get_attachment_image_src( get_post_thumbnail_id($ProductID),'single-post-thumbnail' );
		$ProVal = array();

		if(empty($variation)){
			$ProVal['default']['images'] = $featureimage[0];
			$ProVal['default']['sku'] = $ProductSKU;
			$SizesVAL = array();
			$SizesVAL['size']='one size';
			$SizesVAL['sku']= $ProductSKU;
			$SizesVAL['price']=$price;
			$SizesVAL['actualprice']=$price;
			$SizesVAL['quantity']= $ProductStock;
			$ProVal['default']['sizes']['One Size'] = $SizesVAL;
		}else{
			foreach($variation as $Key=>$value){
				$ProVal[$Key]['images'] = $featureimage[0];
				$ProVal[$Key]['sku'] = '';
				$ProVal[$Key]['Currency'] = $WocommerceCurrency;
				$ProVal[$Key]['sizes'] = $value['sizes']; 
			}
		}
		
		$url = get_permalink($ProductID);
		
		$slug = $product->slug;
		$date_created = $product->date_created;
		$description = $product->description;
		$output = strip_tags($description);
		$itemInfo = preg_split('/<.+?>/', $output);
	
		if($product->regular_price == ''){
			$regular_price = $ProductPrice;
		}else{
			$regular_price = $product->regular_price;
		}
		
		$productid = $product->id;
		$cat_ids = wp_get_post_terms($productid, 'product_cat', array('fields'=>'ids'));
		$OptionData = json_decode(get_option('HipkartCatgs'),true);
		$NEWARRAY = array();
		if(!empty($OptionData)){
			foreach($OptionData as $KEY=>$OP){
				$KET = explode('_',$KEY);
				$NEWARRAY[$KET[0]] = $OP;
			}
		}
		$FINALCATEGORY = array();
		if(!empty($cat_ids)){
			foreach($cat_ids as $CATIDS){
				if(isset($NEWARRAY[$CATIDS]) && !empty($NEWARRAY[$CATIDS])){
					$FINALCATEGORY = $NEWARRAY[$CATIDS];
				}
			}
		}
		$SelectedCategory = array();
		if(!empty($FINALCATEGORY)){
			foreach($FINALCATEGORY as $FNL){
				$dat = explode('_',$FNL);
				$SelectedCategory[]  = $dat[0];
			}
		}
		
		$terms = get_the_terms($productid, 'product_tag' );
		if(!empty($terms)){
			foreach($terms as $tags){
				$AllTags = $tags->name;
			}
		}else{
			$AllTags = '';
		}
		$image_id = $product->image_id;
		$sku = $product->sku;
		$variation_1 = '';
		$variation_2 = '';
		$size = '';
		$Unit_Type = '';
		$Package_Weight = $product->weight;
		$length = $product->length;
		$width = $product->width;
		$height = $product->height;
		$Package_Size = $length.'X'.$width.'X'.$height;
		
		$packageInfo = array('Unit Type'=>$Unit_Type,'Package Weight'=>$Package_Weight,'Package Size'=>$Package_Size);
		
		$product_options = $ProVal;
		if(empty($GalleryImages)){
			$GalleryImages = '';
		}
		$shopurl = site_url();
		
		$Producturl = get_permalink($productid);
		$finalData = array('storeCustomCat'=>$cats_array[0],'shopname'=>'','shopurl'=>$shopurl,'producturl'=>$Producturl,'storeId'=>$hipCommerceStoreID,'aliexpprodid'=>'','HipWocommerceProdid'=>$productid,'platform'=>'HipWocommerce','allBigImages'=>$GalleryImages,'processing_timeMin'=>2,'processing_timeMax'=>10,'productTile'=>$name,'categories'=>$SelectedCategory,'tags'=>$AllTags,'meta_keywords_val'=>'','itemInfo'=>$output,'enableSale'=>'0','profitMrgn'=>'0','discount'=>'','options'=>$product_options,'defaultsaleprice'=>$regular_price,'actualcost'=>$regular_price,'from_country'=>$DispatchCountry,'selectshipping'=>$shipping,'toCountryCost'=>array(),'toCountryID'=>array());
		$finaldataJson = json_encode($finalData);
		echo $finaldataJson;
	}
	die;
}

/*
* Register API route to get Coupon details. // CURRENTLY NOT IN USED WILL BE AVAILABLE FOR USER IN NEXT VERSION
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_GetCouponsDetail', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_GetCouponsDetail',
    ));
});

/*
* @Hipkrt_GetCouponsDetail is callback for "Hipkrt_GetCouponsDetail" route. // CURRENTLY NOT IN USED WILL BE AVAILABLE FOR USER IN NEXT VERSION
*/
function Hipkrt_GetCouponsDetail(WP_REST_Request $request){
		$hipCommerceStoreID = get_option('hipCommerceStoreID');
		$args = array(
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'asc',
			'post_type'        => 'shop_coupon',
			'post_status'      => 'publish',
		);
		
		$Allcoupons = get_posts( $args );
		$finalCouponData = array();
		foreach($Allcoupons as $Coupn){
			$Detailcoupon = new WC_Coupon($Coupn->post_title);
			$finalCoupon['code'] = $Detailcoupon->get_code();
			$finalCoupon['amount'] = $Detailcoupon->get_amount();
			$finalCoupon['discount_type'] = $Detailcoupon->get_discount_type();
			$finalCoupon['individual_uses'] = $Detailcoupon->get_individual_use();
			$finalCoupon['free_shipping'] = $Detailcoupon->get_free_shipping();
			$date_expires = $Detailcoupon->get_date_expires();
			$finalCoupon['date_expires'] = $date_expires->date('Y-m-d H:i:s');
			$date_created = $Detailcoupon->get_date_created();
			$finalCoupon['date_created'] = $date_created->date('Y-m-d H:i:s');
			$date_modified = $Detailcoupon->get_date_modified();
			$finalCoupon['date_modified'] = $date_modified->date('Y-m-d H:i:s');
			$finalCoupon['product_ids'] = $Detailcoupon->get_product_ids();
			$finalCoupon['excluded_product_ids'] = $Detailcoupon->get_excluded_product_ids();
			$finalCoupon['product_categories'] = $Detailcoupon->get_product_categories();
			$finalCoupon['excluded_product_categories'] = $Detailcoupon->get_excluded_product_categories();
			$finalCoupon['usage_limit'] = $Detailcoupon->get_usage_limit();
			$finalCoupon['usage_limit_per_user'] = $Detailcoupon->get_usage_limit_per_user();
			$finalCoupon['limit_usage_to_x_items'] = $Detailcoupon->get_limit_usage_to_x_items();
			$finalCoupon['usage_count'] = $Detailcoupon->get_usage_count();
			$finalCoupon['exclude_sale_items'] = $Detailcoupon->get_exclude_sale_items();
			$finalCoupon['minimum_amount'] = wc_format_decimal($Detailcoupon->get_minimum_amount(), 2);
			$finalCoupon['maximum_amount'] = wc_format_decimal($Detailcoupon->get_maximum_amount(), 2);
			$finalCoupon['email_restrictions'] = $Detailcoupon->get_email_restrictions();
			$finalCoupon['description'] = $Detailcoupon->get_description();
			$finalCouponData['coupons'][] = $finalCoupon;
		}
		$finalCouponData['StoreId']=$hipCommerceStoreID;
		echo json_encode($finalCouponData);
		die;
}


/*
* Register API route to get Boost product details.
*/
add_action( 'rest_api_init', function () {
    register_rest_route( 'hipkartmobi/api', '/Hipkrt_FetchBoostproduct', array(
        'methods' => 'POST',
        'callback' => 'Hipkrt_FetchBoostproduct',
    ));
});

/*
* @Hipkrt_FetchBoostproduct is callback for "Hipkrt_FetchBoostproduct" route.and used when hipkart request for product detail for boost.
*/

function Hipkrt_FetchBoostproduct(WP_REST_Request $request){
	$parameters = $request->get_params();
	$ProductID = $parameters['ProductID'];
	$PostedhipCommerceKey = $parameters['hipCommerceKey'];
	$hipCommerceKey = get_option('hipCommerceKey');
	$hipCommerceStoreID = get_option('hipCommerceStoreID');
	if($PostedhipCommerceKey == $hipCommerceKey && $ProductID!=''  && $PostedhipCommerceKey!='' && $hipCommerceKey!=''){
		global $woocommerce, $product, $post;
		$product = wc_get_product($ProductID);
		$GalleryImages = array();
		$attachment_ids = $product->get_gallery_attachment_ids();
		if($attachment_ids !=''){
			foreach( $attachment_ids as $attachment_id ) {
				 $GalleryImages[] = wp_get_attachment_url( $attachment_id );
			}
		}
		
		$name = $product->get_title();
		
		$attribute = $product->attributes;
		$ProductPrice = '';
		if($product->get_sku() != ''){
			$ProductSKU = $product->get_sku();
		}
		
		$variation = array();
		
		if ($product->is_type( 'variable' )){
			$available_variations = $product->get_available_variations();

			$ProductPrice = $available_variations[0]['display_regular_price'];
			foreach ($available_variations as $key => $value){
				
				if($value['max_qty'] != ''){
					$quantity = $value['max_qty'];
				}else{
					$quantity = $value['min_qty'];
				}
				$sizeVal = '';
				$optionVal = '';
				$color = '';
				foreach($value['attributes'] as $key => $attribute){
					$key = str_replace("attribute_","",$key);
					if("color" == substr($key,0) || "color" == strtolower($key)){
						$color = $attribute;
					}
					if("color" == substr($key,0)){
						unset($key);
						unset($attribute);
					}else{
						if($optionVal == ''){
							$optionVal = $key;
						}else{
							$optionVal = $optionVal.'_@_'.$key;
						}
						if($sizeVal == ''){
							$sizeVal = $attribute;
						}else{
							$sizeVal = $sizeVal.'_@_'.$attribute;
						}
						
					}
				}
				if($color == ''){
					$color = 'default';
				}
				
				if(!is_array($variation[$color]) || empty($variation[$color])){
					$variation[$color] = array();
					$variation[$color]['sizes'] = array();
				}
				$variation[$color]['sku']='';
				$variation[$color]['images']=$featureimage[0];
				$variation[$color]['sizes'][$sizeVal] = array(
					'enableVariant' =>'1',
					'sku' => $value['sku'],
					'price' => $value['display_regular_price'],
					'actualprice' => $value['display_price'],
					'quantity' => $quantity,
					'attribute'=> $optionVal
				);
			}
		}
		
		$WocommerceCurrency = get_woocommerce_currency( $currency );
		
		$ProductStock = $product->get_stock_quantity();
		$price = $product->price;
		$featureimage = wp_get_attachment_image_src( get_post_thumbnail_id($ProductID),'single-post-thumbnail' );
		$ProVal = array();
		if(empty($variation)){
			$ProVal['default']['images'] = $featureimage[0];
			$ProVal['default']['sku'] = $ProductSKU;
			$SizesVAL = array();
			$SizesVAL['size']='Size_1';
			$SizesVAL['sku']= $ProductSKU;
			$SizesVAL['actSkuCalPrice']=$price;
			$SizesVAL['skuCalPrice']=$price;
			$SizesVAL['quantity']= $ProductStock;
			$ProVal['default']['sizes'][] = $SizesVAL;
		}else{
			foreach($variation as $Key=>$value){
				$ProVal[$Key]['images'] = $featureimage[0];
				$ProVal[$Key]['sku'] = '';
				$ProVal[$Key]['Currency'] = $WocommerceCurrency;
				$ProVal[$Key]['size'] = $value; 
			}
		}
		
		$url = get_permalink($ProductID);
		
		$slug = $product->slug;
		$date_created = $product->date_created;
		$description = $product->description;
		$output = strip_tags($description);
		$itemInfo = preg_split('/<.+?>/', $output);
	
		if($product->regular_price == ''){
			$regular_price = $ProductPrice;
		}else{
			$regular_price = $product->regular_price;
		}
		
		$productid = $product->id;
		$cat_ids = wp_get_post_terms($productid, 'product_cat', array('fields'=>'ids'));
		if($cat_ids == ''){
			$cat_ids = '';
		}
		$terms = get_the_terms($productid, 'product_tag' );
		if(!empty($terms)){
			foreach($terms as $tags){
				$AllTags = $tags->name;
			}
		}else{
			$AllTags = '';
		}
		$image_id = $product->image_id;
		$sku = $product->sku;
		$variation_1 = '';
		$variation_2 = '';
		$size = '';
		$Unit_Type = '';
		$Package_Weight = $product->weight;
		$length = $product->length;
		$width = $product->width;
		$height = $product->height;
		$Package_Size = $length.'X'.$width.'X'.$height;
		
		$packageInfo = array('Unit Type'=>$Unit_Type,'Package Weight'=>$Package_Weight,'Package Size'=>$Package_Size);
		
		$product_options = $ProVal;
		if(empty($GalleryImages)){
			$GalleryImages = '';
		}
		$finalData = array('title'=>$name,'price'=>$regular_price,'itemInfo'=>$output,'variation_1'=>'','variation_2'=>'','allBigImages'=>$GalleryImages,'shopname'=>'Jkishor','shopurl'=>site_url(),'producturl'=>get_permalink($ProductID),'storeId'=>$hipCommerceStoreID);
		$finaldataJson = json_encode($finalData);
		echo $finaldataJson;
	}
	die;
}

/*
***********************************************************************************************************
***********************************************************************************************************
************************************** API Routes Registering Complete ************************************
***********************************************************************************************************
***********************************************************************************************************
*/


/*
* Create A Menu in Wp-admin for User to access page for the Plugin.
*/
add_action('admin_menu', 'HipCommerce_Menu');
function HipCommerce_Menu(){
	add_menu_page('hipKartMobi', 'hipKartMobi', 'administrator','hipKartMobi','HipCommerceFunc', plugin_dir_url( __FILE__ ) . '/Images/hk.png');	
}
/*
* @HipCommerceFunc is function to show Page on click of Plugin Menu "hipKartMobi".
*/
function HipCommerceFunc(){ 
	ob_start();
	
	/*
	* First we will check if WooCommerce plugin is installed or not, if not then show message to user to install WooCommerce first.
	*/  
	if(class_exists('woocommerce')){
		/*
		* if WooCommerce plugin is available then check if cURL support on website is enabled or not.
		*/
		if(!function_exists('curl_version')){ ?>
			<div class="MainCOntainer">
				<div class="LogoDiv ErrorCase"><img src="<?php echo plugins_url( '/Images/MobiLogo.png', __FILE__ )?>"></div>
				<div class="WocoomerceErrorrMsg">This plugin requires cURL support to communicate with hipkart.<br />Please contact to your hosting provider to enabled cURL support for your website.</div>
			</div>
	<?php }else{
			/*
			* if WooCommerce plugin and cURL support available, let's start the Process by checking if User linked to hipKart store or not.
			*/
			$hipCommerceKey = get_option('hipCommerceKey');
			if($hipCommerceKey!==''){
				$hipCommerceStoreID = get_option('hipCommerceStoreID');
				$hipCommerceStoreName = get_option('hipCommerceStoreName');
				$hipCommerceStoreURL = get_option('hipCommerceStoreURL');
			}
	?>
		<div class="MainCOntainer">
			<?php if($hipCommerceKey == ''){ ?>
				 <div class="LogoDiv"><img src="<?php echo plugins_url( '/Images/MobiLogo.png', __FILE__ )?>"></div>
				 <div id="Login" >
					<div class="LoginMainHalf">
						<h2>Enchance Your Store With</h2>
						<div class="ThingSDONE">
							<p class="Mobileapps hipHelpInfo">Go Mobile(Android & iOS) <span class="infoCircle"><img src="<?php echo plugins_url('Images/hipInfoImg.png', __FILE__ )?>"><span class="hoverText">Having a mobile app is important, but getting a mobile app is often complex and expensive process . But with hipKart.mobi its been simplified and made it available at very inexpensive cost.<br>Get your mobile app just in few hours our AI and mobile team is excited to build native store app for you on Android and Apple iOS.<br/>Visit : <a href="https://hipkart.mobi/" target="_blank">www.hipkart.mobi</a><br/>Visit : <a href="https://www.hipkart.com/" target="_blank">www.hipkart.com</a></span></span></p>
							<p class="BoostSale hipHelpInfo">Sales Booster <span class="infoCircle"><img src="<?php echo plugins_url('Images/hipInfoImg.png', __FILE__ )?>"><span class="hoverText">e-Commerce targeted marketing made easy and simple, HipBoost allows you to reach real customer who are looking for product then showing banners and targeting people blindly to every one.<br>
							You have $100 credit to start with.<br>
							Visit : www.hipboost.com</span></span></p>
							<p class="DropShipping hipHelpInfo">KartSupply <span class="infoCircle"><img src="<?php echo plugins_url('Images/hipInfoImg.png', __FILE__ )?>"><span class="hoverText">Enhanced your store inventory, add more products to your store without an hassle join the 500,000 global drop-shippers and grow your business.<br/>Visit : www.kart.supply</span></span></p>
						</div>
					</div>
					<div class="LoginMain">
						<h2>Login</h2>
						<div class="LoginCommon USERNAMEMAIN"><input type="text" name="username" class="usernameLogin" placeholder="Username"></div>
						<div class="LoginCommon"><input type="password" name="password" class="passwordClass" placeholder="Password"></div>
						<div class="LoginCommon SubmitButton">
							<input type="submit" name="submit" class="submitClass" onclick="LoginIntoHip();" value="SUBMIT">
							<span class="submitClasLodr"><span class="loading_spinner"></span></span>
						</div>
						<div class="CreateAccountForHipkart">Don't have account with hipkart? <a href="https://www.hipkart.com/sell/" target="_blank">Create Now</a></div>
					 </div>
					 
				</div>
			<?php } else { 
						$section = 'mobile';
						if(isset($_GET) && !empty($_GET)){
							if(isset($_GET['section'])){
								if(sanitize_text_field($_GET['section']) == 'category'){
									$section = 'category';
								}
							}
						}
			?>
						<script type="text/javascript">
							var hipkartmobiSecion = '<?php echo $section;?>';
							var LoggedInHipcommerceKey = '<?php echo $hipCommerceKey;?>';
							var LoggedInhipCommerceStoreID = '<?php echo $hipCommerceStoreID;?>';
							jQuery("document").ready(function() {
								if(hipkartmobiSecion == 'category'){
									openSectionhipkart('LinkCategory',LoggedInHipcommerceKey,LoggedInhipCommerceStoreID,'TabLinkCategory');
								} else {
									GetMobileAppInfohipkart(LoggedInHipcommerceKey,LoggedInhipCommerceStoreID);
								}
							});
						</script>
						<div class="LoginHipcommerceLogin">
							<div class="LogoDiv"><img src="<?php echo plugins_url( '/Images/MobiLogo.png', __FILE__ )?>"></div>
							<div id="RegisterSucessFully">
								<div class="DIV1 StoreINFO"><span class="Heading Storename">Your Store Name</span><br><span class="StoreLinkClass"><?php echo $hipCommerceStoreName;?></span></div>
								<div class="DIV2 StoreINFO">
									<span class="Heading storeAddress">Store Address</span><br/>
									<span class="StoreLinkClass">
										<?php 
											if($hipCommerceStoreURL == ''){ 
												echo 'You haven\'t set store url on hipkart platform.<a href="https://www.hipkart.com/admin/store" target="_blank">Click here</a>';
											}else{
												echo  esc_url($hipCommerceStoreURL);
											}
										?>
									</span>	
								</div>
							</div>
						</div>
						<div class="HipMenuMainContainer">
							<div class="AfterLoginPAge tab"> 
								<button class="tablinks active TabMobile" onclick="openSectionhipkart('MobileApp','<?php echo esc_attr($hipCommerceKey);?>','<?php echo esc_attr($hipCommerceStoreID);?>','TabMobile')"><img src="<?php echo plugins_url('Images/Mobile.png', __FILE__ )?>">Mobile App</button>
								<button class="tablinks TabBooster" onclick="openSectionhipkart('Booster','<?php echo esc_attr($hipCommerceKey);?>','<?php echo esc_attr($hipCommerceStoreID);?>','TabBooster')"><img src="<?php echo plugins_url('Images/rocket.png', __FILE__ )?>">Booster</button>
								<button class="tablinks TabDropShipping" onclick="openSectionhipkart('DropShipping','<?php echo esc_attr($hipCommerceKey);?>','<?php echo esc_attr($hipCommerceStoreID);?>','TabDropShipping')"><img src="<?php echo plugins_url('Images/shipping.png', __FILE__ )?>">DropShipping</button>
								<button class="tablinks TabLinkCategory" onclick="openSectionhipkart('LinkCategory','<?php echo esc_attr($hipCommerceKey);?>','<?php echo esc_attr($hipCommerceStoreID);?>','TabLinkCategory')"><img src="<?php echo plugins_url('Images/category.png', __FILE__ )?>">Link Category</button>
								<button class="tablinks TabUnlinkStore" onclick="openSectionhipkart('UnlinkStore','<?php echo esc_attr($hipCommerceKey);?>','<?php echo esc_attr($hipCommerceStoreID);?>','TabUnlinkStore')"><img src="<?php echo plugins_url('Images/UnlinkStore.png', __FILE__ )?>">Settings</button>
							</div>
						
							<div id="Booster" class="tabcontent MenuContentDiv">
								<?php
									/*
									* List All Products with a Button to Boost, User can click on button to Boost Products.
									*/
									$args = array( 'post_type' => 'product');
									
									$loop = new WP_Query( $args );
									if ($loop->have_posts() ) {
										while ( $loop->have_posts() ) : $loop->the_post();
											global $woocommerce, $product, $post; 
											$GalleryImages = array();
											$attachment_ids = $product->get_gallery_attachment_ids();
											foreach( $attachment_ids as $attachment_id ) {
												 $GalleryImages[] = wp_get_attachment_url( $attachment_id );
											}
											$name = $product->name;
											$Productsize = $product->attributes['size']['options'];
											$Productcolor = $product->attributes['color']['options'];
											$ProductSKU = $product->get_sku();
											$variation = array();
											if ($product->is_type( 'variable' )) {
												$available_variations = $product->get_available_variations();
												
												foreach ($available_variations as $key => $value){
													$value['attributes']['display_price']= $value['display_price'];
													$value['attributes']['display_regular_price']= $value['display_regular_price'];
													$value['attributes']['max_qty']= $value['max_qty'];
													$value['attributes']['sku']= $value['sku'];
													$variation[]=$value['attributes'];
												}
											}
											
											$ProductStock = $product->get_stock_quantity();
											$price = $product->price;
											$featureimage = wp_get_attachment_image_src( get_post_thumbnail_id( $loop->post->ID ),'single-post-thumbnail' );
											$ProVal = array();
											if(empty($variation)){
												$ProVal['default']['images'] = $featureimage[0];
												$ProVal['default']['sku'] = $ProductSKU;
												$SizesVAL = array();
												$SizesVAL['size']='Size_1';
												$SizesVAL['sku']= $ProductSKU;
												$SizesVAL['actSkuCalPrice']=$price;
												$SizesVAL['skuCalPrice']=$price;
												$SizesVAL['quantity']= $ProductStock;
												$ProVal['default']['sizes'][] = $SizesVAL;
											}else{
												foreach($variation as $value){
													if($value['attribute_color'] == ''){
														$value['attribute_color']= 'default';
													}
													if(!isset($ProVal[$value['attribute_color']])){
														$ProVal[$value['attribute_color']] = array();
													}
													
													$ProVal[$value['attribute_color']]['images'] = $featureimage[0];
													$ProVal[$value['attribute_color']]['sku'] =  $value['sku'];
													
													$SizesVAL = array();
													$SizesVAL['size']=$value['attribute_size'];
													$SizesVAL['sku']= $value['sku'];
													$SizesVAL['actSkuCalPrice']=$value['display_regular_price'];
													$SizesVAL['skuCalPrice']=$value['display_price'];
													$SizesVAL['quantity']=$value['max_qty'];
													$ProVal[$value['attribute_color']]['sizes'][] = $SizesVAL;
												}
											}
											
											$url = get_permalink( $loop->post->ID );
											$slug = $product->slug;
											$date_created = $product->date_created;
											$description = $product->description;
											$output = strip_tags($description);
											$itemInfo = preg_split('/<.+?>/', $output);
											
											if($product->regular_price == ''){
												$regular_price = $variation[0]['display_regular_price'];
											}else{
												$regular_price = $product->regular_price;
											}
											$productid = $product->id;
											$category_id = $product->category_ids;
											$image_id = $product->image_id;
											$sku = $product->sku;
											$imageurl= woocommerce_placeholder_img_src();
											$variation_1 = '';
											$variation_2 = '';
											$images = woocommerce_placeholder_img_src();
											$sizes = '';
											$size = '';
											$actSkuCalPrice = '';
											$skuCalPrice = '';
											$quantity = '';
											$sizeAlt = '';
											$Unit_Type = '';
											$Package_Weight = $product->weight;
											$length = $product->length;
											$width = $product->width;
											$height = $product->height;
											$Package_Size = $length.'X'.$width.'X'.$height;
											
											$packageInfo = array('Unit Type'=>$Unit_Type,'Package Weight'=>$Package_Weight,'Package Size'=>$Package_Size);
											$product_options = $ProVal;
											
											$finalData = array('productId'=>$productid,'url'=>$url,'title'=>$name,'price'=>$regular_price,'packageInfo'=>$packageInfo,'itemInfo'=>$itemInfo,'variation_1'=>$variation_1,'variation_2'=>$variation_2,'product_options'=>$product_options,'allBigImages'=>$GalleryImages);
											$finaldataJson = json_encode($finalData);
											$price = $product->get_price_html();
											$image = woocommerce_placeholder_img_src();
											$BoostProductID = $loop->post->ID;?>
											<li class="product">  
										<span class="Productspan">
										<a href="<?php echo get_permalink( $loop->post->ID ) ?>" title="<?php echo esc_attr($loop->post->post_title ? $loop->post->post_title : $loop->post->ID); ?>">
											<?php woocommerce_show_product_sale_flash( $post, $product ); ?>
											<?php 
												if (has_post_thumbnail( $loop->post->ID )){ 
													echo get_the_post_thumbnail($loop->post->ID, 'shop_catalog');
												}else{
													echo '<img src="'.woocommerce_placeholder_img_src().'" alt="Placeholder" width="300px" height="300px" />';
												}											
											?>
											<h3><?php the_title(); ?></h3>
											<span class="price"><?php echo $product->get_price_html(); ?></span>
										</a>
										</span>
										<a href="javascript:void(0);" onclick="BoostFormSubmitHipkart('<?php echo $loop->post->ID;?>');"><span class="BoostProduct">Boost</span></a>
										<form id="form_<?php echo $loop->post->ID;?>" method="post" action="https://www.hipboost.com/adworld/productToBoost" target="_blank">
											<input type="hidden" class="HipcommerceProductClass" name="productId" id="HipcommerceProductID" value="<?php echo $loop->post->ID;?>">
											<input type="hidden" class="hipCommerceKeyClass" id="hipCommerceKeyID" name="hipCommerceKey" value="<?php echo esc_attr($hipCommerceKey);?>">
										</form>
									</li>

									<?php endwhile;
										  wp_reset_query();
									}else{ ?>
										<div class="NoProductDiv floatLeft">
											<span class="NoProductDivSpan1"><img src="<?php echo plugins_url('Images/NoProducts.png', __FILE__ )?>"></span>
											<span class="NoProductDivSpan2">You have not added any products yet.<br />Please add some some products.</span>
										</div>
									<?php } ?>
							</div>
							<div id="MobileApp" class="tabcontent MenuContentDiv"  style="display:none;">
								<div class="MobileFeatureLeftDIV">
									<div class="features-control-item features-control-item-0" data-features-item="0">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/ANDROID.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Android App</h3>
												<p class="HipkartMobiletext">Reach out to millions of Android mobile users with your store's Android App.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div><!--/features-control-item-->
									<div class="features-control-item features-control-item-1 visible-sm visible-xs" data-features-item="1">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/shoppingKart.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Inventory Sync</h3>
												<p class="HipkartMobiletext">Your inventory on your woo-commerce site is auto synced with your store app.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div><!--/features-control-item-->
									<div class="features-control-item features-control-item-2" data-features-item="2">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/GetTogether.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Get Discovered</h3>
												<p class="HipkartMobiletext">You get a free address so new customers can reach you using hipKart discovery network.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div><!--/features-control-item-->
									<!--/features-control-item-->
								</div>
								<div class="mobile_frame">
									<div class="banner_images">
										<div class="full_page_banner">
											<div class="ScrollImagesMobiles">
												<img class="SliderImage1" src="<?php echo plugins_url('Images/OnePAGE.png', __FILE__ )?>">
											</div>
										</div>
									</div>
								</div>
								<div class="Mobile_ThirdDIV">
									<div class="features-control-item features-control-item-3 visible-sm visible-xs" data-features-item="3">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/IOSAPPLE.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Apple iOS App</h3>
												<p class="HipkartMobiletext">Get an Apple iOS app for your store enhance your brand identity.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div><!--/features-control-item-->
									
									<div class="features-control-item features-control-item-5 visible-sm visible-xs" data-features-item="5">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/OrderManagent.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Order management</h3>
												<p class="HipkartMobiletext">You all new orders placed one the app are auto synced with your woo commerce store.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div>
									<div class="features-control-item features-control-item-4 active" data-features-item="4">
										<div class="mediaLeftHip">
											<div class="pull-leftHip">
												<div class="ia-icon">
													<img src="<?php echo plugins_url('Images/LiveChat.png', __FILE__ )?>">
												</div>
											</div>
											<div class="media-body">
												<h3 class="Hipmedia-heading">Live Chat</h3>
												<p class="HipkartMobiletext">In-built live chat gives your customers a level of trust and comfort using your app.</p>
											</div>
										</div>
										<div class="clearfix"></div>
									</div><!--/features-control-item-->
								</div>
							</div>
							<div id="HavingMobileApp" class="tabcontent MenuContentDiv" style="display:none;">
								<div class="AndriodStatus" id="AndriodStatusApp">
									<div class="StatusBoxAndroid" style="display:block;">
										<div class="AndroidAPPAVAIBLE">
											<div class="HeadingOfSTatus"><img src="<?php echo plugins_url('Images/ANDROID.png', __FILE__ )?>"><span>Android App</span></div>
											
											<div class="AppStatusText AndroidButton" style="display:none;">
												<input type="button" class="fusion-button button-flat button-round button-large button-default button-3  get_apps_platform" id="ios_getAppStoreInfo" value="Get Android App" onclick="getApphipkart(2);">
												<form id="Mobiform_2" method="post" action="https://www.hipkart.mobi/ValuefromHipcommerce.php" target="_blank">
													<input type="hidden" class="HipcommerceProductClass" name="HipcommercehipCommerceStoreID2" id="HipcommercehipCommerceStoreID2" value="<?php echo esc_attr($hipCommerceStoreID);?>">
													<input type="hidden" class="hipCommerceKeyClasstoMobi" id="hipCommerceKeyIDMobi2" name="hipCommerceKey2" value="<?php echo esc_attr($hipCommerceKey);?>">
												</form>
											</div>
											<div class="progress_status_bar ANDRIOD" style="display:none;">
												<ul>
													<li class="ProgressBar creatng_app LiStep_1">
														<span class="StepDesign"><a href="javascript:void(0);" id="step1" class="progres_icon">1</a></span>
														<label class="prgess_text">APP BUILDING</label>
													</li>
													<li class="ProgressBar review_app LiStep_2">
														<span class="coneecting_rod first"></span>
														<span class="StepDesign"><a href="javascript:void(0);" id="step2" class="progres_icon">2</a></span>
														<label class="prgess_text">APP UPLOADING</label>
													</li>
													<li class="ProgressBar publishng_app LiStep_3">
														<span class="coneecting_rod second"></span>
														<span class="StepDesign"><a href="javascript:void(0);" id="step3" class="progres_icon">3</a></span>
														<label class="prgess_text">APP IN REVIEW</label>
													</li>
													<li class="ProgressBar published LiStep_4">
														<span class="coneecting_rod last"></span>
														<span class="StepDesign"><a href="javascript:void(0);" id="step4" class="progres_icon">4</a></span>
														<label class="prgess_text">APP PUBLISHED</label>
													</li>
												</ul>
											</div>
											
										</div>
										
									
										<div class="StatusBoxApple">
											<div class="IOSAPPAVAIBLE1">
												<div class="HeadingOfSTatus"><img src="<?php echo plugins_url('Images/IOSAPPLE.png', __FILE__ )?>"><span>iOS App</span></div>
												
												<div class="AppStatusText IOSButton">
													<input type="button" class="fusion-button button-flat button-round button-large button-default button-3  get_apps_platform" id="ios_getAppStoreInfo" value="Get iOS App" onclick="getApphipkart(1);">
													<form id="Mobiform_1" method="post" action="https://www.hipkart.mobi/ValuefromHipcommerce.php" target="_blank">
														<input type="hidden" class="HipcommerceProductClass" name="HipcommercehipCommerceStoreID1" id="HipcommercehipCommerceStoreID1" value="<?php echo esc_attr($hipCommerceStoreID);?>">
														<input type="hidden" class="hipCommerceKeyClasstoMobi" id="hipCommerceKeyIDMobi1" name="hipCommerceKey1" value="<?php echo esc_attr($hipCommerceKey);?>">
													</form>
												</div>
												
												<div class="progress_status_bar APPLEProgress" style="display:none;">
													<ul>
														<li class="ProgressBar creatng_app LiStep_1">
															<span class="StepDesign"><a href="javascript:void(0);" id="step1" class="progres_icon ActiveBlue">1</a></span>
															<label class="prgess_text">APP BUILDING</label>
														</li>
														<li class="ProgressBar review_app LiStep_2">
															<span class="coneecting_rod first ActiveBlue"></span>
															<span class="StepDesign"><a href="javascript:void(0);" id="step2" class="progres_icon">2</a></span>
															<label class="prgess_text">APP UPLOADING</label>
														</li>
														<li class="ProgressBar publishng_app LiStep_3">
															<span class="coneecting_rod second"></span>
															<span class="StepDesign"><a href="javascript:void(0);" id="step3" class="progres_icon">3</a></span>
															<label class="prgess_text">APP IN REVIEW</label>
														</li>
														<li class="ProgressBar published LiStep_4">
															<span class="coneecting_rod last"></span>
															<span class="StepDesign"><a href="javascript:void(0);" id="step4" class="progres_icon">4</a></span>
															<label class="prgess_text">APP PUBLISHED</label>
														</li>
													</ul>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="NotCompeletedYet NoProductDiv floatLeft">
									<span class="NoProductDivSpan1"><img src="<?php echo plugins_url('Images/NoInfo.png', __FILE__ )?>"></span>
									<span class="NoProductDivSpan2">You have not completed, your App styling setup. Please complete that to get your App ready.<br /><a class="Hipcolor" href="https://www.hipkart.mobi/" target="_blank">Click here</a> to do the customization.</span>
								</div>
							</div>
							<div id="DropShipping" class="tabcontent MenuContentDiv">
								<div class="KartSupplyLogo">
									<img src="<?php echo plugins_url('Images/kartSupply.png', __FILE__ )?>">
								</div>
								<div class="homepageSlides floatLeft">
									<div class="findProdInfo">
										<h1 class="findHead floatLeft">Find products for your store in minutes.</h1>
										<p class="findSubHead floatLeft">KartSupply allows you to easily <strong>import dropshipped products</strong> directly <strong>into your free e-commerce store</strong> and ship them directly to your customers – in only a few clicks.</p>
										<div class="getSupplyNow floatLeft">
											<a href="http://kart.supply/" target="_blank" class="getklickBtn" onclick="openLoginPopUp('1');">GET KARTSUPPLY NOW, IT'S FREE.</a>
										</div>
									</div>
									<div class="importProdSlides">
										<div class="KartBannerImage">
											<div class="KartBannerImage floatLeft">
												<img src="<?php echo plugins_url('Images/sell_cosmetics.jpg', __FILE__ )?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div id="LinkCategory" class="tabcontent MenuContentDiv">
								<div class="LinkHeading">Link your category with Hipkart category</div>
								<div class="WocommerceCategory">
									<div class="WocommerceCategoryleft">Your Product Category</div>
									<div class="WocommerceCategoryRight">
										<?php $Allproduct_categories = get_terms( 'product_cat', array('hide_empty' => false,'parent' => 0,'orderby'=>'term_id'));
										
											$i = 0;
											$ProductOption = '<option value="0">Choose Product Category</option>';
											foreach($Allproduct_categories as $CATS){
												if($i>0){
													$ProductOption = $ProductOption.'<option value="'.$CATS->term_id .'_'.$CATS->name .'">'.$CATS->name .'</option>';
												}
												$i++;
											}
										?>
										<select class="ProductSelect">
											<?php echo $ProductOption;?>
										</select>
									</div>
								</div>
								<div class="HipkartMainCategory">
									<div class="HipkartMainCategoryleft">Hipkart Category</div>
									<div class="HipkartMainCategoryRight">
										<div class="HipkartCategories">
											<span class="HipkartMAinSpan"><select id="HipkartCategoriesDropBox" onchange="GetSubCategorieshipkart(this.value,'<?php echo $hipCommerceKey;?>');">
												<option value="0">Choose Hipkart Category</option>
											</select>
											<span class="loading_spinner" style="display:none;"></span>
											</span>
										</div>
										<div class="HipkartSubCategories" style="display:none;"></div>
										<div class="HipkartSubCategories2" style="display:none;"></div>
									</div>
								</div>
								<div class="saveCategory floatLeft">
									<a href="javascript:void(0);" id="SaveCategory" class="addTackBtn" onclick="SavehipkartCategories('<?php echo $hipCommerceKey;?>');">Save<span class="loadingBox"><span class="loading_spinner"></span></span></a>
								</div>
								
								<?php 
								$OptionCATEGORY = get_option('HipkartCatgs');
								$decodecats = json_decode($OptionCATEGORY,true);
								if(!empty($decodecats)){ ?>
									<div class="ShowHipkartCategories">
										<table class="wp-list-table widefat striped">
											<thead>
												  <tr>
													<th>Woocommerce Category</th>
													<th>Hipkart Main Category</th>
													<th>Hipkart Sub Category</th>
													<th>Hipkart Sub2 Category</th>
													<th>Action</th>
												  </tr>
											  </thead>
											  <tbody>
											  <?php
												  $i = 1;
												  foreach($decodecats as $Key=>$CatsHip){ ?>
													<tr id="WocommerceCAtS_<?php echo $i;?>">
														<td><?php $WocommerceCAtS = explode("_",$Key); echo $WocommerceCAtS[1]; ?></td>
														<td>
															<?php
																$CatHipMAin1 = explode("_",$CatsHip[0]);
																if($CatHipMAin1[1] == ''){
																	echo '---';
																}else{
																	echo $CatHipMAin1[1];
																}
															?>
														</td>
														<td>
															<?php 
																$CatHipMAin2 = explode("_",$CatsHip[1]);
																if($CatHipMAin2[1] == ''){
																	echo '---';
																}else{
																	echo $CatHipMAin2[1];
																}
															?>
														</td>
														<td>
															<?php 
																$CatHipMAin3 = explode("_",$CatsHip[2]); 
																if($CatHipMAin3[1] == ''){ 
																	echo '---';
																}else{
																	echo $CatHipMAin3[1];
																}
															?>
														</td>
														<td><span class="DeleteHipCategory" id="DeleteHipCategory" onclick="DeleteHipCategory('<?php echo esc_html($Key);?>',<?php echo $i;?>);">Unlink</span></td>
													</tr> 
												  <?php $i++;} 
											 
											  ?>
											 </tbody>
											</table>
									</div> 
								<?php }?>
							</div>
							<?php  $hipCommerceSettingsVal = json_decode(get_option('hipCommerceSettingsVal'));?>
							<div id="UnlinkStore" class="tabcontent MenuContentDiv">
								<div class="createStorBox floatLeft reset_scale_size">
									<div class="SettingsDiv">
										<div class="SettingLabel">Settings</div>
										<div class="ProWarning floatLeft"><b>Warning:</b> Removing any of these setting may affect the Synchronization of your woocommerce  store with your hipKart.mobi Mobile App.</div>
										<div class="floatLeft">
											<span class="ProductToHIP"><input type="checkbox" class="messageCheckbox" name="Setting[]" value="PUSH"<?php if(in_array('PUSH',$hipCommerceSettingsVal)){echo 'checked';}?>> <span class="PushPro"><b>Push Product to your Hipkart</b> <br /> <span class="PushProText"> - Allow this to push your products to your hipKart Store(Store Mobile App),Whenever you Add/update your product.</span></span><br /></span>
											<span class="SYNCOrderofHIP"><input type="checkbox" class="messageCheckbox" name="Setting[]" value="SYNC"<?php if(in_array('SYNC',$hipCommerceSettingsVal)){echo 'checked';}?>><span class="PushPro"><b>Sync Order</b><br /><span class="PushProText"> - Allow this to add Orders into your woocommerce store. Whenever any new order will be Placed to your hipkart Store or Store Mobile App.</span></span><br /></span>
										</div>
									</div>
									<div class="SaveSettingButton floatLeft">
										<a href="javascript:void(0);" id="SaveSettings" class="addTackBtn" onclick="SaveSettingshipkart()">Save<span class="loadingBox"><span class="loading_spinner"></span></span></a>
									</div>
								</div>
								<div class="createStorBox floatLeft reset_scale_size">
									<h3 class="addTrackHead floatLeft">Unlink Store</h3>
									<div class="floatLeft unlinkStoreName">
										<span class="floatLeft unlinkTxtStor">Do you want to unlink store<span id="unlinkStoreName"><?php echo esc_attr($hipCommerceStoreName);?> ?</span></span>
									</div>
									<div class="addNowTrack floatLeft">
										<a href="javascript:void(0);" id="unlinkyes" class="addTackBtn" onclick="unlinkhip();">Unlink</a>
									</div>
								</div>
							</div>
							<div id="SEctionLoader" class="SectionLoader">
								<span class="loading_spinner"></span>
							</div>
						</div>
				<?php } ?>
			</div>
	<?php } 
		} else{ ?>
			<div class="MainCOntainer">
				<div class="LogoDiv ErrorCase"><img src="<?php echo plugins_url( '/Images/MobiLogo.png', __FILE__ )?>"></div>
				<div class="WocoomerceErrorrMsg">Please Install woocommerce plugin to use this plugin.</div>
			</div>
	<?php }
}
?>
