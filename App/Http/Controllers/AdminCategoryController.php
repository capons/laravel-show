<?php


namespace App\Http\Controllers;


use App\model\DB\Category;
use App\model\DB\File;
use App\model\DB\Location;
use Illuminate\Http\Request;
use Validator;
use App\model\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;


class AdminCategoryController extends Controller {

    

    public function __construct()
    {
    }
    
    public function validation(Request $request){
        $this->validate($request,
            [
                'name' => 'required|string',
            ]);
    }

    public function getCategory(Request $request){
            $filter = \DataFilter::source(new Category);
            $filter->add('name','Name', 'text');
            
            $filter->submit('search');
            $filter->reset('reset');
            $grid = \DataGrid::source($filter);
            $grid->add('name','Name', true); 
            $grid->edit(URL::to('/').'/admin/category', 'Edit','modify|delete'); 
            $grid->add('revision','Revision')->cell( function( $value, $row) {
                return ($value != '') ? "rev.{$value}" : "no revisions for art. {$row->id}";
            });
            $grid->orderBy('id','asc'); 
            $grid->paginate(10); 

            if(isset($_GET['modify'])){ 
                $validator = Validator::make($request->all(), 
                    ['modify' => 'integer']
                );
                $category_id = Input::get('modify');
                $category_view = \DB::table('category')->where('id', $category_id)->get(); 

                return view('admin.category',['category_view' => $category_view]); 
            }
            if(isset($_GET['delete'])){ 
                $id = Input::get('delete'); 
                Category::findOrFail($id)->delete();
                Session::flash('user-info', 'You have successfull detele category name'); 
                return redirect('admin/category');
            }

            return view('admin.category',compact('filter','grid'));
    }

    public function modify(Request $request)
    {
        $id = Input::get('m-c-id'); 
        $name = Input::get('m-c-name'); 
        $messages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator = Validator::make($request->all(), [
            'm-c-name' => 'required', $messages
        ]);
        if ($validator->fails()) { 
            return redirect('admin/category')
            ->withInput()
                ->withErrors($validator); 
        } else {
            $values=array('name'=>$name); 
            Category::where('id',$id)->update($values);
            Session::flash('user-info', 'You have successfully update data'); 
            return redirect('admin/category'); 
        }
    }

    public function addCategory(Request $request){ 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return redirect('admin/category')
                ->withInput()
                ->withErrors($validator); 
        }

        $task = new Category();
        $task->name = $request->name;
        $task->save();

        Session::flash('user-info', 'You have successfully add category'); 

        return redirect('admin/category'); 
        

    }
}