<?php

/**
 * Reviews Widget
 *
 * Displays a list of recent/upcoming/monthly reviews.
 *
 * @package   book-database
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */
class BDB_Reviews_Widget extends WP_Widget {

	/**
	 * BDB_Reviews_Widget constructor.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'bdb_reviews',
			esc_html__( 'BDB - Reviews', 'book-database' ),
			array( 'description' => esc_html__( 'Display a list of recent, upcoming, or monthly reviews.', 'book-database' ) )
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from the database.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function widget( $args, $instance ) {

		$number  = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$format  = ( isset( $instance['display'] ) && 'images' == $instance['display'] ) ? 'images' : 'list';
		$range   = isset( $instance['range'] ) ? $instance['range'] : 'recent';
		$reviews = $this->query_reviews( $range, absint( $number ) );

		if ( empty( $reviews ) ) {
			echo 'No Reviews';

			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$output = get_transient( 'bdb_reviews_widget_' . $this->id );

		if ( false === $output ) {

			ob_start();

			if ( 'images' == $format ) {
				$this->format_reviews_images( $reviews );
			} else {
				$this->format_reviews_list( $reviews );
			}

			$output = ob_get_clean();

			set_transient( 'bdb_reviews_widget_' . $this->id, $output, DAY_IN_SECONDS );

		}

		echo $output;

		echo $args['after_widget'];

	}

	/**
	 * Query Reviews
	 *
	 * @param string $range  Range to get reviews from.
	 * @param int    $number Number of results.
	 *
	 * @access protected
	 * @since  1.0
	 * @return array|bool
	 */
	protected function query_reviews( $range = 'recent', $number = 5 ) {

		$number = ( $number > 0 ) ? absint( $number ) : - 1;

		global $wpdb;

		$reviews = book_database()->reviews->table_name;
		$books   = book_database()->books->table_name;
		$where   = 'WHERE 1=1';

		switch ( $range ) {
			case 'upcoming' :
				$where .= $wpdb->prepare( " AND `date_published` >= %s", current_time( 'mysql', true ) );
				break;

			case 'this_month' :
				$start = gmdate( 'Y-m-01 00:00:00' );
				$end   = current_time( 'mysql', true );

				$where .= $wpdb->prepare( " AND `date_published` >= %s", $start );
				$where .= $wpdb->prepare( " AND `date_published` <= %s", $end );
				break;

			case 'last_month' :
				$last_month = strtotime( '-1month' );
				$start      = gmdate( 'Y-m-01 00:00:00', $last_month );
				$end        = gmdate( 'Y-m-t 23:59:59', $last_month );

				$where .= $wpdb->prepare( " AND `date_published` >= %s", $start );
				$where .= $wpdb->prepare( " AND `date_published` <= %s", $end );
				break;

			default :
				$where .= $wpdb->prepare( " AND `date_published` <= %s", current_time( 'mysql', true ) );
				break;
		}

		$query = $wpdb->prepare(
			"SELECT r.*
						FROM {$reviews} AS r 
						INNER JOIN {$books} AS b ON b.ID = r.book_id
						{$where}
						GROUP BY r.ID
						ORDER BY date_published DESC
						LIMIT %d",
			$number
		);

		$reviews = $wpdb->get_results( $query );

		return wp_unslash( $reviews );

	}

