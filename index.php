// Customizing WooCommerce radio form field
function filter_woocommerce_form_field_radio( $field, $key, $args, $value ) {   
    // Specific key and apply on checkout page
    if ( ! empty( $args['options'] ) && $key == 'radio_packing' && is_checkout() ) {
        $field = str_replace( '</label><input ', '</label><br><input ', $field );
        $field = str_replace( '<label ', '<label style="display:inline;margin-left:8px;" ', $field );
    }
    
    return $field;
}
add_filter( 'woocommerce_form_field_radio', 'filter_woocommerce_form_field_radio', 20, 4 );

function action_woocommerce_cart_calculate_fees( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;
    
    // Dynamic packing fee
    $packing_fee = WC()->session->get( 'chosen_packing' );
    
    // Determine packing fee
    if ( $packing_fee === 'bag' ) {
        $fee = 5.00;
    } else if( $packing_fee === 'box' ) {
        $fee = 29.00;
    } else if( $packing_fee === 'both' ) {
        $fee = 25.00;
    } else {
        $fee = 0.00;
    }
    
    // Add fee: name - amount - taxable 
    $cart->add_fee( __( 'Packaging fee', 'woocommerce' ), $fee, true );
}
add_action( 'woocommerce_cart_calculate_fees', 'action_woocommerce_cart_calculate_fees', 10, 1 );

// Add a custom radio fields for packaging selection
function action_woocommerce_review_order_after_shipping() {
    // Domain
    $domain = 'woocommerce';

    // Output
    echo '<tr class="packing-select"><th>' . __('Packing options', $domain ) . '</th><td>';

    $chosen = WC()->session->get( 'chosen_packing' );

    $chosen = empty( $chosen ) ? WC()->checkout->get_value( 'radio_packing' ) : $chosen;

    $chosen = empty( $chosen ) ? 'none' : $chosen;

    // Add a custom checkbox field
    woocommerce_form_field( 'radio_packing', array(
        'type'      => 'radio',
        'class'     => array( 'form-row-wide packing' ),
        'options'   => array(
            'bag'       => sprintf( __( 'Yes, give it to me in a bag for %s', $domain ), strip_tags( wc_price( 5.00 ) ) ),
            'box'       => sprintf( __( 'Giftbox + Wrapping for %s', $domain ), strip_tags( wc_price( 29.00 ) ) ),
            'both'      => sprintf( __( 'Wrapped Giftbox in a Bag for %s', $domain ), strip_tags( wc_price( 25.00 ) ) ),
            'none'      => sprintf( __( 'Just the product at no extra cost %s', $domain ), strip_tags( wc_price( 0.00 ) ) )
        ),
        'default'   => $chosen,
    ), $chosen );
    
    echo '</td></tr>';
}
add_action( 'woocommerce_review_order_after_shipping', 'action_woocommerce_review_order_after_shipping', 10, 0 );

// jQuery - Ajax script
function action_wp_footer() {
    if ( ! is_checkout() )
        return; // Only checkout page
    ?>
    <script type="text/javascript">
    jQuery( function($){
        $('form.checkout').on('change', 'input[name=radio_packing]', function(e){
            e.preventDefault();
            var p = $(this).val();
            $.ajax({
                type: 'POST',
                url: wc_checkout_params.ajax_url,
                data: {
                    'action': 'woo_get_ajax_data',
                    'packing': p,
                },
                success: function (result) {
                    $('body').trigger('update_checkout');
                    console.log('response: '+result); // just for testing | TO BE REMOVED
                },
                error: function(error){
                    console.log(error); // just for testing | TO BE REMOVED
                }
            });
        });
    });
    </script>
    <?php

}
add_action( 'wp_footer', 'action_wp_footer', 10, 0 );

// Php Ajax (Receiving request and saving to WC session)
function woo_get_ajax_data() {
    if ( isset($_POST['packing']) ){
        $packing = sanitize_key( $_POST['packing'] );
        WC()->session->set('chosen_packing', $packing );
        echo json_encode( $packing );
    }

    die(); // Always at the end (to avoid server error 500)
}
add_action( 'wp_ajax_woo_get_ajax_data', 'woo_get_ajax_data' );
add_action( 'wp_ajax_nopriv_woo_get_ajax_data', 'woo_get_ajax_data' );