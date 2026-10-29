<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <p class="eyebrow">Valdosta and Lowndes County, Georgia</p>
      <h1>Sell your house fast for cash, as-is</h1>
      <p class="lede">Tell us about the house. We make a cash offer. You do not pay us a fee, you do not make repairs for us, and you pick the closing date.</p>
      <ul class="facts">
        <li>As-is. No repairs for us.</li>
        <li>No fee and no listing commission.</li>
        <li>You can say no to the offer.</li>
      </ul>
    </div>
    <div class="form-card">
      <?php partial('offer_form', ['step' => 1, 'draft' => $draft, 'errors' => $errors, 'source' => $source]); ?>
    </div>
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="container">
    <h2>How it works</h2>
    <p class="section-lead">Three steps. A written offer comes after we have enough to price the house. Nothing here obligates you to sell.</p>
    <ol class="steps">
      <li>
        <span class="step-num">1</span>
        <h3>Send the address</h3>
        <p>A few questions about the house, the condition, and your timing.</p>
      </li>
      <li>
        <span class="step-num">2</span>
        <h3>Review a cash offer</h3>
        <p>We look at the property, in person or from photos, and send a written number. You can decline.</p>
      </li>
      <li>
        <span class="step-num">3</span>
        <h3>Pick the closing date</h3>
        <p>If you accept, a local closing attorney handles the paperwork. You do not repair the house for us.</p>
      </li>
    </ol>
    <p class="section-link"><a href="/how-it-works">Read the full steps</a></p>
  </div>
</section>

<section class="section section-alt" id="areas">
  <div class="container">
    <h2>Where we buy</h2>
    <p class="section-lead">Valdosta, Hahira, Lake Park, Remerton, and the rest of Lowndes County.</p>
    <div class="cards">
      <?php foreach (locations() as $slug => $area): ?>
        <a class="card" href="/<?= e((string) $slug) ?>">
          <h3><?= e($area['name']) ?></h3>
          <p><?= e($area['card']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="about">
  <div class="container prose">
    <h2>About this business</h2>
    <p>Prime Property Buys is operated by <?= e((string) ppb('business.owner')) ?> in Valdosta and Lowndes County, Georgia. It is part of <?= e((string) ppb('business.parent')) ?>.</p>
    <p>Legal entity: <span class="<?= is_placeholder(legal_entity_label()) ? 'placeholder' : '' ?>"><?= e(legal_entity_label()) ?></span></p>
    <p><?= e(brokerage_disclosure()) ?> Cash offers are often below a retail asking price. We would rather say that than promise a number we cannot stand behind.</p>
    <p><a href="/about">More about us</a></p>
  </div>
</section>
