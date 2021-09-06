<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SubCategoryResource;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;

class SubCategoryController extends Controller
{
    /**
     * @OA\Get (
     *      path="/subcategories",
     *      tags={"SubCategories"},
     *      summary="Get All SubCategories",
     *      description="get all subcategories",
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
        $categories = SubCategory::with('category')->get();
        return response()->json(['status' => true, 'data' => SubCategoryResource::collection($categories)]);
    }

    /**
     * @OA\Get (
     *      path="/subcategories/{subCategory_id}",
     *      tags={"SubCategories"},
     *      summary="Show SubCategory",
     *      description="show subcategory",
     *
     *     @OA\Parameter(
     *          name="subCategory_id",
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
    public function show(SubCategory $subCategory): JsonResponse
    {
        return response()->json(['status' => true, 'data' => new SubCategoryResource($subCategory)]);
    }
}
