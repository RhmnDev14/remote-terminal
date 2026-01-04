<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateMobileApp extends Command
{
    protected $signature = 'app:generate-mobile {--url= : The production URL for the app}';
    protected $description = 'Generate static HTML for Capacitor mobile app';

    public function handle()
    {
        $url = $this->option('url') ?? config('app.url');
        
        $this->info("Generating mobile app files for URL: {$url}");
        
        // Get the rendered HTML from Laravel
        try {
            $response = Http::get("{$url}/");
            $html = $response->body();
        } catch (\Exception $e) {
            // Fallback: render directly
            $html = view('mobile-app')->render();
        }
        
        // Save to public/index.html
        file_put_contents(public_path('index.html'), $html);
        
        $this->info('✓ Generated public/index.html');
        $this->info('');
        $this->info('Next steps:');
        $this->line('  npx cap sync');
        $this->line('  npx cap open android  # or ios');
        
        return Command::SUCCESS;
    }
}
