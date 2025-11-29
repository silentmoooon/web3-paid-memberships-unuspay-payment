<?php

/**
 * Plugin Name: UnusPay Crypto Payments For Paid Memberships Pro
 * Plugin URI: https://unuspay.com/e-commerce
 * Description: unuspay Payments directly into your own wallet.
 * Author: unuspay
 * Author URI: https://unuspay.com
 * Text Domain: unuspay-crypto-payments-for-paid-memberships-pro
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: paid-memberships-pro
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('unuscpmp_pmp_gateway_load'))
{

    add_action('plugins_loaded', 'unuscpmp_pmp_gateway_load', 20);

    DEFINE("UNUSCPMP_PMP_GATEWAY_NAME", "pmp_unuscpmp_gateway");


    
 

    function unuscpmp_pmp_gateway_load()
    {
        if (!class_exists('PMProGateway')) return;

        add_action('init', array('PMProGateway_unuspay', 'init'));

        //add_filter('pmpro_pages_shortcode_confirmation', array('PMProGateway_unuspay', 'pmpro_pages_shortcode_confirmation'), 20, 1);

        add_filter('plugin_action_links', array('PMProGateway_unuspay', 'plugin_action_links'), 10, 2);

        //add_filter('pmpro_get_gateway', array('PMProGateway_unuspay', 'select_gateway'), 10, 1);

       // add_filter('pmpro_valid_gateways', array('PMProGateway_unuspay', 'valid_gateway'), 10, 1);

        //add_action('pmpro_checkout_boxes', array('PMProGateway_unuspay', 'checkout_boxes'));

 

		add_filter( 'plugin_row_meta', array('PMProGateway_unuspay', 'plugin_row_meta'), 10, 2 );

        class PMProGateway_unuspay extends PMProGateway
        {
            function __construct($gateway = NULL)
            {
                $this->gateway = $gateway;
                return $this->gateway;
            }

            public static function init()
            {
                add_filter('pmpro_gateways', array('PMProGateway_unuspay', 'pmpro_gateways'));

                add_filter('pmpro_payment_options', array('PMProGateway_unuspay', 'pmpro_payment_options'));
                add_filter('pmpro_payment_option_fields', array('PMProGateway_unuspay', 'pmpro_payment_option_fields'), 10, 2);

                $gateway = pmpro_getGateway();
                if ($gateway == "unuspay")
                {
                    add_filter('pmpro_include_billing_address_fields', '__return_false');
                    add_filter('pmpro_include_payment_information_fields', '__return_false');
                    add_filter('pmpro_required_billing_fields', array('PMProGateway_unuspay', 'pmpro_required_billing_fields'));
					add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_unuspay', 'pmpro_checkout_before_change_membership_level'), 1, 2);
                }
            }

            public static function plugin_action_links($links, $file)
            {
                static $this_plugin;

                if (isset($this_plugin) === false || empty($this_plugin) === true)
                {
                    $this_plugin = plugin_basename(__FILE__);
                }

                if ($file == $this_plugin)
                {
                    $settings_link = '<a href="' . admin_url('admin.php?page=pmpro-paymentsettings') . '">' . esc_html__('Settings', 'unuspay-crypto-payments-for-paid-memberships-pro') . '</a>';
                    array_unshift($links,  $settings_link);
                }

                return $links;
            }

            public static function select_gateway($gateway)
            {
                if (!session_id()) session_start();

                if (isset($_POST['gateway']))
                {
                    $gateway = $_SESSION['unuscpmp_pmp_gateway'] = sanitize_text_field(wp_unslash($_POST['gateway']));
                }
                else
                {
                    if (isset($_SESSION['unuscpmp_pmp_gateway']) && $_SESSION['unuscpmp_pmp_gateway'] == 'unuspay')
                    {
                        $gateway = sanitize_text_field($_SESSION['unuscpmp_pmp_gateway']);
                    }
                }

                return $gateway;
            }

            public static function valid_gateway($gateways)
            {
                if (array_search('unuspay', $gateways) === false)
                {
                    $gateways[] = 'unuspay';
                }

                return $gateways;
            }

            

            public static function getGatewayOptions()
            {
                global $wpdb;

                $options = array(
                    'currency',
                    'unuscpmp_payment_key',
                );

 

                return $options;
            }

            public static function pmpro_payment_options($options)
            {
                $unuscpmp_options = PMProGateway_unuspay::getGatewayOptions();

                $options = array_merge($unuscpmp_options, $options);

                return $options;
            }

            public static function pmpro_gateways($gateways)
            {
                if (empty($gateways['unuspay']))
                {
                    $gateways = array_slice($gateways, 0, 1) + array("unuspay" => esc_html__('UnusPay', 'unuspay-crypto-payments-for-paid-memberships-pro')) + array_slice($gateways, 1);
                }

                return $gateways;
            }

            public static function pmpro_required_billing_fields($fields)
            {
                unset($fields['bfirstname']);
                unset($fields['blastname']);
                unset($fields['baddress1']);
                unset($fields['bcity']);
                unset($fields['bstate']);
                unset($fields['bzipcode']);
                unset($fields['bphone']);
                unset($fields['bemail']);
                unset($fields['bcountry']);
                unset($fields['CardType']);
                unset($fields['AccountNumber']);
                unset($fields['ExpirationMonth']);
                unset($fields['ExpirationYear']);
                unset($fields['CVV']);

                return $fields;
            }

            public static function plugin_row_meta($plugin_meta, $plugin_file)
            {

                static $this_plugin;

                if (isset($this_plugin) === false || empty($this_plugin) === true)
                {
                    $this_plugin = plugin_basename(__FILE__);
                }

                if ( $this_plugin === $plugin_file )
                {
                    $row_meta = [
				        'dome' => '<a style="color: #39b54a;" href="https://example-wp.unuspay.com/membership-account/membership-checkout/" aria-label="' . esc_attr( esc_html__( 'View UnusPay Demo', 'unuspay-crypto-payments-for-paid-memberships-pro' ) ) . '" target="_blank">' . esc_html__( 'Demo', 'unuspay-crypto-payments-for-paid-memberships-pro' ) . '</a>',
                        'video' => '<a style="color: #39b54a;" href="https://youtu.be/OCaz-_dbTGA" aria-label="' . esc_attr( esc_html__( 'View UnusPay Video Tutorials', 'unuspay-crypto-payments-for-paid-memberships-pro' ) ) . '" target="_blank">' . esc_html__( 'Video Tutorials', 'unuspay-crypto-payments-for-paid-memberships-pro' ) . '</a>',
                    ];

                    $plugin_meta = array_merge( $plugin_meta, $row_meta );
                }

                return $plugin_meta;
            }

            public static function pmpro_payment_option_fields($options, $gateway)
            {
                $check_result=true;
                if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'pmpro-paymentsettings')
                {
                    $api_key = $options["unuscpmp_payment_key"];
        
                        // 如果为空，直接阻止保存
                    if (!empty($api_key)) {
                        
                         
                        $headers = array(
                                'Content-Type' => 'application/json; charset=utf-8'
                            );
                        $website = get_option("siteurl");
                        $endpoint = 'https://dapp.unuspay.com/api/plugin/collect';
                        $response = wp_remote_post( $endpoint,
                                    array(
                                        'headers' => $headers,
                                        'body' => json_encode([
                                            'website' => $website,
                                            'paymentKey' => $api_key,
                                            'platform' => 'pmp'
                                        ]),
                                        'method' => 'POST',
                                        'data_format' => 'body'
                                    )
                                );
                                    

                        if (is_wp_error($response)) {
                         
                        }

                    
                        $rspBody = json_decode(wp_remote_retrieve_body($response));
                        if ($rspBody->code == 404) {
                            $check_result=false;

                       
                        }
                        if ($rspBody->code != 200) {
                          
                        }
                    }
 
                }
               ?>

              <p style="color:red  <?php if($check_result) { ?> ;display: none<?php } ?> "><b>[UnusPay] Invalid Payment Key. Please check and try again.</b></p><p><br></p>

               <tr class="pmpro_settings_divider gateway gateway_unuspay" >
                <td colspan="2"><hr/><h2>UnusPay Crypto Payment Gateway Settings</h2></td>
               </tr>
                <tr class="gateway gateway_unuspay" <?php if($gateway != "unuspay") { ?>style="display: none;"<?php } ?>>
                <td colspan="2"><div style="font-size:13px;line-height:22px">
                      <a target='_blank' href='https://unuspay.com/' ><img border='0' src='<?php echo esc_url(plugins_url('/assets/images/logo_unuspay_2.png', __FILE__))?>'></a>
                <p style="margin-top: 10px;"><b>UnusPay official <a href="https://unuspay.com/" target="_blank">website.</a></b></p>
               <p style="margin-top: 10px;">UnusPay provides decentralized, trusted crypto payment solutions to thousands of businesses. Scale your operations, increase revenue, and drive conversions in the digital economy.</p>
               <p style="margin-top: 20px;"><a href="https://dapp.unuspay.com/dashboard/" target="_blank">Get Started</a></p>
                </div></td>
                </tr>
                    <tr class="gateway gateway_unuspay"<?php if($gateway != "unuspay") { ?>style="display: none;"<?php } ?> >
                 <th scope="row" valign="top" style="padding-left:10px"><label for="unuscpmp_payment_key">UnusPay payment Key:</label></th><td><input  style="width: 350px" type="text" value="<?php echo esc_attr($options["unuscpmp_payment_key"])?>" name="unuscpmp_payment_key" id="unuscpmp_payment_key"></td>
                </tr>
 
                
                <?php

           

                
            }
 
 
            public static function checkout_boxes()
            {
                global $pmpro_requirebilling, $gateway, $pmpro_review, $wpdb;

                $setting_gateway = get_option("pmpro_gateway");
                if ($setting_gateway == "unuspay")
                {
                    echo '<h2>' . esc_html(esc_html__('Payment method', 'unuspay-crypto-payments-for-paid-memberships-pro')) . '</h2>';
                    echo esc_html(esc_html__('UnusPay', 'unuspay-crypto-payments-for-paid-memberships-pro')) . '<img style="vertical-align:middle" src="' . esc_url(plugins_url("/assets/images/unuspay.png", __FILE__)) . '" border="0" vspace="10" hspace="10" height="43" width="143"><br><br>';
                    return true;
                }


            }


          /*   public static function pmpro_pages_shortcode_confirmation($content)
            {
                global $wpdb;
 
                return $content;
            } */

            public function process(&$order)
            {
                if (!empty($order) && $order->gateway == "unuspay")
                {
                    $order->payment_type = "UnusPay Crypto Payment Gateway";
                    $order->cardtype = "";
                    $order->ProfileStartDate = pmpro_calculate_profile_start_date( $order, 'Y-m-d\TH:i:s\Z' );
                    $order->status = "pending";
                    if(empty($order->code)) $order->code = $order->getRandomCode();
                    $order->saveOrder();
                    do_action('pmpro_before_commit_express_checkout', $order);
                    $_SESSION['unuscpmp_pmp_orderid'] = $order->id;
                }

                return true;
            }

            public static function pmpro_unuscpmp_cryptocoin_payment(&$order)
            {
                global $unuspay, $pmpro_currency, $current_user, $wpdb;

                if (!$order)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html('The UnusPay payment gateway plugin was invoked to process a payment, but it was unable to fetch the order details. Therefore, the process cannot be carried forward. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the UnusPay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp') . "</div>";
                    return false;
                }
                if ( $order->total == 0)
                {
                    return true;
                }
                elseif ( $order->total < 0)
                {
                    echo "<div class='pmpro_message pmpro_error'>" . esc_html('The UnusPay payment gateway plugin was invoked to process a payment, but it was unable to fetch the order details. Therefore, the process cannot be carried forward. Please check the errors through the following steps: 1. Check your backend configuration to ensure it is correct. 2. Check your network environment. 3. Contact the UnusPay(contact@unuspay.com) service provider for further assistance.', 'unuspay-pmp') . "</div>";
                    return false;
                }
                
                $accept = self::getUnusPayOrder($order,$pmpro_currency);
                 
                wp_redirect(pmpro_url("checkout", "?level=" . $order->membership_level->id .   '#pmp-unuscpmp-checkout-' . $accept->id . '@' . time()));
                //$redirect_url = "Location: " .   '#pmp-unuscpmp-checkout-' . $accept->id . '@' . time();
                //header($redirect_url);
                exit();
                

 

            }

            public static function getUnusPayOrder($order,$pmpro_currency)
            {
                $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])) : '';
                $headers = array(
                    'accept-language' => $lang,
                    'Content-Type' => 'application/json; charset=utf-8',
                );
                $website = get_option("siteurl");

                $total = $order->total;

                $payment_key = pmpro_getOption("unuscpmp_payment_key");
                if (empty($payment_key)) {
                    throw new Exception('No payment key found!');
                }

                $post_response = wp_remote_post("https://dapp.unuspay.com/api/payment/ecommerce/order",
                    array(
                        'headers' => $headers,
                        'body' => json_encode([
                            'website' => $website,
                            'lang' => $lang,
                            'orderNo' => $order->id,
                            'email' => $order->email,
                            'payLinkId' => $payment_key,
                            'currency' => $pmpro_currency,
                            'amount' => $total,
                            'commerceType'=>3
                        ]),
                        'method' => 'POST',
                        'data_format' => 'body'
                    )
                );
                $post_response_code = $post_response['response']['code'];
                $post_response_successful = !is_wp_error($post_response_code) && $post_response_code == 200 ;
                if (!$post_response_successful) {
                    throw new Exception('request failed!');
                }
                $post_response_json = json_decode($post_response['body']);
                if ($post_response_json->code != 200) {
                    throw new Exception('request failed!');
                }

                return $post_response_json->data;
            }
             
            public static function httpPost($url, $data, $API_KEY)
            {
                $body = $data;
                $headers = array(
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Content-Length' => strlen($data),
                    'API-KEY' => $API_KEY,
                );
                $args = array(
                    'body' => $body,
                    'timeout'     => '5',
                    'redirection' => '5',
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => $headers,
                );

                $response = wp_remote_post($url, $args);

                if ($response)
                {
                    return $response;
                }
                return [];
            }

           

			public static function pmpro_checkout_before_change_membership_level($user_id, $order)
            {
                if ($order->total == 0)
                {
                    return true;
                }

                self::pmpro_unuscpmp_cryptocoin_payment($order);
                
                exit;
            }
        }
    }
}
 

