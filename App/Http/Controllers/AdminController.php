<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;


class AdminController extends Controller {


	public function __construct()
	{
		
	}
	public function getIndex(){
	
		return view('admin.default');
		
	}
	
	public function getTest(){
		return view('admin.test');
	}
	public function pagePromise(){
		return view('admin.promise');
	}
	
	public function promise(){ 

		$user = \DB::table('promise as p')
			->select('p.title','p.price')
			->get();
		
		return ['data'=>$user];

	}
}