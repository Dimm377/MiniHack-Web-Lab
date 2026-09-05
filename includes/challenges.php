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
 *     hint?: string,
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
            'title' => 'What\'s in the URL?',
            'summary' => 'Something changes when the URL does.',
            'method' => 'GET',
            'instructions' => [
                'Open this challenge with the query parameter inspect=request.',
                'Read the response produced for that exact parameter value.',
            ],
            'hint' => 'Required parameter: <code>inspect=request</code>',
            'learning' => [
                'what_happened' => 'The `inspect=request` value is sent in the URL as a query parameter and read by the server when building the response.',
                'why_it_worked' => 'Query parameters are client-controlled input. They allow the client to send values to the server through the request URL and can influence application behavior.',
                'security_takeaway' => 'Never treat query parameters as trusted input. Validate them and keep sensitive authorization decisions on the server.',
            ],
        ],
        'response-headers' => [
            'title' => 'Check the Fine Print',
            'summary' => 'The page isn\'t telling you everything.',
            'method' => 'GET',
            'instructions' => [
                'Inspect the HTTP response headers for this page.',
                'Find the custom X-MiniHack-Flag response header.',
            ],
            'hint' => 'Inspect the current document response, not a CSS or JavaScript request.',
            'learning' => [
                'what_happened' => 'The flag was returned in an HTTP response header instead of the HTML body.',
                'why_it_worked' => 'An HTTP response contains more than the rendered page. Status information, headers, and the response body are all delivered to the client and can be inspected using browser developer tools, Burp, or tools such as `curl -i`.',
                'security_takeaway' => 'Data does not become secret just because it is not visible on the page. Anything sent to the client in an HTTP header should be considered accessible to that client.',
            ],
        ],
        'page-source' => [
            'title' => 'Behind the Curtain',
            'summary' => 'What you see isn\'t always what you get.',
            'method' => 'GET',
            'instructions' => [
                'Use the browser View Source feature for this page.',
                'Find the MiniHack flag stored in an HTML comment.',
            ],
            'hint' => 'The rendered page intentionally does not display the flag.',
            'learning' => [
                'what_happened' => 'The flag was present in the HTML response even though it was not visibly rendered on the page.',
                'why_it_worked' => 'The browser receives the HTML source before rendering it. Comments, hidden elements, attributes, and other source content can still be inspected by the user.',
                'security_takeaway' => 'If sensitive data is delivered to the browser, it should be considered exposed. HTML comments, hidden elements, CSS, and client-side JavaScript are not access-control mechanisms.',
            ],
        ],
        'cookie-state' => [
            'title' => 'Crumbs Left Behind',
            'summary' => 'Your browser remembers more than you think.',
            'method' => 'GET',
            'instructions' => [
                'Inspect the cookies stored by your browser for this application.',
                'Find the challenge-specific training cookie.',
            ],
            'hint' => 'Your browser remembers more than the page shows.',
            'learning' => [
                'what_happened' => 'The server sent challenge state using `Set-Cookie`, and the browser stored that value for later requests.',
                'why_it_worked' => 'Cookies allow servers to maintain state across otherwise independent HTTP requests. The browser can send stored cookies back using the `Cookie` request header.',
                'security_takeaway' => 'Client-visible or client-controlled state must never be trusted for authorization or other sensitive decisions. Authentication and authorization must still be validated on the server.',
            ],
        ],
        'request-method-body' => [
            'title' => 'Say It Properly',
            'summary' => 'Sometimes it\'s not what you ask, but how you ask.',
            'method' => 'POST',
            'instructions' => [
                'Send a POST request to this challenge URL.',
                'Include `inspect=body` in the request body (form-urlencoded).',
            ],
            'hint' => 'Required body parameter: <code>inspect=body</code>',
            'learning' => [
                'what_happened' => 'The server only responded to the expected HTTP method and request body.',
                'why_it_worked' => 'An HTTP request includes more than a URL. The method, headers, and request body can all influence server behavior.',
                'security_takeaway' => 'Applications must validate request methods, content types, input data, authentication, and authorization independently. Changing the HTTP method must not bypass security controls.',
            ],
        ],
    ];
}

/** @return array{title: string, summary: string, method: string, instructions: list<string>, hint?: string, learning: array{what_happened: string, why_it_worked: string, security_takeaway: string}}|null */
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
