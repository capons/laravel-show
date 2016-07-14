<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;

class AdminUsersController extends Controller
{
  

    public function __construct()
    {

    }

    public function users(Request $request){

        
            $filter = \DataFilter::source(new User);

            $filter->add('f_name','Name', 'text');

            $filter->add('email', 'Email', 'text');

            $filter->submit('search');
            $filter->reset('reset');
            $grid = \DataGrid::source($filter);
            $grid->add('f_name','Name', true);
            $grid->add('email','Email', true);
            $grid->edit(URL::to('/').'/admin/users', 'Edit','modify|delete');
            //cell
            $grid->add('revision','Revision')->cell( function( $value, $row) {
                return ($value != '') ? "rev.{$value}" : "no revisions for art. {$row->id}";
            });
            $grid->link('/admin/users/new',"Add New", "TR");
            $grid->orderBy('id','asc');
            $grid->paginate(10);



            if(isset($_GET['modify'])){
                $validator = Validator::make($request->all(),
                ['modify' => 'integer']
                );
                $user_data_id = Input::get('modify');

                $user_view = \DB::table('users')->where('id', $user_data_id)->get();

                return view('admin.index',['user_view' => $user_view]);
            }
            if(isset($_GET['delete'])){
                $id = Input::get('delete');
                User::findOrFail($id)->delete();
                Session::flash('user-info', 'You have successfull detele user data');
                return redirect('admin/users');
            }
            return view('admin.index', compact('filter','grid'));

    }
    public function modify(Request $request)
    {

            $messages = [
                'required' => 'The :attribute field is required.',
            ];
            $validator = Validator::make($request->all(), [
                'm-name' => 'required',
                'm-email' => 'required'
            ], $messages);
            if ($validator->fails()) {
                return redirect('admin/users')
                ->withInput()
                    ->withErrors($validator);
            } else {
                $id = Input::get('m-id');
                $name = Input::get('m-name');
                $email = Input::get('m-email');
                $values=array('f_name'=>$name,'email'=>$email);
                User::where('id',$id)->update($values);
                Session::flash('user-info', 'You have successfully update data');
                return redirect('admin/users');
            }

    }
    public function newUser(Request $request){


            
            return view('admin.new_user');

    }
    public function delete(Request $request){
        $id = Input::get('delete');
        User::findOrFail($id)->delete();
        Session::flash('user-info', 'You have successfull detele user data'); 
        return redirect('admin/users');
    }
}