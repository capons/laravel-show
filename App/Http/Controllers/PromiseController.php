<?php

namespace App\Http\Controllers;


use App\Library\UpBid;  
//use App\Library\AuctionEnd;
use App\model\DB\Category;
use App\model\DB\File;
use App\model\DB\Location;
use App\model\DB\Promise;
use App\model\DB\Requeste;
use App\model\DB\Search;
use App\model\DB\Winner;
use App\User;
use Illuminate\Http\Request;
use Validator;
use Input;
use Session;
use DB;
use Illuminate\Support\Facades\Mail;

class PromiseController extends Controller {

	protected $redirectTo = '/promise/sell';

	public function __construct(){
		
	}

	public function validation(array $data){
		$messages = [          //validation message
			'prom_category.required' => 'Category is required',
			'prom_location.required' => 'Location is required',
			'prom_title.required' => 'Title is required',
			'prom_title.max:50' => 'Title length max 50',
			'prom_available_time.required' => 'Promise amount is required',
			'prom_available_time.max:20' => 'Promise length-no more than 20 characters',
			'prom_desc.required' => 'Promise description is required',
			'prom_desc.max:150' => 'Promise description max 150 character',
			'prom_terms.required' => 'Promise terms is required',
			'prom_terms.max:150' => 'Promise terms max 150 character',
			'prom_price.required' => 'Promise price is required',
			'prom_auction_number.required' => 'Promise auction amount is required',
			'prom_upload.required' => 'Promise image is required',
			'prom_auction_end.required' => 'Auction expired required',
			'prom_auction_end.numeric' => 'Insert auction active days '
		];
		return Validator::make($data, [   //validation registration form
			'sell_promise_type' => 'numeric',
			'prom_category' => 'required',
			'prom_location' => 'required',
			'prom_title' => 'required|string|max:50',
			'prom_available_time' => 'required|numeric|max:20',
			'prom_desc' => 'required|max:150',
			'prom_terms' => 'required|max:150',
			'prom_auction_number' => 'required|numeric',
			'shows' => '',
			'prom_price' => 'required|numeric',
			'prom_upload' => 'required',
			'prom_auction_end' => 'required|numeric',
		],$messages);
	}


	public function pageSell(){ //sell promise view
		$category = Category::all();
		$location = Location::all();
		return view('promise.sell',['category' => $category,'location' => $location]);
	}

