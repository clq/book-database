<?php
/**
 * Book Layout
 *
 * Primarily functions used in BDB_Book::get_formatted_info()
 *
 * @package   book-database
 * @copyright Copyright (c) 2016, Ashley Gibson
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Book Fields
 *
 * Returns an array of all the available book information fields,
 * their placeholder values, and their default labels.
 *
 * Other plugins can add their own fields using this filter:
 *  + book-database/book/available-fields
 *
 * @since 1.0.0
 * @return array
 */
function bdb_get_book_fields() {
	$fields = array(
		'cover'         => array(
			'name'        => __( 'Cover Image', 'book-database' ),
			'placeholder' => '[cover]',
			'label'       => '[cover]',
			'alignment'   => 'left' // left, center, right
		),
		'title'         => array(
			'name'        => __( 'Book Title', 'book-database' ),
			'placeholder' => '[title]',
			'label'       => '<strong>[title]</strong>',
		),
		'author'        => array(
			'name'        => __( 'Author', 'book-database' ),
			'placeholder' => '[author]',
			'label'       => sprintf( __( ' by %s', 'book-database' ), '[author]' ),
			'linebreak'   => 'on'
		),
		'series'        => array(
			'name'        => __( 'Series Name', 'book-database' ),
			'placeholder' => '[series]',
			'label'       => sprintf( __( '<strong>Series:</strong> %s', 'book-database' ), '[series]' ),
			'linebreak'   => 'on'
		),
		'publisher'     => array(
			'name'        => __( 'Publisher', 'book-database' ),
			'placeholder' => '[publisher]',
			'label'       => sprintf( __( '<strong>Published by:</strong> %s', 'book-database' ), '[publisher]' ),
		),
		'pub_date'      => array(
			'name'        => __( 'Pub Date', 'book-database' ),
			'placeholder' => '[pub_date]',
			'label'       => sprintf( __( ' on %s', 'book-database' ), '[pub_date]' ),
			'linebreak'   => 'on'
		),
		'genre'         => array(
			'name'        => __( 'Genre', 'book-database' ),
			'placeholder' => '[genre]',
			'label'       => sprintf( __( '<strong>Genre:</strong> %s', 'book-database' ), '[genre]' ),
			'linebreak'   => 'on'
		),
		'pages'         => array(
			'name'        => __( 'Pages', 'book-database' ),
			'placeholder' => '[pages]',
			'label'       => sprintf( __( '<strong>Pages:</strong> %s', 'book-database' ), '[pages]' ),
			'linebreak'   => 'on'
		),
		'source'        => array(
			'name'        => __( 'Source', 'book-database' ),
			'placeholder' => '[source]',
			'label'       => sprintf( __( '<strong>Source:</strong> %s', 'book-database' ), '[source]' ),
			'linebreak'   => 'on'
		),
		'goodreads_url' => array(
			'name'        => __( 'Goodreads', 'book-database' ),
			'placeholder' => '[goodreads]',
			'label'       => sprintf( '<a href="%1$s">%2$s</a>', '[goodreads]', __( 'Goodreads', 'book-database' ) ),
			'linebreak'   => 'on'
		),
		'rating'        => array(
			'name'        => __( 'Rating', 'book-database' ),
			'placeholder' => '[rating]',
			'label'       => sprintf( __( '<strong>Rating:</strong> %s', 'book-database' ), '[rating]' ),
			'linebreak'   => 'on'
		),
		'synopsis'      => array(
			'name'        => __( 'Synopsis', 'book-database' ),
			'placeholder' => '[synopsis]',
			'label'       => '<blockquote>[synopsis]</blockquote>',
		),
	);

	return apply_filters( 'book-database/book/available-fields', $fields );
}

/**
 * Book Cover Alignment Options
 *
 * Returns an array of book cover alignment options.
 *
 * @since 1.0.0
 * @return array
 */
