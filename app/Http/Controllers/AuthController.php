<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // หน้า auth หลัก
    public function show()
    {
        return view('auth.auth'); // มีลิงก์ไป login / register
    }

    // --- Login ---
    public function loginForm()
    {
        return view('auth.login');
    }

public function login(Request $request)
{
    $data = $request->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],
    ]);

   if (Auth::attempt($data, $request->boolean('remember'))) {
    $request->session()->regenerate();

    // 🔸เปลี่ยนจาก redirect('/') → เป็น redirect()->route('dashboard')
    return redirect()->route('dashboard')->with('status', 'Welcome back!');
}
}

public function logout(Request $request)
{
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('auth.page')->with('status', 'Logged out successfully.');
}



    // --- Register (เก็บไว้ "ตัวเดียว" เท่านี้) ---
    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8','confirmed'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        Auth::login($user);
        return redirect('/')->with('status','Registered & logged in.');
    }
}
