<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class RecipientResolver
{
    public function __construct(
        private ConfigRepository $config,
    ) {}

    /**
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Model>
     */
    public function resolve(string $model, array $ids): Collection
    {
        if (! $this->allows($model)) {
            throw new InvalidArgumentException(
                "Model [{$model}] is not an allowed notifiable recipient.",
            );
        }

        /** @var Model $instance */
        $instance = new $model;

        /** @var Collection<int, Model> $recipients */
        $recipients = $instance->newQuery()->findMany($ids);

        return $recipients;
    }

    public function allows(string $model): bool
    {
        /** @var array<int, string> $allowed */
        $allowed = (array) $this->config->get('notification-center.notifiable_models', []);

        if (! in_array($model, $allowed, true)) {
            return false;
        }

        if (! class_exists($model)) {
            return false;
        }

        return is_subclass_of($model, Model::class);
    }
}