	public function add(Request $request){ 
		$error = array();
		if(Input::get('sell_promise_type') == 0) { 
			if (!$request->input('select_image_from_our_database')) { 
				$validator = $this->validation($request->all());
				if ($validator->fails()) {
					return redirect('promise/sell')
						->withInput()
						->withErrors($validator);
				} else {
					$v = Validator::make($request->all(), [
						'file' => 'mimes:jpeg,bmp,png',
					]);
					if ($v->fails()) {
						return $v->errors();
					} else {
						$file = \Request::file('prom_upload');
						$path = \Config::get('app.setting.upload') . '\\' . \Auth::user()->id;
						$name = time() . '.' . $file->getClientOriginalExtension();
						if ($file->move($path, $name)) {
							$file = File::create(['name' => $name, 'path' => $path, 'users_id' => \Auth::user()->id, 'url' => \Config::get('app.setting.url_upload') . '/' . \Auth::user()->id]);
						}
					}
				}
			} else {
				$file = File::find($request->input('select_image_from_our_database')); 
			}
				$promise_value = Input::get('prom_available_time');

				$data = array( 
					'title' => Input::get('prom_title'),
					'description' => Input::get('prom_desc'),
					'price' => round((float)Input::get('prom_price'), 2), 
					'terms' => Input::get('prom_terms'),
					'type' => Input::get('sell_promise_type'),       
					'winners' => Input::get('prom_auction_number'),
					'file_id' => $file->id,                         
					'category_id' => Input::get('prom_category'),
					'location_id' => Input::get('prom_location'),
				);

				$promise = Promise::create($data);
				if (!$promise) {
					$error[] = \Lang::get('message.error.save_db');
				} else {
					$request_data = array(
						'promise_id' => $promise->id, 
						'amount' => $promise_value,
						'users_id' => \Auth::user()->id,
					);

					$p_request = Requeste::create($request_data);
					if (!$p_request) {
						$error[] = \Lang::get('message.error.save_db');
					} else {
					
						Session::flash('user-info', 'Promise added successfully'); 
						return redirect($this->redirectTo);
						die();
					}

				}
		} elseif (Input::get('sell_promise_type') == 1){ 
			$error = array();
			if (!$request->input('select_image_from_our_database')) { 
				$validator = $this->validation($request->all());
				if ($validator->fails()) {
					
					return redirect('promise/sell')
						->withInput()
						->withErrors($validator);
				} else {
					$v = Validator::make($request->all(), [
						'file' => 'mimes:jpeg,bmp,png',
					]);
					if ($v->fails()) {
						return $v->errors();
					} else {
						$file = \Request::file('prom_upload');
						$path = \Config::get('app.setting.upload') . '\\' . \Auth::user()->id;
						$name = time() . '.' . $file->getClientOriginalExtension();
						if ($file->move($path, $name)) {
							$file = File::create(['name' => $name, 'path' => $path, 'users_id' => \Auth::user()->id, 'url' => \Config::get('app.setting.url_upload') . '/' . \Auth::user()->id]);
						}
					}
				}
			} else {
				$file = File::find($request->input('select_image_from_our_database')); 
			}
			$promise_value = Input::get('prom_available_time'); 
			$auction_days = Input::get('prom_auction_end'); 
			$to_change = '+ '.$auction_days.' day';
			$date = date("Y-m-d H:i:s");
			$date = strtotime($date);
			$date = strtotime($to_change, $date); 
			$number_of_winners = Input::get('prom_auction_number');
			$winners_array = array();


			$data = array(
				'title' => Input::get('prom_title'),
				'description' => Input::get('prom_desc'),
				'price' => Input::get('prom_price'),
				'terms' => Input::get('prom_terms'),
				'type' => Input::get('sell_promise_type'),       
				'winners' => Input::get('prom_auction_number'),
				'file_id' => $file->id,                         
				'auction_end' => date("Y-m-d H:i:s",$date),     
				'category_id' => Input::get('prom_category'),
				'location_id' => Input::get('prom_location'),
			);
			$promise = Promise::create($data);
			if (!$promise) {
				$error[] = \Lang::get('message.error.save_db');
			} else {
				$request_data = array(
					'promise_id' => $promise->id, 
					'amount' => $promise_value,
					'users_id' => \Auth::user()->id,
				);
				$p_request = Requeste::create($request_data);
				if (!$p_request) {
					$error[] = \Lang::get('message.error.save_db');
				} else {
					Session::flash('user-info', 'Promise added successfully'); 
					return redirect($this->redirectTo);
				}
			}
		}
	}
	
	public function promiseBuy(){

		$category = Category::all();
		/*
		$promise = DB::table('promise')
			->join('category','promise.category_id', '=', 'category.id')
			->join('file','promise.file_id', '=', 'file.id')
			->join('request', 'promise.id', '=', 'request.promise_id')
			->join('users', 'users.id', '=', 'request.users_id')
			->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.url','file.name as file_name','request.amount', 'users.f_name')
    		->get();
		*/

		$promise = \DataSet::source(
			DB::table('promise')
			->join('category','promise.category_id', '=', 'category.id')
			->join('file','promise.file_id', '=', 'file.id')
			->join('request', 'promise.id', '=', 'request.promise_id')
			->join('users', 'users.id', '=', 'request.users_id')
			->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.url','file.name as file_name','request.amount', 'users.f_name')
			->where('promise.active','=',1)
			->where('request.amount','<>',0) 
		);
		$promise->orderBy('id','desc');
		$promise->paginate(7);
		
		$promise->build();
		return view('promise.buy',['category' => $category],compact('promise'));
	}
	
