<?php
/**
 * Plugin Name:       Master Query Loop
 * Description:       Add advanced features to the WordPress core/query block: get specific posts, popular posts and more!
 * Requires at least: 5.9
 * Requires PHP:      7.0
 * Version:           1.0.1
 * Author:            WPMasterpiece
 * Author URI:        https://wpmasterpiece.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       master-query-loop
 *
 */

/**
 * Include a setting page
 *
 * @since 0.1.0
 */
include_once(__DIR__ . '/inc/mql-setting.php');

/**
 * Add more controls & styles to the core/query block
 *
 * @since 0.1.0
 */
if ( ! function_exists( 'masterqueryloop_prefix_editor_assets' ) ):
    function masterqueryloop_prefix_editor_assets() {
        $variations_assets_file = __DIR__ . '/build/index.asset.php';
        if ( file_exists( $variations_assets_file ) ) {
            $assets = include $variations_assets_file;
            wp_enqueue_script(
                'master-query-loop',
                plugin_dir_url( __FILE__ ) . '/build/index.js',
                $assets['dependencies'],
                $assets['version'],
                true
            );
            wp_enqueue_style(
                'master-query-loop',
                plugin_dir_url( __FILE__ ) . '/build/index.css',
                [],
                $assets['version']
            );
        }
    }
    add_action( 'enqueue_block_editor_assets', 'masterqueryloop_prefix_editor_assets' );
endif;

/**
 * Add one_time filter to modify the query params in the frontend side so that it does not affect the other query blocks placed behind it
 * @issue https://github.com/WordPress/gutenberg/issues/49810 
 *
 * @since 0.1.0
 */
if ( ! function_exists( 'masterqueryloop_modify_pre_render_block_defaults' ) ):
    function masterqueryloop_modify_pre_render_block_defaults($pre_render, $parsed_block, $parent_block) {
        $filter = function( $query ) use ( $parsed_block, &$filter ) {
            if ( $parsed_block[ 'attrs' ][ 'query' ][ 'include' ] ) {
                $query[ 'post__in' ] = $parsed_block[ 'attrs' ][ 'query' ][ 'include' ];
                $query[ 'orderby' ] = 'post__in';
            }
            if ( $parsed_block[ 'attrs' ][ 'query' ][ 'meta_key_view_count' ] ) {
                $query[ 'meta_key' ] = $parsed_block[ 'attrs' ][ 'query' ][ 'meta_key_view_count' ];
                $query[ 'orderby' ] = 'meta_value_num';
    
                if ( $parsed_block[ 'attrs' ][ 'query' ][ 'meta_date_range' ] ) {
                    $query['date_query'] = array(
                        'after' => date('Y-m-d', strtotime($parsed_block[ 'attrs' ][ 'query' ][ 'meta_date_range' ]))
                    );
                }
            }
            remove_filter( 'query_loop_block_query_vars', $filter, 10, 2 );
            return $query;
        };
    
        if( 'wpmasterpiece/master-query-loop' === $parsed_block[ 'attrs' ][ 'namespace' ] ) {
            add_filter( 'query_loop_block_query_vars', $filter, 10, 2 );
        }
        return $pre_render; 
    }
    add_filter( "pre_render_block", "masterqueryloop_modify_pre_render_block_defaults", 10, 3 );
endif;

/**
 * Add one_time filter to modify the query params in the editor side
 *
 * @since 0.1.0
 */
if ( ! function_exists( 'masterqueryloop_add_custom_query_params' ) ):
    // Add more query to rest post query to query for popular posts
    function masterqueryloop_add_custom_query_params( $args, $request ) {
        if ( $request->get_param( 'meta_key_view_count' ) ) {
            $args['meta_key'] = $request->get_param( 'meta_key_view_count' );
            $args['orderby'] = 'meta_value_num';
    
            if ( $request->get_param( 'meta_date_range' ) ) {
                $args['date_query'] = array(
                    'after' => date('Y-m-d', strtotime($request->get_param( 'meta_date_range' )))
                );
            }
        }
        return $args;
    }
    add_action(
        'init',
        function() {
            add_filter( 'rest_post_query', 'masterqueryloop_add_custom_query_params', 10, 2 );
        },
        PHP_INT_MAX
    );
endif;

/**
 * Set post view
 *
 * @since 0.1.0
 */
if ( ! function_exists( 'masterqueryloop_post_view_handler' ) ):
    function masterqueryloop_post_view_handler( ) {
        // Check if plugin config turn on view count
        $options = get_option( 'mql_options' );
        if ( !isset($options['mql_field_post_view_count_enable']) ) {
            return;
        }
        if ( is_singular( ) && !is_user_logged_in() ) {
            $object_id = get_queried_object_id();
            $count = get_post_meta( $object_id, MQL_VIEW_COUNT, true );
            if ( ! $count ) {
                add_post_meta( $object_id, MQL_VIEW_COUNT, 1, true );
            } else {
                $count++;
                update_post_meta( $object_id, MQL_VIEW_COUNT, $count );
            }
        }
    }
    add_action( 'template_redirect', 'masterqueryloop_post_view_handler' );
endif;
