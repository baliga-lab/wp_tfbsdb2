<?php
/**
 * @package tfbsdb2
 * @version 1.01
 */
/*
Plugin Name: wp-tfbsdb2
Plugin URI: https://github.com/baliga-lab/wp-tfbsdb2
Description: A plugin that pulls in information from a TFBSDB2 webapi service
Author: Wei-ju Wu
Version: 1.0
Author URI: http://www.systemsbiology.org
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/**********************************************************************
 * Settings Section
 * Users provide and store information about the web service and
 * structure of their web site here
 **********************************************************************/

function tfbsdb2_settings_init() {

    // This is the General section
    add_settings_section(
        "general_section",
        "TFBSDB2",
        "general_section_cb",
        'general'  // general, writing, reading, discussion, media, privacy, permalink
    );
    add_settings_field('source_url', 'Data Source URL', 'source_url_field_cb', 'general',
                       'general_section');
    add_settings_field('static_url', 'Static Data URL', 'static_url_field_cb', 'general',
                       'general_section');
    add_settings_field('tfbsdb2api_slug', 'TFBSDB2 API Slug', 'slug_field_cb', 'general',
                       'general_section');

    register_setting('general', 'source_url');
    register_setting('general', 'static_url');
    register_setting('general', 'tfbsdb2api_slug');
}

function general_section_cb()
{
    echo "<p>General settings for the TFBSDB2 API Plugin</p>";
}

function source_url_field_cb()
{
    $url = get_option('source_url', '');
    echo "<input type=\"text\" name=\"source_url\" value=\"" . $url . "\">";
}

function static_url_field_cb()
{
    $url = get_option('static_url', '');
    echo "<input type=\"text\" name=\"static_url\" value=\"" . $url . "\">";
}

function slug_field_cb()
{
    $slug = get_option('tfbsdb2api_slug', 'tfbsdb2api');
    echo "<input type=\"text\" name=\"tfbsdb2api_slug\" value=\"" . $slug . "\">";
}

/**********************************************************************
 * Plugin Section
 **********************************************************************/

require_once('short_codes.php');
require_once('ajax_source.php');

/*
 * Custom variables that are supposed to be used must be made
 * available explicitly through the filter mechanism.
 */
function add_query_vars_filter($vars) {
    $vars[] = "mutation";
    $vars[] = "gene";
    $vars[] = "id";
    $vars[] = "search_term";
    $vars[] = "";
    return $vars;
}

function tfbsdb2_init()
{
    // add all javascript and style files that are used by our plugin
    wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.css');
    wp_enqueue_style('datatables', plugin_dir_url(__FILE__) . 'css/jquery.dataTables.min.css');
    wp_enqueue_style('wp-tfbsdb2api', plugin_dir_url(__FILE__) . 'css/wp-tfbsdb2api.css');
    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_script('datatables', plugin_dir_url(__FILE__) . 'js/jquery.dataTables.min.js', array('jquery'));

    tfbsdb2api_add_shortcodes();
    tfbsdb2api_ajax_source_init();
    add_filter('query_vars', 'add_query_vars_filter');
}

function search_tfbsdb2api()
{
    $search_term = $_POST['search_term'];
    $source_url = get_option('source_url', '');

    // ask search API if there are results for this term and what type
    $result_json = file_get_contents($source_url . "/search/" .
                                     rawurlencode($search_term));
    $result = json_decode($result_json);

    // Attempt to find the type of result and redirect to that page
    if ($result->num_results > 0) {
        $item_comps = explode(':', $result->results[0]->id);
        error_log("item type: " . $item_comps[0] . " item id: " . $item_comps[1]);
        if ($item_comps[0] == "GENE") {
            $page = get_page_by_path('gene');
        } else if ($item_comps[0] == "MOTIF") {
            $page = get_page_by_path('motif');
        }
        wp_safe_redirect(get_permalink($page->ID) . "?id=" . rawurlencode($item_comps[1]));
        exit;
    }

    // Short circuit search results
    $page = get_page_by_path('no-search-results');
    wp_safe_redirect(get_permalink($page->ID) . "?search_term=" . rawurlencode($search_term));
    exit;
}

add_action('admin_init', 'tfbsdb2_settings_init');
add_action('init', 'tfbsdb2_init');
add_action('admin_post_nopriv_search_tfbsdb2api', 'search_tfbsdb2api');
add_action('admin_post_search_tfbsdb2api', 'search_tfbsdb2api');

?>
