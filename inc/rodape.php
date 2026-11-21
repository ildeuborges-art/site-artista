</main>
<footer class="rodape">
  <span>© <?= date('Y') ?> <?= e($config['nome']) ?>. Todos os direitos reservados.</span>
  <?php if (!empty($config['instagram'])): ?>
    <a href="<?= e($config['instagram']) ?>" rel="noopener" target="_blank">Instagram</a>
  <?php endif; ?>
</footer>
</body>
</html>
