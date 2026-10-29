<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $loc */
?>
<section class="section">
  <div class="container split-page">
    <article class="prose">
      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="/">Home</a> <span aria-hidden="true">/</span> <span><?= e($loc['name']) ?></span>
      </nav>
      <h1>Sell your <?= e($loc['name']) ?> house fast for cash, as-is</h1>
      <?php foreach ($loc['paragraphs'] as $paragraph): ?>
        <p><?= e($paragraph) ?></p>
      <?php endforeach; ?>
      <h2>Situations we can price</h2>
      <ul>
        <?php foreach ($loc['points'] as $point): ?>
          <li><?= e($point) ?></li>
        <?php endforeach; ?>
      </ul>
      <h2>Also in Lowndes County</h2>
      <ul>
        <?php foreach (locations() as $slug => $area): ?>
          <?php if ($slug === $loc['slug']) { continue; } ?>
          <li><a href="/<?= e((string) $slug) ?>"><?= e($area['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </article>
    <div class="form-card">
      <?php partial('offer_form', ['step' => 1, 'draft' => $draft, 'errors' => $errors, 'source' => $source]); ?>
    </div>
  </div>
</section>
