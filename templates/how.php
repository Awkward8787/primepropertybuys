<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
$url = booking_url();
?>
<section class="section">
  <div class="container prose">
    <h1>How a cash sale works</h1>
    <p>The path is the same in Valdosta, Hahira, Lake Park, Remerton, and the rest of Lowndes County.</p>
    <h2>1. Request</h2>
    <p>Send the address and a few facts. The form takes a few minutes. It does not commit you to sell.</p>
    <h2>2. Walkthrough</h2>
    <p>We look at the house, or we start from photos if that is all you have. <?php if ($url !== ''): ?><a href="<?= e($url) ?>">Schedule a time</a>.<?php else: ?>Scheduling: <span class="placeholder"><?= e(booking_label()) ?></span><?php endif; ?></p>
    <h2>3. Written offer</h2>
    <p>You get a cash number in writing. It is often below what a listed house might ask, because of condition and the time we take on. You can say no. We do not publish a formula or a guaranteed percentage of value.</p>
    <h2>4. Closing</h2>
    <p>If you accept, a Georgia closing attorney handles the paperwork. You choose a closing date that works with title. You do not make repairs for us, and we do not charge you a fee to make the offer.</p>
    <h2>Compared with listing</h2>
    <p>A cash sale is usually simpler to schedule, and it does not depend on a loan. The price is often lower than a successful listing. A listing can sell for more, and it can also fall through after an inspection or financing.</p>
    <p><a class="button" href="/get-offer">Get my cash offer</a></p>
  </div>
</section>
