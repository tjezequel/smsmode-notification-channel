<?php

namespace BnitoBzh\Notifications;

use BnitoBzh\Notifications\Channels\SmsmodeChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;

class SmsmodeChannelServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smsmode.php', 'smsmode');

        $this->app->bind(SmsmodeChannel::class, function ($app) {

            $client = HttpClient::create([
                'headers' => [
                    'X-Api-Key'    => config('smsmode.api_key'),
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ]
            ]);

            return new SmsmodeChannel(
                $client,
                $app['config']['smsmode.sender'],
                $app['config']['smsmode.endpoint']
            );
        });

        Notification::resolved(function (ChannelManager $service) {
            $service->extend('smsmode', function ($app) {
                return $app->make(SmsmodeChannel::class);
            });
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smsmode.php' => $this->app->configPath('smsmode.php'),
            ], 'smsmode');
        }
    }
}
