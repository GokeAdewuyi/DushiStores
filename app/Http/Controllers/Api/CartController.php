<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public static $userKey;

    public function __construct()
    {
        self::$userKey = request()->header('X-USER-KEY');
    }

    /**
     * @OA\Get (
     *      path="/cart",
     *      tags={"Cart"},
     *      summary="Get User Cart",
     *      description="get user cart",
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
    public function getCart(): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);
        return self::fetchCartAsJSON();
    }

    /**
     * @OA\Post (
     *      path="/cart/add/{product_id}",
     *      tags={"Cart"},
     *      summary="Add To Cart",
     *      description="add to cart",
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
     *     @OA\Parameter(
     *          name="quantity",
     *          in="query",
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
    public function addToCart(Product $product): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);
        $validator = Validator::make(request()->all(), ['quantity' => 'required|numeric|min:1']);
        if ($validator->fails()) return response()->json(['status' => false, 'errors' => $validator->getMessageBag()], 422);
        $newQuantity = (int) request('quantity') ?? 1;
        self::processAddToCart($product, $newQuantity);
        return self::fetchCartAsJSON();
    }

    /**
     * @OA\delete (
     *      path="/cart/remove/{product_id}",
     *      tags={"Cart"},
     *      summary="Remove From Cart",
     *      description="remove from cart",
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
    public function removeFromCart(Product $product): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);
        $cart = self::prepareUserCart();
        $cartItems = $cart->items;
        foreach ($cartItems as $item)
            if ($item['product_id'] == $product['id']) $item->delete();
        return self::fetchCartAsJSON();
    }

    /**
     * @OA\Post (
     *      path="/cart/clear",
     *      tags={"Cart"},
     *      summary="Clear Cart",
     *      description="clear user cart",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
     * @return JsonResponse
     */
    public function clearCart(): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);

        $cart = self::prepareUserCart();
        foreach ($cart->items as $item)
            $item->delete();
        return response()->json(['status' => true, 'message' => 'Cart cleared']);
    }


    public static function processAddToCart($product, $newQuantity)
    {
        $cart = self::prepareUserCart();
        $cartItems = $cart->items;
        // Product in cart
        $inCart = false;
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $product['id']) {
                $inCart = true;
                $item->update(['quantity' => $item['quantity'] + $newQuantity]);
            }
        }
        // Product not in cart
        if (!$inCart) {
            $cart->items()->create([
                'product_id' => $product['id'],
                'quantity' => $newQuantity
            ]);
        }
    }

    public static function fetchCartAsJSON(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => self::fetchCartAsArray()]);
    }

    public static function fetchCartAsArray($key = null): array
    {
        $cart = self::prepareUserCart(false, $key);
        $items = [];
        foreach ($cart->items as $item) {
            if ($item->product) {
                $items[] = [
                    'id' => $item->id,
                    'product' => new ProductResource($item->product),
                    'quantity' => $item['quantity']
                ];
            }
        }
        return [
            'items' => $items,
            'subTotal' => $cart['total'],
            'total' => Cart::getDiscountedTotal($cart)
        ];
    }

    public static function prepareUserCart($guest = false, $key = null)
    {
        $user = User::find(auth('api')->id());
        if ($user && !$guest) {
            $cart = $user->cart()->with(['items', 'items.product'])->first();
            if (!$cart) $cart = $user->cart()->create(['total' => 0]);
        }
        else {
            $userKey = self::$userKey ?? $key;
            $cart = Cart::where('user', $userKey)->with(['items', 'items.product'])->first();
            if (!$cart) $cart = Cart::create(['user' => $userKey, 'total' => 0]);
        }

        $cart->updateTotal();
        return $cart;
    }

    public static function moveUserCartToDatabase($key)
    {
        try {
            $user = User::find(auth('api')->id());
            $cart = Cart::where('user', $key)->first();
            if ($cart) {
                $userCart = $user->cart()->with(['items', 'items.product'])->first();
                if (!$userCart)
                    $userCart = $user->cart()->create(['total' => 0]);
                foreach ($cart->items as $item) {
                    if ($exist = $userCart->items()->where('product_id', $item['product_id'])->first())
                        if ($exist->update(['quantity' => (int)$exist['quantity'] + (int)$item['quantity']])) {
                            $item->delete();
                            $userCart->update(['total' => (float)$userCart['total'] + ((float)$item->product->price * (int)$item['quantity'])]);
                        } else
                            if ($userCart->items()->create(['product_id' => $item['product_id'], 'quantity' => $item['quantity']])) {
                                $item->delete();
                                $userCart->update(['total' => (float)$userCart['total'] + ((float)$item->product->price * (int)$item['quantity'])]);
                            }
                }
                $cart->delete();
            }
        } catch(Exception $e) {
            logger('Cart Error: ' . $e->getMessage());
        }
    }
}
