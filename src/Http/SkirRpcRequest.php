<?php

declare(strict_types=1);

namespace Skir\Client\Http;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use Skir\Runtime\DenseJson;
use Skir\Runtime\MethodDescriptor;

final class SkirRpcRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly MethodDescriptor $descriptor,
        public readonly mixed $request,
        private readonly string $endpoint = '/',
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array{
     *     Content-Type: string
     * }
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain; charset=utf-8',
        ];
    }

    protected function defaultBody(): string
    {
        $requestJson = DenseJson::toJson($this->descriptor->requestType, $this->request);

        return "{$this->descriptor->name}:{$this->descriptor->number}::{$requestJson}";
    }
}
