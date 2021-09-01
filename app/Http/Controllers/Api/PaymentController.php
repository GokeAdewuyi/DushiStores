<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Notifications\OrderNotification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public static $userKey;

    public function __construct()
    {
        self::$userKey = request('X-USER-KEY');
    }

    /**
     *
     * @OA\Post (
     *      path="/api/payment/initiate/{type}",
     *      tags={"Checkout"},
     *      summary="Checkout",
     *      description="checkout for both web and mobile app",
     *
     *     @OA\Parameter(
     *          name="type",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *               type="string",
     *               enum={"web", "mobile"}
     *          )
     *     ),
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="first_name",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="last_name",
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
     *     @OA\Parameter(
     *          name="country",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="state",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="city",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="address",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="postcode",
     *          in="query",
     *          required=false,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="phone",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="note",
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
     * @param $type
     * @return JsonResponse
     */
    public function initiate($type): JsonResponse
    {
        if (!Auth::guard('api')->check() && !AuthController::validateUserKey(self::$userKey))
            return response()->json(['status' => false, 'errors' => 'User key is invalid']);

        if ($validator = self::validateInput(request()->all())) return response()->json(['status' => false, 'errors' => $validator], 422);

        $cart = CartController::fetchCartAsArray(self::$userKey);
        $ref = paystack()->genTranxRef();
        $charge = self::calculateServiceCharge($cart['total']);

        if (!$ref)
            return response()->json(['status' => false, 'errors' => 'Could not generate reference, try again'], 400);
        if (count($cart['items']) < 1)
            return response()->json(['status' => false, 'errors' => 'Cart is empty'], 400);

        if ($type == 'web') {
            $data = [
                'email' => request('email'),
                'reference' => $ref,
                'subTotal' => $cart['total'],
                'charge' => $charge,
                'amount' => ($cart['total'] + $charge) * 100,
            ];
            request()->merge($data);
            $url = paystack()->getAuthorizationUrl();
            $meta = [
                'user' => request()->only('first_name', 'last_name', 'email', 'country', 'state', 'city', 'address', 'phone', 'note'),
                'userKey' => self::$userKey,
                'userId' => Auth::guard('api')->id(),
                'auth' => (bool) Auth::guard('api')->id(),
                'cart' => $cart,
                'ref' => $ref,
                'redirect_url' => $url,
                'amount' => $cart['total'],
                'charge' => $charge
            ];
            Payment::query()->create(['user_id' => auth()->id(), 'user' => self::$userKey, 'ref' => $ref, 'amount' => $cart['total'], 'charge' => $charge, 'meta' => json_encode($meta)]);
            $data['amount'] = $data['amount'] / 100;
            return response()->json(['status' => true, 'data' => $data, 'redirect_url' => $url->url]);
        }
        elseif ($type == 'mobile') {
            $meta = [
                'user' => request()->only('first_name', 'last_name', 'email', 'country', 'state', 'city', 'address', 'phone', 'note'),
                'userKey' => self::$userKey,
                'userId' => Auth::guard('api')->id(),
                'auth' => (bool) Auth::guard('api')->id(),
                'cart' => $cart,
                'ref' => $ref,
                'amount' => $cart['total'],
                'charge' => $charge
            ];
            Payment::query()->create(['user_id' => auth()->id(), 'user' => self::$userKey, 'ref' => $ref, 'amount' => $cart['total'], 'charge' => $charge, 'meta' => json_encode($meta)]);
            return response()->json(['status' => true, 'message' => 'Initiation Successful', 'reference' => $ref]);
        }
        else
            return response()->json(['status' => false, 'errors' => 'Invalid checkout type. Allowed methods are web or mobile.']);
    }

    public function paymentCallback(): JsonResponse
    {
        try {
            if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || !array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER))
                exit();

            $input = @file_get_contents("php://input");
            define('PAYSTACK_SECRET_KEY', env('PAYSTACK_SECRET_KEY'));

            if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY))
                exit();

            $event = json_decode($input, true);
            $data = $event['data'];
            $payment = Payment::where('ref', $data['reference'])->first();
            if ($payment['status'] != 'pending') {
                http_response_code(200);
                exit();
            }
            $payment->update(['status' => 'successful']);
            $meta = json_decode($payment['meta'], true);
            if ($order = $payment->order()->create(array_merge(['user_id' => $meta['userId'], 'code' => Order::getCode(), 'shipping' => 0.00, 'amount' => $payment['amount']], $meta['user']))) {
                foreach ($meta['cart']['items'] as $item) {
                    $product = $item['product'];
                    if ($product)
                        if (OrderItem::create(['order_id' => $order['id'], 'product_id' => $product['id'], 'quantity' => $item['quantity'], 'price' => $product['discountedPrice']]))
                            CartItem::where('id', $item['id'])->delete();
                }
                if ($meta['auth'])
                    Cart::where('user_id', $meta['userId'])->first()->updateTotal();
                else
                    Cart::where('user', $meta['userKey'])->delete();
            }

            $orderDetails = '<h2>Order Summary</h2><table style="border-collapse: collapse; width: 100%; margin: 25px 0; font-family: sans-serif; min-width: 400px;">';
            foreach ($order->items()->get() as $orderDetail) {
                $orderDetails .= '<tr style="border-bottom: thin solid #dddddd; width: 100%">
                                   <td style="padding: 20px 15px;"><img src="'.asset($orderDetail->product->media()->first()->url ?? null).'" width="80" alt=""></td>
                                   <td style="padding: 20px 15px;">'.$orderDetail->product->name.' x '.$orderDetail->quantity.'</td>
                                   <td style="padding: 20px 15px;">&#8358;'.number_format(($orderDetail->price * $orderDetail->quantity), 2).'</td>
                              </tr>';
            }
            if ($order['shipping']) $orderDetails .= '<tr style="width: 100%"><td colspan="2" style="padding: 20px 15px;"><b>Shipping Fee</b></td><td style="padding: 12px 15px;"> &#8358;'.number_format($order['shipping'], 2).'</td></tr>';
            $orderDetails .= '<tr style="width: 100%"><td colspan="2" style="padding: 20px 15px;"><b>Total</b></td><td style="padding: 12px 15px;">&#8358;'.number_format($order['amount'], 2).'</td></tr></table>';

            $buyerData = [
                'subject' => 'Order ' . $order['code'] . ' confirmed',
                'content' => 'Your order was successfully placed on ' . now()->format('d M, Y') . '. We are getting your order ready to be delivered. We will notify you when it has been sent.',
                'order' => $order,
                'order_details' => $orderDetails,
                'additional_text' => 'You can track your order using the order tracking section with the code <b>'.$order['code'].'</b><br>Thanks for your patronage!'
            ];
            if ($order['email']) Notification::route('mail', $order['email'])->notify(new OrderNotification($buyerData));

            $vendorData = [
                'subject' => 'Order '.$order['code'].' placed',
                'content' => 'An order was successfully placed on '.now()->format('d M, Y').'. Order details listed below, Login now to process the order.',
                'order' => $order,
                'order_details' => $orderDetails,
                'additional_text' => null
            ];
            Notification::route('mail', env('ADMIN_EMAIL'))->notify(new OrderNotification($vendorData));

        } catch (Exception $e) {
            logger('Payment Error');
            logger($e->getMessage());
            exit();
        }

        http_response_code(200);
        exit();
    }

    public function calculateServiceCharge($amount): float
    {
        if ((float) $amount < 2500) {
            $charge = 0.015 * (float) $amount;
        } else {
            $charge = (0.015 * (float) $amount) + 100;
            $charge = $charge > 2000 ? 2000 : $charge;
        }
        return round($charge, 2);
    }

    protected function validateInput($request)
    {
        $validator = Validator::make($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'address' => 'required',
            'phone' => 'required',
        ]);
        if ($validator->fails()) return $validator->getMessageBag();
        return false;
    }
}
