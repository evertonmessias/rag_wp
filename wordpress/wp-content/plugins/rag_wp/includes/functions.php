<?php
// Arquivo: wp-content/plugins/rag_wp/includes/functions.php

// Diretório e URL dos anexos ***********************************************************

// /var/www/html/wp-content/uploads/attachments  (ou .../attachments/{id})
function rag_wp_attachments_dir($post_id = null)
{
    $dir = trailingslashit(wp_upload_dir(null, false)['basedir']) . RAG_WP_ATTACHMENTS;
    return $post_id ? $dir . '/' . intval($post_id) : $dir;
}

// /wp-content/uploads/attachments  (ou .../attachments/{id})
function rag_wp_attachments_url($post_id = null)
{
    $url = trailingslashit(wp_upload_dir(null, false)['baseurl']) . RAG_WP_ATTACHMENTS;
    return $post_id ? $url . '/' . intval($post_id) : $url;
}

// Só posts comuns têm gerenciador de anexos (não há post types personalizados)
function rag_wp_post_types()
{
    return ['post'];
}


// Styles & scripts (só nas páginas do plugin) *********************************************
function rag_wp_admin_assets($hook)
{
    if (strpos($hook, 'rag_wp') === false && !in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    wp_enqueue_style('rag_wp', RAG_WP_URL . 'assets/css/rag_wp.css', [], filemtime(RAG_WP_DIR . 'assets/css/rag_wp.css'));
    wp_enqueue_script('rag_wp', RAG_WP_URL . 'assets/js/rag_wp.js', ['jquery'], filemtime(RAG_WP_DIR . 'assets/js/rag_wp.js'), true);
}
add_action('admin_enqueue_scripts', 'rag_wp_admin_assets');
