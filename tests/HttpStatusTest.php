<?php

declare(strict_types=1);

/**
 * This file is part of Narrowspark Framework.
 *
 * (c) Daniel Bannert <d.bannert@anolilab.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Narrowspark\HttpStatus\Tests;

use DOMDocument;
use DomXPath;
use Narrowspark\HttpStatus\Contract\Exception\HttpException as HttpExceptionContract;
use Narrowspark\HttpStatus\Exception;
use Narrowspark\HttpStatus\Exception\InvalidArgumentException;
use Narrowspark\HttpStatus\Exception\OutOfBoundsException;
use Narrowspark\HttpStatus\HttpStatus;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @small
 */
final class HttpStatusTest extends TestCase
{
    private $errorPhrases = [
        // Successful 2xx
        200 => 'Standard response for successful HTTP requests.',
        201 => 'The request has been fulfilled, resulting in the creation of a new resource.',
        202 => 'The request has been accepted for processing, but the processing has not been completed.',
        203 => 'The server is a transforming proxy (e.g. a Web accelerator) that received a 200 OK from its origin, but is returning a modified version of the origin\'s response.',
        204 => 'The server successfully processed the request and is not returning any content.',
        205 => 'The server successfully processed the request, but is not returning any content.',
        206 => 'The server is delivering only part of the resource (byte serving) due to a range header sent by the client.',
        207 => 'The message body that follows is an XML message and can contain a number of separate response codes, depending on how many sub-requests were made.',
        208 => 'The members of a DAV binding have already been enumerated in a previous reply to this request, and are not being included again.',
        226 => 'The server has fulfilled a request for the resource, and the response is a representation of the result of one or more instance-manipulations applied to the current instance.',
        // Redirection 3xx
        300 => 'Indicates multiple options for the resource from which the client may choose.',
        301 => 'This and all future requests should be directed to the given URI.',
        302 => 'This is an example of industry practice contradicting the standard.',
        303 => 'The response to the request can be found under another URI using a GET method.',
        304 => 'Indicates that the resource has not been modified since the version specified by the request headers If-Modified-Since or If-None-Match.',
        305 => 'The requested resource is available only through a proxy, the address for which is provided in the response.',
        306 => 'No longer used.',
        307 => 'In this case, the request should be repeated with another URI; however, future requests should still use the original URI.',
        308 => 'The request and all future requests should be repeated using another URI.',
        // Client Error 4xx
        400 => 'The request cannot be fulfilled due to bad syntax.',
        401 => 'Authentication is required and has failed or has not yet been provided.',
        402 => 'Reserved for future use.',
        403 => 'The request was a valid request, but the server is refusing to respond to it.',
        404 => 'The requested resource could not be found but may be available again in the future.',
        405 => 'A request was made of a resource using a request method not supported by that resource.',
        406 => 'The requested resource is only capable of generating content not acceptable.',
        407 => 'Proxy authentication is required to access the requested resource.',
        408 => 'The server did not receive a complete request message in time.',
        409 => 'The request could not be processed because of conflict in the request.',
        410 => 'The requested resource is no longer available and will not be available again.',
        411 => 'The request did not specify the length of its content, which is required by the resource.',
        412 => 'The server does not meet one of the preconditions that the requester put on the request.',
        413 => 'The server cannot process the request because the request payload is too large.',
        414 => 'The request-target is longer than the server is willing to interpret.',
        415 => 'The request entity has a media type which the server or resource does not support.',
        416 => 'The client has asked for a portion of the file, but the server cannot supply that portion.',
        417 => 'The expectation given could not be met by at least one of the inbound servers.',
        418 => 'I\'m a teapot',
        421 => 'The request was directed at a server that is not able to produce a response.',
        422 => 'The request was well-formed but was unable to be followed due to semantic errors.',
        423 => 'The resource that is being accessed is locked.',
        424 => 'The request failed due to failure of a previous request.',
        425 => 'The server is unwilling to risk processing a request that might be replayed.',
        426 => 'The server cannot process the request using the current protocol.',
        428 => 'The origin server requires the request to be conditional.',
        429 => 'The user has sent too many requests in a given amount of time.',
        431 => 'The server is unwilling to process the request because either an individual header field, or all the header fields collectively, are too large.',
        451 => 'Resource access is denied for legal reasons.',
        // Server Error 5xx
        500 => 'An error has occurred and this resource cannot be displayed.',
        501 => 'The server either does not recognize the request method, or it lacks the ability to fulfil the request.',
        502 => 'The server was acting as a gateway or proxy and received an invalid response from the upstream server.',
        503 => 'The server is currently unavailable. It may be overloaded or down for maintenance.',
        504 => 'The server was acting as a gateway or proxy and did not receive a timely response from the upstream server.',
        505 => 'The server does not support the HTTP protocol version used in the request.',
        506 => 'Transparent content negotiation for the request, results in a circular reference.',
        507 => 'The method could not be performed on the resource because the server is unable to store the representation needed to successfully complete the request. There is insufficient free space left in your storage allocation.',
        508 => 'The server detected an infinite loop while processing the request.',
        510 => 'Further extensions to the request are required for the server to fulfill it.A mandatory extension policy in the request is not accepted by the server for this resource.',
        511 => 'The client needs to authenticate to gain network access.',
    ];

