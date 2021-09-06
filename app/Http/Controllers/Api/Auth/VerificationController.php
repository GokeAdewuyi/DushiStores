<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except('verify');
    }

    /**
     *
     * @OA\Post (
     *      path="/email/verify",
     *      tags={"Auth"},
     *      summary="Verify Email Address",
     *      description="verify user email address",
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
     *          name="token",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *               type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="expires",
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
    public function verify(): JsonResponse
    {
        if (!$user = User::where('token', request('token'))->first())
            return response()->json(['status' => false, 'errors' => 'Verification link is invalid'], 404);

        if (strtotime($user->token_expiry) != request('expires'))
            return response()->json(['status' => false, 'errors' => 'Verification link is invalid'], 404);

        if (now()->gt($user->token_expiry))
            return response()->json(['Verification link is expired'], 400);

        if ($user->hasVerifiedEmail())
            return response()->json(['status' => false, 'errors' => 'Email Already Verified.'], 400);

        if ($user->markEmailAsVerified()) {
            $user->update(['token' => null, 'token_expiry' => null]);
            return response()->json(['status' => true, 'message' => 'Email Verified Successfully.']);
        }

        return response()->json(['status' => false, 'errors' => 'Email Not Verified.'], 400);
    }

    /**
     *
     * @OA\Post (
     *      path="/email/resend",
     *      tags={"Auth"},
     *      summary="Resend Email Verification link",
     *      description="request a new email address verification link",
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
    public function resendVerifyEmail(): JsonResponse
    {
        request()->user('api')->sendEmailVerificationMail();
        return response()->json((['status' => true, 'message' => 'Verification link sent!']));
    }
}
