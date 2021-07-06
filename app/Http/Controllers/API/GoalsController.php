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
        $validated = $request->validate([
            'employees' => 'nullable|array|exists:App\Models\User,id',
        ]);

        $access_to_employees = \Auth::user()->employees(\App\Models\User::ACCEPTED)->get()->reject(function ($u) use($validated) {
            return count($validated['employees'] ?? [])
                ? !in_array($u->id, $validated['employees'])
                : false;
        });
        $employee_ids = $access_to_employees->pluck('name', 'id');
        $employer = \Auth::user();

        return \App\Http\Resources\GoalResource::collection(
            \App\Models\Goal::whereIn('user_id', $employee_ids)->get()
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
            'title' => 'required',
            'description' => 'nullable',
            'tracking' => 'required',
            'value' => 'required',
            'due_date' => 'nullable|date|date_format:"Y-m-d"',
        ]);

        $goal = \App\Models\Goal::create([
            'title' => $attr['title'],
            'description' => $attr['description'],
            'tracking' => $attr['tracking'],
            'value' => $attr['value'],
            'due_date' => $attr['due_date'],
        ]);
       
        return new \App\Http\Resources\GoalResource(
            \App\Models\Goal::find( $goal )
        );
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
    public function destroy($id) {
        //
    }
}
