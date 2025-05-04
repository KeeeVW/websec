<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ProductsController;
use App\Http\Controllers\Web\UsersController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Web\RolesController;
use App\Http\Controllers\Web\PermissionsController;
use App\Http\Controllers\Web\CreditController;
use App\Http\Controllers\Web\EmployeeCustomerController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\GitHubAuthController;

Route::get('register', [UsersController::class, 'register'])->name('register');
Route::post('register', [UsersController::class, 'doRegister'])->name('do_register');
Route::get('login', [UsersController::class, 'login'])->name('login');
Route::post('login', [UsersController::class, 'doLogin'])->name('do_login');
Route::get('logout', [UsersController::class, 'doLogout'])->name('do_logout');
Route::get('users', [UsersController::class, 'list'])->name('users');
Route::get('profile/{user?}', [UsersController::class, 'profile'])->name('profile');
Route::get('users/edit/{user?}', [UsersController::class, 'edit'])->name('users_edit');
Route::post('users/save/{user}', [UsersController::class, 'save'])->name('users_save');
Route::get('users/delete/{user}', [UsersController::class, 'delete'])->name('users_delete');
Route::get('users/edit_password/{user?}', [UsersController::class, 'editPassword'])->name('edit_password');
Route::post('users/save_password/{user}', [UsersController::class, 'savePassword'])->name('save_password');

// Password reset routes
Route::get('forgot-password', [UsersController::class, 'forgotPassword'])->name('forgot_password');
Route::post('forgot-password', [UsersController::class, 'processForgotPassword'])->name('process_forgot_password');

// Temporary password reset (Basic method)
Route::get('change-temp-password', [UsersController::class, 'changeTempPassword'])->name('change_temp_password');
Route::post('update-temp-password', [UsersController::class, 'updateTempPassword'])->name('update_temp_password');

// Professional password reset
Route::get('reset-password/{token}', [UsersController::class, 'resetPasswordWithToken'])->name('reset_password_token');
Route::post('reset-password/{token}', [UsersController::class, 'processResetPasswordWithToken'])->name('process_reset_password_token');

// Legacy password reset routes (with security questions)
Route::get('reset-password/{user}', [UsersController::class, 'resetPassword'])->name('reset_password');
Route::post('reset-password/{user}', [UsersController::class, 'processResetPassword'])->name('process_reset_password');

Route::get('products', [ProductsController::class, 'list'])->name('products_list');
Route::get('products/show/{product}', [ProductsController::class, 'showProduct'])->name('products_show');
Route::get('products/edit/{product?}', [ProductsController::class, 'edit'])->name('products_edit');
Route::post('products/save/{product?}', [ProductsController::class, 'save'])->name('products_save');
Route::get('products/delete/{product}', [ProductsController::class, 'delete'])->name('products_delete');
Route::post('products/purchase/{product}', [ProductsController::class, 'purchase'])->name('products_purchase');
Route::get('purchase-history', [ProductsController::class, 'purchaseHistory'])->name('purchase_history');

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/multable', function (Request $request) {
    $j = $request->number??5;
    $msg = $request->msg;
    return view('multable', compact("j", "msg"));
});

Route::get('/even', function () {
    return view('even');
});

Route::get('/prime', function () {
    return view('prime');
});

Route::get('/test', function () {
    return view('test');
});

Route::get('/bill', function () {
    return view('bill'); 
 });

 Route::get('/transcript', function () {
    return view('transcript');
});
Route::get('/calculator', function () {
    return view('calculator');
 });
 
 Route::get('/gpa-calculator', function () {
     $courses = [
         ['code' => 'Web and Security Technologies ', 'title' => '', 'credit' => 3],
         ['code' => 'Linux and Shell Programming ', 'title' => ' ', 'credit' => 3],
         ['code' => 'Network Operation and Managment', 'title' => ' ', 'credit' => 3],
         ['code' => 'Digital Forensics Fundamental ', 'title' => ' ', 'credit' => 3],
     ];
     
     return view('gpa-calculator', compact('courses'));
 });

 Route::resource('grades', GradeController::class);

 Route::resource('questions', QuestionController::class);
Route::get('exam/start', [ExamController::class, 'start'])->name('exam.start');
Route::post('exam/submit', [ExamController::class, 'submit'])->name('exam.submit');

