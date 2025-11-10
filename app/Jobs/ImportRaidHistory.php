<?php

namespace App\Jobs;

use App\Services\RaidHistoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for importing raid history asynchronously
 */
class ImportRaidHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public int $backoff = 30;

    /**
     * The maximum number of seconds the job can run before timing out
     */
    public int $timeout = 120;

    /**
     * Create a new job instance
     */
    public function __construct(
        public array $raidHistory
    ) {}

    /**
     * Execute the job
     */
    public function handle(RaidHistoryService $service): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting raid history import job', [
                'records' => count($this->raidHistory),
                'attempt' => $this->attempts(),
            ]);

            $result = $service->importRaidHistory($this->raidHistory);

            $duration = (microtime(true) - $startTime) * 1000;

            // Log warning if nothing was imported/updated, otherwise log as info
            if ($result['imported'] === 0 && $result['updated'] === 0) {
                Log::warning('Raid history import job completed with no changes', [
                    'records' => count($this->raidHistory),
                    'imported' => $result['imported'],
                    'updated' => $result['updated'],
                    'valid' => $result['valid'] ?? 0,
                    'duration_ms' => round($duration, 2),
                    'status' => $result['status'] ?? 'unknown',
                ]);
            } else {
                Log::info('Raid history import job completed', [
                    'records' => count($this->raidHistory),
                    'imported' => $result['imported'],
                    'updated' => $result['updated'],
                    'duration_ms' => round($duration, 2),
                ]);
            }
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('Raid history import job failed', [
                'records' => count($this->raidHistory),
                'attempt' => $this->attempts(),
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Release the job back to the queue with exponential backoff
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff * $this->attempts());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Raid history import job permanently failed', [
            'records' => count($this->raidHistory),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
