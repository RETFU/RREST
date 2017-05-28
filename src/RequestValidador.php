<?php

namespace RREST;

use Psr\Http\Message\RequestInterface;
use RREST\APISpec\APISpecInterface;
use RREST\Exception\InvalidParameterException;

class RequestValidator
{
    protected $apiSpec;

    public function __construct(APISpecInterface $apiSpec)
    {
        $this->apiSpec = $apiSpec;
    }

    public function validate($request): bool
    {
    }

    public function assert(RequestInterface $request)
    {
        $this->assertQueryParameters($request);
    }

    public function assertQueryParameters(RequestInterface $request)
    {
        $requestParameters = $this->getRequestParameters($request);
        $missingParameters = array_filter($this->apiSpec->getParameters(), function ($p) use ($requestParameters) {
            return
                $p->getRequired() &&
                isset($requestParameters[$p->getName()]) === false
            ;
        });

        if (count($missingParameters) === 0) {
            return;
        }

        $errors = [];
        foreach ($missingParameters as $missingParameter) {
            $errors[] = new Error(
                sprintf(
                    'Missing parameter required for this route: %s (%s)',
                    $missingParameter->getName(),
                    $missingParameter->getType()
                )
            );
        }
        throw new InvalidParameterException($errors);
    }

    public function assertHeaders()
    {
    }

    public function assertBody()
    {
    }

    public function assertProtocol()
    {
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     */
    private function getRequestParameters(RequestInterface $request)
    {
        parse_str($request->getUri()->getQuery(), $requestParameters);

        return $requestParameters;
    }
}
