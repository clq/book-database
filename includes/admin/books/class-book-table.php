<?php

/**
 * Book Table Class
 *
 * @package   book-database
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class BDB_Books_Table
 *
 * Renders the book table.
 *
 * @since 1.0
 */
class BDB_Books_Table extends WP_List_Table {

	/**
	 * The mode of the current view.
	 *
	 * @var string
	 * @access public
	 * @since  1.0
	 */
	public $mode = 'list';

	/**
	 * All available modes.
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $modes = array();

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_page = 20;

	/**
	 * Number of books found
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $count = 0;

	/**
	 * Total number of books
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $total = 0;

	/**
	 * The arguments for the data set
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $args = array();

	/**
	 * Display delete message
	 *
	 * @var bool
	 * @access private
	 * @since  1.0
	 */
	private $display_delete_message = false;

	/**
	 * BDB_Books_Table constructor.
	 *
	 * @see    WP_List_Table::__construct()
	 *
	 * @param array $args
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct( $args = array() ) {

		// Set modes
		$this->modes = $this->get_modes();

		parent::__construct( array(
			'singular' => bdb_get_label_singular(),
			'plural'   => bdb_get_label_plural(),
			'ajax'     => false
		) );

	}

	/**
	 * Get available modes.
	 *
	 * @access protected
	 * @since  1.0
	 * @return array
	 */
	protected function get_modes() {

		$modes = array(
			'list'  => esc_html__( 'List', 'book-database' ),
			'month' => esc_html__( 'Month', 'book-database' ),
			//'week'  => esc_html__( 'Week', 'book-database' ),
			//'day'   => esc_html__( 'Day', 'book-database' )
		);

		return $modes;

	}

	/**
	 * Display the view switcher.
	 *
	 * @param string $current_mode
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function view_switcher( $current_mode = '' ) {

		?>
		<div class="view-switch">
			<div class="filter-items">
				<?php
				foreach ( $this->modes as $mode => $title ) {

					// Get the URL for the mode.
					$url = bdb_get_admin_page_books();
					if ( 'list' != $mode ) {
						$url = add_query_arg( 'mode', urlencode( $mode ), $url );
					}

					// Setup classes.
					$classes = array( 'view-' . $mode );
					if ( empty( $this->mode ) || ( $this->mode === $mode ) ) {
						$classes[] = 'current';
					}
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="view-switch-<?php echo esc_attr( $mode ); ?>" title="<?php echo esc_attr( $title ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
					</a>
					<?php

				}
				?>
				<input type="hidden" name="mode" value="<?php echo esc_attr( $this->mode ); ?>">
			</div>
		</div>
		<?php

	}

	/**
	 * Show the Search Field
	 *
	 * @param string $text     Label for the search box.
	 * @param string $input_id ID of the search box.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function search_box( $text, $input_id ) {

		do_action( 'book-database/books/table/top/search/before' );

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '">';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '">';
		}

		$title  = isset( $_REQUEST['book_title'] ) ? wp_unslash( $_REQUEST['book_title'] ) : '';
		$author = isset( $_REQUEST['book_author'] ) ? wp_unslash( $_REQUEST['book_author'] ) : '';
		$series = isset( $_REQUEST['series_name'] ) ? wp_unslash( $_REQUEST['series_name'] ) : '';
		$isbn   = isset( $_REQUEST['isbn'] ) ? wp_unslash( $_REQUEST['isbn'] ) : '';

		?>
		<div class="search-form">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-title"><?php esc_html_e( 'Search by book title', 'book-database' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-title" name="book_title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Book title', 'book-database' ); ?>">

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-author"><?php esc_html_e( 'Search by author name', 'book-database' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-author" name="book_author" value="<?php echo esc_attr( $author ); ?>" placeholder="<?php esc_attr_e( 'Author name', 'book-database' ); ?>">

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-series"><?php esc_html_e( 'Search by series name', 'book-database' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-series" name="series_name" value="<?php echo esc_attr( $series ); ?>" placeholder="<?php esc_attr_e( 'Series name', 'book-database' ); ?>">

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>-isbn"><?php esc_html_e( 'Search by ISBN', 'book-database' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>-isbn" name="isbn" value="<?php echo esc_attr( $isbn ); ?>" placeholder="<?php esc_attr_e( 'ISBN', 'book-database' ); ?>">
		</div>
		<?php
		do_action( 'book-database/books/table/top/search/after' );

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'cover';
	}

	/**
	 * Renders most of the columns in the list table.
	 *
	 * @param object $item        Contains all the data of the books.
	 * @param string $column_name The name of the column.
	 *
	 * @access public
	 * @since  1.0
	 * @return string Column contents
	 */
	public function column_default( $item, $column_name ) {

		$book  = new BDB_Book( $item );
		$value = '';

		switch ( $column_name ) {

			case 'cover' :
				if ( $book->get_cover_id() ) {
					$value = '<a href="' . esc_url( bdb_get_admin_page_edit_book( $item['ID'] ) ) . '">' . $book->get_cover( 'thumbnail' ) . '</a>';
				}
				break;

			case 'author' :
				$author_names = $item['author_name'] ? explode( ',', $item['author_name'] ) : array();
				$author_ids   = $item['author_id'] ? explode( ',', $item['author_id'] ) : array();
				$output       = array();

				if ( count( $author_names ) ) {
					foreach ( $author_names as $key => $author_name ) {
						$author_id = absint( trim( $author_ids[ $key ] ) );
						$url       = add_query_arg( array( 'author_id' => urlencode( $author_id ) ), bdb_get_admin_page_books() );
						$output[]  = '<a href="' . esc_url( $url ) . '">' . esc_html( trim( $author_name ) ) . '</a>';
					}
				}

				$value = implode( ', ', $output );
				break;

			case 'series' :
				$value = '';
				if ( $item['series_id'] && $item['series_name'] ) {
					$name = $item['series_position'] ? sprintf( '%s #%s', $item['series_name'], $item['series_position'] ) : $item['series_name'];

					if ( $name ) {
						$url   = add_query_arg( array( 'series_id' => urlencode( $item['series_id'] ) ), bdb_get_admin_page_books() );
						$value = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
					}
				} else {
					$value = '&ndash;';
				}
				break;

			case 'pub_date' :
				$value = $item['pub_date'] ? date_i18n( get_option( 'date_format' ), strtotime( $item['pub_date'] ) ) : false;
				break;

			case 'rating' :
				if ( $item['rating'] ) {
					$rounded    = round( $item['rating'] * 2 ) / 2;
					$rating_obj = new BDB_Rating( $rounded );
					$url        = add_query_arg( array( 'rating' => urlencode( $rounded ) ), bdb_get_admin_page_books() );
					$value      = '<a href="' . esc_url( $url ) . '">' . $rating_obj->format( 'html_stars' ) . '</a>';
				} else {
					$value = '&ndash;';
				}
				break;

			default :
				$value = isset( $item[ $column_name ] ) ? $item[ $column_name ] : null;
				break;

		}

		return apply_filters( 'book-database/books-table/column/' . $column_name, $value, $item['ID'] );

	}

