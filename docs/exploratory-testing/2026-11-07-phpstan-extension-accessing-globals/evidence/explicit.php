<?php

function updateRequest(array $query, array $session, int $timestamp, int $limit): array
{
    $session['id'] = $query['id'] ?? null;
    $session['timestamp'] = $timestamp;
    $session['limit'] = $limit;
    return $session;
}
