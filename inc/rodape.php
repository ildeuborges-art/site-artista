</main>
<footer class="rodape">
  <span>© <?php echo date('Y') ?> <?php echo e($config['nome']) ?>. Todos os direitos reservados.</span>
  <?php if (!empty($config['instagram'])): ?>
    <a href="<?php echo e($config['instagram']) ?>" rel="noopener" target="_blank">Instagram</a>
  <?php endif; ?>
</footer>
</body>
</html>
