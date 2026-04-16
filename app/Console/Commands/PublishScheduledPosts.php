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
        $this->info(' فحص المنشورات...');
        $now = now();
        $this->line(" توقيت السيرفر: {$now}");

        $posts = ScheduledPost::where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->with(['facebookPage'])
            ->get();

        $this->info(" المنشورات الجاهزة لنشر : {$posts->count()}");

        if ($posts->isEmpty()) {
            $this->warn(' لا توجد منشورات لنشر.');
            return;
        }

        foreach ($posts as $post) {
            $this->line('');
            $this->info(" معالجة المنشور #{$post->id}");
            $this->publishPost($post);
        }

        $this->info(' انتهت المعالجة.');
    }

    private function publishPost(ScheduledPost $post)
    {
        try {
            $page = $post->facebookPage;

            if (!$page || empty($page->page_id)) {
                throw new \Exception('لصفحة (Page ID) مفقود ');
            }

            $accessToken = $page->access_token;

            if (empty($accessToken)) {
                throw new \Exception('Access Token مفقود ');
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

                $this->info(" تم النشر بنجاح!");
            } else {
                $error = $response->json();
                $errorMsg = $error['error']['message'] ?? 'خطأ غير معروف  ';
                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) {
            $this->error(" فشل النشر: {$e->getMessage()}");
            
            $post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}