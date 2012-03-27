<?php
/**
* Cleverness To-Do List Plugin List Table Class
* Extends WP_List_Table class
* @author C.M. Kendrick <cindy@cleverness.org>
* @package cleverness-to-do-list
* @version 3.1
* @since 3.1
*/

if( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * List Table Class
 * @package cleverness-to-do-list
 * @subpackage includes
 */

class ClevernessToDoListTable extends WP_List_Table {

	var $example_data = array(
		array( 'ID' => 1,'item' => 'Quarter Share', 'author' => 'Nathan Lowell',
		       'isbn' => '978-0982514542' ),
		array( 'ID' => 2, 'item' => '7th Son: Descent','author' => 'J. C. Hutchins',
		       'isbn' => '0312384378' ),
		array( 'ID' => 3, 'item' => 'Shadowmagic', 'author' => 'John Lenahan',
		       'isbn' => '978-1905548927' ),
		array( 'ID' => 4, 'item' => 'The Crown Conspiracy', 'author' => 'Michael J. Sullivan',
		       'isbn' => '978-0979621130' ),
		array( 'ID' => 5, 'item'     => 'Max Quick: The Pocket and the Pendant', 'author'    => 'Mark Jeffrey',
		       'isbn' => '978-0061988929' ),
		array('ID' => 6, 'item' => 'Jack Wakes Up: A Novel', 'author' => 'Seth Harwood',
		      'isbn' => '978-0307454355' )
	);

	function __construct(){
		global $status, $page;

		parent::__construct( array(
			'singular'  => __( 'todo', 'cleverness-to-do-list' ),     //singular name of the listed records
			'plural'    => __( 'todos', 'cleverness-to-do-list' ),   //plural name of the listed records
			'ajax'      => false        //does this table support ajax?

		) );

		add_action( 'admin_head', array( &$this, 'admin_header' ) );

	}

	function admin_header() {
		$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if( 'cleverness-to-do-list' != $page )
			return;
		echo '<style type="text/css">';
		echo '.wp-list-table .column-id { width: 5%; }';
		echo '.wp-list-table .column-item { width: 40%; }';
		echo '.wp-list-table .column-author { width: 35%; }';
		echo '.wp-list-table .column-isbn { width: 20%;}';
		echo '</style>';
	}

	function no_items() {
		_e( 'No to-do items', 'cleverness-to-do-list' );
	}

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'item':
			case 'author':
			case 'isbn':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'ID'        => array( 'id', false ),
			'item'      => array( 'item',false) ,
			'priority'  => array( 'priority', false ),
			'progress'  => array( 'progress', false )
		);
		return $sortable_columns;
	}

	function get_columns(){
		$columns = array();
		if ( CTDL_Loader::$settings['show_id'] ) $columns['ID'] = __( 'ID', 'cleverness-to-do-list' );
		$columns['item'] = __( 'Item', 'cleverness-to-do-list' );
		$columns['priority'] = __( 'Priority', 'cleverness-to-do-list' );
		if ( CTDL_Loader::$settings['assign'] == 0  && ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 0
				&& ( current_user_can( CTDL_Loader::$settings['view_all_assigned_capability'] ) ) ) || ( CTDL_Loader::$settings['list_view'] == 1 && CTDL_Loader::$settings['show_only_assigned'] == 1 )
				&& CTDL_Loader::$settings['assign'] == 0 ) $columns['assignedto'] = __( 'Assigned To', 'cleverness-to-do-list' );
		if ( CTDL_Loader::$settings['show_deadline'] == 1 ) $columns['deadline'] = __( 'Deadline', 'cleverness-to-do-list' );
		if ( $completed == 1 && CTDL_Loader::$settings['show_completed_date'] == 1) $columns['completed'] = __( 'Completed', 'cleverness-to-do-list' );
		if ( CTDL_Loader::$settings['show_progress'] == 1 ) $columns['progress'] = __( 'Progress', 'cleverness-to-do-list' );
		if ( CTDL_Loader::$settings['categories'] == 1 ) $columns['category'] = __( 'Category', 'cleverness-to-do-list' );
		if ( CTDL_Loader::$settings['list_view'] == 1  && CTDL_Loader::$settings['todo_author'] == 0 ) $columns['addedby'] = __ ( 'Added By', 'cleverness-to-do-list' );
		if ( current_user_can(CTDL_Loader::$settings['edit_capability'] ) || CTDL_Loader::$settings['list_view'] == 0 ) $columns['action'] = __( 'Action', 'cleverness-to-do-list' );
		return $columns;
	}

	function usort_reorder( $a, $b ) {
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'item';
		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $a[$orderby], $b[$orderby] );
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}

	function column_item( $item ){
		$actions = array(

		);

		return sprintf('%1$s %2$s', $item['item'], $this->row_actions( $actions ) );
	}

	function get_bulk_actions() {
		$actions = array(

		);
		return $actions;
	}

	function column_cb($item) {
		return $item['ID'];
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		usort( $this->example_data, array( &$this, 'usort_reorder' ) );

		$per_page = 5;
		$current_page = $this->get_pagenum();
		$total_items = count( $this->example_data );

		// only ncessary because we have sample data
		$this->found_data = array_slice( $this->example_data,( ( $current_page-1 )* $per_page ), $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		) );
		$this->items = $this->found_data;
	}

}

?>