<?php

namespace App\Console\Commands;

use App\Models\SharedFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Prune expired shared files by deleting both the physical file and database record
 */
class PruneExpiredSharedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shared-files:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired shared files and their database records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting to prune expired shared files...');

        $deletedCount = 0;
        $errorCount = 0;

        SharedFile::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(500, function ($files) use (&$deletedCount, &$errorCount): void {
                foreach ($files as $file) {
                    try {
                        // Attempt to delete the physical file
                        if (Storage::disk('local')->exists($file->file_path)) {
                            Storage::disk('local')->delete($file->file_path);
                        }

                        // Delete the database record
                        $file->delete();

                        $deletedCount++;
                    } catch (\Exception $e) {
                        $this->error("Failed to delete file {$file->id}: {$e->getMessage()}");
                        $errorCount++;
                    }
                }
            });

        $this->info("Pruned {$deletedCount} expired file(s).");

        if ($errorCount > 0) {
            $this->warn("Encountered {$errorCount} error(s) during pruning.");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