	public function buy(Request $request){
		Validator::make($request->all(), [
			'promise_id' => 'numeric',
			'promise_amount' => 'numeric',
			'promise_price' => 'numeric'
		]);
		$promise_id = $request->input('promise_id');
		$amount = $request->input('promise_amount');
		$price = $request->input('promise_price');
		$promise = DB::table('promise')
			->join('request', 'promise.id', '=', 'request.promise_id')
			->select('promise.id','promise.price','promise.type','promise.active','promise.sold','request.amount')
			->where('promise.id','=',$request->input('promise_id'))
			->where('promise.active','=',1)
			->where('promise.type','=',0)
			->where('promise.sold','=',null)
			->first();
		if($promise->amount >= $amount){  


			//Paiment API INSERT HERE


			DB::beginTransaction();      
			try {
				Requeste::where('promise_id', $promise_id)
					->update(['amount' => $promise->amount - $amount]);
			} catch (ValidationException $e) {
				DB::rollback();
				return Redirect::to('promise/buy')
					->withErrors( $e->getErrors() )
					->withInput();
			} catch (\Exception $e) {
				DB::rollback();
				throw $e;
			}
			try {
				$winner = DB::table('winners')->insertGetId( 
					['promise_id' => $promise_id, 'bid' => $price, 'winner_id' => \Auth::user()->id,'if_email' => 1]
				);
			} catch (ValidationException $e) {
				DB::rollback();
				return Redirect::to('promise/buy')
					->withErrors( $e->getErrors() )
					->withInput();
			} catch (\Exception $e) {
				DB::rollback();
				throw $e;
			}
			DB::commit();
			if($promise->amount - $amount == 0){ 
				Promise::where('id', $promise_id)
					->update(['sold' => 1]);
			}
			
			$data = array( 
				'name'=> \Auth::user()->f_name,
				'email'=>\Auth::user()->email,
				'c_message'=> \Lang::get('message.user.successful_purchase').' '.$winner
			);
			Mail::send('mail.promise_buy',$data,function ($message) {
				$message->from(env('admin_email'), 'Auction');
				$message->to(\Auth::user()->email)->cc(\Auth::user()->email);
				$message->subject(\Lang::get('message.promise.buy'));
			});
			Session::flash('user-info', \Lang::get('message.user.successful_purchase').' '.$winner); 
			return redirect('promise/buy');
		} else { 
			Session::flash('user-info', \Lang::get('message.promise.quantity')); 
			return redirect('promise/buy');
		}
	}
	
