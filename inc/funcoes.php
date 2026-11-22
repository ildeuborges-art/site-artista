<?php
// Funções do site. Normalmente não é preciso mexer aqui.
// Compatível com PHP 5.3 ou superior.

// No PHP 5.3, date() gera aviso se o fuso horário não estiver configurado no servidor.
if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Sao_Paulo');
}

$config = require __DIR__ . '/../config.php';

define('PASTA_RAIZ', realpath(__DIR__ . '/..'));
define('PASTA_OBRAS', PASTA_RAIZ . '/obras');
/** Formatos de imagem aceitos (função em vez de constante, para funcionar também no PHP 5). */
function extensoes_imagem()
{
    return array('jpg', 'jpeg', 'png', 'webp', 'gif');
}

/** Escapa texto para HTML. */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Monta uma URL segura a partir de um caminho relativo (aceita espaços e acentos). */
function url_caminho($relativo)
{
    return implode('/', array_map('rawurlencode', explode('/', $relativo)));
}

/**
 * Endereço completo do site, terminando com /.
 * Usa 'url_site' do config.php; se estiver vazio, descobre pelo endereço acessado.
 */
function url_base()
{
    global $config;
    if (!empty($config['url_site'])) {
        return rtrim($config['url_site'], '/') . '/';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', $host); // só caracteres válidos de domínio
    $pasta = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    return ($https ? 'https' : 'http') . '://' . $host . rtrim($pasta, '/') . '/';
}

/** Endereço completo a partir de um caminho relativo já codificado (ex.: url_caminho(...)). */
function url_absoluta($relativo)
{
    return url_base() . ltrim($relativo, '/');
}

/** Resumo de um texto para a descrição da página (corta em palavra inteira). */
function resumo($texto, $max = 160)
{
    $texto = trim(preg_replace('/\s+/u', ' ', $texto));
    if (mb_strlen($texto, 'UTF-8') <= $max) {
        return $texto;
    }
    $corte = mb_substr($texto, 0, $max - 1, 'UTF-8');
    $espaco = mb_strrpos($corte, ' ', 0, 'UTF-8');
    if ($espaco !== false && $espaco > $max * 0.6) {
        $corte = mb_substr($corte, 0, $espaco, 'UTF-8');
    }
    return rtrim($corte, ' ,.;:-–—') . '…';
}

/** Dados do artista no formato schema.org (usado pelo Google). */
function dados_artista()
{
    global $config;
    $pessoa = array(
        '@type'    => 'Person',
        '@id'      => url_base() . '#artista',
        'name'     => $config['nome'],
        'url'      => url_base(),
        'jobTitle' => $config['profissao'],
        'description' => $config['descricao'],
    );
    $retrato = imagem_opcional('retrato');
    if ($retrato) {
        $pessoa['image'] = url_absoluta(url_caminho($retrato));
    }
    if (!empty($config['instagram'])) {
        $pessoa['sameAs'] = array($config['instagram']);
    }
    return $pessoa;
}

/** Imprime um bloco de dados estruturados (JSON-LD). */
function json_ld($dados)
{
    $dados = array_merge(array('@context' => 'https://schema.org'), $dados);
    return '<script type="application/ld+json">' . json_encode($dados, JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/** Remove o número de ordenação do início ("01 ", "02-", "3_") e troca _ por espaço. */
function nome_exibicao($texto)
{
    $limpo = preg_replace('/^\d+[\s._-]+/u', '', $texto);
    $limpo = str_replace('_', ' ', $limpo);
    $limpo = trim($limpo);
    return $limpo === '' ? $texto : $limpo;
}

/** Lista as séries: cada subpasta de /obras, em ordem natural (01, 02, 10...). */
function listar_series()
{
    if (!is_dir(PASTA_OBRAS)) {
        return array();
    }
    $pastas = array();
    foreach (scandir(PASTA_OBRAS) as $item) {
        if ($item[0] === '.' || !is_dir(PASTA_OBRAS . '/' . $item)) {
            continue;
        }
        $pastas[] = $item;
    }
    natcasesort($pastas);

    $series = array();
    foreach ($pastas as $pasta) {
        $series[] = array(
            'pasta' => $pasta,
            'nome'  => nome_exibicao($pasta),
        );
    }
    return $series;
}

/**
 * Lista as imagens de uma série. A ordem é a das linhas do legendas.txt;
 * imagens que não estão no legendas.txt vêm depois, em ordem natural pelo nome do arquivo.
 */
function listar_imagens($pasta_serie)
{
    $dir = PASTA_OBRAS . '/' . $pasta_serie;
    if (!is_dir($dir)) {
        return array();
    }
    $arquivos = array();
    foreach (scandir($dir) as $item) {
        if ($item[0] === '.' || !is_file($dir . '/' . $item)) {
            continue;
        }
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, extensoes_imagem(), true)) {
            $arquivos[] = $item;
        }
    }
    natcasesort($arquivos);

    // Primeiro, as imagens na ordem em que aparecem no legendas.txt
    $por_chave = array();
    foreach ($arquivos as $arquivo) {
        $por_chave[mb_strtolower($arquivo, 'UTF-8')] = $arquivo;
    }
    $ordenados = array();
    foreach (array_keys(ler_legendas($pasta_serie)) as $chave) {
        if (isset($por_chave[$chave])) {
            $ordenados[] = $por_chave[$chave];
            unset($por_chave[$chave]);
        }
    }
    // Depois, as que não têm legenda (continuam em ordem natural)
    foreach ($por_chave as $arquivo) {
        $ordenados[] = $arquivo;
    }
    return $ordenados;
}

/** Texto de apresentação da série (arquivo descricao.txt dentro da pasta, opcional). */
function ler_descricao($pasta_serie)
{
    $arquivo = PASTA_OBRAS . '/' . $pasta_serie . '/descricao.txt';
    return is_file($arquivo) ? trim(file_get_contents($arquivo)) : '';
}

/**
 * Legendas da série (arquivo legendas.txt dentro da pasta, opcional).
 * Uma linha por obra:  arquivo.jpg | Título | Ano | Técnica | Dimensões | Coleção
 * Linhas começando com # são ignoradas.
 */
function ler_legendas($pasta_serie)
{
    $arquivo = PASTA_OBRAS . '/' . $pasta_serie . '/legendas.txt';
    $legendas = array();
    if (!is_file($arquivo)) {
        return $legendas;
    }
    foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#') {
            continue;
        }
        $partes = array_map('trim', explode('|', $linha));
        $chave = mb_strtolower($partes[0], 'UTF-8');
        $legendas[$chave] = array(
            'titulo'   => isset($partes[1]) ? $partes[1] : '',
            'ano'      => isset($partes[2]) ? $partes[2] : '',
            'tecnica'  => isset($partes[3]) ? $partes[3] : '',
            'medidas'  => isset($partes[4]) ? $partes[4] : '',
            'colecao'  => isset($partes[5]) ? $partes[5] : '',
        );
    }
    return $legendas;
}

