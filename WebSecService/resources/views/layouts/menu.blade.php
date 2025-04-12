<nav class="navbar navbar-expand-sm bg-light">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/') }}">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/even') }}">Even Numbers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/prime') }}">Prime Numbers</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/multable') }}">Multiplication Table</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/bill') }}">MiniTest</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/transcript') }}">transcript</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/calculator') }}">calculator</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/gpa-calculator') }}">gpa-calculator</a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="{{ route('grades.index') }}">Grades</a>
            </li>
          
            
            <li class="nav-item">
                <a class="nav-link" href="{{ route('questions.index') }}">Manage Questions</a>
            </li>
        
            <li class="nav-item">
                <a class="nav-link" href="{{ route('exam.start') }}">Take Exam</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('products_list') }}">Products</a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users') }}">Users</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            @auth
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile') }}">{{ auth()->user()->name }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('do_logout') }}">Logout</a>
            </li>
            @else
            <li class="nav-item">
                <a class="nav-link" href="{{ route('login') }}">Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('register') }}">Register</a>
            </li>
            @endauth
        </ul>
    </div>
</nav>