add_action(
    'rest_api_init', 'init_pmp_rest_api'
);

function init_pmp_rest_api()
{
    register_rest_route(
        'unuspay/pmp',
        '/checkouts/(?P<id>[\w-]+)',
        [
            'methods' => 'POST',
            'callback' => 'get_pmp_checkout_accept',
            'permission_callback' => '__return_true'
        ]
    );
    register_rest_route(
        'unuspay/pmp',
        '/track',
        [
            'methods' => 'POST',
            'callback' => 'track_pmp_payment',
            'permission_callback' => '__return_true'
        ]
    );
    register_rest_route(
        'unuspay/pmp',
        '/validate',
        array(
            'methods' => 'POST,GET',
            'callback' => 'process_pmp_notify',
            'permission_callback' => '__return_true'
        )
    );
    register_rest_route(
        'unuspay/pmp',
        '/release',
        [
            'methods' => 'POST',
            'callback' => 'check_pmp_release',
            'permission_callback' => '__return_true'
        ]
    );

}

function get_pmp_checkout_accept($request)
{

    $id = $request->get_param('id');
     $endpoint = 'https://app.unuspay.com/api/payment/ecommerce/orderInfo?orderId='.$id;

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
        );

        $response = wp_remote_get($endpoint,
            array(
                'headers' => $headers   
            )
        );

         $rspBody = json_decode(wp_remote_retrieve_body($response));
        if (!is_wp_error($response) && (wp_remote_retrieve_response_code($response) == 200) && $rspBody->code == 200) {
             $rsp = rest_ensure_response($$rspBody->data);
        }
       

    
    return $rsp;
}