	/**
	 * Render Checkbox Column
	 *
	 * @param array $item Contains all the data of the books.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function column_cb( $item ) {

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		?>
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item['ID'] ); ?>">
			<?php _e( 'Select this book', 'book-database' ) ?>
		</label>
		<input id="cb-select-<?php echo esc_attr( $item['ID'] ); ?>" type="checkbox" name="books[]" value="<?php echo esc_attr( $item['ID'] ); ?>">
		<?php

	}

	/**
	 * Render Column Name
	 *
	 * @param array $item Contains all the data of the books.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function column_title( $item ) {
		$edit_url = bdb_get_admin_page_edit_book( $item['ID'] );
		$name     = '<a href="' . esc_url( $edit_url ) . '" class="row-title" aria-label="' . esc_attr( sprintf( '%s (Edit)', $item['title'] ) ) . '">' . $item['title'] . '</a>';
		$actions  = array(
			'edit'   => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'book-database' ) . '</a>',
			'delete' => '<a href="' . esc_url( bdb_get_admin_page_delete_book( $item['ID'] ) ) . '">' . __( 'Delete', 'book-database' ) . '</a>'
		);

		return $name . $this->row_actions( $actions );
	}

	/**
	 * Get Columns
	 *
	 * Retrieves the column IDs and names.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox">',
			'cover'    => __( 'Cover', 'book-database' ),
			'title'    => __( 'Title', 'book-database' ),
			'author'   => __( 'Author', 'book-database' ),
			'series'   => __( 'Series', 'book-database' ),
			'pub_date' => __( 'Publication Date', 'book-database' ),
			'rating'   => __( 'Rating', 'book-database' )
		);

		return apply_filters( 'book-database/books-table/book-columns', $columns );
	}

	/**
	 * Returns the current base URL.
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_base_url() {

		$args = array();

		// Filter by owned books.
		if ( isset( $_GET['owned'] ) && 'all' != $_GET['owned'] ) {
			$args['owned'] = sanitize_text_field( $_GET['owned'] );
		}

		// Filter by format.
		if ( isset( $_GET['format'] ) && 'all' != $_GET['format'] ) {
			$args['format'] = sanitize_text_field( $_GET['format'] );
		}

		// Filter by ISBN.
		if ( isset( $_GET['isbn'] ) ) {
			$args['isbn'] = sanitize_text_field( $_GET['isbn'] );
		}

		// Filter by author.
		if ( isset( $_GET['author_id'] ) ) {
			$args['author_id'] = absint( $_GET['author_id'] );
		}

		// Filter by series.
		if ( isset( $_GET['series_id'] ) ) {
			$args['series_id'] = absint( $_GET['series_id'] );
			$args['orderby']   = 'series_position';
		}

		// Filter by book title
		if ( isset( $_GET['book_title'] ) ) {
			$args['title'] = wp_strip_all_tags( $_GET['book_title'] );
		}

		// Filter by author name
		if ( isset( $_GET['book_author'] ) ) {
			$args['author_name'] = wp_strip_all_tags( $_GET['book_author'] );
		}

		// Filter by series name
		if ( isset( $_GET['series_name'] ) ) {
			$args['series_name'] = wp_strip_all_tags( $_GET['series_name'] );
		}

		// Add mode.
		$args['mode'] = $this->mode;

		// Sanitize args.
		$safe_args = array();
		foreach ( $args as $key => $value ) {
			$safe_args[ $key ] = urlencode( $value );
		}

		$url = remove_query_arg( array( 'filter_action', 's', 'mode' ), bdb_get_admin_page_books() );

		return add_query_arg( $safe_args, $url );

	}

	/**
	 * Get the sortable columns.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'title'    => array( 'title', true ),
			'series'   => array( 'series', true ),
			'pub_date' => array( 'pub_date', true ),
			'rating'   => array( 'rating', true )
		);
	}

	/**
	 * Display the table.
	 *
	 * @see WP_List_Table::display()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function display() {

		$this->display_tablenav( 'top' );
		?>
		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list" data-wp-lists="list:<?php echo $this->_args['singular']; ?>">
			<?php $this->display_mode(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );

	}

	/**
	 * Display table for "list" mode.
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function display_mode() {
		$this->display_rows_or_placeholder();
	}

	/**
	 * Table Navigation
	 *
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		// Display 'delete' success message.
		if ( 'top' == $which && true === $this->display_delete_message ) {
			?>
			<div id="message" class="updated notice notice-success">
				<p><?php _e( 'Books successfully deleted.', 'book-database' ); ?></p>
			</div>
			<?php
		}

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( 'list' == $this->mode && $this->has_items() ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php
		do_action( 'book-database/books-table/tablenav/after', $which );

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @see    WP_List_Table::extra_tablenav()
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' != $which ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<?php do_action( 'book-database/books-table/extra-tablenav/filters/before' ); ?>

			<label for="bdb-filter-by-owned" class="screen-reader-text"><?php _e( 'Filter by owned', 'book-database' ); ?></label>
			<?php
			echo book_database()->html->select( array(
				'name'             => 'owned',
				'id'               => 'bdb-filter-by-owned',
				'options'          => array(
					'owned'   => sprintf( __( 'Owned %s', 'book-database' ), bdb_get_label_plural() ),
					'signed'  => sprintf( __( 'Signed %s', 'book-database' ), bdb_get_label_plural() ),
					'unowned' => sprintf( __( 'Unowned %s', 'book-database' ), bdb_get_label_plural() )
				),
				'selected'         => isset( $_GET['owned'] ) ? sanitize_text_field( $_GET['owned'] ) : 0,
				'show_option_all'  => sprintf( _x( 'All %s', 'all dropdown items', 'book-database' ), bdb_get_label_plural() ),
				'show_option_none' => false
			) );
			?>

			<label for="bdb-filter-by-format" class="screen-reader-text"><?php _e( 'Filter by format', 'book-database' ); ?></label>
			<?php
			echo book_database()->html->select( array(
				'name'             => 'format',
				'id'               => 'bdb-filter-by-format',
				'options'          => bdb_get_book_formats(),
				'selected'         => isset( $_GET['format'] ) ? sanitize_text_field( $_GET['format'] ) : 0,
				'show_option_all'  => _x( 'All Formats', 'all dropdown items', 'book-database' ),
				'show_option_none' => false
			) );
			?>

			<?php do_action( 'book-database/books-table/extra-tablenav/filters/ater' ); ?>

			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e( 'Filter', 'book-database' ); ?>">
		</div>
		<?php
	}

	/**
	 * Get Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete Permanently', 'book-database' )
		);

		return apply_filters( 'book-database/books-table/get-bulk-actions', $actions );
	}

	/**
	 * Process Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function process_bulk_actions() {

		if ( 'delete' == $this->current_action() ) {

			// Check nonce.
			if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Failed security check.', 'book-database' ) );
			}

			// Checek capability.
			if ( ! current_user_can( 'delete_posts' ) ) {
				wp_die( __( 'You don\'t have permission to delete books.', 'book-database' ) );
			}

			if ( isset( $_GET['books'] ) && is_array( $_GET['books'] ) && count( $_GET['books'] ) ) {
				book_database()->books->delete_by_ids( $_GET['books'] );

				// Display the delete message.
				$this->display_delete_message = true;
			}

		}

	}

	/**
	 * Retrieve the current page number.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieves the search query string.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|string Search query or false if none.
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the book data.
	 *
	 * @param array $args Query args.
	 *
	 * @access protected
	 * @since  1.0
	 * @return array Array of book data.
	 */
	protected function books_data( $args = array() ) {

		$data    = array();
		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';

		$args = wp_parse_args( $args, array(
			'number'         => $this->per_page,
			'offset'         => $offset,
			'order'          => $order,
			'orderby'        => $orderby,
			'include_author' => true,
			'include_rating' => true
		) );

		// Filter by owned books.
		if ( isset( $_GET['owned'] ) && 'all' != $_GET['owned'] ) {
			$args['owned'] = sanitize_text_field( $_GET['owned'] );
		}

		// Filter by format.
		if ( isset( $_GET['format'] ) && 'all' != $_GET['format'] ) {
			$args['format'] = sanitize_text_field( $_GET['format'] );
		}

		// Filter by ISBN.
		if ( isset( $_GET['isbn'] ) ) {
			$args['isbn'] = sanitize_text_field( $_GET['isbn'] );
		}

		// Filter by author.
		if ( isset( $_GET['author_id'] ) ) {
			$args['author_id'] = absint( $_GET['author_id'] );
		}

		// Filter by series.
		if ( isset( $_GET['series_id'] ) ) {
			$args['series_id'] = absint( $_GET['series_id'] );
			$args['orderby']   = 'series_position';
		}

		// Filter by book title
		if ( isset( $_GET['book_title'] ) ) {
			$args['title'] = wp_strip_all_tags( $_GET['book_title'] );
		}

		// Filter by author name
		if ( isset( $_GET['book_author'] ) ) {
			$args['author_name'] = wp_strip_all_tags( $_GET['book_author'] );
		}

		// Filter by series name
		if ( isset( $_GET['series_name'] ) ) {
			$args['series_name'] = wp_strip_all_tags( $_GET['series_name'] );
		}

		// Filter by rating
		if ( isset( $_GET['rating'] ) ) {
			$args['rating'] = wp_strip_all_tags( $_GET['rating'] );
		}

		$this->args = $args;
		$books      = book_database()->books->get_books( $args );

		if ( $books ) {
			foreach ( $books as $book ) {
				$data[] = array(
					'ID'              => $book->ID,
					'cover'           => $book->cover,
					'title'           => $book->title,
					'series_name'     => $book->series_name,
					'series_id'       => $book->series_id,
					'series_position' => $book->series_position,
					'pub_date'        => $book->pub_date,
					'author_name'     => $book->author_name,
					'author_id'       => $book->author_id,
					'rating'          => $book->avg_rating
				);
			}
		}

		return $data;

	}

	/**
	 * Prepare Items
	 *
	 * Setup the final data for the table.
	 *
	 * @uses   BDB_Books_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 * @uses   BDB_Books_Table::books_data()
	 * @uses   WP_List_Table::set_pagination_args()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function prepare_items() {

		// Process bulk actions.
		$this->process_bulk_actions();

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->books_data();

		$this->total = book_database()->books->count( $this->args );

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
		) );

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	 */
	public function no_items() {
		printf(
			__( 'No %s found. Would you like to %sadd one?%', 'book-database' ),
			bdb_get_label_plural( true ),
			'<a href="' . esc_url( bdb_get_admin_page_add_book() ) . '">',
			'</a>'
		);
	}

}