<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data\Issue46;

final class Task
{
    public function isUrgent(Priority $priority): bool
    {
        return $priority === Priority::High;
    }

    public function describe(Priority $priority): string
    {
        return match ($priority) {
            Priority::High => 'Urgent priority',
            Priority::Low => 'Normal priority',
        };
    }

    public function getDefaultPriority(): string
    {
        return Priority::DEFAULT_PRIORITY;
    }
}
