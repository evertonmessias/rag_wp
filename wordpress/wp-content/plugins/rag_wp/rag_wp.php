<?php

/**
 * Plugin Name: RAG WordPress
 * Plugin URI: https://ic.unicamp.br/~everton
 * Description: Plugin RAG WordPress
 * Author: EvM.
 * Version: 1.0
 * Text Domain: RAG WordPress
 * Plugin RAG WordPress
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// ***************** Add DB
function add_db_acessos()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name1 = $wpdb->prefix . 'acessos';
    $sql1 = "CREATE TABLE $table_name1 (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user` text NOT NULL,
        `ipadress` text NOT NULL,
        `url` text NOT NULL,
        `time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
    ) $charset_collate;";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name1'") != $table_name1) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
    }

    $table_name2 = $wpdb->prefix . 'pdf_index';
    $sql2 = "CREATE TABLE $table_name2 (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `arquivo` VARCHAR(255) NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `texto` LONGTEXT NOT NULL,
        `tipo` VARCHAR(10) NOT NULL,
        `ano` INT(4) NOT NULL,
        `id_post` INT(10) NOT NULL,
        INDEX(arquivo)
    ) $charset_collate;";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name2'") != $table_name2) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql2);
    }

    $table_name3 = $wpdb->prefix . 'emails';
    $sql3 = "CREATE TABLE $table_name3 (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `data` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        `de` VARCHAR(255) NOT NULL,
        `para` VARCHAR(255) NOT NULL,
        `assunto` VARCHAR(255) NOT NULL,        
        `mensagem` LONGTEXT NOT NULL,
        `codigo` VARCHAR(255) NOT NULL    
    ) $charset_collate;";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name3'") != $table_name3) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql3);
    }

    // Remove o papel de Autor
    if (get_role('author')) {
        remove_role('author');
    }

    // Remove o papel de Colaborador
    if (get_role('contributor')) {
        remove_role('contributor');
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'add_db_acessos');


// DEACTIVATE *************************************************
function deactivate()
{
    wp_clear_scheduled_hook('icode_sincronizar_usuarios_cron');
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'deactivate');


// LOGS ************************************************
// Pasta central dos logs em texto (protegida por .htaccess e ignorada no deploy)
define('ICODE_LOGS_DIR', ABSPATH . '/wp-content/plugins/novoicode/includes/src/logs/');


// FUNCTIONS ************************************************
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/user.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/usermeta.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/login.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/sync.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/cat.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/permalink.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/pdf.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/frontend.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/functions.php';
include ABSPATH . '/wp-content/plugins/novoicode/includes/pdfparser/vendor/autoload.php';

// ADMIN MENU ************************************************
include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/menu.php';

// SETTINGS ************************************************
include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/settings.php';

// TYPES ************************************************
include ABSPATH . '/wp-content/plugins/novoicode/includes/src/types.php';