<?php
if ( ! isset( $atts ) ) {
	return;
}
defined( 'ABSPATH' ) || exit();

global $hb_settings;

$min_value = hb_get_request( 'min_price' );
$max_value = hb_get_request( 'max_price' );

$fields = apply_filters(
	'hotel_booking/shortcode/search-filter-v2/field/fields',
	array(
		'selection' => array(),
		'price'     => array(
			'min_price' => $atts['min_price'] ?? $hb_settings->get( 'filter_price_min', 0 ),
			'max_price' => $atts['max_price'] ?? $hb_settings->get( 'filter_price_max', 0 ),
			'step_price' => $atts['step_price'] ?? $hb_settings->get( 'filter_price_step', 1 ),
			'min_value' => hb_get_request( 'min_price' ),
			'max_value' => hb_get_request( 'max_price' ),
		),
		'rating'    => array(),
		'types'     => array(),
	)
);
?>
<div id="hotel-booking-search-filter" class="hotel-booking-search-filter">
    <h3><?php esc_html_e( 'Filter By', 'wp-hotel-booking' ); ?></h3>
    <form class="search-filter-form" action="">
        <div class="hb-form-table">
            <div class="clear-filter">
                <button type="button">
					<?php esc_html_e( 'Clear all fields', 'wp-hotel-booking' ); ?>
                </button>
            </div>
			<?php
			foreach ( $fields as $key => $data ) {
				hb_get_template( 'search/v2/search-filter/' . $key . '.php', compact( 'data' ) );
			}
			?>
        </div>
    </form>
</div>
