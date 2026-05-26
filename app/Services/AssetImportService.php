<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Status;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class AssetImportService
{
    private const CORE_COLUMNS = [
        'classification',
        'category',
        'categories',
        'class',
        'asset_category',
        'asset_name',
        'asset',
        'name',
        'system_name',
        'system',
        'hostname',
        'host_name',
        'model',
        'model_name',
        'description',
        'serial_no',
        'serial',
        'serial_number',
        'service_tag',
        'supplier_id',
        'supplier',
        'supplier_name',
        'status_id',
        'status',
        'price',
        'purchase_date',
        'location_id',
        'location',
    ];

    public function __construct(private readonly AssetService $assetService)
    {
    }

    public function import(UploadedFile $file, Category $selectedCategory): array
    {
        $rows = $this->readRows($file);

        if (count($rows) < 2) {
            throw new RuntimeException('The file must include a header row and at least one data row.');
        }

        $headers = array_map(fn ($header) => trim((string) $header), array_shift($rows));
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeHeader($header), $headers);

        $summary = [
            'imported' => 0,
            'skipped' => 0,
            'dynamic_attributes_used' => 0,
            'ignored_columns' => [],
            'errors' => [],
        ];

        $usedDynamicAttributes = [];
        $ignoredColumns = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2;
            $row = $this->padRow($row, count($headers));

            if ($this->isBlankRow($row)) {
                continue;
            }

            try {
                [$customAttributes, $rowIgnoredColumns] = $this->customAttributes($selectedCategory, $headers, $normalizedHeaders, $row);
                $usedDynamicAttributes = array_merge($usedDynamicAttributes, array_keys($customAttributes));
                $ignoredColumns = array_merge($ignoredColumns, $rowIgnoredColumns);

                $systemName = $this->firstValue($row, $normalizedHeaders, ['system_name', 'system', 'hostname', 'host_name']);
                $assetName = $this->firstValue($row, $normalizedHeaders, ['asset_name', 'asset', 'name', 'model_name', 'model', 'description']);
                $serialNo = $this->firstValue($row, $normalizedHeaders, ['serial_no', 'serial_number', 'serial', 'service_tag']);

                if ($assetName === null || $assetName === '') {
                    $assetName = $systemName ?: ($serialNo ?: "{$selectedCategory->name} row {$rowNumber}");
                }

                $data = [
                    'Asset_Name' => $assetName,
                    'system_name' => $systemName,
                    'Serial_No' => $serialNo,
                    'category_id' => $selectedCategory->id,
                    'Asset_Category' => $selectedCategory->name,
                    'Supplier_ID' => $this->resolveSupplierId($this->firstValue($row, $normalizedHeaders, ['supplier_id', 'supplier', 'supplier_name'])),
                    'Status_ID' => $this->resolveStatusId($this->firstValue($row, $normalizedHeaders, ['status_id', 'status'])),
                    'Price' => $this->normalizeMoney($this->firstValue($row, $normalizedHeaders, ['price'])),
                    'Purchase_Date' => $this->normalizeDate($this->firstValue($row, $normalizedHeaders, ['purchase_date'])),
                    'location_id' => $this->resolveLocationId($this->firstValue($row, $normalizedHeaders, ['location_id', 'location'])),
                    'custom_attributes' => $customAttributes,
                    'created_by' => Auth::id(),
                ];

                $data = array_filter($data, fn ($value) => $value !== null && $value !== '');

                $this->assetService->store($data);
                $summary['imported']++;
            } catch (\Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }

        $summary['dynamic_attributes_used'] = count(array_unique($usedDynamicAttributes));
        $summary['ignored_columns'] = array_values(array_unique($ignoredColumns));

        return $summary;
    }

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new RuntimeException('Only .xlsx and .csv files are supported. Save old .xls files as .xlsx first.'),
        };
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open the uploaded Excel file.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('The Excel workbook does not contain a readable worksheet.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('The Excel worksheet could not be parsed.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $columnIndex = $this->columnIndex($ref);
                $row[$columnIndex] = $this->cellValue($cell, $sharedStrings);
            }

            if ($row !== []) {
                ksort($row);
                $rows[] = $this->padRow($row, max(array_keys($row)) + 1);
            }
        }

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $shared = simplexml_load_string($xml);
        if (! $shared instanceof SimpleXMLElement) {
            return [];
        }

        foreach ($shared->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $parts = [];
            foreach ($item->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if (! $workbook instanceof SimpleXMLElement || ! $rels instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheets = $workbook->xpath('//main:sheet');
        $firstSheet = $sheets[0] ?? null;
        $relationshipId = $firstSheet ? (string) $firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : '';

        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = ltrim((string) $relationship['Target'], '/');
                return str_starts_with($target, 'xl/') ? $target : "xl/{$target}";
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return trim((string) $cell->is->t);
        }

        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'b') {
            return $value === '1';
        }

        return trim($value);
    }

    private function columnIndex(string $cellReference): int
    {
        preg_match('/^[A-Z]+/i', $cellReference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function customAttributes(Category $category, array $headers, array $normalizedHeaders, array $row): array
    {
        $attributes = [];
        $ignoredColumns = [];
        $fieldMap = $this->categoryFieldMap($category);

        foreach ($headers as $index => $header) {
            $normalized = $normalizedHeaders[$index] ?? '';
            if ($header === '' || in_array($normalized, self::CORE_COLUMNS, true)) {
                continue;
            }

            $fieldName = $fieldMap[$normalized] ?? $fieldMap[$this->attributeKey($header)] ?? null;
            if (! $fieldName) {
                if (($row[$index] ?? null) !== null && ($row[$index] ?? '') !== '') {
                    $ignoredColumns[] = $header;
                }
                continue;
            }

            $value = $row[$index] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $attributes[$fieldName] = $value;
        }

        return [$attributes, $ignoredColumns];
    }

    private function categoryFieldMap(Category $category): array
    {
        $map = [];

        foreach ($category->fields ?? [] as $field) {
            $name = $field['name'] ?? null;
            if (! $name) {
                continue;
            }

            $map[$this->normalizeHeader($name)] = $name;
            if (! empty($field['label'])) {
                $map[$this->normalizeHeader($field['label'])] = $name;
            }
        }

        return $map;
    }

    private function resolveSupplierId(mixed $value): ?int
    {
        if ($value !== null && $value !== '') {
            if (is_numeric($value) && Supplier::whereKey((int) $value)->exists()) {
                return (int) $value;
            }

            $supplier = Supplier::whereRaw('LOWER(Supplier_Name) = ?', [Str::lower((string) $value)])->first();
            if ($supplier) {
                return $supplier->id;
            }
        }

        return Supplier::query()->value('id');
    }

    private function resolveStatusId(mixed $value): ?int
    {
        if ($value !== null && $value !== '') {
            if (is_numeric($value) && Status::whereKey((int) $value)->exists()) {
                return (int) $value;
            }

            $status = Status::whereRaw('LOWER(Status_Name) = ?', [Str::lower((string) $value)])->first();
            if ($status) {
                return $status->id;
            }
        }

        return Status::whereIn('Status_Name', ['Ready to Deploy', 'Available'])->value('id') ?: Status::query()->value('id');
    }

    private function resolveLocationId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && Location::whereKey((int) $value)->exists()) {
            return (int) $value;
        }

        return Location::whereRaw('LOWER(name) = ?', [Str::lower((string) $value)])->value('id');
    }

    private function firstValue(array $row, array $normalizedHeaders, array $candidates): mixed
    {
        $index = $this->findHeaderIndex($normalizedHeaders, $candidates);

        return $index === null ? null : ($row[$index] ?? null);
    }

    private function findHeaderIndex(array $normalizedHeaders, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $normalizedHeaders, true);
            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    private function attributeKey(string $header): string
    {
        return Str::slug($header, '_') ?: 'attribute';
    }

    private function normalizeMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^\d.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function padRow(array $row, int $length): array
    {
        $padded = [];

        for ($i = 0; $i < $length; $i++) {
            $padded[$i] = $row[$i] ?? null;
        }

        return $padded;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
