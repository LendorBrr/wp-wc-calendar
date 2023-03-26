<?php
/**
 * Plugin Name: WooCommerce Date Time Picker
 * Plugin URI: https://www.elzeego.com
 * Description: This plugin adds a date and time picker to WooCommerce products.
 * Version: 1.0
 * Author: Elzee Go
 * Author URI: https://www.elzeego.com
 * License: GPL2
 * Text Domain: wc-date-time-picker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Date_Time_Picker' ) ) :

class WC_Date_Time_Picker {

    /**
     * Plugin instance.
     */
    protected static $_instance = null;

    /**
     * Main plugin instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_date_time_picker' ) );
        add_filter( 'woocommerce_get_sections_products', array( $this, 'add_product_settings_section' ) );
        add_filter( 'woocommerce_get_settings_products', array( $this, 'add_product_settings_fields' ), 10, 2 );
        add_action( 'woocommerce_add_to_cart_validation', array( $this, 'validate_date_time' ), 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_date_time_to_cart_item_data' ), 10, 3 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_date_time_in_cart' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_date_time_to_order_items' ), 10, 4 );
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_ajax_wc_available_hours', array($this, 'wc_available_hours'));
    add_action('wp_ajax_nopriv_wc_available_hours', array($this, 'wc_available_hours'));
    add_action('woocommerce_checkout_update_order_meta', array($this, 'woocommerce_checkout_update_order_meta'));
    add_action('woocommerce_admin_order_data_after_order_details', array($this, 'woocommerce_admin_order_data_after_order_details'));
    add_action('woocommerce_order_details_after_order_table', array($this, 'woocommerce_order_details_after_order_table'));
    // Conditionally add the 'woocommerce_add_to_cart_validation' filter
    add_action('wp', array($this, 'conditionally_add_validation_filter'));


        // Admin settings
        add_action( 'admin_menu', array( $this, 'register_taken_dates_times_page' ) );
        add_filter( 'woocommerce_get_sections_products', array( $this, 'add_settings_section' ) );
        add_filter( 'woocommerce_get_settings_products', array( $this, 'get_settings' ), 10, 2 );
    }

    public function enqueue_scripts() {
    wp_enqueue_script('jquery-ui-datepicker');
    wp_register_style('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css');
    wp_enqueue_style('jquery-ui');
    wp_register_script('wc-date-time-picker', plugin_dir_url(__FILE__) . 'wc-date-time-picker.js', array('jquery', 'jquery-ui-datepicker'));
    wp_enqueue_script('wc-date-time-picker');
}
    public function conditionally_add_validation_filter() {
    global $post;

    if (!is_product()) {
        return;
    }

    $allowed_products = get_option('wc_datetimepicker_products', array());
    $allowed_products = array_map('intval', $allowed_products);

    if (in_array($post->ID, $allowed_products)) {
        add_filter('woocommerce_add_to_cart_validation', array($this, 'woocommerce_add_to_cart_validation'), 10, 3);
    }
}


    // Create a new WooCommerce section under Products tab
public function add_product_settings_section( $sections ) {
  $sections['wc_datetimepicker_products'] = __( 'Date Time Picker Products', 'woocommerce' );
  return $sections;
}

// Add a setting field for selecting specific products
public function add_product_settings_fields( $settings, $current_section ) {
  if ( 'wc_datetimepicker_products' === $current_section ) {
    $settings = array(
      array(
        'title' => __( 'Select Products for Date Time Picker', 'woocommerce' ),
        'desc'  => __( 'Choose the products that should display the calendar.', 'woocommerce' ),
        'id'    => 'wc_datetimepicker_products',
        'type'  => 'multiselect',
        'options' => $this->get_all_products(),
        'css'   => 'width: 50%;',
      ),
      array(
        'type' => 'sectionend',
        'id' => 'wc_datetimepicker_products',
      ),
    );
  }
  return $settings;
}

// Get all products for the multiselect options
public function get_all_products() {
  $args = array(
    'post_type' => 'product',
    'posts_per_page' => -1,
  );
  $products = get_posts( $args );
  $options = array();
  foreach ( $products as $product ) {
    $options[$product->ID] = $product->post_title;
  }
  return $options;
}


    public function register_taken_dates_times_page() {
    add_submenu_page( 'woocommerce', 'Taken Dates and Times', 'Taken Dates & Times', 'manage_options', 'wc-taken-dates-times', array( $this, 'taken_dates_times_page' ) );
}

public function taken_dates_times_page() {
    global $wpdb;

    $results = $wpdb->get_results( "SELECT order_id, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = 'Date & Time'" );

    echo '<div class="wrap"><h1>Taken Dates and Times</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Order ID</th><th>Date & Time</th></tr></thead><tbody>';

    foreach ( $results as $row ) {
    echo '<tr><td>' . esc_html( $row->order_id ) . '</td><td>' . esc_html( $row->meta_value ) . '</td></tr>';
    }

    echo '</tbody></table></div>';
}

    public function add_date_time_picker() {
        global $post;
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_script('jquery-timepicker', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.3.2/jquery.timepicker.min.js', array( 'jquery' ), '1.3.3.2', true );
    wp_enqueue_style( 'jquery-timepicker', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.3/jquery.timepicker.min.css', array(), '1.3.3' );
    wp_enqueue_script( 'wc-date-time-picker-script', plugin_dir_url( __FILE__ ) . 'assets/js/wc-date-time-picker.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-timepicker' ), '1.0', true );

 if (!is_product()) {
        return;
    }

    $allowed_products = get_option('wc_datetimepicker_products', array());
    $allowed_products = array_map('intval', $allowed_products);

    if (!in_array($post->ID, $allowed_products)) {
        return; // Add this line to conditionally add the action based on the product ID
    }

    wp_register_script('wc-date-time-picker', plugins_url('wc-date-time-picker.js', __FILE__), array('jquery'), '1.0', true);

    wp_localize_script('wc-date-time-picker', 'wc_datetime_picker_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'allowed_products' => $allowed_products,
        'current_product_id' => $post->ID,
    ));

    wp_enqueue_script('wc-date-time-picker');
    
    // Add the action for the selected products
    add_action('woocommerce_before_add_to_cart_button', array($this, 'wc_before_add_to_cart_button'));
        $style = get_option( 'wc_date_time_picker_style', 'classic' );
        wp_enqueue_style( 'wc-date-time-picker-style', plugin_dir_url( __FILE__ ) . 'assets/css/' . $style . '.css', array(), '1.0' );
        wp_enqueue_script( 'wc-date-time-picker-script', plugin_dir_url( __FILE__ ) . 'assets/js/wc-date-time-picker.js', array( 'jquery' ), '1.0', true );

$min_date = get_option( 'wc_date_time_picker_min_date', '0' );
    $min_time = get_option( 'wc_date_time_picker_min_time', '0' );
    $available_dates = get_option( 'wc_date_time_picker_available_dates', '' );
    $available_time_slots = get_option( 'wc_date_time_picker_available_time_slots', '' );
        $min_date = get_option( 'wc_date_time_picker_min_date', '0' );
        $min_time = get_option( 'wc_date_time_picker_min_time', '0' );
        $min_date = get_option( 'wc_date_time_picker_min_date', '0' );
    $min_time = get_option( 'wc_date_time_picker_min_time', '0' );
    $available_dates = get_option( 'wc_date_time_picker_available_dates', '' );
    $available_time_slots = get_option( 'wc_date_time_picker_available_time_slots', '' );

    $available_dates = array_map( 'trim', explode( ',', $available_dates ) );
$available_time_slots = array_map( 'trim', explode( ',', $available_time_slots ) );
wp_localize_script( 'wc-date-time-picker-script', 'wcDateTimePickerData', array(
    'min_date' => $min_date,
    'min_time' => $min_time,
    'available_dates' => $available_dates,
    'available_time_slots' => $available_time_slots,
));


        ?>
        <div class="wc-date-time-picker">
 <!-- Date picker and time picker input elements -->
            <p>
                <label for="wc-date-picker"><?php _e( 'Choose date', 'wc-date-time-picker' ); ?></label>
                <input type="text" id="wc-date-picker" name="wc_date" data-min-date="<?php echo esc_attr( $min_date ); ?>">
            </p>
            <p>
                <label for="wc-time-picker"><?php _e( 'Choose time', 'wc-date-time-picker' ); ?></label>
                <input type="text" id="wc-time-picker" name="wc_time" data-min-time="<?php echo esc_attr( $min_time ); ?>">
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var availableDates = <?php echo json_encode( $available_dates ); ?>;
            var availableTimeSlots = <?php echo json_encode( $available_time_slots ); ?>;
            // ...
        });
    </script>

    <?php
}

    public function validate_date_time( $passed, $product_id, $quantity ) {
        if ( empty( $_POST['wc_date'] ) || empty( $_POST['wc_time'] ) ) {
            $passed = false;
            wc_add_notice( __( 'Please choose a date and time before adding
            to the cart.', 'wc-date-time-picker' ), 'error' );
        }

        return $passed;
    }

    public function add_date_time_to_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['wc_date'] ) && isset( $_POST['wc_time'] ) ) {
            $cart_item_data['wc_date_time'] = sanitize_text_field( $_POST['wc_date'] ) . ' ' . sanitize_text_field( $_POST['wc_time'] );
        }

        return $cart_item_data;
    }

    public function display_date_time_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['wc_date_time'] ) ) {
            $item_data[] = array(
                'key'     => __( 'Date & Time', 'wc-date-time-picker' ),
                'value'   => wc_clean( $cart_item['wc_date_time'] ),
                'display' => '',
            );
        }

        return $item_data;
    }

    public function add_date_time_to_order_items( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['wc_date_time'] ) ) {
            $item->add_meta_data( __( 'Date & Time', 'wc-date-time-picker' ), $values['wc_date_time'], true );
        }
    }

    //
    // Admin settings
    public function add_settings_section( $sections ) {
        $sections['wc_date_time_picker'] = __( 'Date Time Picker', 'wc-date-time-picker' );
        return $sections;
    }

    public function get_settings( $settings, $current_section ) {
        if ( $current_section == 'wc_date_time_picker' ) {
            $settings = array(
                array(
                    'title' => __( 'Date Time Picker Settings', 'wc-date-time-picker' ),
                    'type'  => 'title',
                    'desc'  => '',
                    'id'    => 'wc_date_time_picker_options',
                ),
                array(
                    'title'   => __( 'Style', 'wc-date-time-picker' ),
                    'desc'    => __( 'Choose the style for the date time picker.', 'wc-date-time-picker' ),
                    'id'      => 'wc_date_time_picker_style',
                    'default' => 'classic',
                    'type'    => 'select',
                    'options' => array(
                        'classic' => __( 'Classic', 'wc-date-time-picker' ),
                        'modern'  => __( 'Modern', 'wc-date-time-picker' ),
                    ),
                ),
                array(
                    'title'   => __( 'Minimum Date', 'wc-date-time-picker' ),
                    'desc'    => __( 'Set the minimum selectable date (in days from now). Default: 0', 'wc-date'),
                    'type'    => 'number',
                    'id'      => 'wc_date_time_picker_min_date',
                    'default' => '0',
                ),
                array(
                    'title'   => __( 'Minimum Time', 'wc-date-time-picker' ),
                    'desc'    => __( 'Set the minimum selectable time (in hours from now). Default: 0', 'wc-date-time-picker' ),
                    'type'    => 'number',
                    'id'      => 'wc_date_time_picker_min_time',
                    'default' => '0',
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'wc_date_time_picker_options',
                ),
                array(
                    'title'   => __( 'Available Dates', 'wc-date-time-picker' ),
                    'desc'    => __( 'Set the available dates in the format YYYY-MM-DD, separated by commas.', 'wc-date-time-picker' ),
                     'id'      => 'wc_date_time_picker_available_dates',
                     'default' => '',
                     'type'    => 'textarea',
                ),
                array(
                    'title'   => __( 'Available Time Slots', 'wc-date-time-picker' ),
                     'desc'    => __( 'Set the available time slots in the format HH:mm, separated by commas.', 'wc-date-time-picker' ),
                     'id'      => 'wc_date_time_picker_available_time_slots',
                     'default' => '',
                ),
            );
        }

        return $settings;
    }

}

WC_Date_Time_Picker::instance();

endif;
