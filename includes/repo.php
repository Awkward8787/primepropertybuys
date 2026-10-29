<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function audit(string $action, string $entity = ''): void
{
    $uid = (int) ($_SESSION['admin_id'] ?? 0);
    db()->prepare('INSERT INTO audit_log (user_id, action, entity, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$uid, clean_line($action, 80), clean_line($entity, 255), now_sql()]);
}

function log_form_event(string $step): void
{
    $hash = substr(hash('sha256', session_id()), 0, 32);
    db()->prepare('INSERT INTO form_events (step, session_hash, created_at) VALUES (?, ?, ?)')
        ->execute([clean_line($step, 40), $hash, now_sql()]);
}

function form_event_counts(): array
{
    $rows = db()->query('SELECT step, COUNT(*) AS n FROM form_events GROUP BY step')->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $out[(string) $row['step']] = (int) $row['n'];
    }
    return $out;
}

function insert_lead(array $lead): int
{
    $now = now_sql();
    $sql = 'INSERT INTO leads (
        full_name, phone, email, best_time, tcpa_consent, tcpa_text, tcpa_at, tcpa_ip,
        address, city, zip, county, property_type, beds, baths, condition_label, occupancy,
        timeline, reason, asking_price, utm, source_path, ip_address, user_agent, created_at, updated_at
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
    db()->prepare($sql)->execute([
        $lead['full_name'], $lead['phone'], $lead['email'], $lead['best_time'],
        $lead['tcpa_consent'], $lead['tcpa_text'], $lead['tcpa_at'], $lead['tcpa_ip'],
        $lead['address'], $lead['city'], $lead['zip'], 'Lowndes',
        $lead['property_type'], $lead['beds'], $lead['baths'], $lead['condition_label'], $lead['occupancy'],
        $lead['timeline'], $lead['reason'], $lead['asking_price'], $lead['utm'], $lead['source_path'],
        $lead['ip_address'], $lead['user_agent'], $now, $now,
    ]);
    return (int) db()->lastInsertId();
}

function open_deal(int $leadId): int
{
    $now = now_sql();
    db()->prepare('INSERT INTO deals (
        lead_id, stage, stage_changed_at, dead_reason, access_notes, last_seller_notice, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$leadId, 'lead', $now, '', '', '', $now, $now]);
    $id = (int) db()->lastInsertId();
    db()->prepare('INSERT INTO deal_stage_history (deal_id, from_stage, to_stage, changed_at) VALUES (?, ?, ?, ?)')
        ->execute([$id, '', 'lead', $now]);
    return $id;
}

function add_media(array $file): int
{
    db()->prepare('INSERT INTO media (lead_id, buyer_id, kind, stored_name, original_name, mime, bytes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([
        $file['lead_id'], $file['buyer_id'], $file['kind'], $file['stored_name'],
        $file['original_name'], $file['mime'], $file['bytes'], now_sql(),
    ]);
    return (int) db()->lastInsertId();
}

function list_lead_media(int $leadId): array
{
    $stmt = db()->prepare('SELECT * FROM media WHERE lead_id = ? AND kind = ? ORDER BY id');
    $stmt->execute([$leadId, 'photo']);
    return $stmt->fetchAll();
}

