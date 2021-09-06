<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public static $userKey;

    public function __construct()
    {
        self::$userKey = request()->header('X-USER-KEY');
    }

    /**
     * @OA\Get (
     *      path="/wishlist",
     *      tags={"Wishlist"},
     *      summary="Get User Wishlist",
     *      description="get user wishlist",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
     * @return JsonResponse
     */
    public function getWishlist(): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);
        return self::fetchWishlistAsJSON();
    }

    /**
     * @OA\Post (
     *      path="/wishlist/add/{product_id}",
     *      tags={"Wishlist"},
     *      summary="Add To Wishlist",
     *      description="add to wishlist",
     *
     *     @OA\Parameter(
     *          name="product_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
    public function addToWishlist(Product $product): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);

        $wishlist = self::prepareUserWishlist();
        foreach ($wishlist as $item)
            if ($item['product_id'] == $product['id'])
                return response()->json(['status' => false, 'errors' => 'Product already added to wishlist'], 400);

        if ($user = User::find(auth('api')->id())) {
            $user->wishlist()->create(['product_id' => $product['id']]);
        }
        else {
            $userKey = self::$userKey ?? request()->header('X-USER-KEY');
            Wishlist::create(['user' => $userKey, 'product_id' => $product['id']]);
        }
        return self::fetchWishlistAsJSON();
    }

    /**
     * @OA\delete (
     *      path="/wishlist/remove/{product_id}",
     *      tags={"Wishlist"},
     *      summary="Remove From Wishlist",
     *      description="remove from wishlist",
     *
     *     @OA\Parameter(
     *          name="product_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
    public function removeFromWishlist(Product $product): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);

        $wishlist = self::prepareUserWishlist();
        foreach ($wishlist as $item)
            if ($item['product_id'] == $product['id']) $item->delete();
        return self::fetchWishlistAsJSON();
    }

    public static function fetchWishlistAsJSON(): JsonResponse
    {
        $wishlist = self::prepareUserWishlist();
        $items = [];
        foreach ($wishlist as $item) {
            $items[] = new ProductResource($item->product);
        }
        return response()->json(['status' => true, 'data' => ProductResource::collection($items)]);
    }

    public static function prepareUserWishlist()
    {
        if ($user = User::find(auth('api')->id())) {
            $wishlist = $user->wishlist()->with('product')->get();
        }
        else {
            $userKey = self::$userKey ?? request()->header('X-USER-KEY');
            $wishlist = Wishlist::where('user', $userKey)->with('product')->get();
        }
        return $wishlist;
    }

    public static function moveUserWishlistToDatabase($key)
    {
        try {
            $user = User::find(auth('api')->id());
            $wishlist = Wishlist::where('user', $key)->get();
            if ($wishlist) {
                foreach ($wishlist as $item) {
                    if (!$user->wishlist()->where('id', $item['id'])->first())
                        if ($user->wishlist()->create(['product_id' => $item['product_id']]))
                            $item->delete();
                }
                $wishlist->delete();
            }
        } catch(Exception $e) {
            logger('Wishlist Error: ' . $e->getMessage());
        }
    }
}
