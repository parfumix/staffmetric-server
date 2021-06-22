<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class CategoriesController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $user_categories = $request->user()->categories()->ordered()->get();

        $globl_categories = \App\Models\Category::whereNotIn(
            'title', $user_categories->pluck('title')->values()
        )->ordered()->get();

        return CategoryResource::collection( 
            collect($globl_categories)->merge($user_categories)->sortBy('order_column')
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

            \App\Models\Category::setNewOrder([$user_category->id], $category['order_column']);
        } else {
            $user_category = \Auth::user()->categories()->updateOrCreate(
                ['id' => $attr['category_id']],
                ['productivity' => $attr['productivity']],
            );
        }

        return new CategoryResource(
            \App\Models\Category::find( $user_category->id )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Models\Category $category) {
        return new CategoryResource($category);
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