function bdb_book_alignment_options() {
	$options = array(
		'left'   => __( 'Left', 'book-database' ),
		'center' => __( 'Centered', 'book-database' ),
		'right'  => __( 'Right', 'book-database' )
	);

	return apply_filters( 'book-database/book/cover-alignment-options', $options );
}

/**
 * Default Book Layout Keys
 *
 * Meta enabled by default for the book layout.
 *
 * @since 1.0.0
 * @return array
 */
function bdb_get_default_book_layout_keys() {
	$default_keys = array(
		'cover',
		'title',
		'author',
		'series',
		'publisher',
		'pub_date',
		'genre',
		'pages',
		'source',
		'goodreads_url',
		'rating',
		'synopsis'
	);

	return apply_filters( 'book-database/settings/default-layout-keys', $default_keys );
}

/**
 * Get Default Book Field Values
 *
 * Returns the array of default fields. These are the ones used if no settings have
 * been changed. They're loaded on initial install or when the 'Book Layout' tab
 * is reset to the default.
 *
 * @uses  bdb_get_default_book_layout_keys()
 *
 * @param array|null $all_fields
 *
 * @since 1.0.0
 * @return array
 */
function bdb_get_default_book_field_values( $all_fields = null ) {
	if ( ! is_array( $all_fields ) ) {
		$all_fields = bdb_get_book_fields();
	}
	$default_keys   = bdb_get_default_book_layout_keys();
	$default_values = array();

	if ( ! is_array( $default_keys ) ) {
		return array();
	}

	foreach ( $default_keys as $key ) {
		if ( ! array_key_exists( $key, $all_fields ) ) {
			continue;
		}

		$key_value = $all_fields[ $key ];

		if ( array_key_exists( 'placeholder', $key_value ) ) {
			unset( $key_value['placeholder'] );
		}

		$default_values[ $key ] = $key_value;
	}

	return $default_values;
}