    private $phrasesExceptions = [
        400 => Exception\BadRequestException::class,
        401 => Exception\UnauthorizedException::class,
        402 => Exception\PaymentRequiredException::class,
        403 => Exception\ForbiddenException::class,
        404 => Exception\NotFoundException::class,
        405 => Exception\MethodNotAllowedException::class,
        406 => Exception\NotAcceptableException::class,
        407 => Exception\ProxyAuthenticationRequiredException::class,
        408 => Exception\RequestTimeoutException::class,
        409 => Exception\ConflictException::class,
        410 => Exception\GoneException::class,
        411 => Exception\LengthRequiredException::class,
        412 => Exception\PreconditionFailedException::class,
        413 => Exception\PayloadTooLargeException::class,
        414 => Exception\RequestUriTooLongException::class,
        415 => Exception\UnsupportedMediaTypeException::class,
        416 => Exception\RequestedRangeNotSatisfiableException::class,
        417 => Exception\ExpectationFailedException::class,
        418 => Exception\ImATeapotException::class,
        421 => Exception\MisdirectedRequestException::class,
        422 => Exception\UnprocessableEntityException::class,
        423 => Exception\LockedException::class,
        424 => Exception\FailedDependencyException::class,
        425 => Exception\TooEarlyException::class,
        426 => Exception\UpgradeRequiredException::class,
        428 => Exception\PreconditionRequiredException::class,
        429 => Exception\TooManyRequestsException::class,
        431 => Exception\RequestHeaderFieldsTooLargeException::class,
        451 => Exception\UnavailableForLegalReasonsException::class,
        // Server Error 5xx
        500 => Exception\InternalServerErrorException::class,
        501 => Exception\NotImplementedException::class,
        502 => Exception\BadGatewayException::class,
        503 => Exception\ServiceUnavailableException::class,
        504 => Exception\GatewayTimeoutException::class,
        505 => Exception\HttpVersionNotSupportedException::class,
        506 => Exception\VariantAlsoNegotiatesException::class,
        507 => Exception\InsufficientStorageException::class,
        508 => Exception\LoopDetectedException::class,
        510 => Exception\NotExtendedException::class,
        511 => Exception\NetworkAuthenticationRequiredException::class,
    ];

    public function testGetReasonMessage(): void
    {
        foreach ($this->errorPhrases as $code => $text) {
            self::assertSame(
                $text,
                HttpStatus::getReasonMessage($code),
                'Expected HttpStatus::getReasonMessage(' . $code . ') to return ' . $text
            );
        }
    }

    public function testGetReasonPhraseToThrowInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The submitted code "700" must be a positive integer between 100 and 599.');

