<?php

declare(strict_types=1);

/**
 * @return array<string, array{
 *     title: string,
 *     summary: string,
 *     method: string,
 *     instructions: list<string>
 * }>
 */
function challenge_definitions(): array
{
    return [
        'query-parameters' => [
            'title' => 'Query Parameters',
            'summary' => 'Observe how a GET parameter changes the server response.',
            'method' => 'GET',
            'instructions' => [
                'Open this challenge with the query parameter inspect=request.',
                'Read the response produced for that exact parameter value.',
            ],
        ],
        'response-headers' => [
            'title' => 'Response Headers',
            'summary' => 'Inspect metadata returned outside the HTML response body.',
            'method' => 'GET',
            'instructions' => [
                'Inspect the HTTP response headers for this page.',
                'Find the custom X-MiniHack-Flag response header.',
            ],
        ],
        'page-source' => [
            'title' => 'Page Source',
            'summary' => 'Compare rendered HTML with the original response source.',
            'method' => 'GET',
            'instructions' => [
                'Use the browser View Source feature for this page.',
                'Find the MiniHack flag stored in an HTML comment.',
            ],
        ],
    ];
}

/** @return array{title: string, summary: string, method: string, instructions: list<string>}|null */
function challenge_definition(string $slug): ?array
{
    $definitions = challenge_definitions();
    return $definitions[$slug] ?? null;
}

function instance_secret_path(): string
{
    return dirname(__DIR__) . '/database/instance_secret';
}

function load_instance_secret(): string
{
    $encodedSecret = @file_get_contents(instance_secret_path());
    if (!is_string($encodedSecret)) {
        throw new RuntimeException('The challenge instance secret has not been initialized.');
    }

    $encodedSecret = trim($encodedSecret);
    if (preg_match('/\A[0-9a-f]{64}\z/', $encodedSecret) !== 1) {
        throw new RuntimeException('The challenge instance secret is invalid.');
    }

    $secret = hex2bin($encodedSecret);
    if (!is_string($secret)) {
        throw new RuntimeException('The challenge instance secret could not be decoded.');
    }

    return $secret;
}

function challenge_flag(int $userId, string $slug): string
{
    if ($userId < 1 || challenge_definition($slug) === null) {
        throw new InvalidArgumentException('Cannot generate a flag for an unknown challenge or user.');
    }

    $digest = hash_hmac('sha256', $userId . ':' . $slug, load_instance_secret());
    return 'MHL{' . substr($digest, 0, 24) . '}';
}
