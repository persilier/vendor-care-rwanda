<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TwoFactorAuthController extends Controller
{
	protected $twoFactorService;

	public function __construct(TwoFactorAuthenticationService $twoFactorService)
	{
		$this->twoFactorService = $twoFactorService;
	}

	public function enable(Request $request): JsonResponse
	{
		$user = $request->user();
		
		if ($user->hasTwoFactorEnabled()) {
			return response()->json(['message' => '2FA is already enabled'], 400);
		}

		$secret = $this->twoFactorService->generateSecretKey();
		$qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);
		
		session(['2fa_secret' => $secret]);

		return response()->json([
			'qr_code_url' => $qrCodeUrl,
			'secret' => $secret,
		]);
	}

	public function confirm(Request $request): JsonResponse
	{
		$request->validate([
			'code' => 'required|string',
		]);

		$user = $request->user();
		$secret = session('2fa_secret');

		if (!$secret) {
			return response()->json(['message' => 'Invalid session'], 400);
		}

		if (!$this->twoFactorService->verify($secret, $request->code)) {
			return response()->json(['message' => 'Invalid verification code'], 400);
		}

		$recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
		
		$user->enableTwoFactorAuthentication($secret);
		$user->setRecoveryCodes($recoveryCodes);
		
		session()->forget('2fa_secret');

		return response()->json([
			'message' => '2FA enabled successfully',
			'recovery_codes' => $recoveryCodes
		]);
	}

	public function disable(Request $request): JsonResponse
	{
		$request->validate([
			'code' => 'required|string',
		]);

		$user = $request->user();

		if (!$this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
			return response()->json(['message' => 'Invalid verification code'], 400);
		}

		$user->disableTwoFactorAuthentication();

		return response()->json(['message' => '2FA disabled successfully']);
	}

	public function verify(Request $request): JsonResponse
	{
		$request->validate([
			'code' => 'required|string',
		]);

		$user = $request->user();

		if (!$user->hasTwoFactorEnabled()) {
			return response()->json(['message' => '2FA is not enabled'], 400);
		}

		if ($this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
			return response()->json(['message' => 'Verification successful']);
		}

		if ($this->twoFactorService->verifyRecoveryCode($user, $request->code)) {
			return response()->json(['message' => 'Recovery code used successfully']);
		}

		return response()->json(['message' => 'Invalid verification code'], 400);
	}
}
