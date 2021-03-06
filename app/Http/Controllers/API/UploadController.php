<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UploadController extends Controller {
    
    public function activities(Request $request, \App\Models\Device $device) {
        $data = $request->get('data', []);

        $to_insert = [];
        foreach ($data as $item) {
            $to_insert[] = [
                'user_id' => \Auth::id(),
                'device_id' => $device->id,
                'app' =>  $item['app'],
                'is_url' => $item['is_url'] ? true : false,
                'duration' => $item['duration'],
                'start_at' => $item['start_at'],
                'end_at' => $item['end_at']
            ];
        }

        \DB::table('activities')->insert($to_insert);

        $device->update(['last_update_at' => now()]);

        return response()
            ->json([
                'success' => true,
                'message' => trans('Successfully uploaded'),
            ]);
    }
}
