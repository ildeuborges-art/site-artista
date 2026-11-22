<?php
// Espera $titulo_pagina e $pagina_atual ('inicio', 'sobre', 'obras', 'contato').
// Opcionais (SEO): $descricao_pagina, $caminho_canonico, $imagem_pagina, $dados_estruturados.
$menu = array(
    'sobre'   => array('Sobre', 'sobre.php'),
    'obras'   => array('Obras', 'obras.php'),
    'contato' => array('Contato', 'contato.php'),
);
$titulo_completo = $pagina_atual === 'inicio'
    ? $config['nome'] . ' — ' . $config['profissao']
    : $titulo_pagina . ' — ' . $config['nome'];

$seo_descricao = !empty($descricao_pagina) ? resumo($descricao_pagina) : resumo($config['descricao']);
$seo_url = url_absoluta(isset($caminho_canonico) ? $caminho_canonico : '');
if (empty($imagem_pagina)) {
    $retrato_seo = imagem_opcional('retrato');
    $imagem_pagina = $retrato_seo ? url_caminho($retrato_seo) : '';
}
$seo_imagem = $imagem_pagina ? url_absoluta($imagem_pagina) : '';
if (!isset($dados_estruturados)) {
    $dados_estruturados = array();
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($titulo_completo) ?></title>
<meta name="description" content="<?php echo e($seo_descricao) ?>">
<link rel="canonical" href="<?php echo e($seo_url) ?>">
<meta name="author" content="<?php echo e($config['nome']) ?>">

<!-- Prévia ao compartilhar o link (WhatsApp, Facebook, LinkedIn, X...) -->
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="<?php echo e($config['nome']) ?>">
<meta property="og:title" content="<?php echo e($titulo_completo) ?>">
<meta property="og:description" content="<?php echo e($seo_descricao) ?>">
<meta property="og:url" content="<?php echo e($seo_url) ?>">
<?php if ($seo_imagem): ?>
<meta property="og:image" content="<?php echo e($seo_imagem) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php else: ?>
<meta name="twitter:card" content="summary">
<?php endif; ?>

<?php foreach ($dados_estruturados as $bloco): ?>
<?php echo json_ld($bloco) ?>

<?php endforeach; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>
<header class="topo">
  <a class="marca" href="./"><?php echo e($config['nome']) ?></a>
  <nav aria-label="Principal" class="menu">
    <?php foreach ($menu as $chave => $item): ?>
      <a href="<?php echo $item[1] ?>"<?php echo $pagina_atual === $chave ? ' aria-current="page"' : '' ?>><?php echo $item[0] ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main id="conteudo" class="conteudo">
