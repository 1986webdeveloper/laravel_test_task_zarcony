<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Pizza_items;
use App\Models\Pizza_attribute;
use DB;
use Illuminate\Http\JsonResponse;

class PizzaController extends Controller
{
	public function insert(Request $request): JsonResponse {
		try{
			$input = $request->all();
			$validator = Validator::make($input,['name'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first('name')],"data" => [] ], 400);
			}else{
				Pizza_items::insert(array('name'=> $input['name']));
				$pizza_id = DB::getPdo()->lastInsertId();
				if(!empty($input['pizza_attributes'])) {
					$data = json_decode($input['pizza_attributes'], true);

					if (is_array($data)) {
						$data = array_map(function($arr) use ($pizza_id) {
							return ['pizza_id' => $pizza_id] +  $arr;
						}, $data);
						Pizza_attribute::insert($data);
					}
				}
			   return response()->json(["meta" => ["status" => 'success',"message" => 'Pizza added successfully'],"data" => array('pizza_id'=>$pizza_id)], 200);
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}

	public function update(Request $request): JsonResponse {
		try{
			$input = $request->all();
			$validator = Validator::make($input,['pizza_id'=>'required','name'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => array()], 400);
			}else{
				$pizza_id = $request->input('pizza_id');
				$getpizza = Pizza_items::where('id', $pizza_id)->get()->toArray();
				if(!empty($getpizza)) {
					Pizza_items::where('id', $pizza_id)->update(array('name'=>$input['name']));
					if(!empty($input['pizza_attributes'])) {
						$pizza_attribute = json_decode($input['pizza_attributes'], true);

						if (is_array($pizza_attribute)) {
							$pizza_attribute = array_map(function($arr) use ($pizza_id) {
								return ['pizza_id' => $pizza_id] + $arr;
							}, $pizza_attribute);

							foreach ($pizza_attribute as $key => $value) {
								$value['pizza_id'] = $pizza_id;
								$data = DB::table('pizza_attributes')->where($value)->get()->toArray();
								if (is_array($data) && count($data) > 0) {
									Pizza_attribute::where('id',$data[0]->id)->update($value);
								}else{
									Pizza_attribute::insert($value);
								}
							}
						}
					}
					return response()->json(["meta" => ["status" => 'success',"message" => 'Pizza updated successfully'],"data" => array()], 200);
				}else{
					return response()->json(["meta" => ["status" => 'failed',"message" => 'Pizza Not Found'],"data" => array()], 200);
				}
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => array()], 500);
		}
	}

	public function delete(Request $request): JsonResponse {
		try{
			$input = $request->all();
			$validator = Validator::make($input,['pizza_id'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => [] ], 400);
			}else{
				$pizza_id = $request->input('pizza_id');
				Pizza_items::where('id', $pizza_id)->delete();
				return response()->json(["meta" => ["status" => 'success',"message" => 'Pizza removed successfully'],"data" => [] ], 200);
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}

	public function get(Request $request): JsonResponse {
		try{
			$input = $request->all();
			$validator = Validator::make($input,['pizza_id'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => [] ], 400);
			}else{
				$pizza_id = $request->input('pizza_id');
				$data = Pizza_items::where('id', $pizza_id)->with(['attributes'])->get();
				if($data->isNotEmpty()) {
					return response()->json(["meta" => ["status" => 'success',"message" => 'Pizza details'],"data" => $data], 200);
				}else{
					return response()->json(["meta" => ["status" => 'success',"message" => 'Pizza Not Found'],"data" => [] ], 200);
				}
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}

	public function getList(Request $request): JsonResponse {
		try{
			$input = $request->all();
			$pizzadata = new Pizza_items();
			
			if ($request->filled('name')) {
				$name = $input['name'];
				$pizzadata = $pizzadata->where('name', 'LIKE', "%$name%");
			}

			if($request->filled('paging')) {
				$data = $pizzadata->with(['attributes'])->paginate($input['paging'])->toArray();
				$next = "true";
				if($data['last_page'] == $input['page']) {
					$next = "false";
				}
				return response()->json([ "meta" => [ "status" => "success", "message" => "pizza list", "next" => $next], "data" => $data], 200);

			}else {
				$data = $pizzadata->with(['attributes'])->get();
				if($data->isNotEmpty()) {
					return response()->json(["meta" => ["status" => 'success',"message" => 'pizza details'],"data" => $data], 200);
				}else{
					return response()->json(["meta" => ["status" => 'success',"message" => 'No pizza Found'],"data" => [] ], 200);
				}
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}
}
