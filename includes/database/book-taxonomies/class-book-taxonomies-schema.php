<?php
/**
 * Book Taxonomies Schema
 *
 * @package   book-database
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database;

/**
 * Class Book_Taxonomies_Schema
 *
 * @package Book_Database
 */
class Book_Taxonomies_Schema extends BerlinDB\Database\Schema {

	/**
	 * Array of database columns
	 *
	 * @var array
	 */
	public $columns = array(

		// id
		array(
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true
		),

		// name
		array(
			'name'       => 'name',
			'type'       => 'varchar',
			'length'     => '32',
			'sortable'   => true,
			'searchable' => true,
			'validate'   => 'sanitize_text_field'
		),

		// slug
		array(
			'name'     => 'slug',
			'type'     => 'varchar',
			'length'   => '32',
			'sortable' => true,
			'validate' => 'sanitize_key'
		),

		// format
		array(
			'name'     => 'format',
			'type'     => 'varchar',
			'length'   => '32',
			'sortable' => true,
			'default'  => 'text',
			'validate' => 'sanitize_text_field'
		),

		// date_created
		array(
			'name'       => 'date_created',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		),

		// date_modified
		array(
			'name'       => 'date_modified',
			'type'       => 'datetime',
			'default'    => '0000-00-00 00:00:00',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		),

	);

}