<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Templates;

/**
 * An immutable, raw (un-rendered) template handed to a channel driver. The
 * driver is responsible for substituting variables and applying its own
 * escaping when it renders the subject/body.
 */
final class ChannelTemplate
{
    public function __construct(
        public readonly ?string $subject,
        public readonly string $body,
    ) {}
}
