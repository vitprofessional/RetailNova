<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

/** Serializes the one-time transition from an unprovisioned app to its first admin and business. */
class FirstAdminBootstrap
{
    private const LOCK_KEY = 'retailnova:first-admin-bootstrap';

    /** First-run setup is open only while the installation has no administrator and no business. */
    public static function firstRunOpen(): bool
    {
        return !AdminUser::anyAdminExists() && !BusinessSetup::query()->exists();
    }

    public function locked(callable $callback): mixed
    {
        try {
            $lock = Cache::lock(self::LOCK_KEY, 300);
            if (!$lock->get()) {
                throw new BootstrapException(BootstrapException::IN_PROGRESS);
            }
        } catch (BootstrapException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BootstrapException(BootstrapException::LOCK_UNAVAILABLE, previous: $e);
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * Create the first business and its owning Super Admin together.
     *
     * @param array<string, mixed> $adminData
     */
    public function createAdminWithBusiness(array $adminData, string $businessName, string $locale): AdminUser
    {
        return $this->locked(fn () => DB::transaction(function () use ($adminData, $businessName, $locale): AdminUser {
            if (!self::firstRunOpen()) {
                throw new BootstrapException(BootstrapException::ALREADY_COMPLETED);
            }

            $business = new BusinessSetup();
            $business->businessName = $businessName;
            $business->default_locale = in_array($locale, LocaleResolver::SUPPORTED, true) ? $locale : 'en';
            $business->save();

            $user = new AdminUser();
            $user->fullName = $adminData['fullName'];
            $user->sureName = $adminData['sureName'] ?? null;
            $user->storeName = $adminData['storeName'] ?? null;
            $user->mail = $adminData['mail'];
            $user->contactNumber = $adminData['contactNumber'] ?? null;
            $user->password = Hash::make($adminData['password']);
            // Role and ownership are server-decided; submitted values are never trusted.
            $user->role = 'superadmin';
            $user->businessId = (string) $business->id;
            $user->save();

            return $user;
        }));
    }
}
