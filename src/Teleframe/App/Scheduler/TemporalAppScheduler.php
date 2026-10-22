<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App\Scheduler;

use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;

final class TemporalAppScheduler
{
    public function __construct(
        private readonly SchedulePublisher $publisher,
        private mixed $source = null,
        private ?AppCallSpec $callSpec = null,
    ) {}

    public function install(mixed ...$apps): array
    {
        $source = $apps !== [] ? $apps : $this->source;

        $definitions = $this->collectDefinitions($source);

        $callSpec = $this->callSpec ?? new AppCallSpec;

        $payload = [];
        foreach ($definitions as $app) {
            $payload = array_replace($payload, $callSpec->installSpec($app));
        }

        if ($definitions === [] && is_array($source) && $this->looksLikePayload($source)) {
            $payload = $source;
        } elseif ($definitions === [] && $apps !== [] && is_array($apps[0] ?? null) && $this->looksLikePayload($apps[0])) {
            $payload = $apps[0];
        }

        return $this->publisher->install($payload);
    }

    public function describe(): string
    {
        return $this->publisher->describe();
    }

    public function publisher(): SchedulePublisher
    {
        return $this->publisher;
    }

    /**
     * @return list<AppDefinition>
     */
    private function collectDefinitions(mixed $source): array
    {
        $definitions = [];

        if ($source instanceof AppRuntime) {
            foreach ($source->registry()->all() as $app) {
                $definitions[] = $app;
            }

            return $definitions;
        }

        if ($source instanceof AppDefinition) {
            $definitions[] = $source;

            return $definitions;
        }

        if (is_iterable($source)) {
            foreach ($source as $item) {
                if ($item instanceof AppDefinition) {
                    $definitions[] = $item;
                } elseif ($item instanceof AppRuntime) {
                    foreach ($item->registry()->all() as $app) {
                        $definitions[] = $app;
                    }
                } elseif (is_array($item)) {
                    foreach ($item as $nested) {
                        if ($nested instanceof AppDefinition) {
                            $definitions[] = $nested;
                        } elseif ($nested instanceof AppRuntime) {
                            foreach ($nested->registry()->all() as $app) {
                                $definitions[] = $app;
                            }
                        }
                    }
                }
            }

            return $definitions;
        }

        return $definitions;
    }

    private function looksLikePayload(mixed $value): bool
    {
        if (! is_array($value) || $value === [] || array_is_list($value)) {
            return false;
        }

        foreach ($value as $entry) {
            if (! is_array($entry)) {
                return false;
            }
            if (! isset($entry['interval_seconds']) && ! isset($entry['activity'])) {
                return false;
            }
        }

        return true;
    }
}