function track_pmp_payment($request)
{

    global $wpdb;
    $body = $request->get_body();
    $jsonBody = json_decode($body,true);
    $id = $jsonBody["orderId"];
    

    $endpoint = 'https://dapp.unuspay.com/api/payment/pay';

    $jsonBody["callback"] = get_site_url(null, 'wp-json/unuscpmp/pmp/validate');
    $jsonBody["trackingId"] = $tracking_uuid;
    $jsonBody["orderId"] = $id;

    $headers = array(
        'Content-Type' => 'application/json; charset=utf-8',
        'csrf_token' => $id
    );

    $post = wp_remote_post($endpoint,
        array(
            'headers' => $headers,
            'body' => json_encode($jsonBody),
            'method' => 'POST',
            'data_format' => 'body'
        )
    );

    $response = rest_ensure_response(json_decode(wp_remote_retrieve_body($post),true));
    $response->set_status(200);
    return $response;
}

function check_pmp_release($request)
{

    global $wpdb;
    $body = $request->get_body();
    $jsonBody = json_decode($body,true);

    $orderId =  $jsonBody["orderId"];
     

        $endpoint = 'https://dapp.unuspay.com/api/payment/release';

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
        );

        $response = wp_remote_post($endpoint,
            array(
                'headers' => $headers,
                'body' => json_encode($jsonBody),
                'method' => 'POST',
                'data_format' => 'body'
            )
        );
        $rspBody = json_decode(wp_remote_retrieve_body($response));
        if (!is_wp_error($response) && (wp_remote_retrieve_response_code($response) == 200) && $rspBody->code == 200) {
 
            $order = new MemberOrder();
            $order->getMemberOrderByID($order_id);
            if($order->status === 'success'){
                $response = rest_ensure_response([
                'code' => 200,
                'data' => [
                    'status' => 'success',
                    //'forward_to' => home_url('/membership-orders/').'?invoice='.$order->code
                    'forward_to' =>pmpro_url("confirmation", "?level=" . $order->membership_id)
                ]
                ]);
                $response->set_status(200);
                return $response;
            } 
            
            $status = $rspBody->data->status;
            

            if (
                'success' === $status 
            ) {
            
            order_success($order);
            $response = rest_ensure_response([
                'code' => 200,
                'data' => [
                    'status' => 'success',
                    //'forward_to' => home_url('/membership-orders/').'?invoice='.$order->code
                    'forward_to' =>pmpro_url("confirmation", "?level=" . $order->membership_id)
                ]
                ]);
                $response->set_status(200);
                return $response;

            } else if ('failed' === $status) {
                $failed_reason = 'fail';
                
                pmp_update_order_status($order_id, 'faild');
            }
            
        
        }
        $response->set_status(200);
        return $response;


    
        
    
}

