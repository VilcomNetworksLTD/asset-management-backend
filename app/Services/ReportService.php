<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\ActivityLog;
use App\Models\Asset;

use App\Models\Consumable;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Compiles high-level statistics for the ReportsList view.
     */
    public function getInventorySummary(): array
    {
        return [
            'total_assets' => Asset::count(),
            'total_users' => User::count(),
            'total_accessories' => Accessory::count(),
            'total_consumables' => Consumable::count(),
            'total_licenses' => License::count(),
            'pending_maintenance' => Maintenance::whereNull('Completion_Date')->count(),
            'total_cost' => $this->calculateTotalInvestment(),
        ];
    }

    public function getReportHistory(int $limit = 20)
    {
        return Report::query()
            ->latest()
            ->take(max(1, min(100, $limit)))
            ->get(['id', 'title', 'type', 'category', 'file_path', 'generated_by', 'created_at']);
    }

    public function generateReport(string $category): Report
    {
        $normalized = strtolower(trim($category));
        // Requirement 10: Correcting the time using App Timezone
        $timestamp = Carbon::now(config('app.timezone', 'UTC'))->format('Y-m-d H:i');

        [$title, $payload] = match ($normalized) {
            'inventory', 'asset inventory' => [
                'Asset Inventory Report - ' . $timestamp,
                $this->generateInventoryPayload(),
            ],
            'maintenance', 'maintenance summary' => [
                'Maintenance Summary Report - ' . $timestamp,
                $this->generateMaintenancePayload(),
            ],
            'assignments', 'user assignments' => [
                'User Assignments Report - ' . $timestamp,
                $this->generateAssignmentPayload(),
            ],
            'consumables' => [
                'Consumables Report - ' . $timestamp,
                $this->generateConsumablesPayload(),
            ],
            'accessories' => [
                'Accessories Report - ' . $timestamp,
                $this->generateAccessoriesPayload(),
            ],

            'licenses', 'licences' => [
                'Licenses Report - ' . $timestamp,
                $this->generateLicensesPayload(),
            ],
            'people', 'users' => [
                'People Report - ' . $timestamp,
                $this->generatePeoplePayload(),
            ],
            'activity logs', 'activity_logs', 'logs', 'audit logs' => [
                'Activity Logs Report - ' . $timestamp,
                $this->generateActivityLogsPayload(),
            ],
            default => [
                'System Snapshot Report - ' . $timestamp,
                [
                    'generated_at' => $timestamp,
                    'summary' => $this->getInventorySummary(),
                ],
            ],
        };

        $filename = 'reports/' . Carbon::now(config('app.timezone', 'UTC'))->format('Ymd_His') . '_' . str_replace(' ', '_', strtolower($normalized)) . '.csv';
        Storage::disk('local')->put($filename, $this->payloadToCsv($normalized, $payload));

        $report = Report::create([
            'title' => $title,
            'type' => 'CSV',
            'category' => ucfirst($normalized),
            'file_path' => $filename,
            'generated_by' => Auth::user()->name ?? 'System',
        ]);

        return $report;
    }

    /**
     * Calculates the total financial investment in the system.
     */
    private function calculateTotalInvestment(): float
    {
        $assetCosts = Asset::sum('Price');
        $licenseCosts = License::sum('price');
        $maintenanceCosts = Maintenance::sum('Cost');

        return (float) ($assetCosts + $licenseCosts + $maintenanceCosts);
    }

    private function generateInventoryPayload(): array
    {
        return [
            'generated_at' => Carbon::now(config('app.timezone', 'UTC'))->toDateTimeString(),
            'summary' => [
                'total_assets' => Asset::count(),
                'by_status' => Asset::query()
                    ->selectRaw('Status_ID, COUNT(*) as total')
                    ->groupBy('Status_ID')
                    ->get(),
            ],
            'items' => Asset::with(['status', 'user'])
                ->get(['id', 'Asset_Name', 'Asset_Category', 'Serial_No', 'Employee_ID', 'Status_ID', 'Price', 'Purchase_Date']),
        ];
    }

    private function generateMaintenancePayload(): array
    {
        return [
            'generated_at' => now()->toDateTimeString(),
            'summary' => [
                'pending_maintenance' => Maintenance::whereNull('Completion_Date')->count(),
                'completed_maintenance' => Maintenance::whereNotNull('Completion_Date')->count(),
                'total_cost' => (float) Maintenance::sum('Cost'),
            ],
            'items' => Maintenance::with(['asset', 'status'])
                ->latest()
                ->get(['id', 'Asset_ID', 'Maintenance_Type', 'Request_Date', 'Completion_Date', 'Cost', 'Status_ID']),
        ];
    }

    private function generateAssignmentPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'assigned_assets' => Asset::whereNotNull('Employee_ID')->count(),
                'users_with_assets' => User::whereHas('assets')->count(),
            ],
            'items' => Asset::with(['user', 'status'])
                ->whereNotNull('Employee_ID')
                ->get(['id', 'Asset_Name', 'Serial_No', 'Employee_ID', 'Status_ID']),
        ];
    }

    private function generateConsumablesPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_consumables' => Consumable::count(),
                'total_in_stock' => (int) Consumable::sum('in_stock'),
                'total_value' => (float) Consumable::selectRaw('SUM(in_stock * price) as total')->value('total'),
            ],
            'items' => Consumable::query()->get(['id', 'item_name', 'category', 'in_stock', 'price', 'min_amt']),
        ];
    }

    private function generateAccessoriesPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_accessories' => Accessory::count(),
                'total_remaining_qty' => (int) Accessory::sum('remaining_qty'),
                'total_value' => (float) Accessory::selectRaw('SUM(remaining_qty * price) as total')->value('total'),
            ],
            'items' => Accessory::query()->get(['id', 'name', 'category', 'model_number', 'total_qty', 'remaining_qty', 'price']),
        ];
    }



    private function generateLicensesPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_licenses' => License::count(),
                'total_remaining_seats' => (int) License::sum('remaining_seats'),
                'total_value' => (float) License::selectRaw('SUM(remaining_seats * price) as total')->value('total'),
            ],
            'items' => License::query()->get(['id', 'name', 'product_key', 'manufacturer', 'total_seats', 'remaining_seats', 'price']),
        ];
    }

    private function generatePeoplePayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_people' => User::count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_users' => User::where('role', 'user')->count(),
            ],
            'items' => User::query()->get(['id', 'name', 'email', 'role', 'is_verified', 'created_at']),
        ];
    }

    private function generateActivityLogsPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_logs' => ActivityLog::count(),
            ],
            'items' => ActivityLog::query()->latest()->get([
                'id',
                'Employee_ID',
                'user_name',
                'action',
                'target_type',
                'target_name',
                'details',
                'created_at',
            ]),
        ];
    }

    private function payloadToCsv(string $category, array $payload): string
    {
        return match ($category) {
            'inventory' => $this->formatInventoryCsv($payload),
            'maintenance' => $this->formatMaintenanceCsv($payload),
            'assignments' => $this->formatAssignmentsCsv($payload),
            'consumables' => $this->formatConsumablesCsv($payload),
            'accessories' => $this->formatAccessoriesCsv($payload),
            'licenses', 'licences' => $this->formatLicensesCsv($payload),
            'people', 'users' => $this->formatPeopleCsv($payload),
            'activity logs', 'activity_logs', 'logs', 'audit logs' => $this->formatLogsCsv($payload),
            default => $this->formatDefaultCsv($payload),
        };
    }

    private function formatInventoryCsv(array $payload): string
    {
        $headers = ['generated_at', 'asset_id', 'asset_name', 'category', 'serial_no', 'employee_id', 'status_id', 'price'];
        $rows = array_map(fn($item) => [
            $payload['generated_at'] ?? '',
            $item['id'] ?? '',
            $item['Asset_Name'] ?? '',
            $item['Asset_Category'] ?? '',
            $item['Serial_No'] ?? '',
            $item['Employee_ID'] ?? '',
            $item['Status_ID'] ?? '',
            $item['Price'] ?? '',
        ], $payload['items'] ?? []);

        return $this->generateCsvContent($headers, $rows);
    }

    private function formatMaintenanceCsv(array $payload): string
    {
        $headers = ['generated_at', 'maintenance_id', 'asset_id', 'type', 'request_date', 'completion_date', 'cost', 'status_id'];
        $rows = array_map(fn($item) => [
            $payload['generated_at'] ?? '',
            $item['id'] ?? '',
            $item['Asset_ID'] ?? '',
            $item['Maintenance_Type'] ?? '',
            $item['Request_Date'] ?? '',
            $item['Completion_Date'] ?? '',
            $item['Cost'] ?? '',
            $item['Status_ID'] ?? '',
        ], $payload['items'] ?? []);

        return $this->generateCsvContent($headers, $rows);
    }

    private function formatConsumablesCsv(array $payload): string
    {
        $headers = ['generated_at', 'consumable_id', 'item_name', 'category', 'in_stock', 'price', 'min_amt'];
        $rows = array_map(fn($item) => [
            $payload['generated_at'] ?? '',
            $item['id'] ?? '',
            $item['item_name'] ?? '',
            $item['category'] ?? '',
            $item['in_stock'] ?? '',
            $item['price'] ?? '',
            $item['min_amt'] ?? '',
        ], $payload['items'] ?? []);

        return $this->generateCsvContent($headers, $rows);
    }

    private function formatDefaultCsv(array $payload): string
    {
        $headers = ['generated_at', 'metric', 'value'];
        $rows = [];
        foreach (($payload['summary'] ?? []) as $metric => $value) {
            $rows[] = [
                $payload['generated_at'] ?? now()->toDateTimeString(),
                (string) $metric,
                is_scalar($value) ? (string) $value : json_encode($value),
            ];
        }
        return $this->generateCsvContent($headers, $rows);
    }

    private function generateCsvContent(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headers);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);
        return $csv;
    }
}