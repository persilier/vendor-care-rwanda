<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwoFactorAuthenticationService
{
	protected $google2fa;

	public function __construct()
	{
		$this->google2fa = new Google2FA();
	}

	public function generateSecretKey(): string
	{
		return $this->google2fa->generateSecretKey();
	}

	public function generateRecoveryCodes(): array
	{
		$recoveryCodes = Collection::times(8, function () {
			return Str::random(10);
		})->all();

		return $recoveryCodes;
	}

	public function verify(string $secret, string $code): bool
	{
		return $this->google2fa->verifyKey($secret, $code);
	}

	public function getQrCodeUrl(User $user, string $secret): string
	{
		return $this->google2fa->getQRCodeUrl(
			config('app.name'),
			$user->email,
			$secret
		);
	}

	public function verifyRecoveryCode(User $user, string $recoveryCode): bool
	{
		$recoveryCodes = json_decode($user->two_factor_recovery_codes, true);
		
		if (!$recoveryCodes) {
			return false;
		}

		$position = array_search($recoveryCode, $recoveryCodes);

		if ($position === false) {
			return false;
		}

		unset($recoveryCodes[$position]);
		
		$user->two_factor_recovery_codes = json_encode(array_values($recoveryCodes));
		$user->save();

		return true;
	}
}