function order_success($order){
    global $wpdb;
    $table   = esc_sql( $wpdb->pmpro_membership_levels );
    $pmpro_level =pmpro_getLevel( $order->membership_id );
    $user_id = $order->user_id;

    $old_startdate = current_time('timestamp');
    $old_enddate = current_time('timestamp');

    $active_levels = pmpro_getMembershipLevelsForUser($user_id);

    if (is_array($active_levels))
            foreach ($active_levels as $row)
            {
                if ($row->id == $pmpro_level->id && $row->enddate > current_time('timestamp'))
                {
                    $old_startdate = $row->startdate;
                    $old_enddate   = $row->enddate;
                }
            }

    $startdate = "'" . gmdate("Y-m-d H:i:s", $old_startdate) . "'";
    $enddate = (!empty($pmpro_level->expiration_number)) ? "'" . gmdate("Y-m-d H:i:s", strtotime("+ ".$pmpro_level->expiration_number." ".$pmpro_level->expiration_period, $old_enddate)) . "'" : "NULL";

    $custom_level = array(
        'user_id' => $user_id,
        'membership_id' => $pmpro_level->id,
        'code_id' => '',
        'initial_payment'   => $pmpro_level->initial_payment,
        'billing_amount' 	=> $pmpro_level->billing_amount,
        'cycle_number' 		=> $pmpro_level->cycle_number,
        'cycle_period' 		=> $pmpro_level->cycle_period,
        'billing_limit' 	=> $pmpro_level->billing_limit,
        'trial_amount' 		=> $pmpro_level->trial_amount,
        'trial_limit' 		=> $pmpro_level->trial_limit,
        'startdate' 		=> $startdate,
        'enddate' 			=> $enddate);

    // pmpro_changeMembershipLevel($new_membership_level_id, $user_id);
    pmpro_changeMembershipLevel($custom_level, $user_id, 'changed');
    $order->status = 'success';
    $order->saveOrder();

}

