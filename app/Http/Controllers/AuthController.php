<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    public function authenticate(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
            'remember' => 'boolean'
        ]);

        if (Auth::attempt($credentials, $credentials['remember'] ??= false)) {
            $request->session()->regenerate();

            return redirect()->intended();
        }

        return back()->withErrors([
            'email' => 'invalid email!',
            'password' => 'invalid password!'
        ]);
    }

    public function show() {
        return view('auth.login');
    }
}