Route::get('/test-connection', function () {
    return 'Connection test successful!';
});

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return 'Database connection successful! Database: ' . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        return 'Database connection failed: ' . $e->getMessage();
    }
});

Route::get('/test-exam', function () {
    $questions = [
        (object)[
            'id' => 1,
            'question' => 'What is the correct way to declare a variable in PHP?',
            'option_a' => '$variable = value;',
            'option_b' => 'variable = value;',
            'option_c' => 'var variable = value;',
            'option_d' => 'variable := value;',
            'correct_answer' => 'A'
        ],
        (object)[
            'id' => 2,
            'question' => 'Which SQL statement is used to retrieve data from a database?',
            'option_a' => 'GET',
            'option_b' => 'SELECT',
            'option_c' => 'EXTRACT',
            'option_d' => 'OPEN',
            'correct_answer' => 'B'
        ],
        (object)[
            'id' => 3,
            'question' => 'Which tag is used to define an HTML hyperlink?',
            'option_a' => '<link>',
            'option_b' => '<a>',
            'option_c' => '<href>',
            'option_d' => '<hyperlink>',
            'correct_answer' => 'B'
        ]
    ];
    
    return view('users.exam.start', compact('questions'));
});

Route::get('/test-result', function () {
    return view('users.exam.result', [
        'score' => 2,
        'total' => 3
    ]);
});

// Add these routes for roles and permissions management
Route::middleware(['auth'])->group(function () {
    Route::resource('roles', RolesController::class);
    Route::resource('permissions', PermissionsController::class);
});

// Credit system
Route::middleware(['auth'])->group(function () {
    // Main credit route - redirects based on role
    Route::get('/credits', [App\Http\Controllers\Web\CreditsController::class, 'index'])->name('credits.index');
    
    Route::get('/admin/credits', [App\Http\Controllers\Web\CreditsController::class, 'adminIndex'])
        ->middleware(['auth'])->name('credits.admin');
    
    Route::get('/credits/add', [App\Http\Controllers\Web\CreditsController::class, 'selfAddForm'])
        ->middleware(['auth'])->name('credits.self_add');
    Route::post('/credits/add', [App\Http\Controllers\Web\CreditsController::class, 'selfAddStore'])
        ->middleware(['auth'])->name('credits.self_add_store');
    
    Route::get('/credits/customer/{customer}', [App\Http\Controllers\Web\CreditsController::class, 'addForm'])
        ->middleware(['auth'])->name('credits.add_form');
    Route::post('/credits/customer/{customer}', [App\Http\Controllers\Web\CreditsController::class, 'addCredit'])
        ->middleware(['auth'])->name('credits.add');
    
    Route::get('/employee/customers', [App\Http\Controllers\Web\UsersController::class, 'employeeCustomers'])
        ->name('employee.customers');
    
    Route::resource('employee_customers', App\Http\Controllers\Web\EmployeeCustomerController::class);
});

Route::get('/manage-customers', [App\Http\Controllers\Web\EmployeeCustomerController::class, 'index'])
    ->name('manage.customers');

Route::get('/employee/dashboard', [App\Http\Controllers\Web\EmployeeController::class, 'dashboard'])
    ->name('employee.dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('users', [UsersController::class, 'list'])->name('users');
    Route::get('users/create-employee', [UsersController::class, 'createEmployee'])->name('users_create_employee');
    Route::post('users/create-employee', [UsersController::class, 'storeEmployee'])->name('users_create_employee_post');
    Route::get('users/edit/{user}', [UsersController::class, 'edit'])->name('users_edit');
    Route::post('users/edit/{user}', [UsersController::class, 'save'])->name('users_save');
    Route::get('users/delete/{user}', [UsersController::class, 'delete'])->name('users_delete');
    Route::get('users/ensure-roles', [UsersController::class, 'ensureUserRoles'])->name('users_ensure_roles');
});

