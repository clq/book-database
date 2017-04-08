<?php

/**
 * Reading Log Widget
 *
 * Displays a list of the last x books read.
 *
 * @package   book-database
 * @copyright Copyright (c) 2016, Ashley Gibson
 * @license   GPL2+
 */
class BDB_Reading_Log_Widget extends WP_Widget {

	/**
	 * BDB_Reading_Log_Widget constructor.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'bdb_reading_log',
			esc_html__( 'BDB - Reading Log', 'book-database' ),
			array( 'description' => esc_html__( 'Display a list of recently read books.', 'book-database' ) )
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from the database.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$number       = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$format       = ( isset( $instance['display'] ) && 'images' == $instance['display'] ) ? 'images' : 'list';
		$show_ratings = ( isset( $instance['show_ratings'] ) && $instance['show_ratings'] ) ? true : false;

		$books = $this->query_books( $number );

		if ( ! empty( $books ) ) {
			if ( 'images' == $format ) {
				$this->display_images( $books, $show_ratings );
			} else {
				$this->display_list( $books, $show_ratings );
			}
		}

		echo $args['after_widget'];

	}

	/**
	 * Display Books in Images Format
	 *
	 * @param array $books        Array of objects from the query.
	 * @param bool  $show_ratings Whether or not to show ratings.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function display_images( $books, $show_ratings = true ) {

		?>
        <div class="bookdb-book-grid bookdb-reading-log-images">
			<?php
			foreach ( $books as $book ) {
				echo '<div class="bookdb-book-grid-entry">';

				$image_id = $book->cover_id;

				if ( $image_id ) {
					echo wp_get_attachment_image( absint( $image_id ), 'medium' );
				}

				if ( $show_ratings ) {
					$rating = new BDB_Rating( $book->rating );

					echo '<div class="bookdb-reading-log-rating ' . esc_attr( $rating->format_html_class() ) . '">' . $rating->format_html_stars() . '</div>';
				}

				echo '</div>';
			}
			?>
        </div>
		<?php

	}

	/**
	 * Display Books in List Format
	 *
	 * @param array $books        Array of objects from the query.
	 * @param bool  $show_ratings Whether or not to show ratings.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function display_list( $books, $show_ratings = true ) {

		?>
        <ul class="bookdb-reading-log-list">
			<?php foreach ( $books as $book ) : ?>
                <li>
                    <span class="bookdb-reading-log-date">
                        <?php printf( '[%s]', bdb_format_mysql_date( $book->date_finished, 'j M, Y' ) ); ?>
                    </span>
                    <span class="bookdb-reading-log-title">
                        <?php printf( esc_html__( '%s by %s', 'book-database' ), $book->book_title, $book->author ); ?>
                    </span>
					<?php if ( $show_ratings ) :
						$rating = new BDB_Rating( $book->rating );
						?>
                        <span class="bookdb-reading-log-rating <?php echo esc_attr( $rating->format_html_class() ); ?>"><?php echo $rating->format_html_stars(); ?></span>
					<?php endif; ?>
                </li>
			<?php endforeach; ?>
        </ul>
		<?php

	}

	/**
	 * Query Books Recently Read
	 *
	 * @param int $number Number of results.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return array|false
	 */
	public function query_books( $number = 5 ) {

		$books = get_transient( 'bdb_reading_log_widget' );

		if ( false === $books ) {

			global $wpdb;

			$reading_table      = book_database()->reading_list->table_name;
			$book_table         = book_database()->books->table_name;
			$relationship_table = book_database()->book_term_relationships->table_name;
			$term_table         = book_database()->book_terms->table_name;

			$query = $wpdb->prepare(
				"SELECT book.ID as book_id, book.title as book_title, book.cover as cover_id,
			        log.date_finished, log.rating,
			        GROUP_CONCAT(author.name SEPARATOR ', ') as author
			        FROM $reading_table as log 
			        INNER JOIN $book_table as book ON log.book_id = book.ID 
			        LEFT JOIN $relationship_table as r on book.ID = r.book_id
			        INNER JOIN $term_table as author ON (r.term_id = author.term_id AND author.type = 'author')
			        WHERE date_finished IS NOT NULL
			        GROUP BY log.ID
			        ORDER BY log.date_finished DESC 
			        LIMIT %d",
				$number
			);

			$books = $wpdb->get_results( $query );

			if ( ! empty( $books ) ) {
				$books = wp_unslash( $books );
			}

			set_transient( 'bdb_reading_log_widget', $books, DAY_IN_SECONDS );

		}

		return $books;

	}

	/**
	 * Back-end widget form.
	 *
	 * @see    WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from the database.
	 *
	 * @access public
	 * @since  1.3.0
	 * @return void
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'        => '',
			'number'       => 5,
			'display'      => 'list',
			'show_ratings' => true
		);

		$instance = wp_parse_args( $instance, $defaults );
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'book-database' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number', 'book-database' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $instance['number'] ); ?>">
        </p>

        <p>
            <input id="<?php echo $this->get_field_id( 'display_list' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="list" <?php checked( $instance['display'], 'list' ); ?>>
            <label for="<?php echo $this->get_field_id( 'display_list' ); ?>"><?php _e( 'Display as list', 'book-database' ); ?></label>
            <br>
            <input id="<?php echo $this->get_field_id( 'display_images' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" type="radio" value="images" <?php checked( $instance['display'], 'images' ); ?>>
            <label for="<?php echo $this->get_field_id( 'display_images' ); ?>"><?php _e( 'Display as images', 'book-database' ); ?></label>
        </p>

        <p>
            <input id="<?php echo $this->get_field_id( 'show_ratings' ); ?>" name="<?php echo $this->get_field_name( 'show_ratings' ); ?>" type="checkbox" value="1" <?php checked( $instance['show_ratings'], true ); ?>>
            <label for="<?php echo $this->get_field_id( 'show_ratings' ); ?>"><?php _e( 'Show ratings', 'book-database' ); ?></label>
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
	 * @since  1.3.0
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                 = array();
		$instance['title']        = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['number']       = ( ! empty( $new_instance['number'] ) ) ? absint( $new_instance['number'] ) : 5;
		$instance['display']      = ( 'images' == $new_instance['display'] ) ? 'images' : 'list';
		$instance['show_ratings'] = ( isset( $new_instance['show_ratings'] ) ) ? true : false;

		// Clear transient.
		delete_transient( 'bdb_reading_log_widget' );

		return $instance;

	}

}

add_action( 'widgets_init', function () {
	register_widget( 'BDB_Reading_Log_Widget' );
} );