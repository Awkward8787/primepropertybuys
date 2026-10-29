<?php
declare(strict_types=1);

if (!defined('PPB_APP')) {
    http_response_code(403);
    exit;
}

function db(): PDO
{
    if (isset($GLOBALS['ppb_pdo']) && $GLOBALS['ppb_pdo'] instanceof PDO) {
        return $GLOBALS['ppb_pdo'];
    }

    $driver = db_driver();
    if ($driver === 'sqlite') {
        $path = (string) ppb('db.sqlite_path', dirname(__DIR__) . '/storage/dev.sqlite');
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create the database directory.');
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 3000');
    } else {
        $host = (string) ppb('db.host', 'localhost');
        $port = (int) ppb('db.port', 3306);
        $name = (string) ppb('db.name', '');
        $user = (string) ppb('db.user', '');
        $pass = (string) ppb('db.pass', '');
        $charset = (string) ppb('db.charset', 'utf8mb4');
        if ($name === '' || $user === '') {
            throw new RuntimeException('Database name and user are required in config.php.');
        }
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    $GLOBALS['ppb_pdo'] = $pdo;
    return $pdo;
}

function db_driver(): string
{
    $driver = strtolower((string) ppb('db.driver', 'mysql'));
    return $driver === 'sqlite' ? 'sqlite' : 'mysql';
}

function ensure_schema(PDO $pdo): void
{
    foreach (schema_statements(db_driver()) as $sql) {
        $pdo->exec($sql);
    }
    ensure_added_columns($pdo);
}

function ensure_added_columns(PDO $pdo): void
{
    $columns = [
        'buyers' => ['contact_role' => 'TEXT'],
        'buy_boxes' => ['close_speed' => 'TEXT'],
    ];
    foreach ($columns as $table => $defs) {
        $existing = [];
        if (db_driver() === 'sqlite') {
            foreach ($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll() as $col) {
                $existing[(string) $col['name']] = true;
            }
        } else {
            $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
            $stmt->execute([$table]);
            foreach ($stmt->fetchAll() as $col) {
                $existing[(string) $col['COLUMN_NAME']] = true;
            }
        }
        foreach ($defs as $name => $type) {
            if (isset($existing[$name])) {
                continue;
            }
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $name . ' ' . $type);
        }
    }
}

function schema_statements(string $driver): array
{
    if ($driver === 'sqlite') {
        return sqlite_schema_statements();
    }
    return mysql_schema_statements();
}

function mysql_schema_statements(): array
{
    $sql = file_get_contents(dirname(__DIR__) . '/schema.sql');
    if (!is_string($sql)) {
        throw new RuntimeException('schema.sql is missing.');
    }
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    $parts = explode(';', $sql);
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return $out;
}

