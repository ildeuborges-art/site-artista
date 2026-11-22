<?php
require __DIR__ . '/inc/funcoes.php';

$titulo_pagina = 'Contato';
$pagina_atual = 'contato';

// SEO
$descricao_pagina = 'Contato com ' . $config['nome'] . ': aquisição de obras, projetos, convites e parcerias.';
$caminho_canonico = 'contato.php';

$status = null;   // null | 'ok' | 'erro'
$campos = array('nome' => '', 'email' => '', 'mensagem' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $k => $_) {
        $campos[$k] = trim(isset($_POST[$k]) ? (string) $_POST[$k] : '');
    }
    $robo = !empty($_POST['site']); // campo invisível: se vier preenchido, é spam

    $valido = $campos['nome'] !== ''
        && filter_var($campos['email'], FILTER_VALIDATE_EMAIL)
        && $campos['mensagem'] !== ''
        && mb_strlen($campos['mensagem'], 'UTF-8') <= 5000;

    if ($robo) {
        $status = 'ok';
    } elseif ($valido) {
        $nome_limpo = str_replace(array("\r", "\n"), ' ', $campos['nome']);
        $assunto = '=?UTF-8?B?' . base64_encode('Mensagem pelo site — ' . $nome_limpo) . '?=';
        $corpo = "Nome: {$nome_limpo}\nE-mail: {$campos['email']}\n\n{$campos['mensagem']}\n";
        $cabecalhos = array(
            'From: ' . $config['email_contato'],
            'Reply-To: ' . $campos['email'],
            'Content-Type: text/plain; charset=UTF-8',
        );
        $status = @mail($config['email_contato'], $assunto, $corpo, implode("\r\n", $cabecalhos)) ? 'ok' : 'erro';
        if ($status === 'ok') {
            $campos = array('nome' => '', 'email' => '', 'mensagem' => '');
        }
    } else {
        $status = 'erro';
    }
}

require __DIR__ . '/inc/topo.php';
?>
<h1 class="titulo">Contato</h1>

<div class="contatos">
  <?php if (!empty($config['galerias'])): ?>
    <div>
      <h2 class="rotulo">Aquisições</h2>
      <?php foreach ($config['galerias'] as $g): ?>
        <span><?php echo e($g['nome']) ?>, <?php echo e($g['cidade']) ?></span>
        <?php if (!empty($g['email'])): ?><a href="mailto:<?php echo e($g['email']) ?>"><?php echo e($g['email']) ?></a><?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div>
    <h2 class="rotulo">Ateliê</h2>
    <span>Projetos, convites e parcerias</span>
    <a href="mailto:<?php echo e($config['email_contato']) ?>"><?php echo e($config['email_contato']) ?></a>
  </div>
  <?php if (!empty($config['email_imprensa'])): ?>
    <div>
      <h2 class="rotulo">Imprensa</h2>
      <a href="mailto:<?php echo e($config['email_imprensa']) ?>"><?php echo e($config['email_imprensa']) ?></a>
    </div>
  <?php endif; ?>
</div>

<section class="formulario">
  <h2 class="rotulo">Envie uma mensagem</h2>

  <?php if ($status === 'ok'): ?>
    <p class="retorno">Obrigado. Sua mensagem foi enviada.</p>
  <?php else: ?>
    <?php if ($status === 'erro'): ?>
      <p class="retorno erro" role="alert">Não foi possível enviar. Confira os campos ou escreva para <?php echo e($config['email_contato']) ?>.</p>
    <?php endif; ?>
    <form method="post" action="contato.php">
      <div class="linha">
        <label>Nome <input type="text" name="nome" required value="<?php echo e($campos['nome']) ?>"></label>
        <label>E-mail <input type="email" name="email" required value="<?php echo e($campos['email']) ?>"></label>
      </div>
      <label>Mensagem <textarea name="mensagem" rows="5" required maxlength="5000"><?php echo e($campos['mensagem']) ?></textarea></label>
      <label class="oculto" aria-hidden="true">Não preencha <input type="text" name="site" tabindex="-1" autocomplete="off"></label>
      <button type="submit">Enviar</button>
    </form>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/inc/rodape.php'; ?>
