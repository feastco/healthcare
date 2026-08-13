<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class AppointmentConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Disable the per-test transaction so all writes autocommit and are
     * visible to the external child process.
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    /** @var array<int, int> */
    private array $doctorIds = [];

    /** @var array<int, int> */
    private array $patientIds = [];

    /** @var array<int, int> */
    private array $appointmentIds = [];

    /** @var array<int, int> */
    private array $scheduleIds = [];

    /** @var array<int, string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        $this->cleanup();

        parent::tearDown();
    }

    public function test_appointments_exclusion_constraint_exists(): void
    {
        $constraint = DB::select(
            "SELECT conname FROM pg_constraint WHERE conname = 'appointments_doctor_no_overlap'"
        );

        $this->assertCount(1, $constraint);

        $extension = DB::select(
            "SELECT extname FROM pg_extension WHERE extname = 'btree_gist'"
        );

        $this->assertCount(1, $extension);
    }

    public function test_database_rejects_overlapping_insert_directly(): void
    {
        [$doctor, $patient] = $this->createFixture();
        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET statement_timeout = 15000');

        $insert = $pdo->prepare(
            'INSERT INTO appointments (patient_id, doctor_id, starts_at, ends_at, status, created_at, updated_at) '
            .'VALUES (?, ?, ?, ?, ?, now(), now())'
        );

        $insert->execute([$patient->id, $doctor->id, '2026-09-01 09:00:00', '2026-09-01 10:00:00', 'SCHEDULED']);
        $this->appointmentIds[] = (int) $pdo->lastInsertId();

        try {
            $insert->execute([$patient->id, $doctor->id, '2026-09-01 09:30:00', '2026-09-01 10:30:00', 'SCHEDULED']);
            $this->appointmentIds[] = (int) $pdo->lastInsertId();
            $this->fail('Expected SQLSTATE 23P01 for overlapping appointment');
        } catch (PDOException $e) {
            $this->assertSame('23P01', $e->getCode());
        }

        $insert->execute([$patient->id, $doctor->id, '2026-09-01 10:00:00', '2026-09-01 11:00:00', 'SCHEDULED']);
        $this->appointmentIds[] = (int) $pdo->lastInsertId();
    }

    public function test_two_process_overlapping_insert_is_rejected_by_database(): void
    {
        [$doctor, $patient] = $this->createFixture();
        $pdo = DB::connection()->getPdo();
        $pdo->exec('BEGIN');

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO appointments (patient_id, doctor_id, starts_at, ends_at, status, created_at, updated_at) '
                .'VALUES (?, ?, ?, ?, ?, now(), now())'
            );
            $stmt->execute([$patient->id, $doctor->id, '2026-09-01 09:00:00', '2026-09-01 10:00:00', 'SCHEDULED']);

            $dir = storage_path('framework/testing/probe-'.uniqid());
            mkdir($dir, 0777, true);
            $this->tempDirs[] = $dir;

            $config = [
                'db' => [
                    'host' => config('database.connections.pgsql.host'),
                    'port' => config('database.connections.pgsql.port'),
                    'database' => config('database.connections.pgsql.database'),
                    'username' => config('database.connections.pgsql.username'),
                    'password' => config('database.connections.pgsql.password'),
                ],
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'starts_at' => '2026-09-01 09:30:00',
                'ends_at' => '2026-09-01 10:30:00',
                'go_file' => $dir.'/go.txt',
                'started_file' => $dir.'/started.txt',
                'result_file' => $dir.'/result.json',
            ];
            file_put_contents($dir.'/config.json', json_encode($config));

            touch($dir.'/stdin.txt');

            $cmd = sprintf(
                '"%s" "%s" "%s"',
                PHP_BINARY,
                base_path('tests/Support/ConcurrentOverlapProbe.php'),
                $dir.'/config.json'
            );

            $descriptors = [
                0 => ['file', $dir.'/stdin.txt', 'r'],
                1 => ['file', $dir.'/stdout.txt', 'w'],
                2 => ['file', $dir.'/stderr.txt', 'w'],
            ];

            $proc = proc_open($cmd, $descriptors, $pipes);
            $this->assertIsResource($proc);

            try {
                file_put_contents($config['go_file'], 'go');

                $this->assertTrue(
                    $this->waitForFile($config['started_file'], 15),
                    'Child probe did not reach its INSERT in time'
                );

                $pdo->exec('COMMIT');

                $this->assertTrue(
                    $this->waitForFile($config['result_file'], 20),
                    'Child probe did not produce a result in time'
                );

                $result = json_decode(file_get_contents($config['result_file']), true);
                $this->assertNotNull($result, 'Probe result was not valid JSON');
                $this->assertFalse($result['ok'], 'Overlapping insert unexpectedly succeeded');
                $this->assertSame('23P01', $result['sqlstate']);

                $this->appointmentIds[] = (int) $pdo->lastInsertId();
            } finally {
                if (is_resource($proc)) {
                    proc_close($proc);
                }
            }
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');

            throw $e;
        }
    }

    private function createFixture(): array
    {
        $doctor = Doctor::factory()->create();
        $patient = Patient::factory()->create();
        $schedule = DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $this->doctorIds[] = $doctor->id;
        $this->patientIds[] = $patient->id;
        $this->scheduleIds[] = $schedule->id;

        return [$doctor, $patient];
    }

    private function waitForFile(string $path, int $timeoutSeconds): bool
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (microtime(true) < $deadline) {
            if (is_file($path)) {
                return true;
            }

            usleep(100_000);
        }

        return is_file($path);
    }

    private function cleanup(): void
    {
        if ($this->appointmentIds !== []) {
            DB::table('appointments')
                ->whereIn('id', $this->appointmentIds)
                ->delete();
        }

        if ($this->scheduleIds !== []) {
            DB::table('doctor_schedules')
                ->whereIn('id', $this->scheduleIds)
                ->delete();
        }

        if ($this->doctorIds !== []) {
            DB::table('doctors')
                ->whereIn('id', $this->doctorIds)
                ->delete();
        }

        if ($this->patientIds !== []) {
            DB::table('patients')
                ->whereIn('id', $this->patientIds)
                ->delete();
        }

        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
