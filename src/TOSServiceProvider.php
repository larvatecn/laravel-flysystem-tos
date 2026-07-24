<?php

namespace Larva\Flysystem\Volc;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Larva\Flysystem\Tos\PortableVisibilityConverter;
use Larva\Flysystem\Tos\TOSAdapter as VolcTOSAdapter;
use League\Flysystem\Filesystem;
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
        $this->app->make('filesystem')->extend('tos', function ($app, $config) {
            $root = (string) ($config['root'] ?? '');
            $config['directory_separator'] = '/';
            $visibility = new PortableVisibilityConverter($config['visibility'] ?? Visibility::PUBLIC);
            if ($config['access_key'] && $config['access_secret']) {
                $client = new TosClient([
                    'region' => $config['region'],
                    'endpoint' => $config['endpoint'],
                    'ak' => $config['access_key'],
                    'sk' => $config['access_secret'],
                    'connectionTimeout' => $config['connection_timeout'] ?? 10000,
                    'socketTimeout' => $config['socket_timeout'] ?? 30000,
                    'enableVerifySSL' => !isset($config['verify_ssl']) || boolval($config['verify_ssl']),
                    'autoRecognizeContentType' => true,
                    'isCustomDomain' => $config['is_custom_domain']
                ]);
                if ($config['is_custom_domain']) {
                    $config['url'] = $config['ssl'] ? 'https://' : 'http://'.$config['endpoint'];
                }
            } else {
                $client = new TosClient($config['region']);
            }
            // Flysystem 原始适配器
            $adapter = new VolcTOSAdapter($client, $config['bucket'], $root, $visibility, null, $config['options'] ?? []);

            return new TOSAdapter(
                new Filesystem($adapter, Arr::only($config, [
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
