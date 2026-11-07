<?php

$request = $_GET['id'] ?? null;
$_SESSION['last'] = $request;

function handle(): void
{
    global $counter;
    $counter++;
    $id = $_GET['id'] ?? null;
    $_SESSION['last'] = $id;
}

function handleExplicitly(array $query, array $session): array
{
    $session['last'] = $query['id'] ?? null;
    return $session;
}
