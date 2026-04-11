<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\PublishScheduledPosts;

use App\Models\ScheduledPost;
use App\Jobs\PublishPostJob;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {

    $posts = ScheduledPost::ready()->get();

    foreach ($posts as $post) {
    
        dispatch(new PublishPostJob($post));
        
        $post->update(['status' => 'processing']);
    }

})->everyMinute();

Schedule::command("posts:publish")->everyMinute();