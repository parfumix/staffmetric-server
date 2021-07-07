<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoalsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        return \App\Http\Resources\GoalResource::collection( $request->user()->goals );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $attr = $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'tracking' => 'required',
            'value' => 'required',
            'color' => 'required',
            'due_date' => 'nullable|date',
        ]);

        $goal = \App\Models\Goal::create([
            'title' => $attr['title'],
            'description' => $attr['description'] ?? null,
            'tracking' => $attr['tracking'],
            'value' => $attr['value'],
            'user_id' => \Auth::id(),
            'due_date' => $attr['due_date'],
            'options' => [
                'color' => $attr['color']
            ]
        ]);
       
        return new \App\Http\Resources\GoalResource($goal);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Models\Goal $goal) {
        return new \App\Http\Resources\GoalResource($goal);
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
    public function destroy(\App\Models\Goal $goal) {
        $goal->delete();
    }
}
