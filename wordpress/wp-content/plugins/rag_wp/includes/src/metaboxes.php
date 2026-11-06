<?php
// Arquivo: wp-content/plugins/rag_wp/includes/src/metaboxes.php
// Gerenciador de Anexos (elFinder) na edição do post.
// A pasta /wp-content/uploads/attachments/{id} é criada quando o post é salvo.

if (!defined('ABSPATH')) {
    exit;
}

// Meta box ********************************************************************************
add_action('add_meta_boxes', 'rag_wp_add_meta_boxes');
function rag_wp_add_meta_boxes()
{
    foreach (rag_wp_post_types() as $post_type) {
        add_meta_box(
            'rag_wp_anexos',
            'Gerenciador de Anexos',
            'rag_wp_render_anexos',
            $post_type,
            'advanced',
            'default'
        );
    }
}

function rag_wp_render_anexos($post)
{
    // Post ainda não salvo (tela "Adicionar novo"): o id existe, mas a pasta ainda não
    if (!is_dir(rag_wp_attachments_dir($post->ID))) {
        echo '<p class="rag-wp-aviso">Salve o post (rascunho ou publicado) para liberar o envio de PDFs.</p>';
        return;
    }

    $src = add_query_arg(['action' => 'rag_wp_arquivos', 'post_id' => $post->ID], admin_url('admin-ajax.php'));
    ?>
    <p class="description">
        Envie os PDFs deste post. Para extrair o texto e indexar, selecione os arquivos e clique em
        <strong>Indexar PDFs</strong> (barra de ferramentas ou botão direito).
    </p>
    <iframe class="rag-wp-elfinder" src="<?php echo esc_url($src); ?>"></iframe>
    <?php
}

// Mostra o gerenciador logo abaixo do editor de conteúdo
add_action('edit_form_after_editor', function ($post) {
    global $wp_meta_boxes;
    if (!in_array(get_post_type($post), rag_wp_post_types(), true)) {
        return;
    }
    do_meta_boxes(get_current_screen(), 'advanced', $post);
    unset($wp_meta_boxes[get_post_type($post)]['advanced']);
});


// Cria a pasta ao salvar ************************************************************************
add_action('save_post', 'rag_wp_criar_pasta_anexos', 10, 2);
function rag_wp_criar_pasta_anexos($post_id, $post)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!in_array($post->post_type, rag_wp_post_types(), true)) return;
    if (in_array($post->post_status, ['auto-draft', 'trash'], true)) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $dir = rag_wp_attachments_dir($post_id);
    if (is_dir($dir)) return;

    if (wp_mkdir_p($dir)) {
        // Avisa na próxima tela (o WordPress redireciona depois de salvar)
        add_filter('redirect_post_location', function ($location) {
            return add_query_arg('rag_wp_anexos', 'criado', $location);
        });
    } else {
        add_filter('redirect_post_location', function ($location) {
            return add_query_arg('rag_wp_anexos', 'erro', $location);
        });
        error_log('rag_wp: não foi possível criar ' . $dir);
    }
}


// Mensagens ***********************************************************************************
add_action('admin_notices', function () {
    $status = $_GET['rag_wp_anexos'] ?? '';
    if ($status === 'criado') {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Pasta de anexos criada.</strong> '
            . 'Agora você pode anexar PDFs a este post no <a href="#rag_wp_anexos">Gerenciador de Anexos</a>.</p></div>';
    } elseif ($status === 'erro') {
        echo '<div class="notice notice-error"><p>Não foi possível criar a pasta de anexos deste post. '
            . 'Verifique as permissões de <code>wp-content/uploads/' . esc_html(RAG_WP_ATTACHMENTS) . '</code>.</p></div>';
    }
});

// Remove o parâmetro da URL para a mensagem não reaparecer ao recarregar
add_filter('removable_query_args', function ($args) {
    $args[] = 'rag_wp_anexos';
    return $args;
});
