<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\Component;
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

        [$title, $payload] = match ($normalized) {
            'inventory', 'asset inventory' => [
                'Asset Inventory Report - ' . now()->format('Y-m-d H:i'),
                $this->generateInventoryPayload(),
            ],
            'maintenance', 'maintenance summary' => [
                'Maintenance Summary Report - ' . now()->format('Y-m-d H:i'),
                $this->generateMaintenancePayload(),
            ],
            'assignments', 'user assignments' => [
                'User Assignments Report - ' . now()->format('Y-m-d H:i'),
                $this->generateAssignmentPayload(),
            ],
            'consumables' => [
                'Consumables Report - ' . now()->format('Y-m-d H:i'),
                $this->generateConsumablesPayload(),
            ],
            'accessories' => [
                'Accessories Report - ' . now()->format('Y-m-d H:i'),
                $this->generateAccessoriesPayload(),
            ],
            'components' => [
                'Components Report - ' . now()->format('Y-m-d H:i'),
                $this->generateComponentsPayload(),
            ],
            'licenses', 'licences' => [
                'Licenses Report - ' . now()->format('Y-m-d H:i'),
                $this->generateLicensesPayload(),
            ],
            'people', 'users' => [
                'People Report - ' . now()->format('Y-m-d H:i'),
                $this->generatePeoplePayload(),
            ],
            'activity logs', 'activity_logs', 'logs', 'audit logs' => [
                'Activity Logs Report - ' . now()->format('Y-m-d H:i'),
                $this->generateActivityLogsPayload(),
            ],
            default => [
                'System Snapshot Report - ' . now()->format('Y-m-d H:i'),
                [
                    'generated_at' => Carbon::now()->toDateTimeString(),
                    'summary' => $this->getInventorySummary(),
                ],
            ],
        };

        $filename = 'reports/' . now()->format('Ymd_His') . '_' . str_replace(' ', '_', strtolower($normalized)) . '.csv';
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
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_assets' => Asset::count(),
                'by_status' => Asset::query()
                    ->selectRaw('Status_ID, COUNT(*) as total')
                    ->groupBy('Status_ID')
                    ->get(),
            ],
            'items' => Asset::with(['status', 'user'])
                ->get(['id', 'Asset_Name', 'Asset_Category', 'Serial_No', 'Employee_ID', 'Status_ID', 'Price']),
        ];
    }

    private function generateMaintenancePayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
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

    private function generateComponentsPayload(): array
    {
        return [
            'generated_at' => Carbon::now()->toDateTimeString(),
            'summary' => [
                'total_components' => Component::count(),
                'total_remaining_qty' => (int) Component::sum('remaining_qty'),
                'total_value' => (float) Component::selectRaw('SUM(remaining_qty * price) as total')->value('total'),
            ],
            'items' => Component::query()->get(['id', 'name', 'category', 'serial_no', 'total_qty', 'remaining_qty', 'price']),
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
        $rows = [];
        $headers = [];

        if ($category === 'inventory') {
            $headers = ['generated_at', 'asset_id', 'asset_name', 'category', 'serial_no', 'employee_id', 'status_id', 'price'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['Asset_Name'] ?? '',
                    $item['Asset_Category'] ?? '',
                    $item['Serial_No'] ?? '',
                    $item['Employee_ID'] ?? '',
                    $item['Status_ID'] ?? '',
                    $item['Price'] ?? '',
                ];
            }
        } elseif ($category === 'maintenance') {
            $headers = ['generated_at', 'maintenance_id', 'asset_id', 'type', 'request_date', 'completion_date', 'cost', 'status_id'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['Asset_ID'] ?? '',
                    $item['Maintenance_Type'] ?? '',
                    $item['Request_Date'] ?? '',
                    $item['Completion_Date'] ?? '',
                    $item['Cost'] ?? '',
                    $item['Status_ID'] ?? '',
                ];
            }
        } elseif ($category === 'assignments') {
            $headers = ['generated_at', 'asset_id', 'asset_name', 'serial_no', 'employee_id', 'status_id'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['Asset_Name'] ?? '',
                    $item['Serial_No'] ?? '',
                    $item['Employee_ID'] ?? '',
                    $item['Status_ID'] ?? '',
                ];
            }
        } elseif ($category === 'consumables') {
            $headers = ['generated_at', 'consumable_id', 'item_name', 'category', 'in_stock', 'price', 'min_amt'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['item_name'] ?? '',
                    $item['category'] ?? '',
                    $item['in_stock'] ?? '',
                    $item['price'] ?? '',
                    $item['min_amt'] ?? '',
                ];
            }
        } elseif ($category === 'accessories') {
            $headers = ['generated_at', 'accessory_id', 'name', 'category', 'model_number', 'total_qty', 'remaining_qty', 'price'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['name'] ?? '',
                    $item['category'] ?? '',
                    $item['model_number'] ?? '',
                    $item['total_qty'] ?? '',
                    $item['remaining_qty'] ?? '',
                    $item['price'] ?? '',
                ];
            }
        } elseif ($category === 'components') {
            $headers = ['generated_at', 'component_id', 'name', 'category', 'serial_no', 'total_qty', 'remaining_qty', 'price'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['name'] ?? '',
                    $item['category'] ?? '',
                    $item['serial_no'] ?? '',
                    $item['total_qty'] ?? '',
                    $item['remaining_qty'] ?? '',
                    $item['price'] ?? '',
                ];
            }
        } elseif ($category === 'licenses' || $category === 'licences') {
            $headers = ['generated_at', 'license_id', 'name', 'product_key', 'manufacturer', 'total_seats', 'remaining_seats', 'price'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['name'] ?? '',
                    $item['product_key'] ?? '',
                    $item['manufacturer'] ?? '',
                    $item['total_seats'] ?? '',
                    $item['remaining_seats'] ?? '',
                    $item['price'] ?? '',
                ];
            }
        } elseif ($category === 'people' || $category === 'users') {
            $headers = ['generated_at', 'user_id', 'name', 'email', 'role', 'is_verified', 'created_at'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['name'] ?? '',
                    $item['email'] ?? '',
                    $item['role'] ?? '',
                    $item['is_verified'] ?? '',
                    $item['created_at'] ?? '',
                ];
            }
        } elseif ($category === 'activity logs' || $category === 'activity_logs' || $category === 'logs' || $category === 'audit logs') {
            $headers = ['generated_at', 'log_id', 'employee_id', 'user_name', 'action', 'target_type', 'target_name', 'details', 'created_at'];
            foreach (($payload['items'] ?? []) as $item) {
                $rows[] = [
                    $payload['generated_at'] ?? '',
                    $item['id'] ?? '',
                    $item['Employee_ID'] ?? '',
                    $item['user_name'] ?? '',
                    $item['action'] ?? '',
                    $item['target_type'] ?? '',
                    $item['target_name'] ?? '',
                    $item['details'] ?? '',
                    $item['created_at'] ?? '',
                ];
            }
        } else {
            $headers = ['generated_at', 'metric', 'value'];
            foreach (($payload['summary'] ?? []) as $metric => $value) {
                $rows[] = [
                    $payload['generated_at'] ?? now()->toDateTimeString(),
                    (string) $metric,
                    is_scalar($value) ? (string) $value : json_encode($value),
                ];
            }
        }

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