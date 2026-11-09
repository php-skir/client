<?php

declare(strict_types=1);

namespace Skir\Client\Http;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use Skir\Client\WireFormat;
use Skir\Runtime\DenseJson;
use Skir\Runtime\MethodDescriptor;
use Skir\Runtime\ReadableJson;

final class SkirRpcRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly MethodDescriptor $descriptor,
        public readonly mixed $request,
        private readonly string $endpoint = '/',
        public readonly WireFormat $format = WireFormat::Dense,
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
        $requestType = $this->descriptor->requestType;

        if ($this->format === WireFormat::Readable) {
            $requestJson = ReadableJson::toJson($requestType, $this->request);

            return "{$this->descriptor->name}:{$this->descriptor->number}:readable:{$requestJson}";
        }

        $requestJson = DenseJson::toJson($requestType, $this->request);

        return "{$this->descriptor->name}:{$this->descriptor->number}::{$requestJson}";
    }
}
