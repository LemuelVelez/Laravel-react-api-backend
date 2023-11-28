<?php

namespace App\Http\Controllers\API;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function placeorder(Request $request)
    {
        try {
            if (auth('sanctum')->check()) {
                $validator = Validator::make($request->all(), [
                    'firstname' => 'required|max:191',
                    'lastname' => 'required|max:191',
                    'phone' => 'required|max:191',
                    'email' => 'required|max:191',
                    'address' => 'required|max:191',
                    'city' => 'required|max:191',
                    'state' => 'required|max:191',
                    'zipcode' => 'required|max:191',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'errors' => $validator->messages(),
                    ]);
                } else {
                    $user_id = auth('sanctum')->user()->id;

                    DB::beginTransaction();

                    try {
                        $order = new Order;
                        $order->user_id = $user_id;
                        $order->firstname = $request->firstname;
                        $order->lastname = $request->lastname;
                        $order->phone = $request->phone;
                        $order->email = $request->email;
                        $order->address = $request->address;
                        $order->city = $request->city;
                        $order->state = $request->state;
                        $order->zipcode = $request->zipcode;
                        $order->tracking_no = 'swiftshop' . rand(1111, 9999);
                        $order->save();

                        $cart = Cart::where('user_id', $user_id)->get();

                        $orderitems = [];
                        foreach ($cart as $item) {
                            $orderitems[] = [
                                'product_id' => $item->product_id,
                                'qty' => $item->product_qty,
                                'price' => $item->product->selling_price,
                            ];

                            $item->product->update([
                                'qty' => $item->product->qty - $item->product_qty,
                            ]);
                        }

                        $order->orderitems()->createMany($orderitems);
                        Cart::destroy($cart);

                        DB::commit();

                        return response()->json([
                            'status' => 200,
                            'message' => 'Order Placed Successfully',
                        ]);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Login to Continue',
                ]);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
            ]);
        }
    }
}