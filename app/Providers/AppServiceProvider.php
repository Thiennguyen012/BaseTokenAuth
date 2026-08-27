<?php

namespace App\Providers;

use App\Models\PageConfigs\PageConfig;
use App\Repositories\Category\CategoryInterface;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Tag\TagInterface;
use App\Repositories\Tag\TagRepository;
use App\Repositories\TagGroup\TagGroupInterface;
use App\Repositories\TagGroup\TagGroupRepository;
use App\Repositories\CustomerContact\CustomerContactInterface;
use App\Repositories\CustomerContact\CustomerContactRepository;
use App\Repositories\File\FileInterface;
use App\Repositories\File\FileRepository;
use App\Repositories\Product\ProductInterface;
use App\Repositories\Product\ProductRepository;
use App\Repositories\ProductVariant\ProductVariantInterface;
use App\Repositories\ProductVariant\ProductVariantRepository;
use App\Repositories\ProductVariantValue\ProductVariantValueInterface;
use App\Repositories\ProductVariantValue\ProductVariantValueRepository;
use App\Repositories\RefreshToken\RefreshTokenInterface;
use App\Repositories\RefreshToken\RefreshTokenRepository;
use App\Repositories\VariantGroup\VariantGroupInterface;
use App\Repositories\VariantGroup\VariantGroupRepository;
use App\Repositories\VariantOption\VariantOptionInterface;
use App\Repositories\VariantOption\VariantOptionRepository;
use App\Repositories\PageContent\PageContentInterface;
use App\Repositories\PageContent\PageContentRepository;
use App\Repositories\PageConfig\PageConfigInterface;
use App\Repositories\PageConfig\PageConfigRepository;
use App\Repositories\PageSection\PageSectionInterface;
use App\Repositories\PageSection\PageSectionRepository;
use App\Repositories\SectionItem\SectionItemInterface;
use App\Repositories\SectionItem\SectionItemRepository;
use App\Repositories\User\UserInterface;
use App\Repositories\User\UserRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(TagInterface::class, TagRepository::class);
        $this->app->bind(TagGroupInterface::class, TagGroupRepository::class);
        $this->app->bind(CustomerContactInterface::class, CustomerContactRepository::class);
        $this->app->bind(FileInterface::class, FileRepository::class);
        $this->app->bind(ProductInterface::class, ProductRepository::class);
        $this->app->bind(VariantGroupInterface::class, VariantGroupRepository::class);
        $this->app->bind(VariantOptionInterface::class, VariantOptionRepository::class);
        $this->app->bind(ProductVariantInterface::class, ProductVariantRepository::class);
        $this->app->bind(ProductVariantValueInterface::class, ProductVariantValueRepository::class);
        $this->app->bind(UserInterface::class, UserRepository::class);
        $this->app->bind(RefreshTokenInterface::class, RefreshTokenRepository::class);
        $this->app->bind(PageContentInterface::class, PageContentRepository::class);
        $this->app->bind(PageConfigInterface::class, PageConfigRepository::class);
        $this->app->bind(PageSectionInterface::class, PageSectionRepository::class);
        $this->app->bind(SectionItemInterface::class, SectionItemRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        View::composer('*', function ($view): void {
            static $pageConfigData = null;

            if ($pageConfigData === null) {
                $pageConfig = PageConfig::query()->with('files')->first();

                // Logo resolution
                $logoFile = $pageConfig?->files->firstWhere('type', 'logo');
                $logoPath = $logoFile?->path ?: $pageConfig?->logo_path;
                $logoUrl = $logoFile?->external_url;

                if (!$logoUrl && $logoPath) {
                    $disk = Storage::disk($logoFile?->disk ?: 'public');
                    if ($disk->exists($logoPath)) {
                        $logoUrl = $disk->url($logoPath);
                    } else {
                        $logoUrl = asset('storage/' . ltrim($logoPath, '/'));
                    }
                }

                if ($logoUrl) {
                    $logoVersion = $logoFile?->updated_at?->timestamp
                        ?: optional($pageConfig?->updated_at)->timestamp
                        ?: time();
                    $logoUrl .= (str_contains($logoUrl, '?') ? '&' : '?') . 'v=' . $logoVersion;
                }

                // Favicon resolution
                $faviconFile = $pageConfig?->files->firstWhere('type', 'favicon');
                $faviconPath = $faviconFile?->path ?: $pageConfig?->favicon_path;
                $faviconUrl = $faviconFile?->external_url;

                if (!$faviconUrl && $faviconPath) {
                    $disk = Storage::disk($faviconFile?->disk ?: 'public');
                    if ($disk->exists($faviconPath)) {
                        $faviconUrl = $disk->url($faviconPath);
                    } else {
                        $faviconUrl = asset('storage/' . ltrim($faviconPath, '/'));
                    }
                }

                if ($faviconUrl) {
                    $faviconVersion = $faviconFile?->updated_at?->timestamp
                        ?: optional($pageConfig?->updated_at)->timestamp
                        ?: time();
                    $faviconUrl .= (str_contains($faviconUrl, '?') ? '&' : '?') . 'v=' . $faviconVersion;
                }

                $pageConfigData = [
                    'cmsCompanyName' => $pageConfig?->company_name ?: 'CMS',
                    'cmsCompanyLogoUrl' => $logoUrl,
                    'cmsCompanyFaviconUrl' => $faviconUrl,
                ];
            }

            $view->with($pageConfigData);
        });

        $isCachingConfig = $this->app->runningInConsole()
            && in_array('config:cache', $_SERVER['argv'] ?? [], true);

        if (!$isCachingConfig) {
            config()->set(
                'l5-swagger.defaults.scanOptions.analyser',
                new \App\OpenApi\DocBlockAnalyser()
            );
        }
    }
}
