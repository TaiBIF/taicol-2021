<?php

namespace App\Providers;

use App\FavoriteItem;
use App\Reference;
use App\ReferenceUsage;
use App\TaxonName;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Relation::morphMap([
            FavoriteItem::TYPE_USAGE => ReferenceUsage::class,
            FavoriteItem::TYPE_TAXON_NAME => TaxonName::class,
            FavoriteItem::TYPE_REFERENCE => Reference::class,
        ]);
    }
}
