<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines (Laravel framework defaults)
    |--------------------------------------------------------------------------
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /*
    |--------------------------------------------------------------------------
    | View text — resources/views/auth/login.blade.php
    |--------------------------------------------------------------------------
    */

    'login' => [
        'page_title' => 'Sign in',
        'title' => 'Sign in',
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Stay signed in',
        'submit' => 'Sign in',
        'no_account' => 'No account yet?',
        'register_link' => 'Register',
    ],

    /*
    |--------------------------------------------------------------------------
    | View text — resources/views/auth/register.blade.php
    |--------------------------------------------------------------------------
    */

    'register' => [
        'page_title' => 'Register',
        'title' => 'Create an account',
        'intro' => 'An account is a person. Funds are private: you join one through a link from its administrator.',
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_hint' => 'At least 10 characters.',
        'password_confirmation' => 'Confirm password',
        'submit' => 'Register',
        'already_registered' => 'Already registered?',
        'login_link' => 'Sign in',
    ],

];