	/**
	 * Format list of reviews
	 *
	 * @param array $reviews Array of reviews from the database.
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function format_reviews_list( $reviews ) {

		?>
		<ul>
			<?php foreach ( $reviews as $review ) :
				$review = new BDB_Review( $review );
				$book = new BDB_Book( $review->book_id );
				$rating = $review->get_rating();
				?>
				<li>
					<?php
					$output = '';

					$output .= ( $review->is_review_published() && $review->get_permalink() ) ? '<a href="' . esc_url( $review->get_permalink() ) . '">' : '';
					$output .= sprintf( _x( '%s by %s', 'book title by author', 'book-database' ), $book->get_title(), $book->get_author_names() );

					if ( ! empty( $rating ) ) {
						$obj    = new BDB_Rating( $rating );
						$output .= ' - ' . $obj->format_html_stars();
					}

					$output .= ( $review->is_review_published() && $review->get_permalink() ) ? '</a>' : '';

					echo apply_filters( 'book-database/widget/reviews/list-entry', $output, $review, $this );
					?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php

	}

	/**
	 * Format review images
	 *
	 * @param array $reviews Array of reviews from the database.
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function format_reviews_images( $reviews ) {

		?>
		<div class="bookdb-book-grid bookdb-review-images">
			<?php foreach ( $reviews as $review ) :
				$review = new BDB_Review( $review );
				$book = new BDB_Book( $review->book_id );
				$output = '';
				?>
				<div class="bookdb-book-grid-entry bookdb-review-image">
					<?php
					$output .= ( $review->is_review_published() && $review->get_permalink() ) ? '<a href="' . esc_url( $review->get_permalink() ) . '">' : '';
					$output .= $book->get_cover( 'large' );
					$output .= ( $review->is_review_published() && $review->get_permalink() ) ? '</a>' : '';

					echo apply_filters( 'book-database/widget/reviews/image-entry', $output, $review, $this );
					?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php

	}

	/**
	 * Back-end widget form.
	 *
	 * @see    WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from the database.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'   => '',
			'number'  => 5,
			'display' => 'list',
			'range'   => 'recent'
		);

		$instance = wp_parse_args( $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'book-database' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Maximum Results', 'book-database' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $instance['number'] ); ?>">
			<span class="description"><?php _e( 'Set to 0 for unlimited.', 'book-database' ); ?></span>
		</p>

		<p>
			<strong><?php _e( 'Display:', 'book-database' ); ?></strong>
			<br>
			<input id="<?php echo $this->get_field_id( 'display_list' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="list" <?php checked( $instance['display'], 'list' ); ?>>
			<label for="<?php echo $this->get_field_id( 'display_list' ); ?>"><?php _e( 'Display as list', 'book-database' ); ?></label>
			<br>
			<input id="<?php echo $this->get_field_id( 'display_images' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="images" <?php checked( $instance['display'], 'images' ); ?>>
			<label for="<?php echo $this->get_field_id( 'display_images' ); ?>"><?php _e( 'Display as images', 'book-database' ); ?></label>
		</p>

		<p>
			<strong><?php _e( 'Reviews to Show:', 'book-database' ); ?></strong>
			<br>
			<input id="<?php echo $this->get_field_id( 'range_recent' ); ?>" name="<?php echo $this->get_field_name( 'range' ); ?>" type="radio" value="recent" <?php checked( $instance['range'], 'recent' ); ?>>
			<label for="<?php echo $this->get_field_id( 'range_recent' ); ?>"><?php _e( 'Recent reviews', 'book-database' ); ?></label>
			<br>
			<input id="<?php echo $this->get_field_id( 'range_upcoming' ); ?>" name="<?php echo $this->get_field_name( 'range' ); ?>" type="radio" value="upcoming" <?php checked( $instance['range'], 'upcoming' ); ?>>
			<label for="<?php echo $this->get_field_id( 'range_upcoming' ); ?>"><?php _e( 'Upcoming reviews', 'book-database' ); ?></label>
			<br>
			<input id="<?php echo $this->get_field_id( 'range_this_month' ); ?>" name="<?php echo $this->get_field_name( 'range' ); ?>" type="radio" value="this_month" <?php checked( $instance['range'], 'this_month' ); ?>>
			<label for="<?php echo $this->get_field_id( 'range_this_month' ); ?>"><?php _e( 'Reviews from this month', 'book-database' ); ?></label>
			<br>
			<input id="<?php echo $this->get_field_id( 'range_last_month' ); ?>" name="<?php echo $this->get_field_name( 'range' ); ?>" type="radio" value="last_month" <?php checked( $instance['range'], 'last_month' ); ?>>
			<label for="<?php echo $this->get_field_id( 'range_last_month' ); ?>"><?php _e( 'Reviews from last month', 'book-database' ); ?></label>
		</p>
		<?php

	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see    WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @access public
	 * @since  1.0
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance            = array();
		$instance['title']   = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['number']  = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;
		$instance['display'] = ( 'images' == $new_instance['display'] ) ? 'images' : 'list';

		$allowed_reviews   = array( 'recent', 'upcoming', 'this_month', 'last_month' );
		$instance['range'] = in_array( $new_instance['range'], $allowed_reviews ) ? wp_strip_all_tags( $new_instance['range'] ) : 'recent';

		// Clear transient.
		delete_transient( 'bdb_reviews_widget_' . $this->id );

		return $instance;

	}

}

add_action( 'widgets_init', function () {
	register_widget( 'BDB_Reviews_Widget' );
} );