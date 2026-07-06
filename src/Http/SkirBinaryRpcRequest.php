<?php

declare(strict_types=1);

namespace Skir\Client\Http;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasStringBody;
use Skir\Client\Codecs\SkirClientHttpCodec;
use Skir\Runtime\MethodDescriptor;

final class SkirBinaryRpcRequest extends Request implements HasBody
{
    use HasStringBody;

    protected Method $method = Method::POST;

    public function __construct(
        private readonly MethodDescriptor $descriptor,
        private readonly mixed $request,
        private readonly SkirClientHttpCodec $codec,
        private readonly string $endpoint = '/',
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return array{
     *     Accept: string,
     *     Content-Type: string
     * }
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => $this->codec->contentType(),
            'Content-Type' => $this->codec->contentType(),
        ];
    }

    protected function defaultBody(): string
    {
        return $this->codec->encodeRequestBody($this->descriptor, $this->request);
    }
}
