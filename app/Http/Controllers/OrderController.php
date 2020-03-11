<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Order_master;
use App\Order_items;
use App\Pizza_attribute;
use DB;

class OrderController extends Controller {

	public function insert(Request $request) {

		try{

			$input = $request->all();
			$validator = Validator::make($input,[
				'customer_name'=>'required',
				'customer_address' => 'required',
				'customer_mobile' => 'required',
				'order_items' => 'required',
			]);

			if($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => array()], 400);

			}else{

				if(!empty($input['order_items'])) {

					$order_items = json_decode($input['order_items'], true);
					$total_quantity = 0;
					$total_amount = 0;
					
					foreach ($order_items as $order_itemsValue) {

						$pizza = Pizza_attribute::where('id',$order_itemsValue['pizza_attr_id'])->get()->toArray();
						$total_quantity = $total_quantity + $order_itemsValue['quantity'];
						$total_amount = $total_amount + ($order_itemsValue['quantity'] * $pizza[0]['price']);
					}

					$order_data = array(
						'customer_name' => $input['customer_name'],
						'customer_address' => $input['customer_address'],
						'customer_mobile' => $input['customer_mobile'],
						'total_quantity' => $total_quantity,
						'total_amount' => $total_amount,
						'order_status' => 'Pending',
					);

					Order_master::insert($order_data);
					$order_id = DB::getPdo()->lastInsertId();

					foreach ($order_items as $order_itemsValue) {

						$pizza = Pizza_attribute::where('id',$order_itemsValue['pizza_attr_id'])->get()->toArray();
						$item_data = array(
							'order_id' => $order_id,
							'pizza_id' => $order_itemsValue['pizza_id'],
							'pizza_attr_id' => $order_itemsValue['pizza_attr_id'],
							'quantity' => $order_itemsValue['quantity'],
							'amount' => $pizza[0]['price']
						);

						Order_items::insert($item_data);

					}

					return response()->json(["meta" => ["status" => 'success',"message" => 'Order added successfully'],"data" => array('order_id'=>$order_id)], 200);
				}
			}
		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}
	

	public function update(Request $request) {

		try{

			$input = $request->all();
			$validator = Validator::make($input,['order_id'=>'required','order_status'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => array()], 400);

			}else{

				$order_id = $request->input('order_id');
				Order_master::where('id', $order_id)->update(array('order_status'=>$input['order_status']));
				return response()->json(["meta" => ["status" => 'success',"message" => 'Order updated successfully'],"data" => array()], 200);
			}

		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => array()], 500);
		}
	}
	
	public function delete(Request $request) {

		try{

			$input = $request->all();
			$validator = Validator::make($input,['order_id'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => [] ], 400);

			}else {

				$order_id = $request->input('order_id');
				$order = Order_master::where('id', $order_id)->get()->toArray();

				if(!empty($order)) {

					if ($order[0]['order_status'] == 'Delivered') {
						Order_master::where('id', $order_id)->delete();
						return response()->json(["meta" => ["status" => 'success',"message" => 'Order removed successfully'],"data" => [] ], 200);

					}else{
						return response()->json(["meta" => ["status" => 'failed',"message" => 'undelivered order can not be removed'],"data" => [] ], 400);
					}

				}else {

					$errors = $validator->errors();
					return response()->json(["meta" => ["status" => 'failed',"message" => 'Order not found'],"data" => [] ], 400);
				}
			}

		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}

	public function get(Request $request) {

		try{

			$input = $request->all();
			$validator = Validator::make($input,['order_id'=>'required']);

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json(["meta" => ["status" => 'failed',"message" => $errors->first()],"data" => [] ], 400);

			}else{
				$order_id = $request->input('order_id');
				$data = Order_master::where('id', $order_id)->with(['order_items'])->get();

				if(!empty($data)){
					return response()->json(["meta" => ["status" => 'success',"message" => 'Order details'],"data" => $data], 200);

				}else{
					return response()->json(["meta" => ["status" => 'success',"message" => 'Order Not Found'],"data" => [] ], 200);

				}
			}

		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}


	public function getList(Request $request) {

		try{

			$input = $request->all();
			$orderdata = new Order_master();
			
			if ($request->filled('customer_name')) {
				$customer_name = $input['customer_name'];
				$orderdata = $orderdata->where('customer_name', 'LIKE', "%$customer_name%");

			}

			if ($request->filled('status')) {
				$status = $input['status'];
				$orderdata = $orderdata->whereRaw("order_status = '".$status."'");

			}

			if($request->filled('paging')) {

				$data = $orderdata->with(['order_items'])->paginate($input['paging'])->toArray();
				$next = "true";

				if( $data['last_page'] == $input['page'] ) {
					$next = "false";
				}
				return response()->json([ "meta" => [ "status" => "success", "message" => "Order list", "next" => $next], "data" => $data], 200);

			}else{

				$data = $orderdata->with(['order_items'])->get();
				if( !empty($data) ) {
					return response()->json(["meta" => ["status" => 'success',"message" => 'Order details'],"data" => $data], 200);

				}else{
					return response()->json(["meta" => ["status" => 'success',"message" => 'No Order Found'],"data" => [] ], 200);

				}
			}

		}catch(\Exception $ex) {
			return response()->json(["meta" => ["status" => 'failed',"message" => 'Error: '.$ex->getMessage()],"data" => [] ], 500);
		}
	}
}
