<?php

/**********************************************************************
 * Custom Short codes
 * Render the custom fields by interfacting with the web service
 **********************************************************************/

function summary_shortcode($attr, $content=null)
{
    $source_url = get_option('source_url', '');
    error_log("in summary code");
    $summary_json = file_get_contents($source_url . "/summary");
    $summary = json_decode($summary_json);
    $content = "<h2>Model Overview</h2>";
    $content .= "<table id=\"summary\" class=\"row-border\">";
    $content .= "  <thead><tr><th>#</th><th>Description</th></tr></thead>";
    $content .= "  <tbody>";
    $content .= "    <tr><td><a href=\"index.php/genes\">" . $summary->num_genes . "</a></td><td>Genes</td></tr>";
    $content .= "    <tr><td><a href=\"index.php/motifs\">" . $summary->num_motifs . "</a></td><td>Motifs</td></tr>";
    $content .= "    <tr><td>" . $summary->num_tfbs . "</td><td>TF Binding Sites</td></tr>";
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";

    $content .= "    jQuery('#summary').DataTable({";
    $content .= "      'paging': false,";
    $content .= "      'info': false,";
    $content .= "      'searching': false";
    $content .= "    });";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

/*
 * SEARCH RELATED SHORT CODES
 */
function search_box_shortcode($attr, $content)
{
    $ajax_action = "completions";
    $content = "<form action=\"" . esc_url(admin_url('admin-post.php')) .  "\" method=\"post\">";
    $content .= "Search Term: ";
    $content .= "<div><input name=\"search_term\" type=\"text\" id=\"tfbsdb2api-search\"></input><input type=\"submit\" value=\"Search\" id=\"tfbsdb2api-search-button\"></input></div>";
    $content .= "<input type=\"hidden\" name=\"action\" value=\"search_tfbsdb2api\">";
    $content .= "</form>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#tfbsdb2api-search').autocomplete({";
    $content .= "      source: function(request, response) {";
    $content .= "                jQuery.ajax({ url: ajax_dt.ajax_url, type: 'POST', data: { action: '" . $ajax_action . "', term: request.term }, success: function(data) { response(data.completions) }});";
    $content .= "              },";
    $content .= "      minLength: 2";
    $content .= "    });";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}


function _render_gene_table($gene_infos) {
    $content = "<table id=\"gene_info\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Entrez ID</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>Promoter</th><th>TSS</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($gene_infos as $gene_info) {
        if ($gene_info->entrez == null) { $entrez_link = '-'; }
        else {
            $entrez_link = "<a href=\"https://www.ncbi.nlm.nih.gov/gene/?term=" . $gene_info->entrez . "\" target=\"_blank\">" . $gene_info->entrez . "</a>";
        }

        $content .= "    <tr>";
        $content .= "      <td>$entrez_link</td>";
        $content .= "      <td>$gene_info->description</td>";
        $content .= "      <td>$gene_info->chromosome</td>";
        $content .= "      <td>$gene_info->orientation</td>";
        $content .= "      <td>$gene_info->start_promoter - $gene_info->stop_promoter</td>";
        $content .= "      <td>$gene_info->tss</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";

    $content .= "    jQuery('#gene_info').DataTable({";
    $content .= "      'paging': false,";
    $content .= "      'info': false,";
    $content .= "      'searching': false";
    $content .= "    });";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function gene_info_shortcode($attr, $content=null)
{
    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_info/" .
                                     rawurlencode($gene_id));
    $gene_info = json_decode($result_json);
    $content = "";
    $content .= _render_gene_table([$gene_info]);
    $content .= "";
    return $content;
}

function gene_uniprot_shortcode($attr, $content=null)
{
    $gene_name = get_query_var('gene');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_info/" .
                                     rawurlencode($gene_name));
    $gene_info = json_decode($result_json);
    $content = "";
    //$content .= "<h3>UniProtKB " . $gene_info->uniprot_id . "</h3>";
    $content .= "<div id=\"uniprot-viewer\"></div>";
    $content .= "  <script>";
    $content .= "    window.onload = function() {";
    $content .= "      var yourDiv = document.getElementById('uniprot-viewer');";
    $content .= "      var ProtVista = require('ProtVista');";
    $content .= "      var instance = new ProtVista({";
    $content .= "        el: yourDiv,";
    $content .= "        uniprotacc: '" . $gene_info->uniprot_id . "'";
    $content .= "      });";
    $content .= "    }";
    $content .= "  </script>";
    $content .= "";
    return $content;
}

function gene_table_shortcode($attr, $content=null)
{
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/genes");
    $entries = json_decode($result_json)->genes;
    $content = "<table id=\"genes\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Entrez</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>TSS</th><th>Prom. Start</th><th>Prom. Stop</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $g) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/gene?id=$g->id\">$g->entrez</a></td><td>$g->description</td><td>$g->chromosome</td><td>$g->strand</td><td>$g->tss</td><td>$g->start_promoter</td><td>$g->stop_promoter</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#genes').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function motif_table_shortcode($attr, $content=null)
{
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/motifs");
    $entries = json_decode($result_json)->motifs;
    $content = "<table id=\"motifs\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Motif</th><th>Database</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $m) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/motif?id=$m->id\">$m->name</a></td><td>$m->db</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#motifs').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function gene_tf_binding_sites_shortcode($attr, $content=null)
{
    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_tf_binding_sites/" . rawurlencode($gene_id));
    $entries = json_decode($result_json)->tf_binding_sites;
    $content = "<table id=\"tf_binding_sites\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Motif</th><th>Motif DB</th><th>Strand</th><th>Location</th><th>p-value</th><th>Match Sequence</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $s) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/motif?id=$s->motif_id\">$s->motif</a></td><td>$s->motif_database</td><td>$s->strand</td><td>$s->start-$s->stop</td><td>$s->p_value</td><td>$s->match_sequence</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#tf_binding_sites').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function motif_target_genes_shortcode($attr, $content=null)
{
    $motif_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/motif_target_genes/" . rawurlencode($motif_id));
    $entries = json_decode($result_json)->target_genes;
    $content = "<table id=\"target_genes\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Entrez ID</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>Promoter</th><th>TSS</th><th># Sites</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $g) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/gene?id=$g->gene_id\">$g->entrez_id</a></td><td>$g->description</td><td>$g->chromosome</td><td>$g->strand</td><td>$g->promoter_start-$g->promoter_stop</td><td>$g->tss</td><td>$g->num_sites</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#target_genes').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function tfbsdb2api_add_shortcodes()
{
    add_shortcode('summary', 'summary_shortcode');

    // Gene related short codes
    add_shortcode('gene_info', 'gene_info_shortcode');
    add_shortcode('gene_uniprot', 'gene_uniprot_shortcode');

    // Lists
    add_shortcode('motif_table', 'motif_table_shortcode');
    add_shortcode('gene_table', 'gene_table_shortcode');
    add_shortcode('gene_tf_binding_sites', 'gene_tf_binding_sites_shortcode');
    add_shortcode('motif_target_genes', 'motif_target_genes_shortcode');

    // Search related short codes
    add_shortcode('tfbsdb2_searchbox', 'search_box_shortcode');
}

?>
