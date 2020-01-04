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

Route::get('foo',function(){
    return  'hello world ztk i love you ';

})->name('too');

Route::get('user/{id}',function($id){
    return 'user'.$id;

});

Route::get('users/{id?}',function($id = 100){
    return 'user'.$id;

})->where('id','[0-9]+');

Route::get('ztk',function(){
     return redirect()->route('too');
});

Route::post('ztkmyj','BlogController@blogtest')

->middleware('checkage');