function sqlite_schema_statements(): array
{
    return [
        'CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT \'owner\',
            created_at TEXT NOT NULL,
            last_login TEXT
        )',
        'CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            best_time TEXT NOT NULL DEFAULT \'\',
            tcpa_consent INTEGER NOT NULL DEFAULT 0,
            tcpa_text TEXT NOT NULL DEFAULT \'\',
            tcpa_at TEXT,
            tcpa_ip TEXT NOT NULL DEFAULT \'\',
            address TEXT NOT NULL,
            city TEXT NOT NULL DEFAULT \'\',
            zip TEXT NOT NULL DEFAULT \'\',
            county TEXT NOT NULL DEFAULT \'Lowndes\',
            property_type TEXT NOT NULL DEFAULT \'\',
            beds TEXT NOT NULL DEFAULT \'\',
            baths TEXT NOT NULL DEFAULT \'\',
            condition_label TEXT NOT NULL DEFAULT \'\',
            occupancy TEXT NOT NULL DEFAULT \'\',
            timeline TEXT NOT NULL DEFAULT \'\',
            reason TEXT NOT NULL DEFAULT \'\',
            asking_price INTEGER,
            utm TEXT NOT NULL DEFAULT \'\',
            source_path TEXT NOT NULL DEFAULT \'\',
            ip_address TEXT NOT NULL DEFAULT \'\',
            user_agent TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_leads_city_created ON leads (city, created_at)',
        'CREATE INDEX IF NOT EXISTS idx_leads_email ON leads (email)',
        'CREATE TABLE IF NOT EXISTS deals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER NOT NULL UNIQUE,
            stage TEXT NOT NULL DEFAULT \'lead\',
            stage_changed_at TEXT NOT NULL,
            dead_reason TEXT NOT NULL DEFAULT \'\',
            arv INTEGER,
            repair_est INTEGER,
            mao INTEGER,
            offer_amt INTEGER,
            contract_price INTEGER,
            contract_date TEXT,
            inspection_deadline TEXT,
            closing_date TEXT,
            title_company TEXT NOT NULL DEFAULT \'\',
            assignment_fee INTEGER,
            access_notes TEXT NOT NULL DEFAULT \'\',
            last_seller_notice TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_deals_stage ON deals (stage, updated_at)',
        'CREATE TABLE IF NOT EXISTS deal_stage_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deal_id INTEGER NOT NULL,
            from_stage TEXT NOT NULL DEFAULT \'\',
            to_stage TEXT NOT NULL,
            changed_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_stage_deal ON deal_stage_history (deal_id, changed_at)',
        'CREATE TABLE IF NOT EXISTS notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_type TEXT NOT NULL,
            entity_id INTEGER NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_notes_entity ON notes (entity_type, entity_id)',
        'CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_type TEXT NOT NULL,
            entity_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            due_at TEXT,
            done_at TEXT,
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_tasks_due ON tasks (done_at, due_at)',
        'CREATE TABLE IF NOT EXISTS media (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lead_id INTEGER,
            buyer_id INTEGER,
            kind TEXT NOT NULL DEFAULT \'photo\',
            stored_name TEXT NOT NULL,
            original_name TEXT NOT NULL,
            mime TEXT NOT NULL,
            bytes INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_media_lead ON media (lead_id)',
        'CREATE INDEX IF NOT EXISTS idx_media_buyer ON media (buyer_id)',
        'CREATE TABLE IF NOT EXISTS buyers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company TEXT NOT NULL,
            buyer_type TEXT NOT NULL DEFAULT \'\',
            tier TEXT NOT NULL DEFAULT \'C\',
            contact_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL DEFAULT \'\',
            contact_role TEXT NOT NULL DEFAULT \'\',
            pof_on_file INTEGER NOT NULL DEFAULT 0,
            pof_date TEXT,
            closings_count INTEGER NOT NULL DEFAULT 0,
            email_opt_in INTEGER NOT NULL DEFAULT 0,
            unsubscribed_at TEXT,
            notes TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_buyers_email ON buyers (email)',
        'CREATE TABLE IF NOT EXISTS buy_boxes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            buyer_id INTEGER NOT NULL,
            areas TEXT NOT NULL DEFAULT \'\',
            property_types TEXT NOT NULL DEFAULT \'\',
            min_beds INTEGER,
            min_baths INTEGER,
            price_min INTEGER,
            price_max INTEGER,
            max_rehab INTEGER,
            min_year INTEGER,
            occupancy_ok TEXT NOT NULL DEFAULT \'any\',
            close_speed TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_boxes_buyer ON buy_boxes (buyer_id)',
        'CREATE TABLE IF NOT EXISTS deal_sheets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deal_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            location_label TEXT NOT NULL,
            price_text TEXT NOT NULL DEFAULT \'\',
            arv_text TEXT NOT NULL DEFAULT \'\',
            rehab_text TEXT NOT NULL DEFAULT \'\',
            access_text TEXT NOT NULL DEFAULT \'\',
            attorney_text TEXT NOT NULL DEFAULT \'\',
            deadline_text TEXT NOT NULL DEFAULT \'\',
            show_photos INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_sheet_deal ON deal_sheets (deal_id)',
        'CREATE TABLE IF NOT EXISTS blasts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deal_sheet_id INTEGER NOT NULL,
            buyer_id INTEGER NOT NULL,
            sent_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_blasts_sheet ON blasts (deal_sheet_id)',
        'CREATE TABLE IF NOT EXISTS contact_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            tcpa_consent INTEGER NOT NULL DEFAULT 0,
            tcpa_text TEXT NOT NULL DEFAULT \'\',
            tcpa_at TEXT,
            tcpa_ip TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL
        )',
        'CREATE TABLE IF NOT EXISTS form_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            step TEXT NOT NULL,
            session_hash TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_form_events_step ON form_events (step, created_at)',
        'CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            bucket TEXT NOT NULL,
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_rate_lookup ON rate_limits (ip_address, bucket, created_at)',
        'CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL DEFAULT 0,
            action TEXT NOT NULL,
            entity TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL
        )',
        'CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log (created_at)',
    ];
}
