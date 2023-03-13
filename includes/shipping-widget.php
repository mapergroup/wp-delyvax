<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
add_action ( 'add_meta_boxes', 'delyvax_add_box' );
// add_action ( 'woocommerce_process_shop_order_meta', 'SaveData');
// add_action( 'woocommerce_order_status_completed', 'GetTrackingCode');

function delyvax_add_box() {

	add_meta_box (
	    'DelyvaTrackingMetaBox',
	    'Delyva',
	    'delyvax_show_box',
	    'shop_order',
	    'side',
	    'high'
    );
}

function delyvax_show_box( $post ) {
    $order = wc_get_order ( $post->ID );
	// $TrackingCode = isset( $post->TrackingCode ) ? $post->TrackingCode : '';

	$settings = get_option( 'woocommerce_delyvax_settings' );
	$company_id = $settings['company_id'];
	$company_code = $settings['company_code'];
	$company_name = $settings['company_name'];
	$create_shipment_on_confirm = $settings['create_shipment_on_confirm'];

	if($company_name == null)
	{
		$company_name = 'Delyva';
	}

	$DelyvaXOrderID = $order->get_meta( 'DelyvaXOrderID' );
	$TrackingCode = $order->get_meta( 'DelyvaXTrackingCode' );

	$DelyvaXServiceCode = $order->get_meta( 'DelyvaXServiceCode' );

	$DelyvaXError = $order->get_meta( 'DelyvaXError' );	

	$trackUrl = 'https://'.$company_code.'.delyva.app/customer/strack?trackingNo='.$TrackingCode;
	$printLabelUrl = 'https://api.delyva.app/v1.0/order/'.$DelyvaXOrderID.'/label?companyId='.$company_id;				

    if ($TrackingCode == 'Service Unavailable') {
				echo 'Tracking No.: <b>'.$TrackingCode.'</b>';
        		echo "<div><p>Failed to create shipment in ".$company_name.", you can try again by changing order status to <b>Preparing</b></p></div>";
    } else if ( $order->has_status(array('processing')) || $order->has_status( array( 'on-hold' )) ) {			
				$DelyvaXServices = $order->get_meta( 'DelyvaXServices' );

				$adxservices = array();

				if(!$DelyvaXServices)
				{
					$order = delyvax_get_order_services($order);
					$DelyvaXServices = $order->get_meta( 'DelyvaXServices' );
				}

				if($DelyvaXServices)
				{
					$adxservices = json_decode($DelyvaXServices);
				}

				if($DelyvaXError) {
					echo "Error: ".$DelyvaXError;
				}

				if($TrackingCode)
				{
						echo 'Tracking No.: <b>'.$TrackingCode.'</b>';
						echo "<div>
						<p>
								Set your order to <b>Preparing</b> to print label and track your shipment with ".$company_name.".
						</p>
						</div>";
						echo "<div><p>
		            <a href=\"".wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=preparing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' )."\" class=\"button button-primary\">Set to Preparing</a>
		            </p></div>";
				}

				delyvax_get_services_select($adxservices, $DelyvaXServiceCode);

				echo '<p><button class="button button-primary" type="submit">Fulfill with '.$company_name.'</button></p>';

				// if($create_shipment_on_confirm == 'yes')
				// {
				// 		// echo "<div><p>
				// 		// 	<a href=\"".wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=preparing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' )."\" class=\"button button-primary\">Fulfill with ".$company_name."</a>
				// 		// 	</p></div>";
				// }else {
				// 		// echo "<div><p>
				// 		// 	<a href=\"".wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=preparing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' )."\" class=\"button button-primary\">Fulfill with ".$company_name."</a>
				// 		// 	</p></div>";
				// }
		} else if ( $order->has_status( array( 'preparing' )) || $order->has_status( array( 'ready-to-collect' )) ) {
				
				if($DelyvaXError) {
					echo "Error: ".$DelyvaXError;
				}
				if($TrackingCode)
				{
					echo 'Tracking No.: <b>'.$TrackingCode.'</b>';
						echo "<div><p>
			          <a href=\"".$printLabelUrl."\" class=\"button button-primary\" target=\"_blank\">Print label</a>
			          </p></div>";
			      echo "<div><p>
			          <a href=\"".$trackUrl."\" class=\"button button-primary\" target=\"_blank\">Track shipment</a>
			          </p></div>";
				}else {
						echo "<div>
						<p>
								Set your order to <b>Processing</b> again to fulfill with ".$company_name.", it also works with <i>bulk actions</i> too!
						</p>
						</div>";
						echo "<div><p>
		            <a href=\"".wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' )."\" class=\"button button-primary\">Set to Processing</a>
		            </p></div>";
				}
    } else if ( $order->has_status( array( 'completed' ) ) && $TrackingCode ) {
				echo 'Tracking No.: <b>'.$TrackingCode.'</b>';
        echo "<div>
		    	<p>
		    		<label  for=\"TrackingCode\"> Tracking No :</label>
		    		<br />
		    		$TrackingCode
		        </p>
		    </div>";
	      // echo "<div><p>
	      //     <a href=\"".$printLabelUrl."\" class=\"button button-primary\" target=\"_blank\">Print label</a>
	      //     </p></div>";
	      echo "<div><p>
	          <a href=\"".$trackUrl."\" class=\"button button-primary\" target=\"_blank\">Track shipment</a>
	          </p></div>";
    } else if($TrackingCode){
		echo 'Tracking No.: <b>'.$TrackingCode.'</b>';
		echo "<div><p>
			<a href=\"".$printLabelUrl."\" class=\"button button-primary\" target=\"_blank\">Print label</a>
			</p></div>";
		echo "<div><p>
			<a href=\"".$trackUrl."\" class=\"button button-primary\" target=\"_blank\">Track shipment</a>
			</p></div>";
    } else{
		
        echo "<div>
        <p>
            Set your order to <b>Processing</b> to fulfill with ".$company_name.", it also works with <i>bulk actions</i> too!
        </p>
    		</div>";
	}
}

