<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Released challenge slugs are immutable. Renames require an explicit migration
 * of solve records and consideration of flags derived from the old slug.
 *
 * @return array<string, array{
 *     title: string,
 *     summary: string,
 *     method: string,
 *     instructions: list<string>,
 *     learning: array{
 *         what_happened: string,
 *         why_it_worked: string,
 *         security_takeaway: string
 *     }
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
            'learning' => [
                'what_happened' => 'The `inspect=request` value is sent in the URL as a query parameter and read by the server when building the response.',
                'why_it_worked' => 'Query parameters are client-controlled input. They allow the client to send values to the server through the request URL and can influence application behavior.',
                'security_takeaway' => 'Never treat query parameters as trusted input. Validate them and keep sensitive authorization decisions on the server.',
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
            'learning' => [
                'what_happened' => 'The flag was returned in an HTTP response header instead of the HTML body.',
                'why_it_worked' => 'An HTTP response contains more than the rendered page. Status information, headers, and the response body are all delivered to the client and can be inspected using browser developer tools, Burp, or tools such as `curl -i`.',
                'security_takeaway' => 'Data does not become secret just because it is not visible on the page. Anything sent to the client in an HTTP header should be considered accessible to that client.',
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
            'learning' => [
                'what_happened' => 'The flag was present in the HTML response even though it was not visibly rendered on the page.',
                'why_it_worked' => 'The browser receives the HTML source before rendering it. Comments, hidden elements, attributes, and other source content can still be inspected by the user.',
                'security_takeaway' => 'If sensitive data is delivered to the browser, it should be considered exposed. HTML comments, hidden elements, CSS, and client-side JavaScript are not access-control mechanisms.',
            ],
        ],
    ];
}

/** @return array{title: string, summary: string, method: string, instructions: list<string>, learning: array{what_happened: string, why_it_worked: string, security_takeaway: string}}|null */
function challenge_definition(string $slug): ?array
{
    $definitions = challenge_definitions();
    return $definitions[$slug] ?? null;
}

function instance_secret_path(): string
{
    return data_directory() . '/instance_secret';
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
