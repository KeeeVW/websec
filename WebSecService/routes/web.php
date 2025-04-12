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
Route::get('products/edit/{product?}', [ProductsController::class, 'edit'])->name('products_edit');
Route::post('products/save/{product?}', [ProductsController::class, 'save'])->name('products_save');
Route::get('products/delete/{product}', [ProductsController::class, 'delete'])->name('products_delete');

Route::get('/', function () {
    return view('welcome');
});

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
Route::group(['middleware' => ['auth', 'permission:admin_users']], function () {
    Route::resource('roles', RolesController::class);
    Route::resource('permissions', PermissionsController::class);
});