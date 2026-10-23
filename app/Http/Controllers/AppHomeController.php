<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AppHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('app', ['shop' => $request->attributes->get('shop')]);
    }
}
