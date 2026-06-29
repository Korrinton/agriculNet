<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Policies\FincaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        Finca::class => FincaPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
