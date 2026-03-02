<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request) {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return back()->with('success', 'Berhasil Login!');
        }
        return back()->withErrors(['login' => 'Email atau password salah.']);
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|numeric',
            'password' => 'required|min:6|confirmed'
        ]);
        $user = User::create([
            'name' => $request->name, 'email' => $request->email,
            'phone' => $request->phone, 'password' => Hash::make($request->password),
            'role' => 'user'
        ]);
        Auth::login($user);
        return back()->with('success', 'Registrasi Berhasil!');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Berhasil Logout.');
    }

    
}