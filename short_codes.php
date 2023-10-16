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
    $content .= "    <tr><th>Entrez ID</th><th>Name</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>Promoter</th><th>TSS</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($gene_infos as $gene_info) {
        if ($gene_info->entrez == null) { $entrez_link = '-'; }
        else {
            $entrez_link = "<a href=\"https://www.ncbi.nlm.nih.gov/gene/?term=" . $gene_info->entrez . "\" target=\"_blank\">" . $gene_info->entrez . "</a>";
        }
        $synonyms = '-';
        if (count($gene_info->synonyms) > 0) {
            $synonyms = $gene_info->synonyms[0]->name;
        }

        $content .= "    <tr>";
        $content .= "      <td>$entrez_link</td>";
        $content .= "      <td>$synonyms</td>";
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
    $content .= "<h3>Gene Annotations</h3>";
    $content .= _render_gene_table([$gene_info]);
    $content .= "";
    return $content;
}

function motif_shortinfo_shortcode($attr, $content=null)
{
    $motif_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/motif_shortinfo/" .
                                     rawurlencode($motif_id));
    $motif_info = json_decode($result_json);
    $content = "<div>Motif: $motif_info->motif_name | $motif_info->motif_database</div>";
    return $content;
}

function gene_shortinfo_shortcode($attr, $content=null)
{
    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_info/" .
                                     rawurlencode($gene_id));
    $gene_info = json_decode($result_json);
    if (count($gene_info->synonyms) > 0) {
        $synonyms = $gene_info->synonyms[0]->name;
    } else {
        $synonyms = $gene_info->entrez;
    }
    $content = "<div>$synonyms | $gene_info->description</div>";
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
    $content .= "    <tr><th>Entrez</th><th>Name</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>TSS</th><th>Prom. Start</th><th>Prom. Stop</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $g) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/gene?id=$g->id\">$g->entrez</a></td><td>$g->synonyms</td><td>$g->description</td><td>$g->chromosome</td><td>$g->strand</td><td>$g->tss</td><td>$g->start_promoter</td><td>$g->stop_promoter</td>";
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

