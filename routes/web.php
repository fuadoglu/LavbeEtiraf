<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $categories = array_keys(config('lavbe.categories'));
    $orders = array_keys(config('lavbe.feed_orders'));
    $category = request()->query('category');
    $sort = request()->query('sort', 'new');

    return view('home', [
        'category' => in_array($category, $categories, true) ? $category : null,
        'sort' => in_array($sort, $orders, true) ? $sort : 'new',
    ]);
})->name('home');

Route::get('/topic/{category}', function (string $category) {
    abort_unless(array_key_exists($category, config('lavbe.categories')), 404);

    $sort = request()->query('sort', 'new');

    return view('home', [
        'category' => $category,
        'sort' => in_array($sort, array_keys(config('lavbe.feed_orders')), true) ? $sort : 'new',
    ]);
})->name('topic.show');

Route::get('/_ui/admin-preview', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return view('admin.preview');
})->name('admin.preview');

Route::get('/admin', function () {
    abort(404);
})->name('admin.placeholder');
