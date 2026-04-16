<?php

namespace App\Http\Controllers;

use App\Models\ScheduledPost;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PostController extends Controller
{
    public function store(Request $request)
    {
        //   dd($request->all()); 
        $request->validate([
            'page_name'    => 'required|string',
            'content'      => 'required|string',
            'scheduled_at' => 'nullable|date', 
        ]);

        $page = auth()->user()->pages()
            ->where('page_name', 'LIKE', '%' . trim($request->page_name) . '%')
            ->first();

        if (!$page) {
            return back()
                ->withInput()
                ->withErrors(['page_name' => 'الصفحة غير موجودة، تأ']);
        }

        $post = ScheduledPost::create([
            'user_id'          => auth()->id(),
            'facebook_page_id' => $page->id,
            'content'          => $request->content,
            'scheduled_at'     => $request->scheduled_at ?? now(),
            'status'           => 'pending',
        ]);

        return back()->with('success', "✅ تم جدولة المنشور  بنجاح!");
    }
}
