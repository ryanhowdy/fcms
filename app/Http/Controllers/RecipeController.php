<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeCategory;

class RecipeController extends Controller
{
    /**
     * Show the main recipe page
     *
     * @return Illuminate\View\View
     */
    public function index()
    {
        $recipes = Recipe::latest()
            ->take(10)
            ->get();

        return view('recipes.index', ['recipes' => $recipes]);
    }

    /**
     * create 
     * 
     * @return Illuminate\View\View
     */
    public function create()
    {
        $categories = RecipeCategory::orderBy('name')
            ->get();

        if ($categories->isEmpty())
        {
            return redirect()->route('recipes.categories.create');
        }

        return view('recipes.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store the new discussion in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required'],
            'thumbnail'   => ['required'],
            'category'    => ['required', 'integer'],
            'ingredients' => ['required', 'array'],
            'directions'  => ['required'],
        ]);

        $recipe = new Recipe;

        $recipe->name               = $request->name;
        $recipe->thumbnail          = $request->thumbnail;
        $recipe->recipe_category_id = $request->category;
        $recipe->directions         = $request->directions;
        $recipe->created_user_id    = Auth()->user()->id;
        $recipe->updated_user_id    = Auth()->user()->id;

        foreach ($request->ingredients as $i)
        {
            $recipe->ingredients .= $i."\r\n";
        }

        $recipe->save();

        return redirect()->route('recipes');
    }

    /**
     * categoryCreate 
     * 
     * @return Illuminate\View\View
     */
    public function categoryCreate()
    {
        return view('recipes.category-create');
    }

    /**
     * Store the new category in the db
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Support\Facades\View
     */
    public function categoryStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required'],
        ]);

        $category = new RecipeCategory;


        if ($request->has('description'))
        {
            $category->description = $request->description;
        }

        $category->name            = $request->name;
        $category->created_user_id = Auth()->user()->id;
        $category->updated_user_id = Auth()->user()->id;

        $category->save();

        return redirect()->route('recipes.create');
    }

    /**
     * show 
     * 
     * @param int $id 
     * @return Illuminate\View\View
     */
    public function show (int $id)
    {
        $recipe = Recipe::findOrFail($id);

        $recipe->directions = htmlspecialchars($recipe->directions, ENT_QUOTES, 'UTF-8');
        $recipe->directions = \Illuminate\Mail\Markdown::parse($recipe->directions);

        return view('recipes.show', ['recipe' => $recipe]);
    }
}
