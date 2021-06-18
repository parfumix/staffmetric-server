<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Application;
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
        $globl_applications = \App\Models\App::whereNotIn('name', $user_applications->pluck('name')->values())->get();

        return Application::collection( collect($globl_applications)->merge($user_applications) );
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

        $application = \App\Models\App::find($attr['id']);
        $user_app = $request->user()->apps()->where('id', $attr['application_id'])->first();

        if(! $user_app) {
            $user_app = \Auth::user()->apps()->create(new \App\Models\App([
                'title' => $application->title,
                'productivity' => $application->productivity,
            ]));
        } else {
            \Auth::user()->apps()->updateOrCreate(
                ['id' => $attr['application_id']],
                ['productivity' => $attr['category_id']],
            );
        }

        return new Application($user_app);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }
}