/**
 * Value: Cover
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_cover( $value, $enabled_fields, $book_id, $book ) {
	if ( $book->get_cover_id() ) {
		$alignment = $enabled_fields['cover']['alignment'];
		$class     = 'align' . sanitize_html_class( $alignment );
		$value     = '<img src="' . esc_url( $book->get_cover_url() ) . '" alt="' . esc_attr( wp_strip_all_tags( $book->get_title() ) ) . '" class="' . esc_attr( $class ) . '" itemprop="image">';
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/cover', 'bdb_book_layout_cover', 10, 4 );

/**
 * Value: Title
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_title( $value, $enabled_fields, $book_id, $book ) {
	return '<span itemprop="name">' . $book->get_title() . '</span>';
}

add_filter( 'book-database/book/formatted-info/value/title', 'bdb_book_layout_title', 10, 4 );

/**
 * Value: Author
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_author( $value, $enabled_fields, $book_id, $book ) {
	$author = $book->get_author();

	if ( $author ) {
		if ( bdb_link_terms() ) {
			$names = array();

			foreach ( $author as $obj ) {
				$names[] = '<a href="' . esc_url( bdb_get_term_link( $obj ) ) . '">' . esc_html( $obj->name ) . '</a>';
			}

			$name = implode( ', ', $names );
		} else {
			$name = $book->get_author_names();
		}
		$value = '<span itemprop="author">' . $name . '</span>';
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/author', 'bdb_book_layout_author', 10, 4 );

/**
 * Value: Series
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_series( $value, $enabled_fields, $book_id, $book ) {
	$series = $book->get_series_id();

	if ( $series ) {
		$value = $book->get_formatted_series( bdb_link_terms() );
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/series', 'bdb_book_layout_series', 10, 4 );

/**
 * Value: Publisher
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_publisher( $value, $enabled_fields, $book_id, $book ) {
	$publishers = bdb_get_book_terms( $book_id, 'publisher' );

	if ( $publishers && is_array( $publishers ) ) {
		$pub_names = array();

		foreach ( $publishers as $pub ) {
			$this_pub    = bdb_link_terms() ? '<a href="' . esc_url( bdb_get_term_link( $pub ) ) . '">' . $pub->name . '</a>' : $pub->name;
			$pub_names[] = '<span itemprop="publisher" itemtype="http://schema.org/Organization" itemscope="">' . $this_pub . '</span>';
		}

		$value = implode( ', ', $pub_names );
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/publisher', 'bdb_book_layout_publisher', 10, 4 );

/**
 * Value: Pub Date
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_pub_date( $value, $enabled_fields, $book_id, $book ) {
	$pub_date = $book->get_formatted_pub_date();

	if ( $pub_date ) {
		$value = '<span itemprop="datePublished" content="' . esc_attr( $book->get_formatted_pub_date( 'Y-m-d' ) ) . '">' . $pub_date . '</span>';
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/pub_date', 'bdb_book_layout_pub_date', 10, 4 );

/**
 * Value: Genre
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_genre( $value, $enabled_fields, $book_id, $book ) {
	$genres = bdb_get_book_terms( $book_id, 'genre' );

	if ( $genres && is_array( $genres ) ) {
		$genre_names = array();

		foreach ( $genres as $genre ) {
			$this_genre    = bdb_link_terms() ? '<a href="' . esc_url( bdb_get_term_link( $genre ) ) . '">' . $genre->name . '</a>' : $genre->name;
			$genre_names[] = '<span itemprop="genre">' . $this_genre . '</span>';
		}

		$value = implode( ', ', $genre_names );
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/genre', 'bdb_book_layout_genre', 10, 4 );

/**
 * Value: Pages
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_pages( $value, $enabled_fields, $book_id, $book ) {
	$pages = $book->get_pages();

	if ( $pages ) {
		$value = '<span itemprop="numberOfPages">' . $pages . '</span>';
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/pages', 'bdb_book_layout_pages', 10, 4 );

/**
 * Value: Source
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_source( $value, $enabled_fields, $book_id, $book ) {
	$sources = bdb_get_book_terms( $book_id, 'source' );

	if ( $sources && is_array( $sources ) ) {
		$source_names = array();

		foreach ( $sources as $source ) {
			$source_names[] = bdb_link_terms() ? '<a href="' . esc_url( bdb_get_term_link( $source ) ) . '">' . $source->name . '</a>' : $source->name;
		}

		$value = implode( ', ', $source_names );
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/source', 'bdb_book_layout_source', 10, 4 );

/**
 * Value: Goodreads
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_goodreads_url( $value, $enabled_fields, $book_id, $book ) {
	return $book->get_goodreads_url();
}

add_filter( 'book-database/book/formatted-info/value/goodreads_url', 'bdb_book_layout_goodreads_url', 10, 4 );

/**
 * Value: Rating
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_rating( $value, $enabled_fields, $book_id, $book ) {
	if ( null !== $book->get_rating() ) {
		$rating       = new BDB_Rating( $book->get_rating() );
		$fa_stars     = $rating->format( 'font_awesome' ); // @todo schema markup
		$actual_value = is_numeric( $book->get_rating() ) ? $book->get_rating() : 0;

		$value = '<span itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating">';
		$value .= '<span class="bookdb-font-awesome-star-wrap">' . $fa_stars . '</span>';
		$value .= '<span class="bookdb-actual-rating-values"><span itemprop="ratingValue">' . esc_html( $actual_value ) . '</span>/<span itemprop="bestRating">' . esc_html( $rating->max ) . '</span></span>';
		$value .= '</span>';
	}

	return $value;
}

add_filter( 'book-database/book/formatted-info/value/rating', 'bdb_book_layout_rating', 10, 4 );

/**
 * Value: Synopsis
 *
 * @param mixed    $value
 * @param array    $enabled_fields
 * @param int      $book_id
 * @param BDB_Book $book
 *
 * @since 1.0.0
 * @return string
 */
function bdb_book_layout_synopsis( $value, $enabled_fields, $book_id, $book ) {
	return $book->get_synopsis();
}

add_filter( 'book-database/book/formatted-info/value/synopsis', 'bdb_book_layout_synopsis', 10, 4 );