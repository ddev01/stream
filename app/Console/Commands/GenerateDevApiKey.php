<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateDevApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:api-key {--show : Show the current API key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or show the development API key for external applications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $envFile = base_path('.env');
        $devApiKey = config('app.dev_api_key');

        if ($this->option('show')) {
            if ($devApiKey) {
                $this->info("Current DEV_API_KEY: {$devApiKey}");
            } else {
                $this->warn('No DEV_API_KEY found in .env file');
            }

            return;
        }

        // Generate a new API key
        $newApiKey = 'dev_'.Str::random(40);

        if (! file_exists($envFile)) {
            $this->error('.env file not found. Please create one first.');

            return;
        }

        $envContent = file_get_contents($envFile);

        if (strpos($envContent, 'DEV_API_KEY=') !== false) {
            // Update existing key
            $envContent = preg_replace('/^DEV_API_KEY=.*$/m', "DEV_API_KEY={$newApiKey}", $envContent);
        } else {
            // Add new key
            $envContent .= "\n# Development API Key\nDEV_API_KEY={$newApiKey}\n";
        }

        file_put_contents($envFile, $envContent);

        $this->info('Development API key generated successfully!');
        $this->line("DEV_API_KEY={$newApiKey}");
        $this->line('');
        $this->line('Add this to your C# application:');
        $this->line("CPH.SetGlobalVar(\"laravelApiKey\", \"{$newApiKey}\", true);");
    }
}
