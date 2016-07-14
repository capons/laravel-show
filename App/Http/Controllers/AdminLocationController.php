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



class AdminLocationController extends Controller {


    public function __construct()
    {
        
    }

    public function validation(Request $request){
        $this->validate($request,
            [
                'name' => 'required|string',
            ]);
    }

    public function getIndex(Request $request){
     

           
            $filter = \DataFilter::source(new Location);
            $filter->add('name','Name', 'text');
            
            $filter->submit('search');
            $filter->reset('reset');
            $grid = \DataGrid::source($filter);
            $grid->add('name','Name', true); 
            $grid->edit(URL::to('/').'/admin/location', 'Edit','modify|delete'); 
            $grid->add('revision','Revision')->cell( function( $value, $row) {
                return ($value != '') ? "rev.{$value}" : "no revisions for art. {$row->id}";
            });
            $grid->link('/admin/location/new',"Add New", "TR");  
            $grid->orderBy('id','asc'); 
            $grid->paginate(10); 

            if(isset($_GET['modify'])){ 
                $validator = Validator::make($request->all(), 
                    ['modify' => 'integer']
                );
                $location_id = Input::get('modify');
                $location_view = \DB::table('location')->where('id', $location_id)->get(); 

                return view('admin.location',['location_view' => $location_view]); 
            }
            if(isset($_GET['delete'])){ 
                $id = Input::get('delete'); 
                Location::findOrFail($id)->delete();
                Session::flash('user-info', 'You have successfull detele country data'); 
                return redirect('admin/location');
            }

            return view('admin.location',compact('filter','grid'));
        
    }
    public function modify(Request $request) 
    {
        $id = Input::get('m-l-id'); 
        $name = Input::get('m-l-name'); 
        $messages = [ 
            'required' => 'The :attribute field is required.',
        ];
        $validator = Validator::make($request->all(), [
            'm-l-name' => 'required', $messages
        ]);
        if ($validator->fails()) { 
            return redirect('admin/location')
            ->withInput()
                ->withErrors($validator); 
        } else {
            $values=array('name'=>$name); 
            Location::where('id',$id)->update($values);
            Session::flash('user-info', 'You have successfully update data'); 
            return redirect('admin/location'); 
        }
    }

    public function add(Request $request){
        $error = [];
        $this->validation($request);
        
    }
}