/** Junta arquivo + legenda. Sem legenda, o título vem do nome do arquivo. */
function dados_obra($pasta_serie, $arquivo, $legendas)
{
    $chave = mb_strtolower($arquivo, 'UTF-8');
    $legenda = isset($legendas[$chave]) ? $legendas[$chave] : array();

    $titulo = !empty($legenda['titulo'])
        ? $legenda['titulo']
        : nome_exibicao(pathinfo($arquivo, PATHINFO_FILENAME));
    if (preg_match('/^[\d\s._-]+$/', $titulo)) {
        $titulo = 'Sem título'; // arquivo só com número (01.jpg) e sem legenda
    }

    $caminho = PASTA_OBRAS . '/' . $pasta_serie . '/' . $arquivo;
    $medidas_px = @getimagesize($caminho);
    $cm = medidas_cm($legenda, $medidas_px);

    return array(
        'largura_cm' => $cm ? $cm[0] : null,
        'altura_cm'  => $cm ? $cm[1] : null,
        'url'      => url_caminho('obras/' . $pasta_serie . '/' . $arquivo),
        'titulo'   => $titulo,
        'ano'      => isset($legenda['ano']) ? $legenda['ano'] : '',
        'tecnica'  => isset($legenda['tecnica']) ? $legenda['tecnica'] : '',
        'medidas'  => isset($legenda['medidas']) ? $legenda['medidas'] : '',
        'colecao'  => isset($legenda['colecao']) ? $legenda['colecao'] : '',
        'largura'  => $medidas_px ? $medidas_px[0] : null,
        'altura'   => $medidas_px ? $medidas_px[1] : null,
    );
}

/**
 * Dimensões reais da obra em cm, lidas da legenda ("60 x 90 cm", "60x90cm", "60 × 90 cm").
 * Procura em todos os campos, então funciona mesmo se as colunas estiverem fora de ordem.
 * A orientação vem da foto: se a imagem é horizontal, o número maior é a largura.
 * Devolve [largura, altura] ou null quando não encontra.
 */
function medidas_cm($legenda, $medidas_px)
{
    foreach ($legenda as $campo) {
        if (!preg_match('/(\d+(?:[.,]\d+)?)\s*[x×X]\s*(\d+(?:[.,]\d+)?)\s*cm/u', $campo, $m)) {
            continue;
        }
        $a = (float) str_replace(',', '.', $m[1]);
        $b = (float) str_replace(',', '.', $m[2]);
        if ($a <= 0 || $b <= 0) {
            continue;
        }
        $maior = max($a, $b);
        $menor = min($a, $b);
        if ($medidas_px && $medidas_px[0] < $medidas_px[1]) {
            return array($menor, $maior); // foto vertical
        }
        if ($medidas_px && $medidas_px[0] == $medidas_px[1]) {
            return array($a, $b);
        }
        return array($maior, $menor); // foto horizontal (ou sem dados da foto)
    }
    return null;
}

/** Procura um arquivo de imagem pelo nome-base em /imagens (ex.: "retrato" → retrato.jpg). */
function imagem_opcional($nome_base)
{
    foreach (extensoes_imagem() as $ext) {
        foreach (array($ext, strtoupper($ext)) as $variante) {
            $rel = 'imagens/' . $nome_base . '.' . $variante;
            if (is_file(PASTA_RAIZ . '/' . $rel)) {
                return $rel;
            }
        }
    }
    return null;
}
