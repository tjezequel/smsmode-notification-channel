<?php

namespace BnitoBzh\Notifications\Tests\Unit\Channels;

use BnitoBzh\Notifications\Channels\SmsmodeChannel;
use BnitoBzh\Notifications\Messages\SmsmodeMessage;
use DateTimeImmutable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SmsmodeChannelTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSmsIsSentWithoutOptions()
    {
        $notification = new NotificationSmsmodeChannelTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            'https://rest.smsmode.com/sms/v1'
        );

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://rest.smsmode.com/sms/v1/messages',
                [
                    'json' => [
                        'recipient' => ['to' => '5555555555'],
                        'body'      => ['text' => '', 'encoding' => 'GSM7', 'stop' => false],
                        'from'      => '4444444444',
                    ]
                ]
            )
            ->once();

        $channel->send($notifiable, $notification);
    }

    public function testSmsIsSentWithAllOptions()
    {
        $notification = new NotificationSmsmodeChannelWithFullMessageTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            "https://rest.smsmode.com/sms/v1"
        );

        $message = $notification->toSmsmode($notifiable);

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://rest.smsmode.com/sms/v1/messages',
                [
                    'json' => [
                        'recipient'         => ['to' => '5555555555'],
                        'body'              => ['text' => 'this is my message', 'encoding' => 'UNICODE', 'stop' => true],
                        'from'              => '5554443333',
                        'refClient'         => 'REF123',
                        'callbackUrlStatus' => 'https://example.com/status',
                        'callbackUrlMo'     => 'https://example.com/mo',
                        'sentDate'          => $message->date->format(DateTimeImmutable::ATOM),
                    ]
                ]
            )
            ->once();

        $channel->send($notifiable, $notification);
    }

    public function testSmsIsSentOnChannel()
    {
        $notification = new NotificationSmsmodeChannelOverChannelTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            'https://rest.smsmode.com/sms/v1'
        );

        $message = $notification->toSmsmode($notifiable);

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://rest.smsmode.com/sms/v1/channels/' . $message->channel . '/messages',
                [
                    'json' => [
                        'recipient' => ['to' => '5555555555'],
                        'body'      => ['text' => 'this is my message', 'encoding' => 'GSM7', 'stop' => false],
                        'from'      => '4444444444',
                    ]
                ]
            )
            ->once();

        $channel->send($notifiable, $notification);
    }

    public function testSmsIsSentOnCampaign()
    {
        $notification = new NotificationSmsmodeChannelOverCampaignTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            'https://rest.smsmode.com/sms/v1'
        );

        $message = $notification->toSmsmode($notifiable);

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://rest.smsmode.com/sms/v1/campaigns/' . $message->campaign . '/messages',
                [
                    'json' => [
                        'recipient' => ['to' => '5555555555'],
                        'body'      => ['text' => 'this is my message', 'encoding' => 'GSM7', 'stop' => false],
                        'from'      => '4444444444',
                    ]
                ]
            )
            ->once();

        $channel->send($notifiable, $notification);
    }

    public function testSmsIsSentOnChannelCampaign()
    {
        $notification = new NotificationSmsmodeChannelOverChannelCampaignTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            'https://rest.smsmode.com/sms/v1'
        );

        $message = $notification->toSmsmode($notifiable);

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://rest.smsmode.com/sms/v1/channels/' . $message->channel . '/campaigns/' . $message->campaign . '/messages',
                [
                    'json' => [
                        'recipient' => ['to' => '5555555555'],
                        'body'      => ['text' => 'this is my message', 'encoding' => 'GSM7', 'stop' => false],
                        'from'      => '4444444444',
                    ]
                ]
            )
            ->once();

        $channel->send($notifiable, $notification);
    }

    public function testSmsIsSentToCorrectEndpoint()
    {
        $notification = new NotificationSmsmodeChannelTestNotification;
        $notifiable = new NotificationSmsmodeChannelTestNotifiable;

        $channel = new SmsmodeChannel(
            $client = Mockery::mock(HttpClientInterface::class),
            '4444444444',
            'https://custom-endpoint.com/api'
        );

        $client->shouldReceive('request')
            ->with(
                'POST',
                'https://custom-endpoint.com/api/messages',
                Mockery::any()
            )
            ->once();

        $channel->send($notifiable, $notification);
    }
}

class NotificationSmsmodeChannelTestNotifiable
{
    use Notifiable;

    public string $phone_number = '5555555555';

    public function routeNotificationForSmsmode($notification): string
    {
        return $this->phone_number;
    }
}

class NotificationSmsmodeChannelTestNotification extends Notification
{
    public function toSmsmode($notifiable): SmsmodeMessage
    {
        return new SmsmodeMessage();
    }
}

class NotificationSmsmodeChannelWithFullMessageTestNotification extends Notification
{
    public function toSmsmode($notifiable): SmsmodeMessage
    {
        return (new SmsmodeMessage())
            ->content('this is my message')
            ->stop(true)
            ->unicode()
            ->reference('REF123')
            ->sender('5554443333')
            ->date((new DateTimeImmutable())->modify('+1 day'))
            ->callbackUrl('https://example.com/status')
            ->callbackMOUrl('https://example.com/mo');
    }
}

class NotificationSmsmodeChannelOverChannelTestNotification extends Notification
{
    private string $channel;

    public function __construct() {
        $this->channel = Str::uuid();
    }

    public function toSmsmode($notifiable): SmsmodeMessage
    {
        return (new SmsmodeMessage('this is my message'))
            ->channel($this->channel);
    }
}

class NotificationSmsmodeChannelOverCampaignTestNotification extends Notification
{
    private string $campaign;

    public function __construct() {
        $this->campaign = Str::uuid();
    }

    public function toSmsmode($notifiable): SmsmodeMessage
    {
        return (new SmsmodeMessage('this is my message'))
            ->campaign($this->campaign);
    }
}

class NotificationSmsmodeChannelOverChannelCampaignTestNotification extends Notification
{
    private string $campaign;
    private string $channel;

    public function __construct() {
        $this->channel = Str::uuid();
        $this->campaign = Str::uuid();
    }

    public function toSmsmode($notifiable): SmsmodeMessage
    {
        return (new SmsmodeMessage('this is my message'))
            ->channel($this->channel)
            ->campaign($this->campaign);
    }
}
