<?php
/**
 * WP Hotel Booking list rooms shortcode.
 *
 * @version       1.9.6
 * @author        ThimPress
 * @package       WP_Hotel_Booking/Classes/Shortcode
 * @category      Classes
 * @author        Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPHB_Shortcode_Hotel_Booking_Rooms extends WPHB_Shortcodes {

	public $shortcode = 'hotel_booking_rooms';

	public function __construct() {
		parent::__construct();
	}

	function add_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'room_type'   => '',
				'orderby'     => 'date',
				'order'       => 'DESC',
				'number_room' => - 1,
				'room_in'     => '',
				'room_not_in' => '',
			),
			$atts
		);

		$args = array(
			'post_type'      => 'hb_room',
			'posts_per_page' => $atts['number_room'],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
		);

		$sort_by = hb_get_request( 'sort_by' );
		if ( $sort_by ) {
			if ( $sort_by === 'date-desc' ) {
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
			} elseif ( $sort_by === 'date-asc' ) {
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
			} elseif ( $sort_by === 'title-asc' ) {
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
			} elseif ( $sort_by === 'title-desc' ) {
				$args['orderby'] = 'title';
				$args['order']   = 'DESC';
			}
		}
		if ( isset( $atts['room_type'] ) && $atts['room_type'] ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'hb_room_type',
					'field'    => 'slug',
					'terms'    => $atts['room_type'],
				),
			);
		}

		if ( isset( $atts['room_in'] ) && $atts['room_in'] ) {
			$args['post__in'] = explode( ',', $atts['room_in'] );
		}

		if ( isset( $atts['room_not_in '] ) && $atts['room_not_in '] ) {
			$args['post__not_in'] = explode( ',', $atts['room_not_in'] );
		}

		/* remove action */
		remove_action( 'pre_get_posts', 'hotel_booking_num_room_archive', 999 );

		$query = new WP_Query( $args );

		ob_start();
		?>
        <div class="container">
			<?php
			$data = array(
				'sort_by' => hb_get_request( 'sort_by' )
			);

			$room_number = $query->post_count;
			if ( $room_number ) {
				$data['show_number'] = sprintf( esc_html__( 'Show 1 - %1s of %2s' ), $room_number, $room_number );

			}

			hb_get_template( 'search/v2/sort-by.php', compact( 'data' ) );
			if ( $query->have_posts() ) :
				hotel_booking_room_loop_start();

				while ( $query->have_posts() ) :
					$query->the_post();

					hb_get_template_part( 'content', 'room' );

				endwhile; // end of the loop.

				hotel_booking_room_loop_end();
			else :
				_e( 'No room found', 'wp-hotel-booking' );
			endif;
			wp_reset_postdata();
			/* add action again */
			add_action( 'pre_get_posts', 'hotel_booking_num_room_archive', 999 );
			?>
        </div>
		<?php
		return ob_get_clean();
	}
}

new WPHB_Shortcode_Hotel_Booking_Rooms();
