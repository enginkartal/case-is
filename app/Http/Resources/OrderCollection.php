<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($item) {

            return [
                'id' => $item->id,
                'customer_id' => $item->customer_id,
                'total' => $item->total,
                'items' => DB::table('order_items')->leftJoin('products','order_items.product_id', '=', 'products.id')->select('order_items.*','products.name')->where(["order_id" => $item->id])->get(),
                'created_at' => $item->created_at->format('Y-m-d'),
                'created_at_for_human' => Carbon::parse($item["created_at"])->diffForHumans(Carbon::now())
            ];
        });
    }
}
