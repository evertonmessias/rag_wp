<?php
// Arquivo: wp-content/plugins/novoicode/includes/src/user.php
// ************************************************************************************** Lista de Usuários

// Função auxiliar para obter tipos dinamicamente
function get_tipos_configurados()
{
    return array_map('trim', explode(',', get_option('portal_input_7', '')));
}


// Tempo de cache das consultas à Intranet
define('ICODE_CACHE_INTRANET', HOUR_IN_SECONDS);

// Marca/consulta se alguma consulta à Intranet falhou na requisição atual.
// Usado para NÃO sobrescrever permissões com listas vazias quando a Intranet está fora do ar.
function icode_intranet_erro($marcar = null)
{
    static $erro = false;
    if ($marcar !== null) {
        $erro = $marcar;
    }
    return $erro;
}

// Limpa o cache das consultas à Intranet
function icode_limpar_cache_intranet()
{
    delete_transient('icode_docentes');
    delete_transient('icode_administracao');
    foreach (get_tipos_configurados() as $slug) {
        if (!empty($slug)) {
            delete_transient('icode_colegiado_' . $slug);
        }
    }
}


//************* Docentes
function lista_docentes_intranet()
{
    $cache = get_transient('icode_docentes');
    if ($cache !== false) {
        return $cache;
    }

    $url = INTRANET . "/docentes/siteic";
    $response = wp_remote_get($url, ['sslverify' => false]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('Erro ao acessar API docentes: ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)));
        icode_intranet_erro(true);
        return '[]';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data']['ativos']) || !is_array($data['data']['ativos'])) {
        error_log('Erro ao decodificar JSON docentes: ' . json_last_error_msg());
        icode_intranet_erro(true);
        return '[]';
    }

    $docentes = [];
    if (isset($data['data']['ativos']) && is_array($data['data']['ativos'])) {
        foreach ($data['data']['ativos'] as $docente_api) {
            $departamento = $docente_api['Departamento'] ?? '';
            preg_match('/ - (\bD[STC][SICT]\b)$/', $departamento, $matches);

            $docentes[] = [
                'nome' => trim($docente_api['Nome'] ?? ''),
                'photo_url' => $docente_api['Foto Pessoal'] ?? null,
                'cargo' => $docente_api['Cargo'] ?? '',
                'email' => $docente_api['Email'] ?? '',
                'departamento' => $matches[1] ?? '',
                'matricula' => $docente_api['Matrícula'] ?? '',
                'telefone' => $docente_api['Telefone'] ?? '',
                'sala' => $docente_api['Sala'] ?? '',
                'lattes' => $docente_api['Currículo Lattes'] ?? ''
            ];
        }
    }

    $json = json_encode($docentes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    set_transient('icode_docentes', $json, ICODE_CACHE_INTRANET);
    return $json;
}
add_action("lista_docentes_intranet", "lista_docentes_intranet");



//************* Funcionarios IC
function lista_funcionarios_intranet()
{
    $url = INTRANET . "/funcionarios/siteic";
    $response = wp_remote_get($url, ['sslverify' => false]);

    if (is_wp_error($response)) {
        error_log('Erro ao acessar API funcionários: ' . $response->get_error_message());
        return '[]';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Erro ao decodificar JSON funcionários: ' . json_last_error_msg());
        return '[]';
    }

    $funcionarios = [];
    if (isset($data['data']['ativos']) && is_array($data['data']['ativos'])) {
        foreach ($data['data']['ativos'] as $funcionario_api) {
            $funcionarios[] = [
                'nome' => trim($funcionario_api['Nome'] ?? ''),
                'photo_url' => $funcionario_api['Foto Pessoal'] ?? null,
                'cargo' => $funcionario_api['Cargo'] ?? '',
                'email' => $funcionario_api['Email'] ?? '',
                'matricula' => $funcionario_api['Matrícula'] ?? '',
                'secao' => $funcionario_api['Seção'] ?? '',
                'telefone' => $funcionario_api['Telefone'] ?? '',
                'sala' => $funcionario_api['Sala'] ?? ''
            ];
        }
    }

    return json_encode($funcionarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
add_action("lista_funcionarios_intranet", "lista_funcionarios_intranet");



// ********************* Lista Usuários por Departamento na Intranet
function lista_usuarios_colegiado($depto)
{
    $cache = get_transient('icode_colegiado_' . $depto);
    if ($cache !== false) {
        return $cache;
    }

    $url_intranet = INTRANET . "/composicao_" . $depto . "/json?modo=short";
    $response = wp_remote_get($url_intranet);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('Erro ao acessar API departamento ' . $depto . ': ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)));
        icode_intranet_erro(true);
        return [];
    }

    $dados = json_decode(wp_remote_retrieve_body($response));
    if (!isset($dados->data)) {
        error_log('Erro ao decodificar JSON departamento ' . $depto);
        icode_intranet_erro(true);
        return [];
    }

    $emails = [];

    $campos = ['chefe', 'vice_chefe', 'sec_acad'];
    foreach ($campos as $campo) {
        if (isset($dados->data->$campo->email_inst)) {
            $emails[] = $dados->data->$campo->email_inst;
        }
        if (isset($dados->data->$campo->email_sise)) {
            $emails[] = $dados->data->$campo->email_sise;
        }
    }

    $tipos_membros = ['docentes', 'titulares', 'suplentes'];
    foreach ($tipos_membros as $tipo) {
        if (isset($dados->data->$tipo) && is_array($dados->data->$tipo)) {
            foreach ($dados->data->$tipo as $membro) {
                if (isset($membro->email_inst))
                    $emails[] = $membro->email_inst;
                if (isset($membro->email_sise))
                    $emails[] = $membro->email_sise;
            }
        }
    }

    $emails = array_values(array_unique(array_filter($emails)));
    set_transient('icode_colegiado_' . $depto, $emails, ICODE_CACHE_INTRANET);
    return $emails;
}
add_action("lista_usuarios_colegiado", "lista_usuarios_colegiado");



// ********************* Administração do IC na Intranet (diretoria, chefias, decano...)
function lista_administracao_intranet()
{
    $cache = get_transient('icode_administracao');
    if ($cache !== false) {
        return $cache;
    }

    $url = INTRANET . "/administracao";
    $response = wp_remote_get($url);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        error_log('Erro ao acessar API administração: ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)));
        icode_intranet_erro(true);
        return [];
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['data']['designacoes']) || !is_array($data['data']['designacoes'])) {
        error_log('Erro ao decodificar JSON administração: ' . json_last_error_msg());
        icode_intranet_erro(true);
        return [];
    }

    $designacoes = [];
    foreach ($data['data']['designacoes'] as $designacao) {
        $designacoes[] = [
            'designacao' => trim($designacao['designacao'] ?? ''),
            'emails' => array_values(array_filter(array_map('strtolower', [
                $designacao['email_institucional'] ?? '',
                $designacao['email_sise'] ?? ''
            ]))),
            'data_inicio' => $designacao['data_inicio'] ?? null,
            'data_fim' => $designacao['data_fim'] ?? null
        ];
    }

    set_transient('icode_administracao', $designacoes, ICODE_CACHE_INTRANET);
    return $designacoes;
}
add_action("lista_administracao_intranet", "lista_administracao_intranet");



// ******************* Permissões pela Administração (somente leitura, sem virar editor)
// Designações com acesso a TODOS os tipos (membro, post privado e arquivo privado)
define('ICODE_DESIGNACOES_ACESSO_TOTAL', [
    'Diretor',
    'Diretor associado',
    'CTU',
    'Coord. Serviço Dir. Apoio Adm. Financ. e Operac',
    'Decano'
]);

// Retorna ['membro' => [...], 'post_privado' => [...], 'arquivo_privado' => [...]]
// - Acesso total: todos os tipos configurados
// - "Chefe XXX" / "Vice-Chefe XXX": tipo xxx (Congrega e CI já vêm de portal_input_10)
function permissoes_administracao($email)
{
    $email = strtolower($email);
    $tipos = get_tipos_configurados();
    $hoje = current_time('Y-m-d');
    $permissoes = ['membro' => [], 'post_privado' => [], 'arquivo_privado' => []];

    foreach (lista_administracao_intranet() as $designacao) {
        if (!in_array($email, $designacao['emails'], true)) {
            continue;
        }
        // Ignora designação fora da vigência
        if (($designacao['data_inicio'] && $designacao['data_inicio'] > $hoje) || ($designacao['data_fim'] && $designacao['data_fim'] < $hoje)) {
            continue;
        }

        if (in_array($designacao['designacao'], ICODE_DESIGNACOES_ACESSO_TOTAL, true)) {
            $grupos = $tipos;
        } elseif (preg_match('/^(Vice-)?Chefe (\w+)$/u', $designacao['designacao'], $matches) && in_array(strtolower($matches[2]), $tipos, true)) {
            $grupos = [strtolower($matches[2])];
        } else {
            continue;
        }

        foreach ($permissoes as $tipo => $lista) {
            $permissoes[$tipo] = array_merge($lista, $grupos);
        }
    }

    return array_map(function ($lista) {
        return array_values(array_unique(array_filter($lista)));
    }, $permissoes);
}



// ******************* Retorna Membro Intranet
function retorna_membro($email)
{
    $slugs_array = get_tipos_configurados();

    // 1. PRIMEIRO: Garante que tenha acesso aos tipos obrigatórios
    $grupos = array_map('trim', explode(',', get_option('portal_input_10', '')));
  
    // 2. SEGUNDO: Busca nos colegiados
    foreach ($slugs_array as $slug) {
        if (!empty($slug) && in_array($email, lista_usuarios_colegiado($slug), true)) {
            $grupos[] = $slug;
        }
    }

    // 3. TERCEIRO: Busca no site IC
    $docentes = json_decode(lista_docentes_intranet(), true);
    foreach ($docentes as $professor) {
        if (isset($professor['email']) && $professor['email'] === $email && !empty($professor['departamento'])) {
            $depto_slug = strtolower($professor['departamento']);
            // Adiciona apenas se for um tipo válido configurado
            if (in_array($depto_slug, $slugs_array)) {
                $grupos[] = $depto_slug;
            }
            break;
        }
    }

    // Elimina duplicidades e retorna
    return array_unique($grupos);
}
add_action('retorna_membro', 'retorna_membro');