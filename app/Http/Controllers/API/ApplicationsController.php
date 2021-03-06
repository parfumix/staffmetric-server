<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\App;
use Illuminate\Http\Request;

class ApplicationsController extends Controller {
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $user_applications = $request->user()->apps;

        $globl_applications = \App\Models\App::whereNotIn(
            'name', $user_applications->pluck('name')->values()
        )->ordered()->get();

        return ApplicationResource::collection( 
            collect($globl_applications)->merge($user_applications)->sortBy('order_column')
         );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $attr = $request->validate([
            'application_id' => 'required',
            'category_id' => 'required',
        ]);

        $application = \App\Models\App::find($attr['application_id']);
        $user_app = $request->user()->apps()->where('id', $attr['application_id'])->first();

        if(! $user_app) {
            $user_app = \Auth::user()->apps()->save(new \App\Models\App([
                'name' => $application->name,
                'category_id' => $attr['category_id'],
            ]));

            \App\Models\App::setNewOrder([$user_app->id], $application['order_column']);
        } else {
            $user_app = \Auth::user()->apps()->updateOrCreate(
                ['id' => $attr['application_id']],
                ['category_id' => $attr['category_id']],
            );
        }

        return new ApplicationResource(
            \App\Models\App::find( $user_app->id )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Models\App $application) {
        return new ApplicationResource($application);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, \App\Models\App $application) {
        $request->user()->apps()->where('id', $application->id)->delete();
        return response()->json(['success' => true]);
    }
}