        HttpStatus::getReasonPhrase(700);
    }

    public function testGetReasonMessageToThrowInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The submitted code "700" must be a positive integer between 100 and 599.');

        HttpStatus::getReasonMessage(700);
    }

    public function testGetReasonMessageToThrowOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown http status code: `509`.');

        HttpStatus::getReasonMessage(509);
    }

    public function testGetReasonExceptionToThrowOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown http status code: `509`.');

        HttpStatus::getReasonException(509);
    }

    public function testGetReasonPhraseToThrowOutOfBoundsException(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Unknown http status code: `509`.');

        HttpStatus::getReasonPhrase(509);
    }

    public function testIfAllExceptionsAreExtendedFromTheRightClient(): void
    {
        $clientCount = 0;
        $serverCount = 0;

        foreach ($this->ianaCodesReasonPhrasesProvider() as $data) {
            try {
                HttpStatus::getReasonException((int) $data[0]);
            } catch (Exception\AbstractClientErrorException $client) {
                self::assertInstanceOf($this->phrasesExceptions[$data[0]], $client);

                $clientCount++;
            } catch (Exception\AbstractServerErrorException $server) {
                self::assertInstanceOf($this->phrasesExceptions[$data[0]], $server);

                $serverCount++;
            }
        }

        self::assertSame(28, $clientCount);
        self::assertSame(11, $serverCount);
    }

    /**
     * @dataProvider ianaCodesReasonPhrasesProvider
     *
     * @param mixed $code
     * @param mixed $reasonPhrase
     */
    public function testReasonPhraseDefaultsAgainstIana($code, $reasonPhrase): void
    {
        self::assertEquals(
            $reasonPhrase,
            HttpStatus::getReasonPhrase((int) $code),
            'Expected HttpStatus::getReasonPhrase(' . $code . ') to return ' . $reasonPhrase
        );
    }

    /**
     * @dataProvider ianaCodesReasonPhrasesProvider
     *
     * @param mixed $code
     * @param mixed $reasonPhrase
     */
    public function testGetReasonExceptionAgainstIana($code, $reasonPhrase): void
    {
        // skip http code from 100 to 399
        if ((100 <= $code) && ($code <= 399)) {
            self::assertTrue(true);
        }

        try {
            HttpStatus::getReasonException((int) $code);
        } catch (HttpExceptionContract $exception) {
            self::assertSame($code . ' ' . $reasonPhrase, $exception->getMessage());
            self::assertSame((int) $code, $exception->getStatusCode());
        }
    }

    /**
     * @see http://github.com/zendframework/zend-diactoros for the canonical source repository
     *
     * @author    Fábio Pacheco
     * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
     */
    public function ianaCodesReasonPhrasesProvider(): iterable
    {
        if (! \in_array('https', \stream_get_wrappers(), true)) {
            self::markTestSkipped('The "https" wrapper is not available');
        }

        $ianaHttpStatusCodes = new DOMDocument();

        \libxml_set_streams_context(\stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 30,
                'user_agent' => 'Narrowspark http-status phpunit agent',
            ],
        ]));

        $ianaHttpStatusCodes->load('https://www.iana.org/assignments/http-status-codes/http-status-codes.xml');

        $context = null;

        if (! $ianaHttpStatusCodes->relaxNGValidate(__DIR__ . '/schema/http-status-codes.rng')) {
            self::fail('Invalid IANA\'s HTTP status code list.');
        }

        $ianaCodesReasonPhrases = [];
        $xpath = new DomXPath($ianaHttpStatusCodes);
        $xpath->registerNamespace('ns', 'http://www.iana.org/assignments');
        $records = $xpath->query('//ns:record');

        foreach ($records as $record) {
            $value = $xpath->query('.//ns:value', $record)->item(0)->nodeValue;
            $description = $xpath->query('.//ns:description', $record)->item(0)->nodeValue;

            if (\in_array($description, ['Unassigned', '(Unused)'], true)) {
                continue;
            }

            if (\preg_match('/^([0-9]+)\s*\-\s*([0-9]+)$/', $value, $matches)) {
                for ($value = $matches[1]; $value <= $matches[2]; $value++) {
                    $ianaCodesReasonPhrases[] = [$value, $description];
                }
            } else {
                $ianaCodesReasonPhrases[] = [$value, $description];
            }
        }

        return $ianaCodesReasonPhrases;
    }

    public function testChangeExceptionMessage(): void
    {
        $message = 'new message';
        $exception = new Exception\NotFoundException($message);

        self::assertSame($message, $exception->getMessage());
    }
}
