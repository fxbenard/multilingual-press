<?php
/**
 * Controller for the relationship management above the Advanced Translator.
 *
 * @author  Inpsyde GmbH, toscho
 * @version 2014.10.10
 * @license GPL
 */
class Mlp_Relationship_Control implements Mlp_Updatable {

	/**
	 * Passed by main controller.
	 *
	 * @type Inpsyde_Property_List_Interface
	 */
	private $plugin;

	/**
	 * Unique prefix to detect our registered actions and form names.
	 *
	 * @type string
	 */
	private $prefix = 'mlp_rsc';

	/**
	 * @type Mlp_Relationship_Control_Data
	 */
	private $data;

	/**
	 * Constructor
	 *
	 * @uses  Mlp_Relationship_Control_Data
	 * @param Inpsyde_Property_List_Interface $plugin
	 */
	public function __construct( Inpsyde_Property_List_Interface $plugin ) {

		$this->plugin = $plugin;
		$this->data   = new Mlp_Relationship_Control_Data;

		if ( $this->is_ajax() ) {
			$this->set_up_ajax();
		}
		else {
			add_action(
				'mlp_translation_meta_box_bottom',
				array ( $this, 'set_up_meta_box_handlers' ),
				200,
				3
			);
		}
	}

	/**
	 * Register AJAX callbacks.
	 *
	 * @return void
	 */
	public function set_up_ajax() {

		$this->data = new Mlp_Relationship_Control_Data;

		if ( $this->prefix . '_search' === $_REQUEST['action'] ) {
			add_action(
				"wp_ajax_" . $_REQUEST['action'],
				array ( $this, 'ajax_search_callback' )
			);
			return;
		}

		add_action(
			"wp_ajax_" . $_REQUEST['action'],
			array ( $this, 'ajax_reconnect_callback' )
		);
	}

	/**
	 * Callback for AJAX search.
	 *
	 * @uses   Mlp_Relationship_Control_Ajax_Search
	 * @return void
	 */
	public function ajax_search_callback() {

		$view = new Mlp_Relationship_Control_Ajax_Search( $this->data );
		$view->render();
	}

	/**
	 * Callback for AJAX reconnect.
	 *
	 * @uses   Mlp_Relationship_Changer
	 * @return void
	 */
	public function ajax_reconnect_callback() {

		$start = strlen( $this->prefix ) + 1;
		$func = substr( $_REQUEST['action'], $start );

		$reconnect = new Mlp_Relationship_Changer( $this->plugin );
		$result = $reconnect->$func();

		status_header( 200 );

		// Never visible for the user, for debugging only.
		if ( is_scalar( $result ) )
			print $result;
		else
			print '<pre>' . print_r( $result, 1 ) . '</pre>';

		die;
	}

	/**
	 * Create the UI above the Advanced Translator metabox.
	 *
	 * @wp-hook mlp_translation_meta_box_bottom
	 * @uses    Mlp_Relationship_Control_Meta_Box_View
	 * @param   WP_Post $post
	 * @param   int     $remote_blog_id
	 * @param   WP_Post $remote_post
	 * @return void
	 */
	public function set_up_meta_box_handlers(
		WP_Post $post,
		        $remote_blog_id,
		WP_Post $remote_post
	) {

		global $pagenow;

		if ( 'post-new.php' === $pagenow )
			return; // maybe later, for now, we work on existing posts only

		$this->data = new Mlp_Relationship_Control_Data;

		$this->data->set_ids(
		   array (
			   'source_post_id' => $post->ID,
			   'source_blog_id' => get_current_blog_id(),
			   'remote_blog_id' => $remote_blog_id,
			   'remote_post_id' => $remote_post->ID
		   )
		);
		$view = new Mlp_Relationship_Control_Meta_Box_View( $this->data, $this );
		$view->render();
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		if ( 'default.remote.posts' === $name ) {
			$view = new Mlp_Relationship_Control_Ajax_Search( $this->data );
			$view->render();
		}
	}

	/**
	 * Check if this is our AJAX request.
	 *
	 * @return bool
	 */
	private function is_ajax() {

		if ( ! defined( 'DOING_AJAX' ) or ! DOING_AJAX )
			return FALSE; // no request to an AJAX file

		if ( empty ( $_REQUEST['action'] ) )
			return FALSE; // broken AJAX request

		// Our AJAX actions start with $this->prefix
		return 0 === strpos( $_REQUEST['action'], $this->prefix );
	}

}
