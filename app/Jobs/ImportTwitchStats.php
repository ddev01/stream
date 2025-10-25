<?php

namespace App\Jobs;

use App\Services\TwitchStatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for importing Twitch stats asynchronously
 */
class ImportTwitchStats implements ShouldQueue
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
        public array $stats
    ) {}

    /**
     * Execute the job
     */
    public function handle(TwitchStatsService $statsService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting Twitch stats import job', [
                'records' => count($this->stats),
                'attempt' => $this->attempts(),
            ]);

            $result = $statsService->importStats($this->stats);

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('Twitch stats import job completed', [
                'records' => count($this->stats),
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('Twitch stats import job failed', [
                'records' => count($this->stats),
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
        Log::error('Twitch stats import job permanently failed', [
            'records' => count($this->stats),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
