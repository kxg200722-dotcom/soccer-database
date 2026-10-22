<?php

namespace App\Providers;

use App\Integrations\FaceVerify\FaceVerifyClient;
use App\Integrations\FaceVerify\MockFaceVerifyClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 人脸核验：契约绑定，driver=mock；替换第三方仅需新增实现+改 config
        $this->app->bind(FaceVerifyClient::class, fn () => new MockFaceVerifyClient(config('faceverify.timeout', 3)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
