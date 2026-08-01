<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

final class DiscountStoreSubmittedController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('discount-stores.submitted', [
            'storeName' => $request->session()->get('submitted_store_name'),
        ]);
    }
}
