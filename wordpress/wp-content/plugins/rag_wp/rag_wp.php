<?php

/**
 * Plugin Name: RAG WordPress
 * Plugin URI: https://ic.unicamp.br/~everton
 * Description: Anexa PDFs aos posts (elFinder), extrai o texto e indexa no banco para consulta com IA (RAG).
 * Author: EvM.
 * Version: 1.0
 * Text Domain: rag_wp
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

define('RAG_WP_DIR', plugin_dir_path(__FILE__));
define('RAG_WP_URL', plugin_dir_url(__FILE__));

// Anexos de cada post ficam em /wp-content/uploads/attachments/{id do post}/
define('RAG_WP_ATTACHMENTS', 'attachments');


// ACTIVATE *************************************************
function rag_wp_activate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'pdf_index';
    $charset_collate = $wpdb->get_charset_collate();

    // dbDelta exige: dois espaços depois de PRIMARY KEY e um campo por linha
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        id_post BIGINT UNSIGNED NOT NULL,
        arquivo VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        texto LONGTEXT NOT NULL,
        indexado_em DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY arquivo (arquivo),
        KEY id_post (id_post)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    wp_mkdir_p(rag_wp_attachments_dir());
}
register_activation_hook(__FILE__, 'rag_wp_activate');


// INCLUDES ************************************************
require_once RAG_WP_DIR . 'includes/pdfparser/vendor/autoload.php';
require_once RAG_WP_DIR . 'includes/functions.php';
require_once RAG_WP_DIR . 'includes/src/pdf.php';
require_once RAG_WP_DIR . 'includes/elfinder.php';
require_once RAG_WP_DIR . 'includes/src/metaboxes.php';
require_once RAG_WP_DIR . 'includes/pages/menu.php';
