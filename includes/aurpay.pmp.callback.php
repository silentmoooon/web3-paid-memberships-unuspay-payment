<?php

if (!defined('ABSPATH')) exit;

if (isset($_GET["platform"]) && ($_GET["platform"] == "AURPAY") && isset($_GET["type"]) && ($_GET["type"] == "AURPAYPMP"))
{

    if (isset($_GET["status"]) && isset($_GET["order_id"]) && isset($_GET["unuspay_order_id"]))
    {
        $data = [];
        $status = sanitize_text_field($_GET["status"]);
        $order_id = sanitize_text_field($_GET["order_id"]);
        $unuspay_order_id = sanitize_text_field($_GET["unuspay_order_id"]);

        if ($status == "completed")
        {
            global $wpdb;

            $table_name = "wp_pmpro_membership_orders";
            $order_status = "success";

            require_once ABSPATH . 'wp-load.php';
            require_once(PMPRO_DIR . '/classes/class.memberorder.php');
            $order = new MemberOrder();
            $order->getMemberOrderByID($order_id);
            
			$pmpro_level = $wpdb->get_row("SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '" . (int)$order->membership_id . "' LIMIT 1");

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

            $startdate = "'" . date("Y-m-d H:i:s", $old_startdate) . "'";
            $enddate = (!empty($pmpro_level->expiration_number)) ? "'" . date("Y-m-d H:i:s", strtotime("+ ".$pmpro_level->expiration_number." ".$pmpro_level->expiration_period, $old_enddate)) . "'" : "NULL";

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

            $order->membership_id = $pmpro_level->id;
            $order->status = 'success';
            $order->payment_transaction_id = $unuspay_order_id;
            $order->saveOrder();

            $data['code'] = 200;
            $data['result'] = 1;
            $data['message'] = '[Unuspay PMP] Success! Order Payment status already updated.';

            status_header(200);
            header('Content-Type: application/json; charset=utf-8');
            echo wp_json_encode($data);
            exit();
        }
        else
        {
            $data['code'] = 500;
            $data['result'] = 0;
            $data['message'] = '[Unuspay PMP] Failed: Order status is incorrect.';

            status_header(500);
            header('Content-Type: application/json; charset=utf-8');
            echo wp_json_encode($data);
            exit();
        }
    }
    else
    {
        $data['code'] = 500;
        $data['result'] = 0;
        $data['message'] = '[Unuspay PMP] Failed: status, order_id and type didn\'t exist';

        status_header(500);
        header('Content-Type: application/json; charset=utf-8');
        echo wp_json_encode($data);
        exit();
    }

    status_header(500);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($data);
    exit();
}