function get_media(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_deal(int $id): ?array
{
    $stmt = db()->prepare('SELECT d.*, l.full_name, l.phone, l.email, l.best_time, l.tcpa_consent, l.address, l.city,
        l.zip, l.property_type, l.beds, l.baths, l.condition_label, l.occupancy, l.timeline, l.reason, l.asking_price,
        l.source_path, l.created_at AS lead_created
        FROM deals d JOIN leads l ON l.id = d.lead_id WHERE d.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function list_deals(array $filters, int $page = 1, int $perPage = 50): array
{
    $where = [];
    $params = [];
    $stage = (string) ($filters['stage'] ?? '');
    if ($stage !== '' && isset(deal_stages()[$stage])) {
        $where[] = 'd.stage = ?';
        $params[] = $stage;
    }
    $city = (string) ($filters['city'] ?? '');
    if ($city !== '' && isset(service_towns()[$city])) {
        $where[] = 'l.city = ?';
        $params[] = $city;
    }
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $like = like_pattern(mb_substr($q, 0, 100));
        $where[] = '(l.address LIKE ? OR l.full_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $from = (string) ($filters['from'] ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = 'l.created_at >= ?';
        $params[] = $from . ' 00:00:00';
    }
    $to = (string) ($filters['to'] ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = 'l.created_at <= ?';
        $params[] = $to . ' 23:59:59';
    }
    $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $count = db()->prepare("SELECT COUNT(*) FROM deals d JOIN leads l ON l.id = d.lead_id {$sqlWhere}");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT d.*, l.full_name, l.phone, l.email, l.tcpa_consent, l.address, l.city, l.timeline, l.condition_label, l.created_at AS lead_created
        FROM deals d JOIN leads l ON l.id = d.lead_id {$sqlWhere}
        ORDER BY d.updated_at DESC, d.id DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function stage_counts(): array
{
    $rows = db()->query('SELECT stage, COUNT(*) AS n FROM deals GROUP BY stage')->fetchAll();
    $out = [];
    foreach (deal_stages() as $key => $label) {
        $out[$key] = 0;
    }
    foreach ($rows as $row) {
        $out[(string) $row['stage']] = (int) $row['n'];
    }
    return $out;
}

function save_deal_fields(int $id, array $fields): void
{
    db()->prepare('UPDATE deals SET
        arv = ?, repair_est = ?, mao = ?, offer_amt = ?, contract_price = ?, assignment_fee = ?,
        contract_date = ?, inspection_deadline = ?, closing_date = ?, title_company = ?, access_notes = ?, updated_at = ?
        WHERE id = ?')->execute([
        $fields['arv'], $fields['repair_est'], $fields['mao'], $fields['offer_amt'], $fields['contract_price'],
        $fields['assignment_fee'], $fields['contract_date'], $fields['inspection_deadline'], $fields['closing_date'],
        $fields['title_company'], $fields['access_notes'], now_sql(), $id,
    ]);
}

function change_deal_stage(array $deal, string $stage, string $deadReason): ?string
{
    if (!isset(deal_stages()[$stage])) {
        return null;
    }
    $from = (string) $deal['stage'];
    $now = now_sql();
    db()->prepare('UPDATE deals SET stage = ?, stage_changed_at = ?, dead_reason = ?, updated_at = ? WHERE id = ?')
        ->execute([$stage, $now, $deadReason, $now, (int) $deal['id']]);
    if ($from !== $stage) {
        db()->prepare('INSERT INTO deal_stage_history (deal_id, from_stage, to_stage, changed_at) VALUES (?, ?, ?, ?)')
            ->execute([(int) $deal['id'], $from, $stage, $now]);
    }
    return $stage;
}

function mark_seller_notice(int $dealId, string $kind): void
{
    db()->prepare('UPDATE deals SET last_seller_notice = ?, updated_at = ? WHERE id = ?')
        ->execute([$kind, now_sql(), $dealId]);
}

function list_history(int $dealId): array
{
    $stmt = db()->prepare('SELECT * FROM deal_stage_history WHERE deal_id = ? ORDER BY id');
    $stmt->execute([$dealId]);
    return $stmt->fetchAll();
}

function add_note(string $type, int $id, string $body): void
{
    db()->prepare('INSERT INTO notes (entity_type, entity_id, body, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$type, $id, $body, now_sql()]);
}

function list_notes(string $type, int $id): array
{
    $stmt = db()->prepare('SELECT * FROM notes WHERE entity_type = ? AND entity_id = ? ORDER BY id DESC');
    $stmt->execute([$type, $id]);
    return $stmt->fetchAll();
}

function add_task(string $type, int $id, string $title, ?string $due): void
{
    db()->prepare('INSERT INTO tasks (entity_type, entity_id, title, due_at, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$type, $id, $title, $due, now_sql()]);
}

function list_tasks(string $type, int $id): array
{
    $stmt = db()->prepare('SELECT * FROM tasks WHERE entity_type = ? AND entity_id = ? ORDER BY done_at IS NOT NULL, due_at, id');
    $stmt->execute([$type, $id]);
    return $stmt->fetchAll();
}

function tasks_today(): array
{
    $today = (new DateTimeImmutable('now'))->format('Y-m-d');
    $stmt = db()->prepare('SELECT t.*, d.id AS deal_id, l.address
        FROM tasks t
        LEFT JOIN deals d ON t.entity_type = ? AND d.id = t.entity_id
        LEFT JOIN leads l ON l.id = d.lead_id
        WHERE t.done_at IS NULL AND (t.due_at IS NULL OR t.due_at <= ?)
        ORDER BY t.due_at, t.id');
    $stmt->execute(['deal', $today]);
    return $stmt->fetchAll();
}

function complete_task(int $id): void
{
    db()->prepare('UPDATE tasks SET done_at = ? WHERE id = ? AND done_at IS NULL')->execute([now_sql(), $id]);
}

function insert_contact(array $row): void
{
    db()->prepare('INSERT INTO contact_messages (full_name, phone, email, message, tcpa_consent, tcpa_text, tcpa_at, tcpa_ip, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
        $row['full_name'], $row['phone'], $row['email'], $row['message'], $row['tcpa_consent'],
        $row['tcpa_text'], $row['tcpa_at'], $row['tcpa_ip'], now_sql(),
    ]);
}

function list_contacts(): array
{
    return db()->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200')->fetchAll();
}

function insert_buyer(array $row): int
{
    $now = now_sql();
    db()->prepare('INSERT INTO buyers (
        company, buyer_type, tier, contact_name, email, phone, contact_role, pof_on_file, pof_date, closings_count,
        email_opt_in, notes, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
        $row['company'], $row['buyer_type'], $row['tier'], $row['contact_name'], $row['email'], $row['phone'],
        $row['contact_role'] ?? '', $row['pof_on_file'], $row['pof_date'], $row['closings_count'], $row['email_opt_in'],
        $row['notes'], $now, $now,
    ]);
    return (int) db()->lastInsertId();
}

function update_buyer(int $id, array $row): void
{
    db()->prepare('UPDATE buyers SET company = ?, buyer_type = ?, tier = ?, contact_name = ?, email = ?, phone = ?,
        contact_role = ?, pof_on_file = ?, pof_date = ?, closings_count = ?, email_opt_in = ?, notes = ?, updated_at = ? WHERE id = ?')
        ->execute([
            $row['company'], $row['buyer_type'], $row['tier'], $row['contact_name'], $row['email'], $row['phone'],
            $row['contact_role'] ?? '', $row['pof_on_file'], $row['pof_date'], $row['closings_count'], $row['email_opt_in'],
            $row['notes'], now_sql(), $id,
        ]);
}

function list_buyers(): array
{
    return db()->query('SELECT * FROM buyers ORDER BY tier, company')->fetchAll();
}

function get_buyer(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM buyers WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_buyer_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM buyers WHERE email = ?');
    $stmt->execute([strtolower($email)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function unsubscribe_buyer(string $email): void
{
    db()->prepare('UPDATE buyers SET email_opt_in = 0, unsubscribed_at = ?, updated_at = ? WHERE email = ?')
        ->execute([now_sql(), now_sql(), strtolower($email)]);
}

function insert_box(int $buyerId, array $box): void
{
    db()->prepare('INSERT INTO buy_boxes (
        buyer_id, areas, property_types, min_beds, min_baths, price_min, price_max, max_rehab, min_year, occupancy_ok, close_speed, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
        $buyerId, $box['areas'], $box['property_types'], $box['min_beds'], $box['min_baths'],
        $box['price_min'], $box['price_max'], $box['max_rehab'], $box['min_year'], $box['occupancy_ok'],
        $box['close_speed'] ?? '', now_sql(),
    ]);
}

function list_boxes(int $buyerId): array
{
    $stmt = db()->prepare('SELECT * FROM buy_boxes WHERE buyer_id = ? ORDER BY id');
    $stmt->execute([$buyerId]);
    return $stmt->fetchAll();
}

function delete_box(int $id, int $buyerId): void
{
    db()->prepare('DELETE FROM buy_boxes WHERE id = ? AND buyer_id = ?')->execute([$id, $buyerId]);
}

function insert_sheet(array $sheet): int
{
    db()->prepare('INSERT INTO deal_sheets (
        deal_id, token, expires_at, location_label, price_text, arv_text, rehab_text, access_text,
        attorney_text, deadline_text, show_photos, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
        $sheet['deal_id'], $sheet['token'], $sheet['expires_at'], $sheet['location_label'], $sheet['price_text'],
        $sheet['arv_text'], $sheet['rehab_text'], $sheet['access_text'], $sheet['attorney_text'],
        $sheet['deadline_text'], $sheet['show_photos'], now_sql(),
    ]);
    return (int) db()->lastInsertId();
}

function get_sheet_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT s.*, d.lead_id FROM deal_sheets s JOIN deals d ON d.id = s.deal_id WHERE s.token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function list_sheets(int $dealId): array
{
    $stmt = db()->prepare('SELECT * FROM deal_sheets WHERE deal_id = ? ORDER BY id DESC');
    $stmt->execute([$dealId]);
    return $stmt->fetchAll();
}

function log_blast(int $sheetId, int $buyerId): void
{
    db()->prepare('INSERT INTO blasts (deal_sheet_id, buyer_id, sent_at) VALUES (?, ?, ?)')
        ->execute([$sheetId, $buyerId, now_sql()]);
}

function opted_in_buyers(): array
{
    return db()->query('SELECT * FROM buyers WHERE email_opt_in = 1 AND unsubscribed_at IS NULL ORDER BY tier, company')->fetchAll();
}

function csv_leads(): array
{
    return db()->query('SELECT l.*, d.id AS deal_id, d.stage FROM leads l LEFT JOIN deals d ON d.lead_id = l.id ORDER BY l.id')->fetchAll();
}

function csv_deals(): array
{
    return db()->query('SELECT d.*, l.address, l.city, l.full_name, l.email, l.phone FROM deals d JOIN leads l ON l.id = d.lead_id ORDER BY d.id')->fetchAll();
}

function csv_buyers(): array
{
    return db()->query('SELECT * FROM buyers ORDER BY id')->fetchAll();
}

function csv_boxes(): array
{
    return db()->query('SELECT b.company, b.email, x.* FROM buy_boxes x JOIN buyers b ON b.id = x.buyer_id ORDER BY x.id')->fetchAll();
}