function process_pmp_notify(WP_REST_Request $request)
{
    global $wpdb;
    $response = new WP_REST_Response();
    $body = $request->get_body();
    $jsonBody = json_decode($body,true);

    $tracking_uuid = $jsonBody['trackingId'];
    $order_id = $jsonBody['originOrderId'];
   $order = new MemberOrder();
    $order->getMemberOrderByID($order_id);
    if ($order->status === 'success'){
        $response->set_status(200);
        return $response;
    }
    if (
        'success' === $status 
    ) {
        
        
        order_success($order);

    } else {
        
        pmp_update_order_status($order_id, 'failed');
    }

    $response->set_status(200);
    return $response;
}

add_action('wp_enqueue_scripts', 'pmp_custom_scripts');

function pmp_custom_scripts()
{
    // 仅在 EDD 结账页面加载
    //if (pmp_is_checkout()) {
    wp_register_script( 'UNUSCPMP_PMP_WIDGETS',plugin_dir_url(__FILE__) .'assets/js/widgets.bundle.js', array(), '1.0', true);
    wp_enqueue_script( 'UNUSCPMP_PMP_WIDGETS' );

    // 注册脚本（依赖 jQuery）
    wp_register_script(
        'UNUSCPMP_PMP_CHECKOUT',
        plugin_dir_url(__FILE__) . 'assets/js/checkout.js', // 脚本路径
        array('wp-api-request', 'jquery'), // 依赖
        '1.0', // 版本号
        true // 在页脚加载
    );


    // 加载脚本
    wp_enqueue_script('UNUSCPMP_PMP_CHECKOUT');
    //}
}
