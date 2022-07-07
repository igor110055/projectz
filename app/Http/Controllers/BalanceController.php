<?php

namespace App\Http\Controllers;

use App\Services\CalculationServices\UserWalletCalculations as UWC;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function allDetails(Request $request, UWC $uwc){
        return $uwc->detailStatistics();
    }
}
