<?php
declare(strict_types=1);
if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}
?>
<h1>Tasks due</h1>
<?php if (!$tasks): ?>
  <p>Nothing due.</p>
<?php else: ?>
  <ul class="task-list">
    <?php foreach ($tasks as $task): ?>
      <li>
        <form method="post" action="/admin/tasks">
          <?= csrf_field() ?>
          <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
          <button type="submit">Done</button>
        </form>
        <?= e((string) $task['title']) ?>
        <?php if ($task['due_at']): ?> · <?= e((string) $task['due_at']) ?><?php endif; ?>
        <?php if (!empty($task['address'])): ?> · <a href="/admin/deals/<?= (int) $task['deal_id'] ?>"><?= e((string) $task['address']) ?></a><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
