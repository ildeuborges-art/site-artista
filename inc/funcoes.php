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

/** Lista as imagens de uma série, em ordem natural pelo nome do arquivo. */
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
    return array_values($arquivos);
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