	public function buyAuction(Request $request){
		$messages = [ 
			'au_promise_bid.required' => 'Bid is required',
			'au_promise_bid.numeric' => 'Bid is numeric'
		];
		$validator = Validator::make($request->all(), [
			'au_promise_id' => 'numeric',
			'au_promise_bid' => 'numeric|required'
		], $messages);
		if ($validator->fails()) { 
			return redirect('/promise/details/'.$request->au_promise_id)
				->withInput()
				->withErrors($validator); 
		} else {
			$promise_id = Input::get('au_promise_id');
			$promise_bid = round((float)Input::get('au_promise_bid'), 2); 
			if($this->auctionCheckTime($promise_id) == false){            
				Session::flash('user-info', \Lang::get('message.error.auction_end_time')); 
				return redirect('promise/buy');
				die();
			}
			$winner = DB::table('winners')
				->where('promise_id','=',$promise_id)
				->get();
			if(count($winner) == 0){ 
				$promise = DB::table('promise')
					->join('request', 'promise.id', '=', 'request.promise_id')
					->select('promise.id','promise.price','promise.auction_end')
					->where ('promise.id', '=' , $promise_id)
					->first();
				$promis_min_bid = $promise->price; 
				if($promise_bid <= $promis_min_bid ){ 
					Session::flash('user-info', \Lang::get('message.error.auction_min_bid').' '.$promise->price); 
					return redirect('promise/details/'.$promise_id);
					die();
				}
				DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 

				$new_winner = DB::table('winners')->insert(
					['promise_id' => $promise_id, 'bid' => $promise_bid,'winner_id' => \Auth::user()->id]
				);

				DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 
				if($new_winner){ 
					Session::flash('user-info', \Lang::get('message.promise.true_bid')); 
					return redirect('promise/buy');
				} else {
					Session::flash('user-info', \Lang::get('message.error.error')); 
					return redirect('promise/details/'.$promise_id);
				}
			} else {             
				if($this->auctionCheckTime($promise_id) == false){ 
					Session::flash('user-info', \Lang::get('message.error.auction_end_time')); 
					return redirect('promise/buy');
					die();
				}
				$promise = Promise::find($promise_id);
				if($promise->winners == count($winner)) { 
					$winners_data = json_decode(json_encode($winner), true); 
					$update_bid = new UpBid();
					$up_bid = $update_bid->changeBid($winners_data);


					if ($promise_bid <= $up_bid['check_data']['user_old_bid']) { 
						Session::flash('user-info', \Lang::get('message.error.auction_min_bid') . ' ' . $up_bid['check_data']['user_old_bid']); //send message to user via flash data
						return redirect('promise/details/' . $promise_id);
						die();
					}

					$update_auction = DB::table('winners')
						
						->where('promise_id', $promise_id)
						->where('bid', $up_bid['update_data']['user_old_bid'])
						->update(['bid' => $promise_bid,'winner_id' => \Auth::user()->id]);
					if ($update_auction) {
						Session::flash('user-info', \Lang::get('message.promise.true_bid')); 
						return redirect('promise/buy');
					} else {
						Session::flash('user-info', \Lang::get('message.error.error')); 
						return redirect('promise/buy');
					}
				} else {                             
					$winners_data = json_decode(json_encode($winner), true); 
					$update_bid = new UpBid();
					$next_winner_bid = $update_bid->changeBid($winners_data);

					if ($promise_bid <= $next_winner_bid['check_data']['user_old_bid']) { 
						Session::flash('user-info', \Lang::get('message.error.auction_min_bid') . ' ' . $next_winner_bid['check_data']['user_old_bid']); //send message to user via flash data
						return redirect('promise/details/' . $promise_id);
						die();
					}

					$duplicate_winners = DB::table('winners')
						->where ('promise_id', '=' , $promise_id)
						->where('winner_id', '=' , \Auth::user()->id)
						->get();
					if(count($duplicate_winners) !== 0) { 
						DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
						$update_auction = DB::table('winners')
							->where('promise_id', $promise_id)
							->where('bid', $next_winner_bid['update_data']['user_old_bid'])
							->update(['bid' => $promise_bid,'winner_id' => \Auth::user()->id]);
						DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 
						if ($update_auction) {
							Session::flash('user-info', \Lang::get('message.promise.true_bid')); 
							return redirect('promise/buy');
						} else {
							Session::flash('user-info', \Lang::get('message.error.error')); 
							return redirect('promise/buy');
						}
					}
					$new_winner = DB::table('winners')->insert(  
						['promise_id' => $promise_id, 'bid' => $promise_bid,'winner_id' => \Auth::user()->id]
					);
					if($new_winner){ 
						Session::flash('user-info', \Lang::get('message.promise.true_bid'));
						return redirect('promise/buy');
					} else {
						Session::flash('user-info', \Lang::get('message.error.error'));
						return redirect('promise/details/'.$promise_id);
					}
				}
			}
		}
	}
	protected function auctionCheckTime($promise_id){
		$promise = DB::table('promise')
			->join('request', 'promise.id', '=', 'request.promise_id')
			->select('promise.id','promise.price','promise.auction_end')
			->where ('promise.id', '=' , $promise_id)
			->first();
		$end_time = strtotime($promise->auction_end); 
		if (time() > $end_time) { 
			return false; 
		} else {
			return true;
		}
	}
	/*
	public function check()
	{
		$msg = [];
		$id = \Request::input('id');
		//return ['check' => \App\model\DB\Promise::find($id)->request()->orderBy('amount', 'desc')->first()->users_id];
		$promise = \App\model\DB\Promise::find($id);
		if ($promise->request->isEmpty()) {
			$msg['check'] = false;
		} else {
			if ($promise->request()->orderBy('amount', 'desc')->first()->users_id == \Auth::user()->id) {
				$msg['check'] = true;
			} else {
				$msg['check'] = false;
			}
		}
		return $msg;
	}
	*/
	/*
	public function getPromiseByCategory(){
		$cat = \Request::input('category');
		$promise = Promise::select('file.name','file.url','promise.*');
		if($cat != 0){
			$promise = $promise->where('category_id',$cat);
		}
		$promise = $promise->join('file','promise.file_id','=','file.id')->get();
		return $promise->toArray();
	}
	*/
	
	
	public function pageRequest(Request $request){ 
		$category = Category::all();
		$location = Location::all();
		if(empty($request->all())){ 
			return view('promise.request', ['category' => $category,'location' => $location]);
			} else {
				
			$promise =	DB::table('promise')
						->join('category','promise.category_id', '=', 'category.id')
						->join('file','promise.file_id', '=', 'file.id')
						->join('request', 'promise.id', '=', 'request.promise_id')
						//->join('users', 'users.id', '=', 'request.users_id')
						->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.url','file.name as file_name','request.amount')
						->where('promise.active','=',1)
						->where('request.amount','<>',0) //not equal 0
						->where('promise.category_id','=',$request->input('request_cat'))
						->where('promise.description','like','%'.$request->input('request_desc').'%')
						->get();

			if($promise == true){ 
				return view('promise.request', ['category' => $category,'location' => $location,'promise' => $promise]);
				die();
			} else { 
				
				$user_promise_email = DB::table('promise')
									->join('category','promise.category_id', '=', 'category.id')
									->join('file','promise.file_id', '=', 'file.id')
									->join('request', 'promise.id', '=', 'request.promise_id')
									->join('users', 'users.id', '=', 'request.users_id')
									->select('promise.id','promise.title','promise.description','promise.price','promise.type','promise.auction_end','promise.active','category.name as category_name','file.path as file_path','file.url','file.name as file_name','users.email','users.f_name')
									->where('promise.active','=',1)
									->where('request.amount','<>',0) //not equal 0
									->where('promise.category_id','=',$request->input('request_cat'))
									//->where('promise.description','like','%'.$request->input('request_desc').'%')
									->get();
				
				$user_promise_email = json_decode(json_encode($user_promise_email), true); 
				$this->requestEmail($user_promise_email,$request->input('request_desc'));  


				DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
				$request_promise = Search::create(['users_id' => \Auth::user()->id,'price' => $request->input('request_price'),'descript' => $request->input('request_desc'),'expires' => $request->input('request_end')]);
				
				
				
				
				DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 


				Session::flash('user-info', \Lang::get('message.promise.request_negative'));
				return redirect('promise/request');
				die();
			}
		}
	}

	public function requestEmail(array $data, $desc){ 
		if(is_array($data)){
			foreach ($data as $row) {
				Mail::send('mail.request', ['desc' => $desc], function ($m) use ($row) {
					$m->from('hello@app.com', 'Your Application');
					$m->to($row['email'],$row['f_name'] )->subject('Create new Promise!!!'); 
				});
			}
		}
	}

	public function addRequest(Request $request){

	}

	public function pageBuy(){

		return view('promise.buy', [
			'category' => Category::all()
		]);
	}

	public function pageProfile($id){
		$promise = Promise::find($id);
		$req = $promise->request()->orderBy('amount', 'desc')->first();
		return view('promise.profile', ['promise' => $promise, 'request' => $req]);
	}

	public function pageBuypromise(){
		$promise = Promise::where('active',1)->get();
		$cat = Category::all();
		return view('promise.buypromise', ['promise' => $promise,'category' => $cat]);
	}

}