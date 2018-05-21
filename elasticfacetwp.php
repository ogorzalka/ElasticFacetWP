<?php

declare(strict_types=1);
/*
Plugin Name: ElasticFacetWP
Plugin URI: https://www.behaba.com
Description: Adds ElasticPress search provider for FacetWP.
Version: 0.1.0
Author: Olivier Gorzalka
Author URI: https://www.behaba.com
*/

if (! defined('ABSPATH')) {
    exit;
}

class elasticfacetwp
{
    /**
     * ElasticFacetWP constructor.
     */
    public function __construct()
    {
        add_filter('facetwp_query_args', [ $this, 'search_query_args' ], 10, 2);
        add_filter('ep_formatted_args', [ $this, 'fix_facetwp_order' ], 10, 2);
        add_filter('facetwp_settings_admin', [ $this, 'filter_prefix' ], 10, 1);
    }

    public function filter_prefix($settings)
    {
        $settings['general']['fields']['prefix']['html'] = __('Default ElasticPress prefix', 'elasticfacetwp') . ' (<code>filter_</code>)';

        return $settings;
    }

    public function activate()
    {

        // force new search prefix

        $facetwp_opts = json_decode(get_option('facetwp_settings'));

        $search_prefix = $facetwp_opts->settings->prefix;

        $facetwp_opts->settings->prefix = 'filter_';

        $old_facetwp_opts = [];

        $old_facetwp_opts['prefix'] = $search_prefix;

        update_option('old_facetwp_settings', $old_facetwp_opts);
        update_option('facetwp_settings', json_encode($facetwp_opts));
    }

    public function deactivate()
    {
        $facetwp_opts = json_decode(get_option('facetwp_settings'));
        $old_facetwp_opts = get_option('old_facetwp_settings');

        foreach ($old_facetwp_opts as $key => $setting) {
            $facetwp_opts->settings->$key = $setting;
        }

        update_option('facetwp_settings', json_encode($facetwp_opts));
    }

    /**
     * Fix for a misunderstood argument sort:post__in by FacetWP
     */
    public function fix_facetwp_order($ep_formatted_args, $args = '')
    {
        foreach ($ep_formatted_args['sort'] as $key => $sort) {
            if (array_key_exists('post__in', $sort)) {
                unset($ep_formatted_args['sort'][$key]);
            }
        }

        return $ep_formatted_args;
    }

    /**
     * Intercept search facets using ElasticPress engine
     *
     * @since 0.1.0
     */
    public function search_query_args($search_args, $params)
    {
        $search_args['ep_integrate'] = true;

        return $search_args;
    }
}

new ElasticFacetWP();

register_activation_hook(__FILE__, [ 'ElasticFacetWP', 'activate' ]);
register_deactivation_hook(__FILE__, [ 'ElasticFacetWP', 'deactivate' ]);
