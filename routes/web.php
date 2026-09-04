<?php

use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::get('/login', function () {
    return view('pages.auth.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.auth.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('pages.auth.forgot-password');
})->name('password.request');

Route::get('/reset-password', function (\Illuminate\Http\Request $request) {
    return view('pages.auth.reset-password', [
        'token' => $request->query('token', ''),
        'email' => $request->query('email', ''),
    ]);
})->name('password.reset');

Route::get('/auth/google/callback', function () {
    return view('pages.auth.google-callback');
})->name('auth.google.callback');

Route::get('/search', function () {
    return view('pages.search');
})->name('search');

Route::get('/discovery', function () {
    return view('pages.discovery');
})->name('discovery');

Route::get('/chat', function () {
    return view('pages.chat');
})->name('chat');

Route::get('/topics', function () {
    return view('pages.topics');
})->name('topics.index');

Route::get('/topics/{slug}', function (string $slug) {
    return view('pages.topic', ['slug' => $slug]);
})->name('topics.show');

Route::get('/posts/create', function () {
    return view('pages.create-post');
})->name('posts.create');

Route::get('/posts/{identifier}', function (string $identifier) {
    return view('pages.post', ['identifier' => $identifier]);
})->name('posts.show');

Route::get('/profile', function () {
    return view('pages.profile', ['username' => null]);
})->name('profile');

Route::get('/users/{username}', function (string $username) {
    return view('pages.profile', ['username' => $username]);
})->name('users.show');

Route::get('/notifications', function () {
    return view('pages.notifications');
})->name('notifications');

Route::get('/settings', function () {
    return view('pages.settings');
})->name('settings');

Route::get('/about', [App\Http\Controllers\PageController::class, 'about'])->name('about');
Route::get('/privacy', [App\Http\Controllers\PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [App\Http\Controllers\PageController::class, 'terms'])->name('terms');
Route::get('/contact', [App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post('/contact', [App\Http\Controllers\ContactController::class, 'send'])->name('contact.send');
Route::get('/newsletter', [App\Http\Controllers\PageController::class, 'newsletter'])->name('newsletter');
