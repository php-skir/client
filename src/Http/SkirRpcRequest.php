<?php

declare(strict_types=1);

namespace Skir\Client\Http;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;
use Skir\Client\Codecs\SkirClientCodec;
use Skir\Runtime\MethodDescriptor;

final class SkirRpcRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly MethodDescriptor $descriptor,
        private readonly mixed $request,
        private readonly SkirClientCodec $codec,
        private readonly string $endpoint = '/',
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array{
     *     method: string,
     *     request: mixed
     * }
     */
    protected function defaultBody(): array
    {
        return [
            'method' => $this->descriptor->name,
            'request' => $this->codec->encodeRequest($this->descriptor, $this->request),
        ];
    }
}
