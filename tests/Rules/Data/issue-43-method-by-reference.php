<?php

declare(strict_types=1);

namespace AccessingGlobals\Tests\Rules\Data;

final class StateService
{
    /**
     * @param array<string, mixed> $data
     */
    public function update(array &$data): void
    {
        $data['modified'] = true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function staticUpdate(array &$data): void
    {
        $data['modified'] = true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function readOnly(array $data): void
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function staticReadOnly(array $data): void
    {
    }
}

function mutateViaInstanceMethod(StateService $service): void
{
    $service->update($_SESSION['items']);
    $service->update($GLOBALS['config']);
    $service?->update($_SESSION['items']);
    $service->update(data: $_SESSION['items']);

    // By-value calls must not be reported as mutations
    $service->readOnly($_SESSION['items']);
    $service->readOnly($GLOBALS['config']);
}

function mutateViaStaticMethod(): void
{
    StateService::staticUpdate($_SESSION['items']);
    StateService::staticUpdate($GLOBALS['config']);
    StateService::staticUpdate(data: $_SESSION['items']);

    // By-value calls must not be reported as mutations
    StateService::staticReadOnly($_SESSION['items']);
    StateService::staticReadOnly($GLOBALS['config']);
}

function mutateGlobalKeywordViaMethod(StateService $service): void
{
    global $appData;
    $service->update($appData);
    StateService::staticUpdate($appData);

    // By-value call must not be reported
    $service->readOnly($appData);
}
