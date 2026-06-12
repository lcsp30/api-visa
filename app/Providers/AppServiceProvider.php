<?php

namespace App\Providers;

use App\Repositories\DocumentosCnpjRepository;
use App\Repositories\DocumentosCpfRepository;
use App\Repositories\EstabelecimentoCnpjRepository;
use App\Repositories\EstabelecimentoCpfRepository;
use App\Repositories\EstabelecimentoNotificadosRepository;
use App\Repositories\IntimacaoConstatacaoRepository;
use App\Repositories\LicencaRepository;
use App\Repositories\UserRepository;
use App\Services\DocumentosService;
use App\Services\EstabelecimentoCnpjService;
use App\Services\EstabelecimentoCpfService;
use App\Services\EstabelecimentoNotificadosService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->singleton(UserRepository::class);
        $this->app->singleton(DocumentosCnpjRepository::class);
        $this->app->singleton(DocumentosCpfRepository::class);
        $this->app->singleton(EstabelecimentoNotificadosRepository::class);
        $this->app->singleton(IntimacaoConstatacaoRepository::class);
        $this->app->singleton(LicencaRepository::class);
        $this->app->singleton(EstabelecimentoCnpjRepository::class);
        $this->app->singleton(EstabelecimentoCpfRepository::class);

        // Services
        $this->app->singleton(UserService::class);
        $this->app->singleton(DocumentosService::class);
        $this->app->singleton(EstabelecimentoNotificadosService::class);
        $this->app->singleton(EstabelecimentoCnpjService::class);
        $this->app->singleton(EstabelecimentoCpfService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
