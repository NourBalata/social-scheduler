<?php

namespace App\Console\Commands;

use App\Models\FacebookPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
class CheckFacebookTokens extends Command
{
    protected $signature = 'facebook:check-tokens';
    protected $description = 'Check tokens status';

    public function handle()
    {
        $this->info('check token!');
        
        $pages = FacebookPage::with('user')->get();
        
        if ($pages->isEmpty()) {
            $this->warn('Not Found pages ');
            return;
        }

        foreach ($pages as $page) {
            $this->line('');
            $this->info("page: {$page->page_name} (ID: {$page->id})");
            $this->line("users: {$page->user->name} ({$page->user->email})");
            
            if (empty($page->access_token)) {
                $this->error("Not Found Token!");
            } else {
                $tokenPreview = substr($page->access_token, 0, 20) . '...';
                $this->info("Token : {$tokenPreview}");
                $this->checkTokenValidity($page);
            }
        }
    }

    private function checkTokenValidity($page)
    {
        try {
            $response = \Http::get("https://graph.facebook.com/v18.0/me", [
                'access_token' => $page->access_token
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->info("Token usefull");
            } else {
                $error = $response->json();
                $this->error("unusefull: " . ($error['error']['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
        }
    }
}