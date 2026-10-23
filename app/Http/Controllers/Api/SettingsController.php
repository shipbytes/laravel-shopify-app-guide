<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->attributes->get('shop')->settings);
    }

    public function update(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'auto_approve' => ['required', 'boolean'],
            'widget_title' => ['required', 'string', 'max:80'],
        ]);

        // updateOrCreate because the row does not exist until the merchant saves once.
        $settings = $shop->settings()->updateOrCreate([], $data);

        return response()->json($settings);
    }
}
