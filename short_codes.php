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
    $content = "<table>";
    $content .= "  <thead>";
    $content .= "    <tr><th>Entrez ID</th><th>EnsEMBL ID</th><th>Preferred Name</th><th>Uniprot ID</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($gene_infos as $gene_info) {
        if ($gene_info->entrez_id == null) { $entrez_link = '-'; }
        else {
            $entrez_link = "<a href=\"https://www.ncbi.nlm.nih.gov/gene/?term=" . $gene_info->entrez_id . "\" target=\"_blank\">" . $gene_info->entrez_id . "</a>";
        }

        $content .= "    <tr>";
        $content .= "      <td>" . $entrez_link . "</td>";
        $content .= "      <td><a href=\"http://www.ensembl.org/id/" . $gene_info->ensembl_id . "\" target=\"_blank\">" . $gene_info->ensembl_id . "</a></td>";
        $content .= "      <td><a href=\"index.php/gene/?gene=$gene_info->preferred\">$gene_info->preferred</a></td>";
        $content .= "      <td><a href=\"https://www.uniprot.org/uniprot/" . $gene_info->uniprot_id . "\" target=\"_blank\">" . $gene_info->uniprot_id . "</a></td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    return $content;
}

function gene_info_table($gene_name)
{
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_info/" .
                                     rawurlencode($gene_name));
    $gene_info = json_decode($result_json);
    $content = "";
    if ($gene_info->preferred == 'NA') {
        return $content;
    }
    if ($gene_info->preferred != null) {
        $preferred_name = $gene_info->preferred;
    } else {
        $preferred_name = $gene_name;
        $gene_info->preferred = '-';
    }

    $desc = preg_replace('/\[.*\]/', '', $gene_info->description);

    $content .= "<h3>" . $preferred_name . " - " . $desc;
    $content .= "</h3>";
    $content .= "<h4>Function</h4>";
    $content .= "<p>$gene_info->function</p>";
    $content .= "<a href=\"index.php/gene-uniprot/?gene=" . $gene_name . "\">" . "Uniprot Browser" . "</a>";
    $content .= _render_gene_table([$gene_info]);
    $content .= "";
    return $content;
}

function gene_info_shortcode($attr, $content=null)
{
    return gene_info_table(get_query_var('gene'));
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
        $content .= "      <td>$g->entrez</td><td>$g->description</td><td>$g->chromosome</td><td>$g->strand</td><td>$g->tss</td><td>$g->start_promoter</td><td>$g->stop_promoter</td>";
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
        $content .= "      <td>$m->name</td><td>$m->db</td>";
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


function tfbsdb2api_add_shortcodes()
{
    add_shortcode('summary', 'summary_shortcode');

    // Gene related short codes
    add_shortcode('gene_info', 'gene_info_shortcode');
    add_shortcode('gene_uniprot', 'gene_uniprot_shortcode');

    // Lists
    add_shortcode('motif_table', 'motif_table_shortcode');
    add_shortcode('gene_table', 'gene_table_shortcode');

    // Search related short codes
    add_shortcode('tfbsdb2_searchbox', 'search_box_shortcode');
}

?>