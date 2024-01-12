<?php

declare(strict_types=1);

namespace LLPhant\Embeddings\EmbeddingGenerator;

use Exception;
use LLPhant\Embeddings\Document;
use LLPhant\OpenAIConfig;
use OpenAI;
use OpenAI\Client;

use function getenv;
use function str_replace;

final class OpenAIEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public const OPENAI_EMBEDDING_LENGTH = 1536;

    public Client $client;

    public string $modelName = 'text-embedding-ada-002';

    /**
     * @throws Exception
     */
    public function __construct(?OpenAIConfig $config = null)
    {
        if ($config instanceof OpenAIConfig && $config->client instanceof Client) {
            $this->client = $config->client;
        } else {
            $apiKey = $config->apiKey ?? getenv('OPENAI_API_KEY');
            if (! $apiKey) {
                throw new Exception('You have to provide a OPENAI_API_KEY env var to request OpenAI .');
            }

            $this->client = OpenAI::client($apiKey);
        }
    }

    /**
     * Call out to OpenAI's embedding endpoint.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        $text = str_replace("\n", ' ', $text);

        $response = $this->client->embeddings()->create([
            'model' => $this->modelName,
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    public function embedDocument(Document $document): Document
    {
        $text = $document->formattedContent ?? $document->content;
        $document->embedding = $this->embedText($text);

        return $document;
    }

    /**
     * @param  Document[]  $documents
     * @return Document[]
     */
    public function embedDocuments(array $documents): array
    {
        $embedDocuments = [];
        foreach ($documents as $document) {
            $embedDocuments[] = $this->embedDocument($document);
        }

        return $embedDocuments;
    }
}
