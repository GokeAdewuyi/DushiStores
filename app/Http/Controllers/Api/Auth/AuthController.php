<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\Cart;
use App\Models\User;
use App\Models\Wishlist;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public static $userKey;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        self::$userKey = request()->header('X-USER-KEY');
        $this->middleware('auth:api', ['except' => ['login', 'register', 'generateUserKey']]);
    }

    /**
     *
     * @OA\Post (
     *      path="/key/generate",
     *      tags={"Auth"},
     *      summary="Generate User Key",
     *      description="Generate a unique key which would be used while the user is unauthenticated. It must be included in every request header with the key X-USER-KEY",
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
     * Get a JWT via given credentials.
     * @return JsonResponse
     */
    public function generateUserKey(): JsonResponse
    { return response()->json(['status' => true, 'key' => 'DFS'.mt_rand(100, 999).time()]); }

    /**
     *
     * @OA\Post (
     *      path="/login",
     *      tags={"Auth"},
     *      summary="Login",
     *      description="login",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
     *          name="password",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *      ),
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
     * Get a JWT via given credentials.
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
            return response()->json(['status' => false, 'errors' => 'Invalid credentials'], 401);
        }

        CartController::moveUserCartToDatabase(request('X-USER-KEY'));
        WishlistController::moveUserWishlistToDatabase(request('X-USER-KEY'));
        return static::createNewToken($token);
    }

    /**
     * @OA\Post (
     *      path="/register",
     *      tags={"Auth"},
     *      summary="Register",
     *      description="register",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="header",
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
     *          name="phone",
     *          in="query",
     *          required=false,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="password",
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
     * Register a User.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'last_name' => 'required|string|between:2,100',
            'first_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails())
            return response()->json([
                "status" => false,
                "errors" => $validator->errors()
            ], 400);

        if (!$user = User::create(array_merge($validator->validated(), ['phone' => $request['phone'], 'password' => bcrypt($request['password'])])))
            return response()->json(['status' => false, 'errors' => 'Unable to register user, try again'], 400);

        event(new Registered($user));

        if (!$token = auth('api')->attempt(['email' => $user['email'], 'password' => $request['password']])) {
            return response()->json(['status' => false, 'errors' => 'Account created but could not log user in'], 401);
        }

        CartController::moveUserCartToDatabase(self::$userKey);
        WishlistController::moveUserWishlistToDatabase(self::$userKey);
        return static::createNewToken($token);
    }


    /**
     * @OA\Post (
     *      path="/logout",
     *      tags={"Auth"},
     *      summary="Logout",
     *      description="logout",
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
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        $cart = Cart::where('user', self::$userKey)->first();
        foreach ($cart->items as $item)
            $item->delete();
        $cart->delete();
        Wishlist::where('user', self::$userKey)->delete();
        return response()->json(['status' => true, 'message' => 'User successfully signed out']);
    }

    /**
     *
     * @OA\Post (
     *      path="/refresh",
     *      tags={"Auth"},
     *      summary="Refresh Token",
     *      description="refresh token",
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
     *
     * Refresh a token.
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        return static::createNewToken(auth('api')->refresh());
    }

    /**
     *
     * @OA\Put (
     *      path="/profile/update",
     *      tags={"Auth"},
     *      summary="Update Profile",
     *      description="update profile",
     *
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
    public function update_profile(): JsonResponse
    {
        $validator = Validator::make(request(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'address' => 'required',
            'phone' => 'required',
        ]);
        if ($validator->fails())
            return response()->json(['status' => false, 'errors' => $validator->getMessageBag()], 422);

        $data = request()->only('name', 'country', 'state', 'city', 'address', 'phone', 'postcode');
        User::query()->find(auth('api')->id())->update($data);
        return response()->json(['status' => true, 'message' => 'Profile updated successfully']);
    }

    /**
     * @OA\Put (
     *      path="/password/update",
     *      tags={"Auth"},
     *      summary="Update Password",
     *      description="update password",
     *
     *     @OA\Parameter(
     *          name="old_password",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="new_password",
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
     *
     * Refresh a token.
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function change_password(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
        if ($validator->fails())
            return response()->json(['status' => false, 'errors' => $validator->getMessageBag()], 422);
        if (!Hash::check(request('old_password'), auth('api')->user()['password']))
            return response()->json(['status' => false, 'errors' => 'Old password incorrect'], 400);
        User::query()->find(auth()->user())->update([
            'password' => Hash::make(request('new_password'))
        ]);
        return response()->json(['status' => true, 'message' => 'Password changed successfully']);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function userProfile(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function createNewToken(string $token): JsonResponse
    {
        return response()->json([
            'status' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => new UserResource(auth('api')->user())
        ]);
    }

    public static function validateUserKey($key): bool
    {
        if (strlen($key) != 16)
            return false;
        elseif (substr($key, 0, 3) != 'DFS')
            return false;
        return true;
    }
}
