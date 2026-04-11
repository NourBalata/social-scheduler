<?php

namespace App\Observers;

use App\Models\User;
use App\Models\FacebookPage;

class UserObserver
{
    public function created(\App\Models\User $user)
{
    $user->facebookPages()->create([
        'page_name' => "صفحة " . $user->name, 
        'page_id' => 'me', 
        'access_token' => null, 
        'is_active' => false,
    ]);

}
}