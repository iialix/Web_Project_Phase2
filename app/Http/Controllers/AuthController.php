<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Show the signup form.
     */
    public function showSignupForm()
    {
        return view('auth.signup');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user by email
        $user = User::where('Email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->Password)) {
            return redirect()->back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput();
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));

        return redirect()->route('home')
            ->with('success', 'Login successful!');
    }

    /**
     * Handle a signup request.
     */
    public function signup(Request $request)
    {
        $validated = $request->validate([
            'userName'  => 'required|string|max:255|unique:users,UserName',
            'email'     => 'required|email|max:255|unique:users,Email',
            'password'  => 'required|string|min:6',
            'birthDate' => 'required|date|before:-13 years',
        ]);

        $user = User::create([
            'UserName'  => $validated['userName'],
            'Email'     => $validated['email'],
            'Password'  => Hash::make($validated['password']),
            'BirthDate' => $validated['birthDate'],
        ]);

        return redirect()->route('login')
            ->with('success', 'Account created successfully! Please login.');
    }

    /**
     * Handle a logout request.
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('home')
            ->with('success', 'Logged out successfully.');
    }

    /**
     * Check if the current user is admin (AJAX endpoint).
     */
    public function checkAdmin()
    {
        if (!Auth::check()) {
            return response()->json(['success' => true, 'isAdmin' => false]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'isAdmin' => $user->isAdmin(),
        ]);
    }
}