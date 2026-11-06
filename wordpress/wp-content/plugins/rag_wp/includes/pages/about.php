<?php
// Arquivo: wp-content/plugins/rag_wp/includes/pages/about.php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap rag-wp-page">
    <h1>RAG WP</h1>

    <div class="card">
        <h2>Sobre</h2>
        <p><strong>RAG WP</strong> anexa PDFs aos posts, extrai o texto e o guarda no banco para consulta com IA (RAG).</p>
        <ol>
            <li>Crie o post e clique em <em>Salvar rascunho</em> ou <em>Publicar</em>. Isso cria a pasta
                <code>wp-content/uploads/<?php echo esc_html(RAG_WP_ATTACHMENTS); ?>/{id do post}</code>.</li>
            <li>Na edição do post, envie os PDFs pelo <strong>Gerenciador de Anexos</strong>.</li>
            <li>Selecione os PDFs e clique em <strong>Indexar PDFs</strong>. Para indexar tudo de uma vez, use a página
                <a href="<?php echo esc_url(admin_url('admin.php?page=rag_wp_pdfparser')); ?>">PDFParser</a>.</li>
        </ol>
        <p>
            <a href="https://github.com/evertonmessias/rag_wp" target="_blank" rel="noopener">Projeto</a>
            &emsp;|&emsp;
            <a href="https://ic.unicamp.br/~everton" target="_blank" rel="noopener">Site do Desenvolvedor</a>
        </p>
    </div>
</div>
