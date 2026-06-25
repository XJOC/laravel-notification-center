<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Channels;

use XJOC\NotificationCenter\Exceptions\UnsupportedWhatsappMessageException;

/**
 * A structured WhatsApp message handed to a WhatsappTransport. Modeled after
 * Laravel's MailMessage: one stable transport entry point (WhatsappTransport::send),
 * while the message object is what grows to support new kinds.
 *
 * v1 implements TEXT only. The factories for richer kinds (file, location,
 * buttons) are reserved as the intended API surface but throw until a future
 * release implements them — so the contract shape is fixed now without building
 * a full messaging layer.
 */
final class WhatsappMessage
{
    public const TYPE_TEXT = 'text';

    private function __construct(
        public readonly string $type,
        public readonly string $to,
        public readonly string $body,
    ) {}

    public static function text(string $to, string $body): self
    {
        return new self(self::TYPE_TEXT, $to, $body);
    }

    public function isText(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    /**
     * Reserved for a future release. Not supported in v1.
     */
    public static function file(string $to, string $url): self
    {
        throw UnsupportedWhatsappMessageException::forKind('file');
    }

    /**
     * Reserved for a future release. Not supported in v1.
     */
    public static function location(string $to, float $latitude, float $longitude): self
    {
        throw UnsupportedWhatsappMessageException::forKind('location');
    }

    /**
     * Reserved for a future release. Not supported in v1.
     *
     * @param  array<int, mixed>  $buttons
     */
    public static function buttons(string $to, string $body, array $buttons): self
    {
        throw UnsupportedWhatsappMessageException::forKind('buttons');
    }
}
