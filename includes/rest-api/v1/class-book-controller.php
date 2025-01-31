<?php
/**
 * Book Controller
 *
 * @package   book-database
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database\REST_API\v1;

use Book_Database\Books_Query;
use Book_Database\Exception;
use Book_Database\REST_API\Controller;
use function Book_Database\add_book;
use function Book_Database\delete_book;
use function Book_Database\generate_book_index_title;
use function Book_Database\get_book;
use function Book_Database\get_books;
use function Book_Database\update_book;

/**
 * Class Book
 * @package Book_Database\REST_API\v1
 */
class Book extends Controller {

	protected $rest_base = 'book';

	/**
	 * Register routes
	 */
	public function register_routes() {

		// Get all books. (this is /books)
		register_rest_route( $this->namespace, $this->rest_base . 's', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_books' ),
			'permission_callback' => array( $this, 'can_view' ),
			'args'                => array(
				'number'  => array(
					'default'           => 20,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return absint( $param );
					}
				),
				'orderby' => array(
					'default' => 'book.date_created'
				),
				'order'   => array(
					'default' => 'ASC'
				)
			)
		) );

		// Add a new book.
		register_rest_route( $this->namespace, $this->rest_base . '/add', array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'add_book' ),
			'permission_callback' => array( $this, 'can_edit' ),
			'args'                => array(
				'cover_id'        => array(
					'default'           => 0,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return absint( $param );
					}
				),
				'title'           => array(
					'required'          => true,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return sanitize_text_field( $param );
					}
				),
				'index_title'     => array(
					'sanitize_callback' => function ( $param, $request, $key ) {
						return sanitize_text_field( $param );
					}
				),
				'series_id'       => array(
					'default'           => null,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return empty( $param ) ? null : absint( $param );
					}
				),
				'series_position' => array(
					'default' => null,
				),
				'pub_date'        => array(
					'default'           => null,
					'sanitize_callback' => function ( $param, $request, $key ) {
						if ( empty( $param ) ) {
							return null;
						}

						// Format date.
						return date( 'Y-m-d H:i:s', strtotime( $param ) );
					}
				),
				'pages'           => array(
					'default'           => 0,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return empty( $param ) ? null : absint( $param );
					}
				),
				'synopsis'        => array(
					'default'           => '',
					'sanitize_callback' => function ( $param, $request, $key ) {
						return wp_kses_post( $param );
					}
				),
				'goodreads_url'   => array(
					'default'           => '',
					'sanitize_callback' => function ( $param, $request, $key ) {
						return ! empty( $param ) ? esc_url_raw( $param ) : '';
					}
				),
			)
		) );

		// Update an existing book.
		register_rest_route( $this->namespace, $this->rest_base . '/update/(?P<id>\d+)', array(
			'methods'             => \WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'update_book' ),
			'permission_callback' => array( $this, 'can_edit' ),
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $param, $request, $key ) {
						$book = get_book( $param );

						return ! empty( $book );
					},
					'sanitize_callback' => function ( $param, $request, $key ) {
						return absint( $param );
					}
				)
			)
		) );

		// Delete a book.
		register_rest_route( $this->namespace, $this->rest_base . '/delete/(?P<id>\d+)', array(
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'delete_book' ),
			'permission_callback' => array( $this, 'can_edit' ),
			'args'                => array(
				'id' => array(
					'required'          => true,
					'validate_callback' => function ( $param, $request, $key ) {
						$book = get_book( $param );

						return ! empty( $book );
					},
					'sanitize_callback' => function ( $param, $request, $key ) {
						return absint( $param );
					}
				)
			)
		) );

		// Get the `index_title` version of a title
		register_rest_route( $this->namespace, $this->rest_base . '/index-title', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_index_title' ),
			'permission_callback' => array( $this, 'can_view' ),
			'args'                => array(
				'title' => array(
					'required'          => true,
					'sanitize_callback' => function ( $param, $request, $key ) {
						return sanitize_text_field( $param );
					}
				)
			)
		) );

	}

	/**
	 * Get all books
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_books( $request ) {

		try {
			$args = wp_parse_args( $request->get_params(), array(
				'orderby' => 'book.date_created',
				'order'   => 'ASC'
			) );

			$query = new Books_Query();
			$books = $query->get_books( $args );

			return new \WP_REST_Response( $books );
		} catch ( Exception $e ) {
			return new \WP_REST_Response( $e->getMessage(), $e->getCode() );
		}

	}

	/**
	 * Add a new book
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function add_book( $request ) {

		try {

			$book_id = add_book( $request->get_params() );
			$book    = get_book( $book_id );

			if ( empty( $book ) ) {
				throw new Exception( 'database_failure', __( 'Failed to retrieve new book from the database.', 'book-database' ), 500 );
			}

			return new \WP_REST_Response( $book->get_data() );

		} catch ( Exception $e ) {
			return new \WP_REST_Response( $e->getMessage(), $e->getCode() );
		}

	}

	/**
	 * Update an existing book
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_book( $request ) {

		try {

			$book_id = $request->get_param( 'id' );

			update_book( $book_id, $request->get_params() );

			$book = get_book( $book_id );

			return new \WP_REST_Response( $book->get_data() );

		} catch ( Exception $e ) {
			return new \WP_REST_Response( $e->getMessage(), $e->getCode() );
		}

	}

	/**
	 * Delete a book
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function delete_book( $request ) {

		try {

			$book_id = $request->get_param( 'id' );

			delete_book( $book_id );

			return new \WP_REST_Response( true );

		} catch ( Exception $e ) {
			return new \WP_REST_Response( $e->getMessage(), $e->getCode() );
		}

	}

	/**
	 * Get the `index_title` version of a book title
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_index_title( $request ) {

		try {

			$title = $request->get_param( 'title' );

			return new \WP_REST_Response( generate_book_index_title( $title ) );

		} catch ( Exception $e ) {
			return new \WP_REST_Response( $e->getMessage(), $e->getCode() );
		}

	}

}