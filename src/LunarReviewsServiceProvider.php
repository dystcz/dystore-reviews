<?php

namespace Dystcz\LunarApiReviews;

use Dystcz\LunarApiReviews\Domain\Reviews\LunarReviews;
use Dystcz\LunarApiReviews\Domain\Reviews\Models\Review;
use Dystcz\LunarApiReviews\Domain\Reviews\Policies\ReviewPolicy;
use Dystcz\LunarApiReviews\Hub\Components\Slots\ReviewsSlot;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Hub\Facades\Slot;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class LunarReviewsServiceProvider extends ServiceProvider
{
    protected $policies = [
        Review::class => ReviewPolicy::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/Hub/resources/views', 'lunar-reviews');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->registerDynamicRelations();

        Livewire::component('lunar-reviews::reviews-slot', ReviewsSlot::class);

        Slot::register('product.show', ReviewsSlot::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lunar-reviews.php' => config_path('lunar-reviews.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/Hub/resources/views' => resource_path('views/vendor/lunar-reviews'),
            ], 'views');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/lunar-reviews.php', 'lunar-reviews');

        // Register the main class to use with the facade
        $this->app->singleton('lunar-reviews', function () {
            return new LunarReviews;
        });
    }

    protected function registerDynamicRelations(): void
    {
        ProductVariant::resolveRelationUsing('reviews', function ($model) {
            return $model->morphMany(Review::class, 'purchasable');
        });

        Product::resolveRelationUsing('reviews', function ($model) {
            return $model->hasManyThrough(
                Review::class,
                ProductVariant::class,
                'product_id',
                'purchasable_id'
            )
            ->where(
                'purchasable_type',
                ProductVariant::class
            );
        });
    }
}
