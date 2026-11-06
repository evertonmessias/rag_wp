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
        <p><strong>RAG WP</strong> anexa PDFs aos posts, exibe os anexos no site, extrai o texto dos PDFs e o guarda no
            banco para consulta com IA (RAG).</p>
        <p>
            <a href="https://github.com/evertonmessias/rag_wp" target="_blank" rel="noopener">Projeto</a>
            &emsp;|&emsp;
            <a href="https://ic.unicamp.br/~everton" target="_blank" rel="noopener">Site do Desenvolvedor</a>
        </p>
    </div>

    <div class="card">
        <h2>Manual</h2>

        <h3>1. Anexar PDFs a um post</h3>
        <ol>
            <li>Crie o post e clique em <em>Salvar rascunho</em> ou <em>Publicar</em>. O gerenciador só aparece depois do
                primeiro salvamento, porque a pasta usa o id do post:
                <code>wp-content/uploads/<?php echo esc_html(RAG_WP_ATTACHMENTS); ?>/{id do post}</code>.</li>
            <li>Na edição do post, use o <strong>Gerenciador de Anexos</strong>, abaixo do editor, para enviar os PDFs.
                Só arquivos PDF são aceitos.</li>
            <li>Para separar os anexos em seções (por exemplo "Pautas" e "Deliberações"), crie subpastas no
                gerenciador. Cada subpasta vira uma seção na página do post.</li>
            <li>A ordem é alfabética pelo nome do arquivo. Para controlar a ordem, comece o nome com um número:
                <code>01 - Ata.pdf</code>, <code>02 - Pauta.pdf</code>.</li>
        </ol>

        <h3>2. Exibir os anexos no post</h3>
        <p>Escolha uma das duas formas:</p>
        <ul class="ul-disc">
            <li><strong>Automática:</strong> marque <em>Exibir anexos no post?</em>, abaixo do gerenciador, e salve o
                post. A lista aparece no fim do conteúdo.</li>
            <li><strong>Shortcode:</strong> deixe a caixa desmarcada e escreva o shortcode no ponto do conteúdo onde a
                lista deve aparecer. O shortcode pronto, com o id do post, aparece abaixo do gerenciador:
                <ul class="ul-square">
                    <li><code>[rag_wp_anexos id="123"]</code>: todos os anexos do post 123.</li>
                    <li><code>[rag_wp_anexos id="123" pasta="Pautas"]</code>: só os anexos da subpasta "Pautas" (use o
                        nome exato da pasta).</li>
                </ul>
            </li>
        </ul>
        <p>Regras:</p>
        <ul class="ul-disc">
            <li>O shortcode só funciona no post do id indicado. Copiado para outro post, não exibe nada.</li>
            <li>Os anexos aparecem só na página do próprio post, nunca em listagens, resumos ou widgets.</li>
            <li>Se o post tiver o shortcode, a exibição automática não acontece, mesmo com a caixa marcada, para a lista
                não aparecer duas vezes.</li>
        </ul>

        <h3>3. Indexar o texto dos PDFs (RAG)</h3>
        <ul class="ul-disc">
            <li><strong>Por post:</strong> no gerenciador, selecione os PDFs e clique em <strong>Indexar PDFs</strong>
                (barra de ferramentas ou botão direito).</li>
            <li><strong>Todos de uma vez:</strong> na página
                <a href="<?php echo esc_url(admin_url('admin.php?page=rag_wp_pdfparser')); ?>">PDFParser</a>, clique em
                <strong>Indexar todos</strong>. Os PDFs já indexados são pulados e os que foram apagados saem do
                índice.</li>
            <li>PDFs escaneados (imagem, sem texto) não podem ser indexados.</li>
        </ul>
    </div>
</div>
