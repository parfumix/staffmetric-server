<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UploadController extends Controller {
    
    public function activities(Request $request, \App\Models\Device $device) {
        $data = $request->get('data', []);

        foreach ($data as $item) {
            $request->user()->activities()->create([
                'device_id' => $device->id,
                'app' => $item['app'],
                'full_url' => $item['url'],
                'duration' => $item['duration'],
                'start_at' => $item['start_at'],
                'end_at' => $item['end_at']
            ]);
        }

        $device->update(['last_update_at' => now()]);

        return response()
            ->json([
                'success' => true,
                'message' => trans('Successfully uploaded'),
            ]);
    }
}
