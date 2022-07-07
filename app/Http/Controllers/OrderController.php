<?php

namespace App\Http\Controllers;

use App\Models\Pair;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\OrderStoreRequest;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function store($data)
    {
        // if(arry_keys($data))
        return Order::create([
            'symbol'               => $data['symbol'],
            'pairId'               => Pair::whereName($data['symbol'])->first()->id,
            'balanceId'            => $data['balanceId'],
            'remoteOrderId'        => $data['orderId'],
            'clientOrderId'        => 0,
            'type'                 => $data['type'],
            'side'                 => $data['side'],
            'price'                => $data['price'],
            'origQty'              => $data['origQty'],
            'icebergQty'           => 0,
            'executedQty'          => $data['executedQty'],
            'cumulativeQuoteQty'   => 0,
            'status'               => $data['status'],
            'isWorking'            => true,
            'orderTimestamp'       => $data['time'],
            'orderUpdateTimestamp' => $data['updateTime'],
            'orderDateTime'        => createFromTimestamp($data['time']),
            'userId'               => 2,
        ]);
    }

    /*
    $validator = Validator::make($request->all(), [
            'symbol'               => 'string|required|max:20',
            'pairId'               => 'integer|required',
            'orderId'              => 'integer|required',
            'clientOrderId'        => 'nullable',
            'type'                 => 'string|required',
            'side'                 => 'string|required',
            'price'                => 'required',
            'origQty'              => 'required',
            'icebergQty'           => 'nullable',
            'executedQty'          => 'nullable',
            'cumulativeQuoteQty'   => 'nullable',
            'status'               => 'string|required',
            'isWorking'            => 'boolean|nullable',
            'timeInForce'          => 'string|nullable',
            'stopPrice'            => 'float|nullable',
            'orderTimestamp'       => 'integer|required',
            'orderUpdateTimestamp' => 'integer|required',
            'orderDateTime'        => 'date|required',
            'userId'               => 'integer|required',
            'exchanges'            => 'string|nullable'
        ]);

        if($validator->fails()){
            dd($validator);
            return response()->json($validator->errors());
        } else {
            return Order::create($request->validated());
        }

        */
}
