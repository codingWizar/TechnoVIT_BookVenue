<?php
if ( ! isset( $data ) ) {
	return;
}
?>
	<div class="hb-rating-field">
		<h4><?php esc_html_e( ' Star rating', 'wp-hotel-booking' ); ?></h4>
		<ul class="rating-list">
			<?php
			for ( $i = 1; $i <= 4; $i++ ) {
				?>
				<li class="list-item">
					<div class="rating">
						<label>
							<input type="radio" name="rating" value="<?php echo esc_attr( $i ); ?>">
							<span>
								<?php
								printf( esc_html( _n( '%s star &amp; up', '%s stars &amp; up', $i, 'wp-hotel-booking' ) ), $i );
								?>
							</span>
						</label>
					</div>
					<div class="rating-number">
						<?php echo esc_html( wp_hotel_booking_get_count_rating( $i ) ); ?>
					</div>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
<?php
