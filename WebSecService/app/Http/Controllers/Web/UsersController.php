<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\PasswordReset;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller {

	use ValidatesRequests;

    public function list(Request $request) {
        // Show all users regardless of permissions
        $query = User::select('*');
        $query->when($request->keywords, 
        fn($q)=> $q->where("name", "like", "%$request->keywords%"));
        $users = $query->get();
        return view('users.list', compact('users'));
    }

	public function register(Request $request) {
        return view('users.register');
    }

    public function doRegister(Request $request) {

    	try {
    		$this->validate($request, [
	        'name' => ['required', 'string', 'min:5'],
	        'email' => ['required', 'email', 'unique:users'],
	        'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            'security_question' => ['required'],
            'security_answer' => ['required'],
	    	]);
    	}
    	catch(\Exception $e) {
    		return redirect()->back()->withInput($request->input())->withErrors('Invalid registration information.');
    	}

    	
    	$user =  new User();
	    $user->name = $request->name;
	    $user->email = $request->email;
	    $user->password = bcrypt($request->password); //Secure
        $user->security_question = $request->security_question;
        $user->security_answer = $request->security_answer;
	    $user->save();

        return redirect('/');
    }

    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request) {
    	
    	if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return redirect()->back()->withInput($request->input())->withErrors('Invalid login information.');

        $user = User::where('email', $request->email)->first();
        Auth::setUser($user);
        
        // Check if user is logging in with a temporary password
        if ($user->is_using_temp_password) {
            return redirect()->route('change_temp_password');
        }

        return redirect('/');
    }

    public function doLogout(Request $request) {
    	
    	Auth::logout();

        return redirect('/');
    }

    public function profile(Request $request, User $user = null) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }

        $user = $user??auth()->user();
        
        $permissions = [];
        foreach($user->permissions as $permission) {
            $permissions[] = $permission;
        }
        foreach($user->roles as $role) {
            foreach($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return view('users.profile', compact('user', 'permissions'));
    }

    public function edit(Request $request, User $user = null) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }
   
        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) {
                return redirect()->route('profile');
            }
        }
    
        $roles = [];
        foreach(Role::all() as $role) {
            $role->taken = ($user->hasRole($role->name));
            $roles[] = $role;
        }

        $permissions = [];
        $directPermissionsIds = $user->permissions()->pluck('id')->toArray();
        foreach(Permission::all() as $permission) {
            $permission->taken = in_array($permission->id, $directPermissionsIds);
            $permissions[] = $permission;
        }      

        return view('users.edit', compact('user', 'roles', 'permissions'));
    }

    public function save(Request $request, User $user) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }

        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) {
                return redirect()->route('profile');
            }
        }

        $user->name = $request->name;
        
        if(auth()->check() && auth()->user()->hasPermissionTo('admin_users')) {
            $user->is_admin = $request->has('is_admin') ? 1 : 0;
            $user->syncRoles($request->roles);
            $user->syncPermissions($request->permissions);

            Artisan::call('cache:clear');
        }

        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }

    public function delete(Request $request, User $user) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }

        if(!auth()->user()->hasPermissionTo('delete_users')) {
            return redirect()->route('users');
        }

        $user->delete();

        return redirect()->route('users');
    }

    public function editPassword(Request $request, User $user = null) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) {
                return redirect()->route('profile');
            }
        }

        return view('users.edit_password', compact('user'));
    }

    public function savePassword(Request $request, User $user) {
        // Check if user is logged in
        if(!auth()->check()) {
            return redirect()->route('login');
        }

        if(auth()->id()==$user?->id) {
            
            $this->validate($request, [
                'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            ]);

            if(!Auth::attempt(['email' => $user->email, 'password' => $request->old_password])) {
                
                Auth::logout();
                return redirect('/');
            }
        }
        else if(!auth()->user()->hasPermissionTo('edit_users')) {
            return redirect()->route('profile');
        }

        $user->password = bcrypt($request->password); //Secure
        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }

    public function forgotPassword()
    {
        return view('users.forgot_password');
    }

    public function processForgotPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'This email is not registered in our system.',
        ]);

        $user = User::where('email', $request->email)->first();
        
        // Determine if we use the basic or professional reset method based on config
        // Default to professional if not specified
        $useBasicReset = config('app.use_basic_password_reset', false);
        
        if ($useBasicReset) {
            // Basic password reset (send temporary password)
            $tempPassword = Str::random(10);
            
            // Store the reset record
            PasswordReset::create([
                'email' => $user->email,
                'token' => bcrypt($tempPassword),
                'is_temp_password' => true,
                'created_at' => now(),
                'expires_at' => now()->addDays(1),
            ]);
            
            // Update user to flag they're using temp password
            $user->password = bcrypt($tempPassword);
            $user->is_using_temp_password = true;
            $user->save();
            
            // Send email with temp password
            Mail::send('emails.reset_password_temp', ['user' => $user, 'tempPassword' => $tempPassword], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Your Temporary Password');
            });
            
            return redirect()->route('login')->with('success', 'A temporary password has been sent to your email.');
        } else {
            // Professional password reset (send secure link)
            $token = Str::random(60);
            
            // Delete any existing reset tokens for this email
            PasswordReset::where('email', $user->email)->delete();
            
            // Store the reset record
            PasswordReset::create([
                'email' => $user->email,
                'token' => $token,
                'is_temp_password' => false,
                'created_at' => now(),
                'expires_at' => now()->addHour(),
            ]);
            
            // Generate reset link
            $resetLink = route('reset_password_token', ['token' => $token]) . '?email=' . urlencode($user->email);
            
            // Send email with reset link
            Mail::send('emails.reset_password_link', ['user' => $user, 'resetLink' => $resetLink], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Your Password');
            });
            
            return redirect()->route('login')->with('success', 'A password reset link has been sent to your email.');
        }
    }

    // Original security question based reset (keeping for backward compatibility)
    public function resetPassword(User $user)
    {
        return view('users.security_question', compact('user'));
    }

    public function processResetPassword(Request $request, User $user)
    {
        $this->validate($request, [
            'security_answer' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
        ]);

        // Check if the security answer is correct
        if ($request->security_answer !== $user->security_answer) {
            return redirect()->back()->withErrors(['security_answer' => 'The answer to the security question is incorrect.']);
        }

        // Update the password
        $user->password = bcrypt($request->password);
        $user->save();

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. You can now log in with your new password.');
    }
    
    // Professional password reset methods (with token)
    public function resetPasswordWithToken(Request $request, $token)
    {
        $email = $request->query('email');
        
        // Check if token is valid
        $reset = PasswordReset::where('token', $token)
            ->where('email', $email)
            ->where('is_temp_password', false)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$reset) {
            return redirect()->route('login')->with('error', 'Invalid or expired password reset link.');
        }
        
        return view('users.reset_password', ['token' => $token, 'email' => $email]);
    }
    
    public function processResetPasswordWithToken(Request $request, $token)
    {
        $email = $request->input('email');
        
        $this->validate($request, [
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            'email' => 'required|email|exists:users,email',
        ]);
        
        // Check if token is valid
        $reset = PasswordReset::where('token', $token)
            ->where('email', $email)
            ->where('is_temp_password', false)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$reset) {
            return redirect()->route('login')->with('error', 'Invalid or expired password reset link.');
        }
        
        // Find the user and update password
        $user = User::where('email', $email)->first();
        $user->password = bcrypt($request->password);
        $user->save();
        
        // Delete the token after successful reset
        $reset->delete();
        
        return redirect()->route('login')->with('success', 'Your password has been reset successfully. You can now log in with your new password.');
    }
    
    // Basic password reset methods (temp password)
    public function changeTempPassword()
    {
        // Only allow access if user is using a temp password
        if (!Auth::check() || !Auth::user()->is_using_temp_password) {
            return redirect()->route('home');
        }
        
        return view('users.change_temp_password');
    }
    
    public function updateTempPassword(Request $request)
    {
        // Only allow access if user is using a temp password
        if (!Auth::check() || !Auth::user()->is_using_temp_password) {
            return redirect()->route('home');
        }
        
        $this->validate($request, [
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
        ]);
        
        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->is_using_temp_password = false;
        $user->save();
        
        return redirect()->route('home')->with('success', 'Your password has been updated successfully.');
    }
} 