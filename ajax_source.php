<?php

/**
 * AJAX backend.
 */
function completions_callback() {
    header("Content-type: application/json");
    $term = $_POST['term'];
    $source_url = get_option('source_url', '');
    $comps_json = file_get_contents($source_url . "/completions/" . $term);
    echo $comps_json;
    wp_die();
}

function tfbsdb2api_ajax_source_init()
{
    // a hook Javascript to anchor our AJAX call
    wp_enqueue_script('ajax_dt', plugins_url('js/ajax_dt.js', __FILE__), array('jquery'));
    wp_localize_script('ajax_dt', 'ajax_dt', array('ajax_url' => admin_url('admin-ajax.php')), '1.0', true);

    // We need callbacks for both non-privileged and privileged users
    add_action('wp_ajax_nopriv_completions', 'completions_callback');
    add_action('wp_ajax_completions', 'completions_callback');
}

?>
