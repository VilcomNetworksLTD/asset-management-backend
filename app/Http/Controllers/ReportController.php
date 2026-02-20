<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Fetch the summary using the Service.
     */
    public function index(): JsonResponse
    {
        $data = $this->reportService->getInventorySummary();
        return response()->json($data);
    }

    public function history(): JsonResponse
    {
        return response()->json($this->reportService->getReportHistory());
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => 'required|string|max:100',
        ]);

        $report = $this->reportService->generateReport($data['category']);

        return response()->json([
            'message' => 'Report generated successfully.',
            'report' => $report,
        ], 201);
    }

    public function download(int $id): Response|StreamedResponse|JsonResponse
    {
        $report = Report::findOrFail($id);
        $safeTitle = preg_replace('/[^A-Za-z0-9\-_]+/', '_', $report->title ?? 'report');
        $isCsv = strtoupper((string) $report->type) === 'CSV';
        $filename = trim($safeTitle, '_') . ($isCsv ? '.csv' : '.json');

        // Backward compatibility: older rows may store JSON directly in file_path.
        if (is_string($report->file_path)) {
            $trimmed = ltrim($report->file_path);
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                return response($report->file_path, 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }
        }

        if (is_string($report->file_path) && Storage::disk('local')->exists($report->file_path)) {
            $fullPath = Storage::disk('local')->path($report->file_path);
            return response()->download($fullPath, $filename, [
                'Content-Type' => $isCsv ? 'text/csv; charset=UTF-8' : 'application/json',
            ]);
        }

        // Fallback: always return downloadable metadata if historical file is missing.
        if ($isCsv) {
            $stream = fopen('php://temp', 'r+');
            fputcsv($stream, ['id', 'title', 'type', 'category', 'generated_by', 'created_at', 'note']);
            fputcsv($stream, [
                $report->id,
                $report->title,
                $report->type,
                $report->category,
                $report->generated_by,
                $report->created_at,
                'Original report file missing; metadata fallback exported.',
            ]);
            rewind($stream);
            $csv = stream_get_contents($stream) ?: '';
            fclose($stream);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        return response()->json([
            'id' => $report->id,
            'title' => $report->title,
            'type' => $report->type,
            'category' => $report->category,
            'generated_by' => $report->generated_by,
            'created_at' => $report->created_at,
            'note' => 'Original report file missing; metadata fallback exported.',
        ], 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}