// Direct test route for employee permissions
Route::get('/employee/test', function() {
    $user = auth()->user();
    
    if (!$user) {
        return 'Not logged in';
    }
    
    $output = '<h1>Role & Permission Test</h1>';
    
    // Check roles
    $output .= '<h2>Roles</h2>';
    $output .= 'Has employee role: ' . ($user->hasRole('employee') ? 'Yes' : 'No') . '<br>';
    $output .= 'Is employee (method): ' . ($user->isEmployee() ? 'Yes' : 'No') . '<br>';
    $output .= 'All roles: ' . implode(', ', $user->getRoleNames()->toArray()) . '<br>';
    
    // Check permissions
    $output .= '<h2>Permissions</h2>';
    $output .= 'Has view_customers permission: ' . ($user->hasPermissionTo('view_customers') ? 'Yes' : 'No') . '<br>';
    $output .= 'Has view_users permission: ' . ($user->hasPermissionTo('view_users') ? 'Yes' : 'No') . '<br>';
    $output .= 'Has manage_customers permission: ' . ($user->hasPermissionTo('manage_customers') ? 'Yes' : 'No') . '<br>';
    $output .= 'All direct permissions: ' . implode(', ', $user->getDirectPermissions()->pluck('name')->toArray()) . '<br>';
    $output .= 'All permissions (including role-based): ' . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()) . '<br>';
    
    $output .= '<h2>Test Links</h2>';
    $output .= '<a href="' . route('users') . '">Users Page</a><br>';
    $output .= '<a href="' . route('employee.customers') . '">Manage Customers Page</a><br>';
    $output .= '<a href="/">Home</a>';
    
    return $output;
})->name('employee.test');


Route::middleware(['auth'])->group(function () {
    Route::get('/toggle-block/{user}', [App\Http\Controllers\Web\UsersController::class, 'toggleBlockStatus'])
        ->name('toggle_block');
});

Route::get('/debug/credits/{userId}', function($userId) {
    $user = \App\Models\User::findOrFail($userId);
    $credit = \App\Models\UserCredit::where('user_id', $userId)->first();
    $transactions = \App\Models\CreditTransaction::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();
    
    $html = "<h1>Credit Debug for User: {$user->name} (ID: {$user->id})</h1>";
    $html .= "<h2>Credit Record</h2>";
    
    if ($credit) {
        $html .= "<p>Balance in database: {$credit->amount}</p>";
    } else {
        $html .= "<p>No credit record found!</p>";
    }
    
    $html .= "<p>Balance from User::getCreditAmount(): {$user->getCreditAmount()}</p>";
    
    $html .= "<h2>Recent Transactions</h2>";
    $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    $html .= "<tr><th>ID</th><th>Type</th><th>Amount</th><th>Description</th><th>Added By</th><th>Date</th></tr>";
    
    foreach ($transactions as $transaction) {
        $addedBy = $transaction->added_by ? \App\Models\User::find($transaction->added_by)->name : 'N/A';
        $html .= "<tr>";
        $html .= "<td>{$transaction->id}</td>";
        $html .= "<td>{$transaction->type}</td>";
        $html .= "<td>{$transaction->amount}</td>";
        $html .= "<td>{$transaction->description}</td>";
        $html .= "<td>{$addedBy} (ID: {$transaction->added_by})</td>";
        $html .= "<td>{$transaction->created_at}</td>";
        $html .= "</tr>";
    }
    
    $html .= "</table>";
    
    // Fix credits button
    $html .= "<form method='POST' action='/debug/fix-credits/{$userId}'>";
    $html .= csrf_field();
    $html .= "<button type='submit' style='margin-top:20px; padding:10px; background-color:#4CAF50; color:white; border:none; cursor:pointer;'>Fix Credits (Recalculate from Transactions)</button>";
    $html .= "</form>";
    
    return $html;
})->name('debug.credits');

Route::post('/debug/fix-credits/{userId}', function($userId) {
    $user = \App\Models\User::findOrFail($userId);
    
    // Calculate total from transactions
    $transactions = \App\Models\CreditTransaction::where('user_id', $userId)->get();
    $total = 0;
    
    foreach ($transactions as $transaction) {
        $total += $transaction->amount;
    }
    
    // Update or create the credit record
    \Illuminate\Support\Facades\DB::transaction(function() use ($userId, $total) {
        $credit = \App\Models\UserCredit::firstOrCreate(
            ['user_id' => $userId],
            ['amount' => 0]
        );
        $credit->amount = $total;
        $credit->save();
    });
    
    return redirect()->route('debug.credits', $userId)->with('success', 'Credits fixed!');
})->name('debug.fix-credits');

// GitHub Authentication Routes
Route::get('/auth/github', [GitHubAuthController::class, 'redirectToGitHub'])->name('auth.github');
Route::get('/auth/github/callback', [GitHubAuthController::class, 'handleGitHubCallback'])->name('auth.github.callback');