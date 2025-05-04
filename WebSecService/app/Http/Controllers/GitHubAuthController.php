<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;

class GitHubAuthController extends Controller
{
    // Redirect to GitHub for authentication
    public function redirectToGitHub()
    {
        try {
            // Hard-coded GitHub OAuth credentials since env variables might not be working
            $githubClientId = 'Ov23lieBKhiY05fDG0Qf';
            $redirectUri = url('auth/github/callback');
            
            $state = Str::random(40);
            session(['github_oauth_state' => $state]);
            
            $queryParams = http_build_query([
                'client_id' => $githubClientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'user:email',
                'state' => $state,
            ]);
            
            return redirect('https://github.com/login/oauth/authorize?' . $queryParams);
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Failed to initialize GitHub login: ' . $e->getMessage());
        }
    }
    
    // Handle the callback from GitHub
    public function handleGitHubCallback(Request $request)
    {
        try {
            // Verify state to prevent CSRF
            if ($request->state !== session('github_oauth_state')) {
                return redirect()->route('login')->with('error', 'Invalid state parameter. Authentication failed.');
            }
            
            // Hard-coded GitHub OAuth credentials
            $githubClientId = 'Ov23lieBKhiY05fDG0Qf';
            $githubClientSecret = 'f2ba2f73d47ca243012507678ce79e95b2305388';
            
            // Exchange authorization code for access token
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post('https://github.com/login/oauth/access_token', [
                'client_id' => $githubClientId,
                'client_secret' => $githubClientSecret,
                'code' => $request->code,
                'redirect_uri' => url('auth/github/callback'),
            ]);
            
            $tokenData = $response->json();
            
            if (!isset($tokenData['access_token'])) {
                return redirect()->route('login')->with('error', 'Failed to get access token from GitHub.');
            }
            
            $accessToken = $tokenData['access_token'];
            
            // Get user information with the access token
            $userResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get('https://api.github.com/user');
            
            if ($userResponse->failed()) {
                return redirect()->route('login')->with('error', 'Failed to get user data from GitHub.');
            }
            
            $githubUser = $userResponse->json();
            
            // Get user's email if it's not included in the profile
            if (empty($githubUser['email'])) {
                $emailResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ])->get('https://api.github.com/user/emails');
                
                if ($emailResponse->successful()) {
                    $emails = $emailResponse->json();
                    foreach ($emails as $email) {
                        if ($email['primary'] && $email['verified']) {
                            $githubUser['email'] = $email['email'];
                            break;
                        }
                    }
                }
                
                // If still no email, use a placeholder with GitHub username
                if (empty($githubUser['email']) && isset($githubUser['login'])) {
                    $githubUser['email'] = $githubUser['login'] . '@github.com';
                }
            }
            
            if (empty($githubUser['email'])) {
                return redirect()->route('login')->with('error', 'Could not retrieve email from GitHub account.');
            }
            
            // Database transaction to ensure data consistency
            return DB::transaction(function () use ($githubUser) {
                // Check if user exists by GitHub ID
                $user = User::where('provider', 'github')
                    ->where('provider_id', (string)$githubUser['id'])
                    ->first();
                
                // If not found by GitHub ID, check email
                if (!$user) {
                    $user = User::where('email', $githubUser['email'])->first();
                }
                
                // For new users
                if (!$user) {
                    // Create a new user
                    $user = new User();
                    $user->name = $githubUser['name'] ?? $githubUser['login'];
                    $user->email = $githubUser['email'];
                    $user->provider = 'github';
                    $user->provider_id = (string)$githubUser['id'];
                    $user->avatar = $githubUser['avatar_url'] ?? null;
                    $user->password = bcrypt(Str::random(24));
                    $user->save();
                    
                    // Ensure customer role exists
                    $customerRole = Role::firstOrCreate(['name' => 'customer']);
                    
                    // Assign customer role
                    $user->assignRole('customer');
                    
                    // Force role assignment as backup
                    if (!$user->hasRole('customer')) {
                        DB::table('model_has_roles')->insert([
                            'role_id' => $customerRole->id,
                            'model_type' => User::class,
                            'model_id' => $user->id
                        ]);
                    }
                    
                    // Login the new user
                    Auth::login($user);
                    
                    // Redirect to products page with success message
                    return redirect()->route('products_list')
                        ->with('success', 'Your account has been created successfully! Welcome to our store.');
                } else {
                    // Update existing user with GitHub info if needed
                    if ($user->provider !== 'github' || $user->provider_id !== (string)$githubUser['id']) {
                        $user->provider = 'github';
                        $user->provider_id = (string)$githubUser['id'];
                        if (!$user->avatar && isset($githubUser['avatar_url'])) {
                            $user->avatar = $githubUser['avatar_url'];
                        }
                        $user->save();
                    }
                    
                    // Login existing user
                    Auth::login($user);
                    
                    // Redirect to products page with welcome back message
                    return redirect()->route('products_list')
                        ->with('success', 'Welcome back! You\'ve successfully logged in with GitHub.');
                }
            });
        } catch (\Exception $e) {
            // Log the error with full details
            if (method_exists(Log::class, 'error')) {
                Log::error('GitHub authentication error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // Redirect with error message
            return redirect()->route('login')
                ->with('error', 'GitHub authentication failed: ' . $e->getMessage());
        }
    }
} 