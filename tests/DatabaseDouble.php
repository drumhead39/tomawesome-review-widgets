<?php
/**
 * Query-recording database double. It never connects to or modifies a database.
 *
 * @package TomAwesomeReviewWidgets
 */

/**
 * Records SQL templates, bound parameters, and final queries for regression tests.
 */
final class TARW_Test_Database {

	public $prefix = 'wp_';
	public $options = 'wp_options';
	public $prepared = array();
	public $queries = array();
	public $rows = array();
	public $column = array();

	/**
	 * Substitutes only the placeholder types used by these fixtures.
	 * This verifies query construction, not WordPress's own escaping implementation.
	 */
	public function prepare( $template, ...$arguments ) {
		if ( 1 === count( $arguments ) && is_array( $arguments[0] ) ) {
			$arguments = $arguments[0];
		}
		$this->prepared[] = array( 'template' => $template, 'arguments' => $arguments );
		$index = 0;
		$query = preg_replace_callback(
			'/%[ids]/',
			static function ( $match ) use ( $arguments, &$index ) {
				if ( ! array_key_exists( $index, $arguments ) ) {
					throw new RuntimeException( 'Missing SQL placeholder argument.' );
				}
				$value = $arguments[ $index++ ];
				if ( '%i' === $match[0] ) {
					return '`' . str_replace( '`', '``', (string) $value ) . '`';
				}
				if ( '%d' === $match[0] ) {
					return (string) (int) $value;
				}
				return "'" . str_replace( "'", "''", (string) $value ) . "'";
			},
			$template
		);
		if ( $index !== count( $arguments ) ) {
			throw new RuntimeException( 'Extra SQL placeholder arguments.' );
		}
		return $query;
	}

	public function query( $sql ) {
		$this->queries[] = $sql;
		return 1;
	}

	public function get_results( $sql ) {
		$this->queries[] = $sql;
		return $this->rows;
	}

	public function get_col( $sql ) {
		$this->queries[] = $sql;
		return $this->column;
	}

	public function esc_like( $value ) {
		return addcslashes( $value, '_%\\' );
	}
}
