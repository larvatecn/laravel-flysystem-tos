<?php

namespace Larva\Flysystem\Volc;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Larva\Flysystem\Tos\TOSAdapter as VolcTOSAdapter;
use Larva\Flysystem\Tos\PortableVisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Visibility;
use Tos\TosClient;

/**
 * Volc TOS 服务提供
 */
class TOSServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->make('filesystem')->extend('oss', function ($app, $config) {
            $root = (string) ($config['root'] ?? '');
            $config['directory_separator'] = '/';
            $visibility = new PortableVisibilityConverter($config['visibility'] ?? Visibility::PUBLIC);
            if($config['accessKey'] && $config['accessSecret']) {
                $client = new TosClient($config['region'], $config['access_key'], $config['access_secret'],
                    $config['endpoint']);
            } else {
                $client = new TosClient($config['region']);
            }
            $adapter = new VolcTOSAdapter($client, $config['bucket'], $root, $visibility, null,
                $config['options'] ?? []);

            return new TOSAdapter(
                new Flysystem($adapter, Arr::only($config, [
                    'directory_visibility', 'disable_asserts',
                    'temporary_url', 'url', 'visibility',
                ])),
                $adapter,
                $config,
                $client
            );
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
