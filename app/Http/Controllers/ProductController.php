<?php

namespace App\Http\Controllers;

use App\Category;
use App\Gallery;
use App\Http\Traits\Seo;
use App\Product;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Class ProductController
 * @package App\Http\Controllers
 */
class ProductController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|Application|Response|View
     */
    public function index()
    {


        $products = Product::orderBy('id','desc')->get();
        return view('admin.product-list',compact('products'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $categories = Category::where('role','main')->get();
        return view('admin.productadd',compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function store(Request $request, Product $data)
    {
        $data->fill($request->all());
        $data->category = $request->mainid.",".$request->subid.",".$request->childid;

        if ($file = $request->file('photo')){
            $photo_name = time().$request->file('photo')->getClientOriginalName();
            $file->move('assets/images/products',$photo_name);
            $data['feature_image'] = $photo_name;
        }
        if ($request->featured == 1){
            $data->featured = 1;
        }
        $data->save();
        $lastid = $data->id;

        if ($files = $request->file('gallery')){
            foreach ($files as $file){
                $gallery = new Gallery;
                $image_name = str_random(2).time().$file->getClientOriginalName();
                $file->move('assets/images/gallery',$image_name);
                $gallery['image'] = $image_name;
                $gallery['productid'] = $lastid;
                $gallery->save();
            }
        }
        Session::flash('message', 'New Product Added Successfully.');
        return redirect('admin/products');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Product $product
     * @return Response
     */
    public function edit($id, Product  $product)
    {
        $product -> findOrFail($id);
        $child = Category::where('role','child')->where('subid',$product->category[1])->get();
        $subs = Category::where('role','sub')->where('mainid',$product->category[0])->get();
        $categories = Category::where('role','main')->get();
        return view('admin.productedit',compact('product','categories','child','subs'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @param Product $product
     * @return Response
     */
    public function update(Request $request, $id, Product $product)
    {
        $product->findOrFail($id);
        $input = $request->all();
        $input['category'] = $request->mainid.",".$request->subid.",".$request->childid;
        if ($file = $request->file('photo')){
            $photo_name = time().$request->file('photo')->getClientOriginalName();
            $file->move('assets/images/products',$photo_name);
            $input['feature_image'] = $photo_name;
        }

        if ($request->galdel == 1){
            $gal = Gallery::where('productid',$id);
            $gal->delete();
        }

        if ($request->featured == 1){
            $input['featured'] = 1;
        }else{
            $input['featured'] = 0;
        }

        $product->update($input);

        if ($files = $request->file('gallery')){

            foreach ($files as $file){
                $gallery = new Gallery;
                $image_name = str_random(2).time().$file->getClientOriginalName();
                $file->move('assets/images/gallery',$image_name);
                $gallery['image'] = $image_name;
                $gallery['productid'] = $id;
                $gallery->save();
            }
        }
        Session::flash('message', 'Product Updated Successfully.');

        return redirect('admin/products');
    }

    /**
     * @param $id
     * @param $status
     * @param Product $product
     * @return Application|RedirectResponse|Redirector
     */
    public function status($id , $status, Product $product)
    {
        $product->findOrFail($id);
        $product->update(['status' => $status]);
        Session::flash('message', 'Product Status Updated Successfully.');
        return redirect('admin/products');
    }

    /**
     * Remove the specified resource from storage.
     * @method destroy
     * @param int $id
     * @return Response
     * @throws \Exception
     */
    public function destroy($id, Product $product)
    {
        $product->findOrFail($id);
        unlink('assets/images/products/'.$product->feature_image);
        $product->delete();
        return redirect('admin/products')->with('message','Product Delete Successfully.');
    }
}
