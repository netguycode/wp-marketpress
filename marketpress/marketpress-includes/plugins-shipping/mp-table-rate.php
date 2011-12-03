<?php
/*
MarketPress Table-Rate Shipping Plugin
Author: Nick Bunn (Salty Dog Interactive)
Version: 1.0
*/

class MP_Shipping_Table_Rate extends MP_Shipping_API {

  //private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'table-rate';

  //public name of your method, for lists and such.
  var $public_name = '';

  //set to true if you need to use the shipping_metabox() method to add per-product shipping options
  var $use_metabox = true;

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //declare here for translation
    $this->public_name = __('Table Rate', 'mp');
	}

  /**
   * Echo anything you want to add to the top of the shipping screen
   */
	function before_shipping_form() {

  }

  /**
   * Echo anything you want to add to the bottom of the shipping screen
   */
	function after_shipping_form() {

  }

  /**
   * Echo a table row with any extra shipping fields you need to add to the form
   */
	function extra_shipping_field() {

  }

  /**
   * Use this to process any additional field you may add. Use the $_POST global,
   *  and be sure to save it to both the cookie and usermeta if logged in.
   */
	function process_shipping_form() {

  }

	/**
   * Echo a settings meta box with whatever settings you need for you shipping module.
   *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
   *  You can access saved settings via $settings array.
   */
	function shipping_settings_box($settings) {
		global $mp;
    ?>
    <script type="text/javascript">
			jQuery(document).ready(function ($) {
				$("#mp-table-rate-rows").change(function() {
					$("#mp-shipping-form").submit();
				});
			});
    </script>
    <div id="mp_table_rate" class="postbox">
      <h3 class='hndle'><span><?php _e('Table Rate Settings', 'mp'); ?></span></h3>
      <div class="inside">
        <span class="description"><?php _e('Be sure to enter a shipping price for every option or those customers may get free shipping. Each layer must be a higher price than the previous.', 'mp') ?></span>
        <table class="form-table">
					<tr>
      		<td scope="row"><?php _e('Number of Layers:', 'mp');?>
      			<select name="mp[shipping][table-rate][rowcount]" id="mp-table-rate-rows">
      				<?php for ( $k = 1; $k <= 20; $k++ )	{	?>
							<option value="<?php echo $k; ?>" <?php selected($settings['shipping']['table-rate']['rowcount'], $k); ?>><?php echo $k; ?></option>
        			<?php }	?>
            </select>
         	</td>
					</tr>
					<tr>
						<th>
						<?php _e('Each layer must be a higher price than the one above it.', 'mp') ?>
						</th>
					</tr>
					<tr>
						<td scope="row"><?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][0][mincost]" value="0.01" size="5" maxlength="10" disabled="disabled" /><?php _e(' and above.', 'mp'); ?>
								<?php _e('Shipping Cost: ', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][shipping]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['shipping']); ?>" size="5" maxlength="10" />
						</td>
					</tr>
					<?php for ( $i = 1; $i < $settings['shipping']['table-rate']['rowcount']; $i++ ) { ?>
					<tr>
						<td scope="row"><?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i; ?>][mincost]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['mincost']); ?>" size="5" maxlength="10" /><?php _e(' and above.', 'mp'); ?>
							<?php _e('Shipping Cost: ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i?>][shipping]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['shipping']); ?>" size="5" maxlength="10" />
						</td>
					</tr>
					<?php } ?>
        </table>
      </div>
    </div>
    <?php
  }

  /**
   * Filters posted data from your form. Do anything you need to the $settings['shipping']['plugin_name']
   *  array. Don't forget to return!
   */
	function process_shipping_settings($settings) {

    return $settings;
  }

  /**
   * Echo any per-product shipping fields you need to add to the product edit screen shipping metabox
   *
   * @param array $shipping_meta, the contents of the post meta. Use to retrieve any previously saved product meta
   * @param array $settings, access saved settings via $settings array.
   */
	function shipping_metabox($shipping_meta, $settings) {
		global $mp;
    ?>
    <label><?php _e('Extra Shipping Cost', 'mp'); ?>:<br />
    <?php echo $mp->format_currency(); ?><input type="text" size="6" id="mp_extra_shipping_cost" name="mp_extra_shipping_cost" value="<?php echo ($shipping_meta['extra_cost']) ? $mp->display_currency($shipping_meta['extra_cost']) : '0.00'; ?>" />
    </label>
    <?php

  }

  /**
   * Save any per-product shipping fields from the shipping metabox using update_post_meta
   *
   * @param array $shipping_meta, save anything from the $_POST global
   * return array $shipping_meta
   */
	function save_shipping_metabox($shipping_meta) {
		$shipping_meta['extra_cost'] = (!empty($_POST['mp_extra_shipping_cost'])) ? round($_POST['mp_extra_shipping_cost'], 2) : 0;

    return $shipping_meta;
  }

  /**
   * Use this function to return your calculated price as an integer or float
   *
   * @param int $price, always 0. Modify this and return
   * @param float $total, cart total after any coupons and before tax
   * @param array $cart, the contents of the shopping cart for advanced calculations
   * @param string $address1
   * @param string $address2
   * @param string $city
   * @param string $state, state/province/region
   * @param string $zip, postal code
   * @param string $country, ISO 3166-1 alpha-2 country code
   *
   * return float $price
   */
	function calculate_shipping($price, $total, $cart, $address1, $address2, $city, $state, $zip, $country) {
		global $mp;
    $settings = get_option('mp_settings');

    //don't charge shipping if only digital products
    if ( $mp->download_only_cart($cart) )
      return 0;
		
		for ($i = $settings['shipping']['table-rate']['rowcount'] - 1; $i >= 0; $i--) {
			if ($total >= $settings['shipping']['table-rate'][$i]['mincost']) {
				$price = $settings['shipping']['table-rate'][$i]['shipping'];
				break;
			}
		}

    //calculate extra shipping
    $extras = array();
    foreach ($cart as $product_id => $variations) {
	    $shipping_meta = get_post_meta($product_id, 'mp_shipping', true);
			foreach ($variations as $variation => $data) {
			  if (!$data['download'])
	      	$extras[] = $shipping_meta['extra_cost'] * $data['quantity'];
			}
    }
    $extra = array_sum($extras);

    //merge
    $price = round($price + $extra, 2);

    return $price;
  }

}

//register plugin - uncomment to register
mp_register_shipping_plugin( 'MP_Shipping_Table_Rate', 'table-rate', __('Table Rate', 'mp') );
?>