/**
 * Saves the custom meta input
 */
function delyvax_meta_save( $post_id ) {
	$order = wc_get_order ( $post_id );

    // Checks save status
    $is_autosave = wp_is_post_autosave( $post_id );
    $is_revision = wp_is_post_revision( $post_id );
    $is_valid_nonce = ( isset( $_POST[ 'prfx_nonce' ] ) && wp_verify_nonce( $_POST[ 'prfx_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
 
    // Exits script depending on save status
    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
    }
 
    // Checks for input and sanitizes/saves if needed
    if( isset( $_POST[ 'service_code' ] ) ) {
		$order->update_meta_data( 'DelyvaXServiceCode', sanitize_text_field( $_POST[ 'service_code' ] ) );
		$order->save();
		
		//change status to preparing
		$order->update_status('preparing', 'Order status changed to Preparing.', false);
    }

}
add_action( 'save_post', 'delyvax_meta_save' );

function delyvax_get_order_services( $order ) {
    if (!class_exists('DelyvaX_Shipping_API')) {
        include_once 'delyvax-api.php';
    }

	$user = $order->get_user();

	$DelyvaXOrderID = $order->get_meta( 'DelyvaXOrderID' );

	$dxorder = null;
	$services = array();
	
	if($DelyvaXOrderID)
	{
		$dxorder = DelyvaX_Shipping_API::getOrderQuotesByOrderId($DelyvaXOrderID);
	}else {
		delyvax_create_order($order, $user, false);

		$DelyvaXOrderID = $order->get_meta( 'DelyvaXOrderID' );
		$dxorder = DelyvaX_Shipping_API::getOrderQuotesByOrderId($DelyvaXOrderID);
	}

	if($dxorder)
	{
		if(isset($dxorder['data']))
		{
			if(isset($dxorder['data']['quotes']))
			{
				$quotes = $dxorder['data']['quotes'];

				if($quotes)
				{
					$services = $dxorder['data']['quotes']['services'];
					
					if($services)
					{
						$jservices = json_encode($services);

						$order->update_meta_data( 'DelyvaXServices', $jservices );
						$order->save();
					}				
				}				
			}
		}
	}

	return $order;
}

function delyvax_get_services_select($adxservices, $DelyvaXServiceCode)
{
	if(sizeof($adxservices) > 0)
	{
		// echo '<label for"service">Select Service</a>';

		echo '<select name="service_code" id="service_code">';
		echo '<option value="">(Select Service)</option>';

		foreach( $adxservices as $i => $service )	
		{
			$serviceName = $service->name;

			if($service->price)
			{
				$serviceName = $serviceName.' '.$service->price->currency.number_format($service->price->amount,2);
			}
		
			if($DelyvaXServiceCode 
				&& ( $DelyvaXServiceCode == $service->code || $DelyvaXServiceCode == $service->serviceCompanyCode ) )
			{
				echo '<option value="'.$service->code.'" selected>'.$serviceName.'</option>';
			}else {
				echo '<option value="'.$service->code.'">'.$serviceName.'</option>';
			}
		}
		echo '</select>';
	}
}

