<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test', function () {
    // return (new \App\Post)->all();
    $test = (new \App\Post)->filter('foo', 'a cool bar')->first();
    dd($test);
    // $post = new \App\Post;
    // $post->create([
    //     'test' => 'some cool test',
    //     'foo' => 'a cool bar'
    // ]);
    // $post->all();
});