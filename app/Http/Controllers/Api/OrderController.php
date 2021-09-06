<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public static $userKey;

    public function __construct()
    {
        self::$userKey = request()->header('X-USER-KEY');
        $this->middleware('auth:api')->except('tracking');
    }

    /**
     * @OA\Get (
     *      path="/orders",
     *      tags={"Orders"},
     *      summary="Get User Orders",
     *      description="get user orders",
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
        $user = User::find(Auth::id());
        $orders = $user->orders()->get();
        return response()->json(['status' => true, 'data' => OrderResource::collection($orders)]);
    }

    /**
     * @OA\Get (
     *      path="/orders/{order_id}",
     *      tags={"Orders"},
     *      summary="Get User Order details",
     *      description="get user order details",
     *
     *     @OA\Parameter(
     *          name="order_id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="integer"
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
     * @param Order $order
     * @return JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json(['status' => true, 'data' => new OrderResource($order)]);
    }

    /**
     * @OA\Get (
     *      path="/orders/tracking/get",
     *      tags={"Orders"},
     *      summary="Track order",
     *      description="track order",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
     *          required=false,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="tracking_code",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="email",
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
     * @return JsonResponse
     */
    public function tracking(): JsonResponse
    {
        $validator = Validator::make(request()->all(), ['tracking_code' => 'required', 'email' => 'required']);
        if ($validator->fails()) return response()->json(['status' => false, 'errors' => $validator->getMessageBag()]);
        if ($order = Order::where('code', request('tracking_code'))->where('email', request('email'))->first())
            return response()->json(['status' => true, 'data' => new OrderResource($order)]);
        return response()->json(['status' => false, 'errors' => 'Invalid order tracking details']);
    }
}
