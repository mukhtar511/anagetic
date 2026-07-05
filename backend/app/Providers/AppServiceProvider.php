<?php

namespace App\Providers;

use App\Services\Ai\AiListingService;
use App\Services\Ai\MockAiListingService;
use App\Services\Payment\FakeGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Sms\FakeSmsChannel;
use App\Services\Sms\SmsChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bind the swappable abstractions to their dev drivers. The real
     * providers (Moyasar/NeoLeap, an SMS gateway, a Vision API) are bound
     * here later without touching any business logic. SPEC §1.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, FakeGateway::class);
        $this->app->bind(SmsChannel::class, FakeSmsChannel::class);
        $this->app->bind(AiListingService::class, MockAiListingService::class);
    }

    public function boot(): void
    {
        // Catch N+1 and silent attribute typos during local development only
        // (kept off in testing/production so service relation walks don't throw).
        Model::preventLazyLoading($this->app->environment('local'));
        Model::preventSilentlyDiscardingAttributes($this->app->environment('local'));
    }
}
