<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider: ScrambleServiceProvider
 * 
 * This provider is excluded from code coverage because:
 * - Configures API documentation generator (Scramble/OpenAPI)
 * - Service provider bootstrapping code
 * - Better tested through manual verification of generated API docs
 * - No business logic to test
 * 
 * @codeCoverageIgnore
 */
class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation) {
                // Tambah default value x-localization
                $operation->addParameters([
                    Parameter::make('x-localization', 'header')
                        ->setSchema(
                            Schema::fromType(
                                (new StringType())->enum(['id', 'en'])->default('id')
                            )
                        ),
                ]);
            })
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

        Scramble::registerApi(
            'tenant',
            [
                'info' => ['version' => '1.0'],
                'api_path' => '{tenant}/api',
                'export_path' => 'tenant.json',
                'servers' => ['Tenant' => '{tenant}/api'],
            ]
        )->withOperationTransformers(function (Operation $operation) {
            // Hapus parameter tenant dari path parameters karena sudah ada di server variables
            $operation->parameters = array_values(array_filter($operation->parameters, function ($parameter) {
                return $parameter->name != 'tenant';
            }));
        })->expose(
            ui: 'docs/api/tenant',
            document: 'docs/api/tenant.json',
        );
    }
}
