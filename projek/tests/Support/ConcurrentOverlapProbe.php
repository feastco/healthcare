<?php

// Standalone child process for the true-concurrency appointment test.
// Usage: php ConcurrentOverlapProbe.php <path-to-config.json>
//
// Config (JSON):
//   db:            {host, port, database, username, password}
//   doctor_id, patient_id, starts_at, ends_at
//   go_file, started_file, result_file
//
// Flow:
//   1. Wait until go_file exists (parent signal) or 10s timeout.
//   2. Connect via PDO and SET statement_timeout = 10000.
//   3. Write started_file, then attempt the overlapping INSERT.
//   4. Write result_file with JSON {ok, sqlstate, message}.

if (PHP_SAPI !== 'cli') {
    exit(9);
}

$configPath = $argv[1] ?? '';
if (! is_file($configPath)) {
    file_put_contents('probe_missing_config.log', json_encode(['ok' => false, 'sqlstate' => null, 'message' => 'missing-config']));
    exit(2);
}

$config = json_decode(file_get_contents($configPath), true);

$goFile = $config['go_file'];
$deadline = microtime(true) + 10.0;
while (! is_file($goFile) && microtime(true) < $deadline) {
    usleep(50_000);
}
if (! is_file($goFile)) {
    file_put_contents($config['result_file'], json_encode([
        'ok' => false,
        'sqlstate' => null,
        'message' => 'go-signal-timeout',
    ]));
    exit(2);
}

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['database'],
);

try {
    $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password']);
    $pdo->exec('SET statement_timeout = 10000');

    file_put_contents($config['started_file'], '1');

    $stmt = $pdo->prepare(
        'INSERT INTO appointments (patient_id, doctor_id, starts_at, ends_at, status, created_at, updated_at) '
        .'VALUES (:patient, :doctor, :starts, :ends, :status, now(), now())'
    );
    $stmt->execute([
        ':patient' => $config['patient_id'],
        ':doctor' => $config['doctor_id'],
        ':starts' => $config['starts_at'],
        ':ends' => $config['ends_at'],
        ':status' => 'SCHEDULED',
    ]);

    file_put_contents($config['result_file'], json_encode([
        'ok' => true,
        'sqlstate' => null,
        'message' => 'insert-succeeded',
    ]));
    exit(0);
} catch (PDOException $e) {
    file_put_contents($config['result_file'], json_encode([
        'ok' => false,
        'sqlstate' => $e->getCode(),
        'message' => $e->getMessage(),
    ]));
    exit(1);
}