function regulated_by_shortcode($attr, $content=null)
{
    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/regulated_by/" . rawurlencode($gene_id));
    $entries = json_decode($result_json)->tf_binding_sites;
    $content = "<h3>Regulated by</h3>";
    $content .= "<table id=\"regulated_by\" class=\"stripe row-border\">";
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
    $content .= "    jQuery('#regulated_by').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}

function regulates_shortcode($attr, $content=null)
{
    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/regulates/" . rawurlencode($gene_id));
    $entries = json_decode($result_json)->result;
    $content = "<h3>Regulates</h3>";
    $content .= "<table id=\"regulates\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Target gene</th><th>Motif</th><th>Motif DB</th><th># sites</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $s) {
        $content .= "    <tr>";
        $content .= "      <td>$s->gene_name</td><td><a href=\"index.php/motif?id=$s->motif_id\">$s->motif</a></td><td>$s->motif_database</td><td>$s->num_sites</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#regulates').DataTable({});";
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
    $content .= "<h2>Genes with Motif</h2>";
    $content .= "<table id=\"target_genes\" class=\"stripe row-border\">";
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

function motif_tfs_shortcode($attr, $content=null)
{
    $motif_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/motif_tfs/" . rawurlencode($motif_id));
    $entries = json_decode($result_json)->tfs;
    $content .= "<h2>TFS that bind to Motif</h2>";
    $content .= "<table id=\"tfs\" class=\"stripe row-border\">";
    $content .= "  <thead>";
    $content .= "    <tr><th>Entrez ID</th><th>Name</th><th>Description</th><th>Chromosome</th><th>Strand</th><th>Promoter</th><th>TSS</th></tr>";
    $content .= "  </thead>";
    $content .= "  <tbody>";
    foreach ($entries as $g) {
        $content .= "    <tr>";
        $content .= "      <td><a href=\"index.php/gene?id=$g->gene_id\">$g->entrez_id</a></td><td>$g->synonyms</td><td>$g->description</td><td>$g->chromosome</td><td>$g->strand</td><td>$g->promoter_start-$g->promoter_stop</td><td>$g->tss</td>";
        $content .= "    </tr>";
    }
    $content .= "  </tbody>";
    $content .= "</table>";
    $content .= "<script>";
    $content .= "  jQuery(document).ready(function() {";
    $content .= "    jQuery('#tfs').DataTable({});";
    $content .= "  });";
    $content .= "</script>";
    return $content;
}


function igv_shortcode($attr, $content=null)
{
    $static_url = get_option('static_url', '');

    $gene_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/gene_info/" . rawurlencode($gene_id));
    $gene_info = json_decode($result_json);
    $locus = "$gene_info->chromosome:$gene_info->start_promoter-$gene_info->stop_promoter";

    $chrom = $gene_info->chromosome;

    $encode_all_cre_url = $static_url . "/GRCh38-cCREs_$chrom.bed";
    $encode_dnase_url = $static_url . "/GRCh38-cCREs.DNase-H3K4me3_$chrom.bed";
    $encode_ctcf_url = $static_url . "/GRCh38-cCREs.CTCF-only_$chrom.bed";
    $encode_promoter_url = $static_url . "/GRCh38-cCREs.PLS_$chrom.bed";
    $encode_pels_url = $static_url . "/GRCh38-cCREs.pELS_$chrom.bed";
    $encode_dels_url = $static_url . "/GRCh38-cCREs.dELS_$chrom.bed";

    $content = "<div id=\"igv-div\"></div>";
    $content .= "<script>";
    $content .= "var igvDiv = document.getElementById(\"igv-div\");";
    $content .= "console.log(igvDiv);";
    $content .= "var options =";
    $content .= "{";
    $content .= "  genome: \"hg38\",";
    $content .= "  locus: \"$locus\",";
    $content .= "  tracks: [";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE all cCREs\",";
    $content .= "      \"url\": \"$encode_all_cre_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    },";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE H3K4me3 DNAse signal\",";
    $content .= "      \"url\": \"$encode_dnase_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    },";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE CTCF ChiP-seq signal\",";
    $content .= "      \"url\": \"$encode_ctcf_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    },";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE Promoter-like\",";
    $content .= "      \"url\": \"$encode_promoter_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    },";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE Proximal-like\",";
    $content .= "      \"url\": \"$encode_pels_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    },";
    $content .= "    {";
    $content .= "      \"name\": \"ENCODE distal-like\",";
    $content .= "      \"url\": \"$encode_dels_url\",";
    $content .= "      \"format\": \"annotation\"";
    $content .= "    }";
    $content .= "  ]";
    $content .= "};";
    $content .= "igv.createBrowser(igvDiv, options)";
    $content .= "  .then(function (browser) {";
    $content .= "    console.log(\"Created IGV browser\");";
    $content .= "  });";
    $content .= "</script>";
    /*
      ENCODE['wgEncodeRegMarkH3k27acNhlf', 'wgEncodeRegMarkH3k27acNhek', 'wgEncodeRegMarkH3k27acK562',
      'wgEncodeRegMarkH3k27acHuvec', 'wgEncodeRegMarkH3k27acHsmm', 'wgEncodeRegMarkH3k27acH1hesc', 'wgEncodeRegMarkH3k27acGm12878'
      ]
      https://api.genome.ucsc.edu/getData/track?track=wgEncodeRegMarkH3k27acGm12878&genome=hg38&chrom=chr1
     */
    return $content;
}


function seqlogo_shortcode($attr, $content=null)
{
    $motif_id = get_query_var('id');
    $source_url = get_option('source_url', '');
    $result_json = file_get_contents($source_url . "/motif_pssm/" . rawurlencode($motif_id));
    $pssm = json_decode($result_json)->pssm;
    $content = "<div><div id=\"motif-pssm\"></div></div>";
    $content .= "<script>";
    $content .= "seqlogo.makeLogo('motif-pssm',";
    $content .= "{";
    $content .= "  alphabet: ['A', 'C', 'G', 'T'],";
    $content .= "  values: [";
    foreach ($pssm as $row) {
        $content .= "    [$row->a, $row->c, $row->g, $row->t],";
    }
    $content .= "  ]";
    $content .= "},";
    $content .= "{";
    $content .= "  width: 300, height: 180,";
    $content .= "  glyphStyle: '20pt Helvetica'";
    $content .= "});";
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
    add_shortcode('regulates', 'regulates_shortcode');
    add_shortcode('regulated_by', 'regulated_by_shortcode');
    add_shortcode('motif_target_genes', 'motif_target_genes_shortcode');
    add_shortcode('igv_browser', 'igv_shortcode');
    add_shortcode('seqlogo', 'seqlogo_shortcode');

    add_shortcode('gene_shortinfo', 'gene_shortinfo_shortcode');
    add_shortcode('motif_shortinfo', 'motif_shortinfo_shortcode');
    add_shortcode('motif_tfs', 'motif_tfs_shortcode');

    // Search related short codes
    add_shortcode('tfbsdb2_searchbox', 'search_box_shortcode');
}


?>
