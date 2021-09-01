<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{


    /**
     *
     * @OA\Post (
     *      path="/api/password/forgot",
     *      tags={"Auth"},
     *      summary="Password Reset Request",
     *      description="request a password reset link",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="query",
     *          required=false,
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
    public function sendResetLink(): JsonResponse
    {
        $validator = Validator::make(request()->all(), ['email' => 'required|email']);
        if ($validator->fails()) return response()->json(['status' => false, 'errors' => $validator->getMessageBag()], 422);

        if (!$user = User::where('email', request('email'))->first())
            return response()->json(['status' => false, 'errors' => 'User not found'], 404);

        event(new PasswordReset($user));

        return response()->json(['status' => true, 'message' => 'A password reset link has been sent to you mail.']);
    }



    /**
     *
     * @OA\Post (
     *      path="/api/password/reset",
     *      tags={"Auth"},
     *      summary="Reset Password",
     *      description="reset password",
     *
     *     @OA\Parameter(
     *          name="X-USER-KEY",
     *          in="query",
     *          required=false,
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
     *          name="token",
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
     * @return JsonResponse
     */
    public function reset(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails())
            return response()->json(['status' => false, 'errors' => $validator->getMessageBag()], 422);

        if (!$reset = DB::table('password_resets')->where('email', request('email'))->where('token', request('token'))->first())
            return response()->json(['status' => false, 'errors' => 'Password Reset link is Invalid'], 400);

        if (now()->gt(Carbon::make($reset->created_at)->addHours(2)))
            return response()->json(['status' => false, 'errors' => 'Password Reset link is expired'], 400);

        if (User::where('email', request('email'))->first()->update(['password' => bcrypt(request('password'))])) {
            DB::table('password_resets')->where('email', request('email'))->where('token', request('token'))->delete();
            return response()->json(['status' => true, 'message' => 'Password reset was successful']);
        }

        return response()->json(['status' => false, 'errors' => 'Password reset failed'], 400);
    }
}
