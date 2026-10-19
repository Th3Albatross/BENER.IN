<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    public function authenticate(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string'
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember')) ) {
            $request->session()->regenerate();

            return redirect()->intended();
        }

        return back()->withErrors([
            'loginFailure' => 'Incorrect email or password!'
        ]);
    }

    public function deauthenticate(Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerate();

        return redirect('/');
    }

    public function show() {
        return view('auth.login');
    }
}
