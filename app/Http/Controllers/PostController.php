<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\ScheduledPost;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
class PostController extends Controller
{
    // public function store(StorePostRequest $request)
    // {
    //     $user = auth()->user();
    //     if (!$user->canSchedulePost()) {
    //         return response()->json([
    //             'status' => 'error', 
    //             'message' => 'لقد وصلت للحد الأقصى'
    //         ], 403);
    //     }

    //     try {
    //        
    //         $post = ScheduledPost::create([
    //             'user_id'          => $user->id,
    //             'content'          => $request->content,
    //             'scheduled_at'     => Carbon::parse($request->scheduled_at),
    //             'status'           => 'pending',
    //             'facebook_page_id' => $request->facebook_page_id,
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'تمت جدولة المنشور بنجاح!',
    //             'data' => $post
    //         ]);

    //     } catch (\Exception $e) {
    //         // في حال فشل التخزين (مثلاً نقص حقل في الداتابيز)
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'حدث خطأ تقني أثناء التخزين: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

//    public function store(Request $request)
// {

// // dd($request->all());
//  
//     $pageName = trim($request->page_name);
//     $page = \App\Models\FacebookPage::where('page_name', $pageName)->first();

//     if (!$page) {
//        
//         return back()->with('error', "خطأ: لم نجد صفحة مربوطة باسم [{$pageName}].");
//     }

//   
//     $post = new \App\Models\ScheduledPost();
//     $post->user_id = auth()->id();
//     $post->facebook_page_id = $page->id;
//     $post->content = $request->content;
//     $post->scheduled_at = $request->scheduled_at ?? now();
//     $post->status = 'pending';
//     $post->save();

//     return redirect()->route('dashboard')->with('success', 'تم حفظ المنشور رقم 10 بنجاح!');
// }


public function store(Request $request)
{

    $page = \App\Models\FacebookPage::where('page_name', 'LIKE', '%' . trim($request->page_name) . '%')->first();

    
    if (!$page) {
        return "لا يوجد باسم " . $request->page_name . ". الصفحات المتاحة " . \App\Models\FacebookPage::pluck('page_name')->implode(', ');
    }

   
    $post = new \App\Models\ScheduledPost();
    $post->user_id = auth()->id() ?? 1; 
    $post->facebook_page_id = $page->id;
    $post->content = $request->content;
    $post->scheduled_at = $request->scheduled_at ?? now();
    $post->status = 'pending';
    
    if ($post->save()) {
       return back()->with('success','تم حفظ المنشور رقم المنشور هو');
    }

    return "فشل.";
}
}