<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish';
    protected $description = 'نشر المنشورات ';

    public function handle()
    {
        $this->info(' Check post...');
        $now = now();
        $this->line("time server: {$now}");

        $posts = ScheduledPost::where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->with(['facebookPage'])
            ->get();

        $this->info("Post ready : {$posts->count()}");

        if ($posts->isEmpty()) {
            $this->warn(' Not found post  .');
            return;
        }

        foreach ($posts as $post) {
            $this->line('');
            $this->info("  check #{$post->id}");
            $this->publishPost($post);
        }

        $this->info(' end check.');
    }

    private function publishPost(ScheduledPost $post)
    {
        try {
            $page = $post->facebookPage;

            if (!$page || empty($page->page_id)) {
                throw new \Exception('Not found page id ');
            }

            $accessToken = $page->access_token;

            if (empty($accessToken)) {
                throw new \Exception('not found token ');
            }

            $this->warn("Token Check: " . substr($accessToken, 0, 10) . "...");

            $endpoint = "/{$page->page_id}/feed";
            $data = [
                'message'      => $post->content,
                'access_token' => $accessToken,
            ];

    
            // if ($post->media_url) {
            //     if ($post->media_type === 'image') {
            //         $data['url'] = $post->media_url;
            //         $endpoint = "/{$page->page_id}/photos";
            //     } elseif ($post->media_type === 'video') {
            //         $data['file_url'] = $post->media_url;
            //         $endpoint = "/{$page->page_id}/videos";
            //     }
            // }

            $url = "https://graph.facebook.com/v18.0" . $endpoint;
            $this->line("ٌRequest to Facebook...");

            $response = Http::timeout(60)->post($url, $data);

            if ($response->successful()) {
                $result = $response->json();
                $fbId = $result['id'] ?? $result['post_id'] ?? null;
                
                $post->update([
                    'status'       => 'published',
                    'published_at' => now(),
                    'fb_post_id'   => $fbId,
                ]);

                $this->info(" Sucessfull!");
            } else {
                $error = $response->json();
                $errorMsg = $error['error']['message'] ?? 'not found error   ';
                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) {
            $this->error(" falied: {$e->getMessage()}");
            
            $post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}