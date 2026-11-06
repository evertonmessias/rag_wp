<?php
// Arquivo: wp-content/plugins/rag_wp/includes/pages/menu.php

if (!defined('ABSPATH')) {
    exit;
}

function rag_wp_menu()
{
    add_menu_page('RAG WP', 'RAG WP', 'edit_posts', 'rag_wp', 'rag_wp_page_about', 'dashicons-media-document', 3);
    add_submenu_page('rag_wp', 'Sobre', 'Sobre', 'edit_posts', 'rag_wp', 'rag_wp_page_about');
    add_submenu_page('rag_wp', 'PDFParser', 'PDFParser', 'edit_others_posts', 'rag_wp_pdfparser', 'rag_wp_page_pdfparser');
}
add_action('admin_menu', 'rag_wp_menu');

function rag_wp_page_about()
{
    include RAG_WP_DIR . 'includes/pages/about.php';
}

function rag_wp_page_pdfparser()
{
    include RAG_WP_DIR . 'includes/pages/pdfparser.php';
}
