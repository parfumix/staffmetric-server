<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;
use App\Http\Controllers\Controller;

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
            'token' => 'nullable',
            'uuid' => 'required',
            'os' => 'required',
            'employer_id' => 'required',
        ]);

        //TODO if present token id than we should authenticate by token ID

        $device = \App\Models\Device::ofUuid( $attr['uuid'] )->get()->first();

        if(! $device) {
            $user = \App\Models\User::create([
                'profile_id' => \App\Models\Profile::first()->id,
                'name' => $attr['os'],
                'email' => $attr['uuid'] . '@mail.com',
                'password' => bcrypt('secret'),
            ]);

            $user->markEmailAsVerified();

            $employer = \App\Models\User::find($attr['employer_id']);

            $employer->employees()->attach($user->id);
        }

        return response()->json([
            'token' => $user->createToken('Device Token')->plainTextToken,
		]);
    }
}
