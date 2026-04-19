<?php

namespace App\Http\Controllers;

use App\Contracts\SocialMediaProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FacebookController extends Controller
{
    protected $socialService;

    public function __construct(SocialMediaProvider $socialService)
    {
        $this->socialService = $socialService;
    }

    public function redirect()
    {
        return redirect()->away($this->socialService->getAuthUrl());
    }

    public function callback(Request $request)
    {

        if ($request->has('error')) {
            Log::warning('Facebook authorization denied', [
                'user_id' => Auth::id(),
                'error' => $request->get('error_description', $request->get('error'))
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Facebook authorization was denied.');
        }

  
        if (!$request->has('code')) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid authorization response.');
        }

        try {
           
            $tokenData = $this->socialService->getAccessToken($request->code);

            if (empty($tokenData['access_token'])) {
                throw new \Exception('Failed to obtain access token from Facebook.');
            }

            $shortToken = $tokenData['access_token'];

  
            $longLivedToken = $this->socialService->getLongLivedToken($shortToken);
            $userToken = $longLivedToken['access_token'];


            DB::beginTransaction();

            try {
             
                $account = Auth::user()->facebookAccounts()->updateOrCreate(
                    ['facebook_id' => $tokenData['user_id'] ?? null],
                    [
                        'name'             => $tokenData['name'] ?? 'Facebook User',
                        'access_token'     => encrypt($userToken), 
                        'token_expires_at' => $longLivedToken['expires_at'],
                    ]
                );

                $pages = $this->socialService->getUserPages($userToken);

                if (empty($pages)) {
                    DB::commit(); 
                    
                    return redirect()->route('dashboard')
                        ->with('warning', 'Facebook account connected, but no pages were found.');
                }

                $linkedCount = 0;
                $user = Auth::user();

 
                foreach ($pages as $pageData) {
                    if (empty($pageData['id']) || empty($pageData['access_token'])) {
                        Log::warning('Skipping page with missing data', [
                            'page_data' => $pageData
                        ]);
                        continue;
                    }

                    \App\Models\FacebookPage::updateOrCreate(
                        [
                            'page_id' => (string) $pageData['id'],
                            'user_id' => $user->id,
                        ],
                        [
                            'page_name'           => $pageData['name'],
                            'facebook_account_id' => $account->id,
                            'access_token'        => encrypt($pageData['access_token']),
                            'token_expires_at'    => now()->addDays(60),
                            'is_active'           => true,
                        ]
                    );

                    $linkedCount++;
                }

                DB::commit();

                Log::info('Facebook pages connected successfully', [
                    'user_id' => $user->id,
                    'pages_count' => $linkedCount
                ]);

            
                return redirect()->route('dashboard')
                    ->with('success', "{$linkedCount} Facebook page connected successfully.");

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
      
            Log::error('Facebook API error', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'code'    => $e->getCode()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Facebook API error. Please try again.');

        } catch (\Exception $e) {
          
            Log::error('Facebook callback error', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', ' error while connecting to Facebook. Please try again.');
        }
    }
}