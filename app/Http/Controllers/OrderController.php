<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderCollection;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|object
     */
    public function index()
    {
        return response()->json([
            "success" => true,
            "title" => "",
            "detail" => "",
            "data" => [
                "orders" => new  OrderCollection(Order::get())
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
                'customer_id' => ['required', 'int']
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors());
            }

            foreach ($req_data["items"] as $item) {
                $p_id = $item["product_id"];
                $product = $this->getProduct($p_id);
                if (($product["stock"] < 1) || ($item["quantity"] > $product["stock"])) {
                    throw new \Exception($product["name"] . " bu ürüne ait yeterli stok mevcut değil");
                }
            }

            $new_order = new Order();

            $new_order->customer_id = $req_data["customer_id"];
            $new_order->total = $req_data["total"];
            $new_order->save();
            $order_id = $new_order->id;
            foreach ($req_data["items"] as $item) {
                $p_id = $item["product_id"];
                $p_qtn = $item["quantity"];
                $p_unit_price = $item["unit_price"];
                $p_total = $item["total"];
                DB::table("order_items")
                    ->insert(
                        [
                            "order_id" => $order_id,
                            "product_id" => $p_id,
                            "quantity" => $p_qtn,
                            "unit_price" => $p_unit_price,
                            "total" => $p_total
                        ]
                    );

                DB::table('products')
                    ->where('id', $p_id)
                    ->decrement('stock', $p_qtn);
            }


            $res_data["title"] = $order_id . " order created";

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
        $res_data = [
            "success" => true,
            "title" => "",
            "detail" => ""
        ];
        try {

            DB::table('order_items')->where('order_id', '=', $id)->delete();

            $order_ = Order::find($id);
            $order_->delete();

            $res_data["title"] = $id . " order deleted";

            return response()->json($res_data)->setStatusCode(200);
        } catch (\Exception $e) {
            $res_data["success"] = false;
            $res_data["error_message"] = $e->getMessage();
            return response()->json($res_data)->setStatusCode(400);
        }
    }

    public function getDiscoutCalculate($order_id)
    {
        $res_discounts = [
            "order_id" => $order_id,
            "discounts" => [],
            "total_discount" => 0,
            "discounted_total" => 0,
        ];

        $order = Order::where("id", $order_id)->first();
        $order_total = $order->total;

        $total_discount_list = DB::table('order_discount_total')->where("type", "=", "total")->orderBy("priority")->get();

        foreach ($total_discount_list as $td) {
            if ($td->type == "total") {

                $d_value = $td->value;
                $d_type = $td->discount_type;

                if ($td->operator === "greater_than_or_equal") {
                    if ($this->greater_than_or_equal($order_total, $d_value)) {
                        if ($d_type == "percent") {
                            $discount["discount_amount"] = ($order_total * (10 / 100));
                            $discount["discount_reason"] = $td->reason;
                            $discount["subtotal"] = round($order_total - ($order_total * (10 / 100)));

                            array_push($res_discounts["discounts"], $discount);

                            $res_discounts["discounted_total"] = $discount["subtotal"];
                            $res_discounts["total_discount"] =+ $discount["discount_amount"];

                        } else if ($d_type == "fixed") {

                        }
                    }

                }
            }
        }


        // discount_rules

        // type > category - product - total -
        // min quantity
        // max  quantity
        // discount_type > percent price

        return response()->json([
            "success" => true,
            "title" => "",
            "detail" => "",
            "data" =>  $res_discounts

        ])->setStatusCode(200);

    }

    private function getProduct($product_id)
    {
        $product = Product::where("id", $product_id)->first();
        return $product;
    }

    private function greater_than_or_equal($v1, $v2)
    {
        return $v1 >= $v2;
    }

}
