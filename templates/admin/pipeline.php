<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
/** @var array $filters */
/** @var array $result */
/** @var array $counts */
$qs = static function (array $extra = []) use ($filters): string {
    $query = array_merge($filters, $extra);
    unset($query['view']);
    if (($extra['view'] ?? $filters['view']) === 'board') {
        $query['view'] = 'board';
    }
    return http_build_query(array_filter($query, static fn ($v) => $v !== '' && $v !== null));
};
?>
<div class="admin-head">
  <h1>Pipeline</h1>
  <p>
    <a href="/admin/deals?<?= e($qs(['view' => 'table'])) ?>">Table</a>
    · <a href="/admin/deals?<?= e($qs(['view' => 'board'])) ?>">Board</a>
    · <a href="/admin/export/leads.csv">Export leads</a>
    · <a href="/admin/export/deals.csv">Export deals</a>
  </p>
</div>
<?php if (!empty($saved)): ?><p class="notice" role="status"><?= e($saved) ?></p><?php endif; ?>
<ul class="count-row">
  <?php foreach (deal_stages() as $key => $label): ?>
    <li><a href="/admin/deals?stage=<?= e($key) ?>"><?= e($label) ?> <strong><?= (int) ($counts[$key] ?? 0) ?></strong></a></li>
  <?php endforeach; ?>
</ul>
<p class="fine">Form steps recorded: address <?= (int) ($events['address'] ?? 0) ?>, property <?= (int) ($events['property'] ?? 0) ?>, situation <?= (int) ($events['situation'] ?? 0) ?>, submitted <?= (int) ($events['submitted'] ?? 0) ?>.</p>
<form class="filters" method="get" action="/admin/deals">
  <input type="hidden" name="view" value="<?= e($filters['view']) ?>">
  <label>Stage <select name="stage"><?= options_html(deal_stages(), (string) $filters['stage'], true, 'All') ?></select></label>
  <label>Town <select name="city"><?= options_html(service_towns(), (string) $filters['city'], true, 'All') ?></select></label>
  <label>From <input type="date" name="from" value="<?= e((string) $filters['from']) ?>"></label>
  <label>To <input type="date" name="to" value="<?= e((string) $filters['to']) ?>"></label>
  <label>Search <input name="q" value="<?= e((string) $filters['q']) ?>" placeholder="Address, name, email, phone"></label>
  <button class="button" type="submit">Filter</button>
</form>
<?php if (!$result['rows']): ?>
  <p>No leads yet. When a homeowner submits the offer form, the deal shows up here.</p>
<?php elseif ($filters['view'] === 'board'): ?>
  <div class="board">
    <?php foreach (deal_stages() as $key => $label): ?>
      <section>
        <h2><?= e($label) ?></h2>
        <?php foreach ($result['rows'] as $row): ?>
          <?php if ($row['stage'] !== $key) { continue; } ?>
          <a class="board-card" href="/admin/deals/<?= (int) $row['id'] ?>">
            <strong><?= e((string) $row['address']) ?></strong>
            <span><?= e((string) $row['city']) ?> · <?= e((string) $row['full_name']) ?></span>
          </a>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr><th>Received</th><th>Consent</th><th>Seller</th><th>Property</th><th>Stage</th></tr>
      </thead>
      <tbody>
        <?php foreach ($result['rows'] as $row): ?>
          <tr>
            <td><?= e(format_when((string) $row['lead_created'])) ?></td>
            <td><?php if ((int) $row['tcpa_consent'] === 1): ?><span class="badge badge-yes">Call and text OK</span><?php else: ?><span class="badge badge-no">Email only — do not call or text</span><?php endif; ?></td>
            <td><?= e((string) $row['full_name']) ?><br><?= e((string) $row['phone']) ?><br><?= e((string) $row['email']) ?></td>
            <td><a href="/admin/deals/<?= (int) $row['id'] ?>"><?= e((string) $row['address']) ?></a><br><?= e((string) $row['city']) ?> · <?= e((string) $row['condition_label']) ?></td>
            <td><?= e(deal_stages()[$row['stage']] ?? (string) $row['stage']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ((int) $result['pages'] > 1): ?>
    <p class="pager">
      <?php if ((int) $result['page'] > 1): ?><a href="/admin/deals?<?= e($qs(['page' => (int) $result['page'] - 1])) ?>">Previous</a><?php endif; ?>
      Page <?= (int) $result['page'] ?> of <?= (int) $result['pages'] ?>
      <?php if ((int) $result['page'] < (int) $result['pages']): ?><a href="/admin/deals?<?= e($qs(['page' => (int) $result['page'] + 1])) ?>">Next</a><?php endif; ?>
    </p>
  <?php endif; ?>
<?php endif; ?>
