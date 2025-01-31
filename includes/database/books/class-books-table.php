<?php
/**
 * Books Table
 *
 * @package   book-database
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database;

/**
 * Class Books_Table
 *
 * @package Book_Database
 */
class Books_Table extends BerlinDB\Database\Table {

	/**
	 * @var string Table name
	 */
	protected $name = 'books';

	/**
	 * @var int Database version in format {YYYY}{MM}{DD}{1}
	 */
	protected $version = 201910271;

	/**
	 * @var array Upgrades to perform
	 */
	protected $upgrades = array(
		'201910092' => 201910092,
		'201910093' => 201910093,
		'201910094' => 201910094,
		'201910095' => 201910095,
		'201910096' => 201910096,
		'201910271' => 201910271
	);

	/**
	 * Books_Table constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set up the database schema
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
			cover_id bigint(20) UNSIGNED DEFAULT NULL,
			title text NOT NULL,
			index_title text NOT NULL,
			series_id bigint(20) UNSIGNED DEFAULT NULL,
			series_position float DEFAULT NULL,
			pub_date date DEFAULT NULL,
			pages bigint(20) UNSIGNED DEFAULT NULL,
			synopsis longtext NOT NULL,
			goodreads_url text NOT NULL,
			date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			INDEX title( title(64) ),
			INDEX series_id ( series_id )";
	}

	/**
	 * If the old `wp_bdb_books_db_version` option exists, copy that value to our new version key.
	 * This will ensure new upgrades are processed on old installs.
	 */
	public function maybe_upgrade() {

		$old_key     = $this->get_db()->prefix . 'bdb_books_db_version';
		$old_version = get_option( $old_key );

		if ( false !== $old_version ) {
			update_option( $this->db_version_key, get_option( $old_key ) );

			delete_option( $old_key );
		}

		return parent::maybe_upgrade();
	}

	/**
	 * Upgrade to version 201910092
	 *      - Rename `ID` to `id` & add `unsigned`
	 *
	 * @return bool
	 */
	protected function __201910092() {

		$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} CHANGE `ID` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT" );

		return $this->is_success( $result );

	}

	/**
	 * Upgrade to version 201910093
	 *      - Rename `cover` to `cover_id` & add `unsigned`
	 *
	 * @return bool
	 */
	protected function __201910093() {

		if ( $this->column_exists( 'cover' ) ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} CHANGE `cover` `cover_id` bigint(20) UNSIGNED DEFAULT NULL" );
		} else {
			$result = true;
		}

		return $this->is_success( $result );

	}

	/**
	 * Upgrade to version 201910094
	 *      - Add `unsigned` to `series_id` and `pages`
	 *
	 * @return bool
	 */
	protected function __201910094() {

		$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY series_id bigint(20) UNSIGNED DEFAULT NULL" );
		$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY pages bigint(20) UNSIGNED DEFAULT NULL" );

		return $this->is_success( $result );

	}

	/**
	 * Upgrade to version 201910095
	 *      - Add `date_created` column
	 *      - Add `date_modified` column
	 *
	 * @return bool
	 */
	protected function __201910095() {

		$result = $this->column_exists( 'date_created' );

		if ( ! $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00'" );
		}

		$result = $this->column_exists( 'date_modified' );

		if ( ! $result ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00'" );
		}

		return $this->is_success( $result );

	}

	/**
	 * Upgrade to version 201910096
	 *      - Add `title` index
	 *
	 * @return bool
	 */
	protected function __201910096() {

		if ( ! $this->get_db()->query( "SHOW INDEX FROM {$this->table_name} WHERE Key_name = 'title'" ) ) {
			$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD INDEX title( title(64) )" );
		} else {
			$result = true;
		}

		return $result;

	}

	/**
	 * Upgrade to version 201910271
	 *      - Convert `pub_date` to a DATE field
	 *
	 * @return bool
	 */
	protected function __201910271() {

		$result = $this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY pub_date date DEFAULT NULL" );

		return $this->is_success( $result );

	}

}
