<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = array(
            0 =>
                array(
                    'id' => 1,
                    'customerId' => 1,
                    'items' =>
                        array(
                            0 =>
                                array(
                                    'productId' => 102,
                                    'quantity' => 10,
                                    'unitPrice' => '11.28',
                                    'total' => '112.80',
                                ),
                        ),
                    'total' => '112.80',
                ),
            1 =>
                array(
                    'id' => 2,
                    'customerId' => 2,
                    'items' =>
                        array(
                            0 =>
                                array(
                                    'productId' => 101,
                                    'quantity' => 2,
                                    'unitPrice' => '49.50',
                                    'total' => '99.00',
                                ),
                            1 =>
                                array(
                                    'productId' => 100,
                                    'quantity' => 1,
                                    'unitPrice' => '120.75',
                                    'total' => '120.75',
                                ),
                        ),
                    'total' => '219.75',
                ),
            2 =>
                array(
                    'id' => 3,
                    'customerId' => 3,
                    'items' =>
                        array(
                            0 =>
                                array(
                                    'productId' => 102,
                                    'quantity' => 6,
                                    'unitPrice' => '11.28',
                                    'total' => '67.68',
                                ),
                            1 =>
                                array(
                                    'productId' => 100,
                                    'quantity' => 10,
                                    'unitPrice' => '120.75',
                                    'total' => '1207.50',
                                ),
                        ),
                    'total' => '1275.18',
                ),
        );

        return response()->json([
            "success" => true,
            "title" => "",
            "detail" => "",
            "data" => [
                "orders" => $data
            ]
        ])->setStatusCode(200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $res_data = [
            "success" => true,
            "title" => "",
            "detail" => ""
        ];
        try {
            $req_data = $request->all();
            $validator = \Validator::make($req_data, [
                'customerId' => ['required', 'int']
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors());
            }

            foreach ($req_data["items"] as $item) {
                $p_id = $item["productId"];
                $product = $this->getProduct($p_id);
                if ($product["stock"] < 1) {
                    throw new \Exception($product["name"] . " bu ürüne ait yeterli stok mevcut değil");
                }
            }

            $new_order = new Order();

            $new_order->customer_id = $req_data["customer_id"];
            $new_order->total = $req_data["total"];
            $new_order->save();

            $res_data["title"] = $new_order->id . " order created";

            return response()->json($res_data)->setStatusCode(200);
        } catch (\Exception $e) {
            $res_data["success"] = false;
            $res_data["error_message"] = $e->getMessage();
            return response()->json($res_data)->setStatusCode(400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getDiscoutCalculate($order_id)
    {
        $discounts = [
            "orderId" => $order_id,
            "totalDiscount" => 0,
            "discountedTotal" => ""
        ];

        $order = Order::where("id", $order_id)->get();

        $discounts["discountedTotal"] = $order->total;

        $order_items = DB::table('order_items')->where(["order_id", $order_id])->get();

        foreach ($order_items as $order_item) {

        }
        return response()->json([
            "success" => true,
            "title" => "",
            "detail" => "",
            "data" => [
                "discounts" => $discounts
            ]
        ])->setStatusCode(200);

    }

    private function getProduct($product_id)
    {
        $product = Product::where("id", $product_id)->first();
        return $product;
    }

}
