<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;

class DeviceController extends Controller {
    

    public function create(Request $request) {
        $attr = $request->validate([
            'name' => 'required',
            'uuid' => 'required',
            'os' => 'required',
        ]);

        $uuid = Uuid::generate(5, $request->get('uuid'), Uuid::NS_DNS)->string;

        if( ! $device = \Auth::user()->devices()->where('uuid', $uuid)->first() ) {
            $device = \Auth::user()->devices()->create([
                'uuid' => $uuid,
                'name' => $request->get('name'),
                'os' => $request->get('os'),
                'last_update_at' => now()
            ]);
        }

        return response()->json([
            'data' => $device->toArray()
        ]);
    }

    // authenticate user by UUID
    public function login(Request $request) {
        $attr = $request->validate([
            'uuid' => 'required',
        ]);

        $device = \App\Models\Device::ofUuid( $attr['uuid'] )->get()->first();

        if(! $device) {
            $user = \App\Models\User::create([
                'name' => 'ad',
                'email' => 'ad@mail.ru',
                'password' => bcrypt('secret'),
                'email_verified_at' => now(),
            ]);
        }

        return response()->json([
            'token' => $user->createToken('DEVICE Token')->plainTextToken,
		]);
    }
}
