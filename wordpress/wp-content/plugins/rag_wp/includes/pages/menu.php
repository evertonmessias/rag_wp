<?php
//Arquivo: wp-content/plugins/novoicode/includes/pages/menu.php

// ***************** Add in Menu ******************************************************************
function menu_icode()
{
    add_menu_page('ICODE', 'ICODE', 'edit_posts', 'icode', 'function_about', 'dashicons-screenoptions', 1);
    add_submenu_page('icode', 'Acessos', 'Acessos', 'edit_posts', 'icode_acessos', 'function_acessos', 2);
    add_submenu_page('icode', 'PDFParser', 'PDFParser', 'edit_posts', 'icode_pdfparser', 'function_pdfparser', 3);
    add_submenu_page('icode', 'E-Mails', 'E-Mails', 'edit_posts', 'icode_emails', 'function_emails', 3);
    add_submenu_page('icode', 'Sincronizar Usuários', 'Sincronizar Usuários', 'manage_options', 'icode_sync', 'function_sync', 4);

}

add_action('admin_menu', 'menu_icode');

// ***************** Add About ************************************************************
function function_about()
{
    include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/about.php';
}
// ***************** Add Acessos ***************************************************************
function function_acessos()
{
    include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/acessos.php';
}
// ***************** Add page pdfparser ******************************************************
function function_pdfparser()
{
    include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/pdfparser.php';
}

// ***************** Add page emails ******************************************************
function function_emails()
{
    include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/emails.php';
}

// ***************** Add page sync ******************************************************
function function_sync()
{
    include ABSPATH . '/wp-content/plugins/novoicode/includes/pages/sync.php';
}