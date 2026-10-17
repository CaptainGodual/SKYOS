<?php

namespace App\Providers;

use App\Models\CrewMessage;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        // The crew and admin layouts are built on CoreUI (Bootstrap 5), so the
        // paginator should render Bootstrap markup rather than Tailwind's.
        Paginator::useBootstrapFive();

        // The crew navigation shows a message badge on every page, so it is
        // filled in here rather than by each controller.
        View::composer('layouts.app', function (ViewInstance $view): void {
            $user = auth()->user();

            $view->with('unreadMessages', $user === null ? 0 : CrewMessage::query()
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count());
        });
    }
}
