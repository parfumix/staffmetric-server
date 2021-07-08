<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeamsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return \App\Http\Resources\TeamResource::collection(
            \Auth::user()->ownedTeams 
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
            'name' => 'required',
            'type' => 'required',
        ]);

        $team = new \App\Models\Team([
            'owner_id' => \Auth::id(),
            'name' => $attr['name'],
            'type' => $attr['type'],
        ]);
    
        $team->save();

        \Auth::user()->attachTeam($team);

        return new \App\Http\Resources\TeamResource($team);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Models\Team $team) {
        return new \App\Http\Resources\TeamResource($team);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, \App\Models\Team $team) {
        if(! \Auth::user()->isOwnerOfTeam($team)) {
            return response()->json(['message' => 'Error'], 500);
        }
        
        $attr = $request->validate([
            'name' => 'required',
        ]);

        $team->update($attr);
        return new \App\Http\Resources\TeamResource($team);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(\App\Models\Team $team) {
        if(! \Auth::user()->isOwnerOfTeam($team)) {
            return response()->json(['message' => 'Error'], 500);
        }

        $team->delete();
        return response()->json(['success' => true]);
    }
}
