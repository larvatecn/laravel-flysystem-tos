<?php

namespace Larva\Flysystem\Volc;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Larva\Flysystem\Tos\TOSAdapter as VolcTOSAdapter;
use League\Flysystem\FilesystemOperator;
use Tos\Model\PreSignedURLInput;
use Tos\TosClient;

/**
 * TOS 适配器
 * @author Tongle Xu <xutongle@msn.com>
 */
class TOSAdapter extends FilesystemAdapter
{
    /**
     * The Volc tos client.
     *
     * @var TosClient
     */
    protected TosClient $client;

    /**
     * Create a new TOSAdapter instance.
     *
     * @param  FilesystemOperator  $driver
     * @param  VolcTOSAdapter  $adapter
     * @param  array  $config
     * @param  TosClient  $client
     */
    public function __construct(FilesystemOperator $driver, VolcTOSAdapter $adapter, array $config, TosClient $client)
    {
        parent::__construct($driver, $adapter, $config);
        $this->client = $client;
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function url($path): string
    {
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (isset($this->config['url'])) {
            return $this->concatPathToUrl($this->config['url'], $this->prefixer->prefixPath($path));
        }
        $visibility = $this->getVisibility($path);
        if ($visibility == FilesystemContract::VISIBILITY_PRIVATE) {
            return $this->temporaryUrl($path, Carbon::now()->addMinutes(5), []);
        } else {
            $scheme = $this->config['ssl'] ? 'https://' : 'http://';
            return $this->concatPathToUrl($scheme.$this->config['bucket'].'.'.$this->config['endpoint'],
                $this->prefixer->prefixPath($path));
        }
    }

    /**
     * Determine if temporary URLs can be generated.
     */
    public function providesTemporaryUrls(): bool
    {
        return true;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array<string, mixed>  $options
     */
    public function temporaryUrl($path, $expiration, array $options = []): string
    {
        $uri = new Uri($this->signUrl($this->prefixer->prefixPath($path), $expiration, $options, 'GET', $this->config['endpoint']));

        return (string) $uri;
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array<string, mixed>  $options
     *
     * @return array{url: string, headers: never[]}
     */
    public function temporaryUploadUrl($path, $expiration, array $options = []): array
    {
        $uri = $this->preSignedURL($this->prefixer->prefixPath($path), $expiration, $options, 'PUT', $this->config['endpoint']);

        return [
            'url' => $uri->getSignedUrl(),
            'headers' => $uri->getSignedHeader(),
        ];
    }

    /**
     * Get the underlying tos client.
     *
     * @return TosClient
     */
    public function getClient(): TosClient
    {
        return $this->client;
    }

    /**
     * Get a signed URL for the file at the given path.
     *
     * @param  array<string, mixed>  $options
     */
    public function signUrl(string $path, \DateTimeInterface|int $expiration, array $options = [], string $method = 'GET', string $alternativeEndpoint = ''): string
    {
        $uri = $this->preSignedURL($path, $expiration, $options, $method, $alternativeEndpoint);

        return $uri->getSignedUrl();
    }

    /**
     * Get a signed URL for the file at the given path.
     *
     * @param  array<string, mixed>  $options
     */
    protected function preSignedURL(string $path, \DateTimeInterface|int $expiration, array $options = [], string $method = 'GET', string $alternativeEndpoint = ''): \Tos\Model\PreSignedURLOutput
    {
        $expires = $expiration instanceof \DateTimeInterface ? $expiration->getTimestamp() - time() : $expiration;

        $preSignedURLInput = new PreSignedURLInput($method, $alternativeEndpoint === '' || $alternativeEndpoint === '0' ? $this->config['bucket'] : '', $path, $expires);
        if ($alternativeEndpoint !== '' && $alternativeEndpoint !== '0') {
            $preSignedURLInput->setAlternativeEndpoint($alternativeEndpoint);
        }

        $preSignedURLInput->setQuery($options);
        return $this->getClient()->preSignedURL($preSignedURLInput);
    }

}
