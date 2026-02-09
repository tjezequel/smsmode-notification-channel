<?php

namespace BnitoBzh\Notifications\Channels;

use BnitoBzh\Notifications\Messages\SmsmodeMessage;
use DateTimeInterface;
use Illuminate\Notifications\Notification;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsmodeChannel
{

    /**
     * The API endpoint
     */
    protected string $endpoint;

    /**
     * The HTTP Client
     */
    protected HttpClientInterface $client;

    /**
     * The default sender ID
     */
    protected string $sender;

    /**
     * Create a new Smsmode channel instance.
     *
     * @param HttpClientInterface $client
     * @param string              $sender
     * @param string|null         $endpoint
     *
     * @return void
     */
    public function __construct(HttpClientInterface $client, string $sender, string $endpoint)
    {
        $this->client = $client;
        $this->sender = $sender;
        $this->endpoint = $endpoint;
    }

    /**
     * Send the given notification.
     *
     * @param mixed        $notifiable
     * @param Notification $notification
     */
    public function send(object $notifiable, Notification $notification)
    {
        $to = $notifiable->routeNotificationFor('smsmode', $notification)
            ?? $notifiable->routeNotificationFor(SmsmodeChannel::class, $notification);

        if (!$to) {
            return;
        }

        $message = $notification->toSmsmode($notifiable);
        if (is_string($message)) {
            $message = new SmsmodeMessage($message);
        }

        if (!$message instanceof SmsmodeMessage) {
            return;
        }

        $data = [
            'recipient' => ['to' => $to],
            'body'      => [
                'text'     => trim($message->content),
                'encoding' => $message->encoding,
                'stop'     => $message->stop,
            ],
            'from'      => $message->sender ?? $this->sender,
        ];

        if ($message->reference) {
            $data['refClient'] = $message->reference;
        }

        if ($message->callbackUrl) {
            $data['callbackUrlStatus'] = $message->callbackUrl;
        }

        if ($message->callbackMOUrl) {
            $data['callbackUrlMo'] = $message->callbackMOUrl;
        }

        if ($message->date) {
            $data['sentDate'] = $message->date->format(DateTimeInterface::ATOM);
        }

        $this->client->request(
            'POST',
            $this->buildApiUrl($message),
            ['json' => $data]
        );
    }

    protected function buildApiUrl(SmsmodeMessage $message): string
    {
        $url = $this->endpoint;

        if ($message->campaign && $message->channel) {
            $url .= '/channels/' . $message->channel . '/campaigns/' . $message->campaign;
        } elseif ($message->channel) {
            $url .= '/channels/' . $message->channel;
        } elseif ($message->campaign) {
            $url .= '/campaigns/' . $message->campaign;
        }

        return $url . '/messages';
    }
}
