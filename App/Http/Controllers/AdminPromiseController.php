<?php

namespace App\Http\Controllers;

use App\model\DB\Promise;
use App\model\DB\RequestPro;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
//use Request;
use URL;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class AdminPromiseController extends Controller {


    public function __construct()
    {
    }
    public function pagePromise(Request $request){

       

            $filter = \DataFilter::source(
                DB::table('promise')
                    ->join('category','promise.category_id', '=', 'category.id')
                    ->join('file','promise.file_id', '=', 'file.id')
                    ->join('request', 'promise.id', '=', 'request.promise_id')
                    ->join('users', 'users.id', '=', 'request.users_id')
                    ->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.name as file_name','request.amount', 'users.f_name')
            );

            $filter->add('title','Title', 'text');
            $filter->add('price','Price','text');
            $filter->submit('search');
            $filter->reset('reset');


            $grid = \DataGrid::source($filter);

            $grid->add('title','Title',true);
            $grid->add('description','Description',true);
            $grid->add('type','Type',true);
            $grid->add('price','Price',true);
            $grid->add('active','Active');

            $grid->edit(URL::to('/').'/admin/promise', 'Edit','show|modify|delete');
            $grid->link(URL::to('/').'/admin/promise', "TR");

            $grid->paginate(2);

            $grid->row(function ($row) {
                if ($row->cell('active')->value == 0) {
                    $row->cell('active')->value = 'Need to be active';
                    $row->cell('active')->style("color:Green");
                } elseif($row->cell('active')->value == 1) {
                    $row->cell('active')->value = 'Activated';
                }

            });
            $grid->row(function ($row) {
                if ($row->cell('type')->value == 0) {
                    $row->cell('type')->value = 'For sell';
                } elseif($row->cell('type')->value == 1) {
                    $row->cell('type')->value = 'Auction';
                }

            });
            if(isset($_GET['modify'])){
                $validator = Validator::make($request->all(),
                    ['modify' => 'integer']
                );
                $promise_id = Input::get('modify');
                $promise = Promise::find($promise_id);
                $promise->active = 1;
                $promise->save();
                Session::flash('user-info', 'You have activated Promise');
                return redirect('admin/promise');
            }
            if(isset($_GET['delete'])){
                $validator = Validator::make($request->all(),
                    ['modify' => 'integer']
                );
                $promise_id = Input::get('delete');
                DB::delete('
                      DELETE promise, request, file FROM
                      promise
                      INNER JOIN  request on promise.id = request.promise_id
                      INNER JOIN file on promise.file_id = file.id
                      WHERE  promise.id= "'.$promise_id.'" AND request.promise_id= "'.$promise_id.'";
                ');
                Session::flash('user-info', 'You have delete Promise');
                return redirect('admin/promise');
            }
            
            return  view('admin.promise', compact('filter', 'grid'));
    }
}