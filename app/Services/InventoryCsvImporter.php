<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Kit;
use Illuminate\Validation\ValidationException;

class InventoryCsvImporter
{
    private const REQUIRED = ['sku', 'name', 'recorded_quantity'];

    private const ALLOWED = [
        'sku', 'name', 'category', 'manufacturer', 'model', 'recorded_quantity',
        'physical_quantity', 'verification_notes', 'reorder_threshold', 'description',
        'kit_code', 'kit_name', 'kit_quantity', 'kit_notes',
    ];

    public function parse(string $path): array
    {
        $file = fopen($path, 'rb');
        if ($file === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded CSV could not be read.']);
        }

        try {
            $headers = fgetcsv($file);
            if (! is_array($headers)) {
                throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
            }
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);
            if (count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['file' => 'The CSV contains duplicate column headings.']);
            }
            $missing = array_diff(self::REQUIRED, $headers);
            $unknown = array_diff($headers, self::ALLOWED);
            if ($missing || $unknown) {
                $messages = [];
                if ($missing) {
                    $messages[] = 'Required columns missing: '.implode(', ', $missing).'.';
                }
                if ($unknown) {
                    $messages[] = 'Unmapped columns found: '.implode(', ', $unknown).'. Use the documented column names before importing.';
                }
                throw ValidationException::withMessages(['file' => $messages]);
            }

            $rows = [];
            $seenSkus = [];
            $line = 1;
            while (($cells = fgetcsv($file)) !== false) {
                $line++;
                if (count($rows) >= 500) {
                    throw ValidationException::withMessages(['file' => 'CSV import is limited to 500 data rows per review.']);
                }
                if (count($cells) === 1 && trim((string) $cells[0]) === '') {
                    $rows[] = ['_line' => $line, '_action' => 'skip', '_reason' => 'Blank row'];

                    continue;
                }
                if (count($cells) !== count($headers)) {
                    $rows[] = ['_line' => $line, '_action' => 'error', '_reason' => 'Column count does not match the header row.'];

                    continue;
                }

                $row = array_combine($headers, array_map(fn ($cell) => trim((string) $cell), $cells));
                foreach (self::ALLOWED as $column) {
                    $row[$column] ??= '';
                }
                $row['_line'] = $line;
                $row['_columns'] = $headers;
                $reason = $this->validateRow($row);
                if ($reason !== null) {
                    $row['_action'] = 'error';
                    $row['_reason'] = $reason;
                } elseif (isset($seenSkus[strtolower($row['sku'])])) {
                    $row['_action'] = 'skip';
                    $row['_reason'] = 'Duplicate SKU in this file; only the first occurrence can be imported.';
                } else {
                    $seenSkus[strtolower($row['sku'])] = true;
                    $row['_action'] = 'ready';
                }
                $rows[] = $row;
            }

            $skus = collect($rows)->whereIn('_action', ['ready'])->pluck('sku')->all();
            $existing = Component::whereIn('sku', $skus)->pluck('id', 'sku');
            $kits = Kit::pluck('name', 'code');
            foreach ($rows as &$row) {
                if ($row['_action'] !== 'ready') {
                    continue;
                }
                $row['_action'] = $existing->has($row['sku']) ? 'update' : 'insert';
                if ($row['kit_code'] !== '' && $kits->has($row['kit_code']) && $kits[$row['kit_code']] !== $row['kit_name']) {
                    $row['_action'] = 'error';
                    $row['_reason'] = 'Kit code already exists with a different kit name.';

                    continue;
                }
                $row['_warning'] = '';
                if ($row['physical_quantity'] !== '') {
                    $recorded = (int) $row['recorded_quantity'];
                    if ((int) $row['physical_quantity'] !== $recorded) {
                        $row['_warning'] = 'Physical count differs from recorded quantity; both values will be retained separately.';
                    }
                }
            }
            unset($row);

            return $rows;
        } finally {
            fclose($file);
        }
    }

    private function validateRow(array $row): ?string
    {
        if ($row['sku'] === '' || strlen($row['sku']) > 255) {
            return 'SKU is required (maximum 255 characters) to support repeatable imports.';
        }
        if ($row['name'] === '' || strlen($row['name']) > 255) {
            return 'Component name is required (maximum 255 characters).';
        }
        if (! $this->validQuantity($row['recorded_quantity'])) {
            return 'Recorded quantity must be a non-negative whole number.';
        }
        foreach (['physical_quantity', 'reorder_threshold'] as $column) {
            if ($row[$column] !== '' && ! $this->validQuantity($row[$column])) {
                return $column.' must be a non-negative whole number.';
            }
        }
        foreach (['category', 'manufacturer', 'model'] as $column) {
            if (strlen($row[$column]) > 255) {
                return $column.' exceeds 255 characters.';
            }
        }
        if ($row['kit_code'] !== '' xor $row['kit_name'] !== '') {
            return 'Both kit_code and kit_name are required when assigning a component to a kit.';
        }
        if ($row['kit_code'] !== '') {
            if (strlen($row['kit_code']) > 80 || strlen($row['kit_name']) > 255) {
                return 'Kit code or name exceeds its allowed length.';
            }
            if (! $this->validQuantity($row['kit_quantity']) || (int) $row['kit_quantity'] < 1) {
                return 'kit_quantity must be a positive whole number when a kit is provided.';
            }
        }
        foreach (['description', 'verification_notes', 'kit_notes'] as $column) {
            if (strlen($row[$column]) > 60000) {
                return $column.' exceeds the 60,000 character import limit.';
            }
        }

        return null;
    }

    private function validQuantity(string $value): bool
    {
        return preg_match('/^\d+$/', $value) === 1 && (int) $value <= 1000000;
    }
}
