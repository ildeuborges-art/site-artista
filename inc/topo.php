<?php
// Espera $titulo_pagina e $pagina_atual ('inicio', 'sobre', 'obras', 'contato').
$menu = [
    'sobre'   => ['Sobre', 'sobre.php'],
    'obras'   => ['Obras', 'obras.php'],
    'contato' => ['Contato', 'contato.php'],
];
$titulo_completo = $pagina_atual === 'inicio'
    ? $config['nome']
    : $titulo_pagina . ' — ' . $config['nome'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo_completo) ?></title>
<meta name="description" content="<?= e($config['nome'] . ' — ' . $config['linguagens']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>
<header class="topo">
  <a class="marca" href="./"><?= e($config['nome']) ?></a>
  <nav aria-label="Principal" class="menu">
    <?php foreach ($menu as $chave => $item): ?>
      <a href="<?= $item[1] ?>"<?= $pagina_atual === $chave ? ' aria-current="page"' : '' ?>><?= $item[0] ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<main id="conteudo" class="conteudo">
