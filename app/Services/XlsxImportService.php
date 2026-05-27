<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Grades;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XlsxImportService
{
    // ── Subjects xlsx column map ──────────────────────────────
    private array $subjectColumnMap = [
        'Mācību priekšmets' => 'macibu_prieksments',
        'Kategorija'        => 'kategorija',
    ];

    // ── Grades xlsx column map ────────────────────────────────
    private array $gradeColumnMap = [
        'Informācijas tips'  => 'informacijas_tips',
        'Informācijas veids' => 'informacijas_veids',
        'Priekšmetu veids'   => 'prieksmetu_veids',
        'Datums'             => 'datums',
        'Vērtējuma tips'     => 'vertejuma_tips',
        'Īlens'              => 'ilens',
        'Kārkliņš'           => 'karklins',
        'Varizeja'           => 'varizeja',
    ];

    // ─────────────────────────────────────────────────────────

    /**
     * Import subjects file. Safe to re-run — uses updateOrCreate.
     */
    public function importSubjects(string $filePath): array
    {
        $stats = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $rows      = $this->loadRows($filePath);
        $headerRow = array_shift($rows);
        $fieldMap  = $this->buildFieldMap($headerRow, $this->subjectColumnMap);

        if (empty($fieldMap)) {
            Log::warning('importSubjects: no headers matched.', [
                'found' => array_values($headerRow)
            ]);
        }

        DB::transaction(function () use ($rows, $fieldMap, &$stats) {
            foreach ($rows as $i => $row) {
                if ($this->isBlankRow($row)) { $stats['skipped']++; continue; }
                try {
                    $data = $this->mapRow($row, $fieldMap);
                    if (empty($data['macibu_prieksments'])) {
                        $stats['skipped']++;
                        continue;
                    }
                    Subject::updateOrCreate(
                        ['macibu_prieksments' => $data['macibu_prieksments']],
                        ['kategorija'         => $data['kategorija'] ?? null]
                    );
                    $stats['imported']++;
                } catch (\Exception $e) {
                    $stats['errors'][] = "Row " . ($i + 2) . ": " . $e->getMessage();
                    $stats['skipped']++;
                }
            }
        });

        return $stats;
    }

    /**
     * Import grades file.
     * Resolves subject_id by matching prieksmetu_veids against subjects.macibu_prieksments.
     * Auto-creates any subject not already in the subjects table.
     */
    public function importGrades(string $filePath): array
    {
        $stats = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $rows      = $this->loadRows($filePath);
        $headerRow = array_shift($rows);
        $fieldMap  = $this->buildFieldMap($headerRow, $this->gradeColumnMap);

        // Log what was and wasn't matched so you can diagnose header issues easily
        Log::debug('importGrades fieldMap:', $fieldMap);
        Log::debug('importGrades raw headers:', array_map(
            fn($h) => bin2hex($this->normalizeHeader((string)$h)) . ' = ' . $this->normalizeHeader((string)$h),
            $headerRow
        ));

        if (empty($fieldMap)) {
            throw new \RuntimeException(
                'No grade columns could be matched. Check storage/logs/laravel.log for header details.'
            );
        }

        // Pre-load all subjects as name → id to avoid per-row queries
        $subjectLookup = Subject::pluck('id', 'macibu_prieksments')->toArray();

        DB::transaction(function () use ($rows, $fieldMap, &$subjectLookup, &$stats) {
            $batch = [];

            foreach ($rows as $i => $row) {
                if ($this->isBlankRow($row)) { $stats['skipped']++; continue; }

                try {
                    $data = $this->mapRow($row, $fieldMap);

                    // ── Resolve subject_id from prieksmetu_veids ──
                    // prieksmetu_veids holds the subject name in the grades file.
                    // We look it up in subjects table and store both the raw
                    // value (in prieksmetu_veids) and the FK (in subject_id).
                    $subjectId   = null;
                    $subjectName = $data['prieksmetu_veids'] ?? null;

                    if (!empty($subjectName)) {
                        if (!isset($subjectLookup[$subjectName])) {
                            // Auto-create missing subjects so no row is lost
                            $new = Subject::create([
                                'macibu_prieksments' => $subjectName,
                                'kategorija'         => null,
                            ]);
                            $subjectLookup[$subjectName] = $new->id;
                        }
                        $subjectId = $subjectLookup[$subjectName];
                    }
                    // ─────────────────────────────────────────────

                    $batch[] = array_merge($data, ['subject_id' => $subjectId]);
                    // Note: no created_at/updated_at — grades table has no timestamps

                    if (count($batch) >= 100) {
                        Grades::insert($batch);
                        $stats['imported'] += count($batch);
                        $batch = [];
                    }

                } catch (\Exception $e) {
                    $stats['errors'][] = "Row " . ($i + 2) . ": " . $e->getMessage();
                    $stats['skipped']++;
                }
            }

            if (!empty($batch)) {
                Grades::insert($batch);
                $stats['imported'] += count($batch);
            }
        });

        return $stats;
    }

    // ── Shared helpers ────────────────────────────────────────

    /**
     * Normalize a header string consistently everywhere it's compared.
     * Handles BOM, non-breaking spaces, extra whitespace, and ensures UTF-8.
     */
    private function normalizeHeader(string $value): string
    {
        // Strip UTF-8 BOM if present
        $value = str_replace("\xEF\xBB\xBF", '', $value);
        // Convert to UTF-8 from whatever encoding Excel used
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        // Replace non-breaking spaces and other whitespace variants with regular space
        $value = preg_replace('/[\x{00A0}\x{200B}\s]+/u', ' ', $value);
        return trim($value);
    }

    private function loadRows(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getActiveSheet();
        return $sheet->toArray(null, true, true, true);
    }

    private function buildFieldMap(array $headerRow, array $columnMap): array
    {
        // Pre-normalize the column map keys so comparison is always apples-to-apples
        $normalizedMap = [];
        foreach ($columnMap as $header => $dbField) {
            $normalizedMap[$this->normalizeHeader($header)] = $dbField;
        }

        $map = [];
        foreach ($headerRow as $colLetter => $headerValue) {
            $normalized = $this->normalizeHeader((string) $headerValue);
            if (isset($normalizedMap[$normalized])) {
                $map[$colLetter] = $normalizedMap[$normalized];
            }
        }
        return $map;
    }

    private function mapRow(array $row, array $fieldMap): array
    {
        $record = [];
        foreach ($fieldMap as $colLetter => $dbField) {
            $value = $row[$colLetter] ?? null;
            $record[$dbField] = match ($dbField) {
                'datums' => $this->parseDate($value),
                default  => $value !== null ? trim((string) $value) : null,
            };
        }
        return $record;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return date('Y-m-d', ExcelDate::excelToTimestamp((float) $value));
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') return false;
        }
        return true;
    }
}