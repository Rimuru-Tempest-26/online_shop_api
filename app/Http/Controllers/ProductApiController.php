<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResoucre;
use App\Models\Photo;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::when(request("keyword"), fn($q)=>$q->where("name", "like", "%".request("keyword")."%"))
                    ->latest("id")
                    ->paginate(5)
                    ->withQueryString()
                    ->onEachSide(1);
//        return response()->json($products);
        return ProductResoucre::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        sleep(5);
        $request->validate([
            "name"=>"required|min:3|max:50",
            "price"=>"required|numeric|min:1",
            "stock"=>"required|numeric|min:1",
            "photos"=>"required",
            "photos.*"=>"mimes:jpeg,png|max:512"
        ]);

        $product = Product::create([
            "name"=>$request->name,
            "price"=>$request->price,
            "stock"=>$request->stock,
            "user_id"=>Auth::id()
        ]);

        $photos=[];
        foreach ($request->file('photos') as $key=>$photo){
            $newName = $photo->store("public");
            $photos[$key] = new Photo(["name"=>$newName]);
        }

        $product->photos()->saveMany($photos);

        return response()->json([
            "success" => true,
            "message" => "Product created successfully!",
            "product" => new ProductResoucre($product)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);
        if(is_null($product)){
            return response()->json(["message"=>"Product Not Found!"], 404);
        }

//        return response()->json($product);
        return new ProductResoucre($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            "name"=>"nullable|min:3|max:50",
            "price"=>"nullable|numeric|min:1",
            "stock"=>"nullable|numeric|min:1"
        ]);

        $product = Product::find($id);
        if(is_null($product)){
            return response()->json(["message"=>"Product Not Found!"], 404);
        }

        if($request->has("name")){
            $product->name = $request->name;
        }
        if($request->has("price")){
            $product->price = $request->price;
        }
        if($request->has("stock")){
            $product->stock = $request->stock;
        }
        $product->update();

        return response()->json([
            "success" => true,
            "message" => "Product updated successfully!",
            "product" => new ProductResoucre($product)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if(is_null($product)){
            return response()->json(["message"=>"Product Not Found!"], 404);
        }
        $product->delete();
        return response()->json(["message"=>"Product deleted successfully!"]);
    }
}
