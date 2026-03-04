<?php
/**
 * User Count List Table.
 *
 * @package User_Count
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class User_Count_List_Table extends WP_List_Table {
	/**
	 * Initialize the list table with labels and behavior for listing users.
	 *
	 * Sets the table's singular label to "user", plural label to "users", and disables AJAX.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'user',
				'plural'   => 'users',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the list table's columns and their display labels.
	 *
	 * @return array<string,string> Associative array mapping column keys to their label strings.
	 */
	public function get_columns() {
		return array(
			'name'    => __( 'Name', 'user-count' ),
			'total'   => __( 'Total Posts', 'user-count' ),
			'publish' => __( 'Published', 'user-count' ),
			'future'  => __( 'Scheduled', 'user-count' ),
		);
	}

	/**
	 * Define sortable columns for the list table.
	 *
	 * @return array<string, array{0:string,1:bool}> Associative array mapping column keys to an array where
	 *         element 0 is the corresponding orderby value and element 1 is a boolean indicating
	 *         whether that column is the default sort (true) or not (false).
	 */
	public function get_sortable_columns() {
		return array(
			'name'  => array( 'name', false ),
			'total' => array( 'total', false ),
		);
	}

	/**
	 * Populate the list table with editor users and their post counts, applying optional date filtering, sorting, and pagination.
	 *
	 * When both $from_date and $to_date are provided, only posts within the inclusive date range are counted.
	 * The method sets up column headers, collects published and scheduled (future) post counts per editor,
	 * sorts the results by name or total posts (asc/desc), applies pagination (20 items per page), and assigns
	 * the current page items to $this->items and pagination arguments via set_pagination_args().
	 *
	 * @param string $from_date Optional start date for post counting. Used only if $to_date is also provided.
	 * @param string $to_date   Optional end date for post counting. Used only if $from_date is also provided.
	 */
	public function prepare_items( $from_date = '', $to_date = '' ) {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$users = get_users(
			array(
				'role'    => 'editor',
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);

		$date_query = array();
		if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
			$date_query[] = array(
				'after'     => $from_date,
				'before'    => $to_date,
				'inclusive' => true,
			);
		}

		$items = array();

		foreach ( $users as $user ) {
			$base_args = array(
				'post_type'              => 'post',
				'author'                 => $user->ID,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			if ( ! empty( $date_query ) ) {
				$base_args['date_query'] = $date_query;
			}

			$publish_args                 = $base_args;
			$publish_args['post_status']   = 'publish';
			$publish_args['posts_per_page'] = 1;
			$publish_query                = new WP_Query( $publish_args );
			$publish_count                = (int) $publish_query->found_posts;
			wp_reset_postdata();

			$future_args                 = $base_args;
			$future_args['post_status']   = 'future';
			$future_args['posts_per_page'] = 1;
			$future_query                = new WP_Query( $future_args );
			$future_count                = (int) $future_query->found_posts;
			wp_reset_postdata();

			$items[] = array(
				'id'      => $user->ID,
				'name'    => $user->display_name,
				'total'   => $publish_count + $future_count,
				'publish' => $publish_count,
				'future'  => $future_count,
			);
		}

		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'name';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'asc';

		$allowed_orderby = array( 'name', 'total' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'name';
		}

		if ( 'desc' !== $order ) {
			$order = 'asc';
		}

		usort(
			$items,
			static function ( $first, $second ) use ( $orderby, $order ) {
				if ( 'total' === $orderby ) {
					$result = (int) $first['total'] <=> (int) $second['total'];
				} else {
					$result = strcasecmp( $first['name'], $second['name'] );
				}

				if ( 'desc' === $order ) {
					$result = -$result;
				}

				return $result;
			}
		);

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count( $items );

		$this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Render the Name column as a link to the admin posts list filtered by the user.
	 *
	 * @param array $item Associative array representing the row; must contain 'id' (user ID) and 'name' (display name).
	 * @return string The HTML anchor linking to the posts admin filtered by the author, with the user's display name as the link text.
	 */
	public function column_name( $item ) {
		$url = add_query_arg(
			array(
				'post_type' => 'post',
				'author'    => $item['id'],
			),
			admin_url( 'edit.php' )
		);

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html( $item['name'] )
		);
	}

	/**
	 * Render a default column value for a table row when no specific column handler exists.
	 *
	 * @param array  $item        Row data array containing columns keyed by column name.
	 * @param string $column_name The column key to render.
	 * @return string The column value escaped for safe HTML output.
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * Render the total post count for a table row.
	 *
	 * @param array $item Row data containing a 'total' numeric count.
	 * @return string The 'total' value escaped for HTML output.
	 */
	public function column_total( $item ) {
		return esc_html( $item['total'] );
	}
}
