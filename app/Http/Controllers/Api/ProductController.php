<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\ProductResource;
use App\Http\Resources\Api\SubCategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * @OA\Get (
     *      path="/api/products",
     *      tags={"Product"},
     *      summary="Get All Products",
     *      description="get all products",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $products = Product::with(['categories', 'media'])->get();
        return response()->json(['status' => true, 'data' => ProductResource::collection($products)]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/show/{product_id}",
     *      tags={"Product"},
     *      summary="Get Product Details",
     *      description="get product detail",
     *
     *     @OA\Parameter(
     *          name="product_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        $categories = $product->categories()->get()->map(function($category){
            return $category['id'];
        });

        $related = Product::query()->whereHas('categories', function($q) use ($categories) {
            $q->whereIn('categories.id', $categories);
        })->where('id', '!=', $product['id'])->get();

        return response()->json(['status' => true, 'data' => ['product' => new ProductResource($product), 'related_products' => $related]]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/category/{category_id}",
     *      tags={"Product"},
     *      summary="Get Products By Category",
     *      description="get products by category",
     *
     *     @OA\Parameter(
     *          name="category_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function get_products_by_category(Category $category): JsonResponse
    {
        $products = $category->products()->get();
        return response()->json(['status' => true, 'data' => ['category' => new CategoryResource($category), 'products' => ProductResource::collection($products)]]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/subcategory/{sub_category_id}",
     *      tags={"Product"},
     *      summary="Get Products By SubCategory",
     *      description="get products by subcategory",
     *
     *     @OA\Parameter(
     *          name="sub_category_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param SubCategory $subCategory
     * @return JsonResponse
     */
    public function get_products_by_subcategory(SubCategory $subCategory): JsonResponse
    {
        $products = $subCategory->products()->get();
        return response()->json(['status' => true, 'data' => ['subCategory' => new SubCategoryResource($subCategory), 'products' => ProductResource::collection($products)]]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/deals",
     *      tags={"Product"},
     *      summary="Get Disconted Products",
     *      description="get discounted products",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param $category
     * @return JsonResponse
     */
    public function get_discounted_deals($category = null): JsonResponse
    {
        $products =  Product::query()
            ->where('discount', '>', 0)
            ->with(['categories', 'media'])
            ->orderBy('discount', 'desc');
        if ($category)
            $products = $products->whereHas('categories', function ($q) use ($category) { $q->where('name', $category); });

        return response()->json(['status' => true, 'data' => ProductResource::collection($products->get())]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/top",
     *      tags={"Product"},
     *      summary="Get Top Selling Products",
     *      description="get top selling products",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param $category
     * @return JsonResponse
     */
    public function get_top_selling($category = null): JsonResponse
    {
        $products =  Product::query()
            ->orderBy('sold', 'desc');
        if ($category)
            $products = $products->whereHas('categories', function ($q) use ($category) { $q->where('name', $category); });
        return response()->json(['status' => true, 'data' => ProductResource::collection($products->get())]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/new",
     *      tags={"Product"},
     *      summary="Get Products By New Arrivals",
     *      description="get products by new arrivals",
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param $category
     * @return JsonResponse
     */
    public function get_new_arrivals($category = null): JsonResponse
    {
        $products =  Product::query()->latest();
        if ($category)
            $products = $products->whereHas('categories', function ($q) use ($category) { $q->where('name', $category); });
        return response()->json(['status' => true, 'data' => ProductResource::collection($products->get())]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/filter",
     *      tags={"Product"},
     *      summary="Filter Products By (price range)",
     *      description="get discounted products",
     *
     *     @OA\Parameter(
     *          name="from",
     *          in="query",
     *          required=false,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="to",
     *          in="query",
     *          required=false,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param $category
     * @return JsonResponse
     */
    public function filter_products($category = null): JsonResponse
    {
        $products = Product::query();
//        $variations = request('variations');
//        $brands = request('brands');
        $from = request('from');
        $to = request('to');
//        if ($variations)
//            $products->whereHas('variationItems', function ($q) use ($variations) {
//                $q->whereIn('variation_items.id', $variations);
//            });
        if ($from && $to)
            $products->whereBetween('price', [$from, $to]);
//        if ($brands)
//            $products->whereHas('brands', function ($q) use ($brands) {
//                $q->whereIn('brands.id', $brands);
//            });
        if ($category)
            $products = $products->whereHas('categories', function ($q) use ($category) { $q->where('name', $category); });

        return response()->json(['status' => true, 'data' => ProductResource::collection($products->get())]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/sort",
     *      tags={"Product"},
     *      summary="Sort Products By (name, sale, price)",
     *      description="get discounted products",
     *
     *     @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string",
     *              enum={"name", "sale", "price"}
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @return JsonResponse
     */
    public function sort_products(): JsonResponse
    {
        $products = Product::query();
        if (request('sort') == 'sale')
            $products = $products->orderBy('sold');
        if (request('sort') == 'price')
            $products = $products->orderBy('price');
        if (request('sort') == 'name')
            $products = $products->orderBy('name');

        return response()->json(['status' => true, 'data' => ProductResource::collection($products->get())]);
    }

    /**
     * @OA\Get (
     *      path="/api/products/search/{search}",
     *      tags={"Product"},
     *      summary="Search Products",
     *      description="search products",
     *
     *     @OA\Parameter(
     *          name="search",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      )
     *     )
     *
     * @param $search
     * @return JsonResponse
     */
    public function search_products($search): JsonResponse
    {
        $products = Product::query()->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', '%'.$search.'%')
                ->orWhere('slug', 'LIKE', '%'.$search.'%')
                ->orWhere('description', 'LIKE', '%'.$search.'%');
        })->get();

        return response()->json(['status' => true, 'data' => ProductResource::collection($products)]);
    }
}
