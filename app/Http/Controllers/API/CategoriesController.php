<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $user_categories = $request->user()->categories;

        $globl_categories = \App\Models\Category::whereNotIn(
            'title', $user_categories->pluck('title')->values()
        )->get();

        return Category::collection( 
            collect($globl_categories)->merge($user_categories)
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
            'category_id' => 'required',
            'productivity' => 'required',
        ]);

        $category = \App\Models\Category::find($attr['category_id']);
        $user_category = $request->user()->categories()->where('id', $attr['category_id'])->first();

        if(! $user_category) {
            $user_category = \Auth::user()->categories()->save(new \App\Models\Category([
                'title' => $category->title,
                'productivity' => $attr['productivity'],
            ]));
        } else {
            \Auth::user()->categories()->updateOrCreate(
                ['id' => $attr['category_id']],
                ['productivity' => $attr['productivity']],
            );
        }

        return new Category($user_category);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Models\Category $category) {
        return new Category($category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, \App\Models\Category $category) {
        $request->user()->categories()->where('id', $category->id)->delete();
        return response()->json(['success' => true]);
    }
}
