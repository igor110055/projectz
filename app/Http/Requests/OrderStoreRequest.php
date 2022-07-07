<?php

namespace App\Http\Requests;

use App\Models\Pair;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // not for now
    }

    /**
     *  Prepare the data for validation.
     *
     * @return array
     */
/*
     protected function prepareForValidation()
    {
        $date = new \DateTime();
        $date = $date->setTimestamp($this->transactTime);

        $this->merge([
            'pairId'               => Pair::where('name',$this->symbol)->first()->id,
            'orderDateTime'        => $date,
            'userId'               => 2,
            'orderTimestamp'       => $this->transactTime,
            'orderUpdateTimestamp' => $this->transactTime,
            'exchanges'            => 'Binance'
        ]);
    }
*/
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
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
            'isWorking'            => 'boolean|defult:1',
            'timeInForce'          => 'string|nullable',
            'stopPrice'            => 'float|nullable',
            'orderTimestamp'       => 'integer|required',
            'orderUpdateTimestamp' => 'integer|required',
            'orderDateTime'        => 'date|required',
            'userId'               => 'integer|required',
            'exchanges'            => 'string|nullable'
        ];
    }

    /**
     * Get custom attributes for validator errors
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'symbol'        => 'sembol',
            'pairId'        => 'işlem çifti numarası',
            'orderId'       => 'talimat numarası',
            'clientOrderId' => 'müşteri talimat numarası',
            'type'          => 'tip',
            'side'          => 'yön',
            'price'         => 'fiyat'
        ];
    }
}
