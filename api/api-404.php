<?php

/**
 * @api {get} /redirection/v1/404 Get 404 logs
 * @apiDescription Get 404 logs
 * @apiGroup 404
 *
 * @apiParam {string} groupBy Group by 'ip' or 'url'
 * @apiParam {string} orderby
 * @apiParam {string} direction
 * @apiParam {string} filterBy
 * @apiParam {string} per_page
 * @apiParam {string} page
 */

/**
 * @api {post} /redirection/v1/404 Delete 404 logs
 * @apiDescription Delete 404 logs either by ID or filter or group
 * @apiGroup 404
 *
 * @apiParam {string} items Array of log IDs
 * @apiParam {string} filterBy
 * @apiParam {string} groupBy Group by 'ip' or 'url'
 */

/**
 * @api {post} /redirection/v1/bulk/404/delete Bulk actions on 404s
 * @apiDescription Delete 404 logs either by ID
 * @apiGroup 404
 */
class Redirection_Api_404 extends Redirection_Api_Filter_Route {
	public function __construct( $namespace ) {
		$orders = [ 'url', 'ip', 'total' ];
		$filters = [ 'ip', 'url-exact', 'referrer', 'agent', 'url' ];

		register_rest_route( $namespace, '/404', array(
			'args' => $this->get_filter_args( $orders, $filters ),
			$this->get_route( WP_REST_Server::READABLE, 'route_404' ),
			$this->get_route( WP_REST_Server::EDITABLE, 'route_delete_all' ),
		) );

		$this->register_bulk( $namespace, '/bulk/404/(?P<bulk>delete)', $orders, 'route_bulk' );
	}

	public function route_404( WP_REST_Request $request ) {
		return $this->get_404( $request->get_params() );
	}

	public function route_bulk( WP_REST_Request $request ) {
		$params = $request->get_params();
		$items = explode( ',', $request['items'] );

		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( is_numeric( $item ) ) {
					RE_404::delete( intval( $item, 10 ) );
				} else {
					RE_404::delete_all( $this->get_delete_group( $params ), $item );
				}
			}

			return $this->route_404( $request );
		}

		return $this->add_error_details( new WP_Error( 'redirect', 'Invalid array of items' ), __LINE__ );
	}

	private function get_delete_group( array $params ) {
		if ( isset( $params['groupBy'] ) && $params['groupBy'] === 'ip' ) {
			return 'ip';
		}

		return 'url-exact';
	}

	public function route_delete_all( WP_REST_Request $request ) {
		$params = $request->get_params();
		$filter = false;
		$filter_by = false;

		if ( isset( $params['items'] ) && is_array( $params['items'] ) ) {
			foreach ( $params['items'] as $url ) {
				RE_404::delete_all( $this->get_delete_group( $params ), $url );
			}
		} else {
			if ( isset( $params['filterBy'] ) && is_array( $params['filterBy'] ) ) {
				$filter_by = $params['filterBy'];
			}

			RE_404::delete_all( $filter_by );

			unset( $params['filterBy'] );
		}

		unset( $params['page'] );

		return $this->get_404( $params );
	}

	private function get_404( array $params ) {
		if ( isset( $params['groupBy'] ) && in_array( $params['groupBy'], array( 'ip', 'url' ), true ) ) {
			return RE_Filter_Log::get_grouped( 'redirection_404', $params['groupBy'], $params );
		}

		return RE_Filter_Log::get( 'redirection_404', 'RE_404', $params );
	}
}
