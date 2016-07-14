<?php
use App\model\DB\Category;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', 'UserController@getIndex');

Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

Route::get('auth/register', 'Auth\AuthController@getRegister'); //view auth/register
Route::post('auth/register', 'Auth\AuthController@postRegister'); //receive data from registration form
Route::get('auth/active', 'Auth\AuthController@postActivate'); //activate user account


Route::get('home','UserController@getIndex'); //maby page for quest

Route::get('promise/buy','PromiseController@promiseBuy');
Route::get('promise/buy/{id}', function($id) {
    $category = Category::all();
    $promise = \DataSet::source(
        DB::table('promise')
            ->join('category','promise.category_id', '=', 'category.id')
            ->join('file','promise.file_id', '=', 'file.id')
            ->join('request', 'promise.id', '=', 'request.promise_id')
            ->join('users', 'users.id', '=', 'request.users_id')
            ->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.url','file.name as file_name','request.amount', 'users.f_name')
            ->where('promise.active','=',1)
            ->where ('category.id', '=' , $id)
    );
    $promise->paginate(5);
    $promise->build();
    return view('promise.buy', ['category' => $category],compact('promise'));
});
//Promise detailes
Route::get('promise/details/{id}', function($id){
    $promise_details =  DB::table('promise')
        ->join('category','promise.category_id', '=', 'category.id')
        ->join('file','promise.file_id', '=', 'file.id')
        ->join('request', 'promise.id', '=', 'request.promise_id')
        ->join('users', 'users.id', '=', 'request.users_id')
        ->join('location','promise.location_id','=','location.id')
        ->select('promise.id','promise.title','promise.description','promise.price','promise.terms','promise.type','promise.auction_end','promise.active','promise.winners','category.name as category_name','file.path as file_path','file.url','file.name as file_name','request.amount', 'users.f_name','location.name as location_name')
        ->where ('promise.id', '=' , $id)
        ->get();
    
    return view('promise.details', ['promise_details' => $promise_details]);
});



Route::group(['middleware' => ['auth']], function(){


    Route::get('/promise/profile/{id}', 'PromiseController@pageProfile');
    Route::get('/promise/buypromise', 'PromiseController@pageBuypromise');

    Route::get('/account','AccountController@getIndex');
    Route::get('/account/broughtpromise', 'AccountController@pageBroughtpromise');
    Route::get('/account/otherpromise', 'AccountController@pageOtherpromise');
    Route::get('/account/sellpromise', 'AccountController@pageSellpromise');
    Route::get('/account/yourpromise', 'AccountController@pageYourpromise');
    Route::get('/account/requeste','AccountController@requestePromise');

    Route::post('/promise/addrequest', 'PromiseController@addRequest');
    Route::post('/promise/getdata', 'PromiseController@getData');
    Route::post('/promise/buy', 'PromiseController@buy');
    Route::post('/promise/auction', 'PromiseController@buyAuction');
    Route::post('/promise/check', 'PromiseController@check');
    Route::post('/promise/getpromisebycategory', 'PromiseController@getPromiseByCategory');
    Route::get('/promise/sell', 'PromiseController@pageSell');
    Route::post('/promise/sell', 'PromiseController@add');
    Route::get('/promise/request', 'PromiseController@pageRequest');
    Route::post('/promise/request', 'PromiseController@pageRequest');
    Route::get('/home','UserController@getIndex');
    Route::get('/user/getfile','UserController@uploadedFile');
});

Route::group(['middleware' => ['admin']], function() {
    Route::get('/admin', 'AdminController@getIndex');
    Route::get('/admin/users','AdminUsersController@users');
    Route::post('/admin/users','AdminUsersController@modify');
    Route::get('/admin/users/new','AdminUsersController@newUser');
    Route::get('/admin/category','AdminCategoryController@getCategory');
    Route::post('/admin/category','AdminCategoryController@modify');
    Route::get('/admin/location','AdminLocationController@getIndex');
    Route::post('/admin/location','AdminLocationController@modify');
    Route::get('/admin/promise', 'AdminPromiseController@pagePromise');
});




