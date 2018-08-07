<?php
namespace byTorsten\GraphQL\Subscriptions\Domain\Model;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;

class ExecutionParameters
{
    /**
     * @var string|DocumentNode
     */
    protected $query;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var string|null
     */
    protected $operationName;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @var callable
     */
    protected $formatResponse;

    /**
     * @var callable
     */
    protected $formatError;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @return DocumentNode|string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string|DocumentNode $query
     */
    public function setQuery($query): void
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @param array $variables
     */
    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * @return string
     */
    public function getOperationName(): ?string
    {
        return $this->operationName;
    }

    /**
     * @param string $operationName
     */
    public function setOperationName(?string $operationName): void
    {
        $this->operationName = $operationName;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }

    /**
     * @return callable
     */
    public function getFormatResponse(): ?callable
    {
        return $this->formatResponse;
    }

    /**
     * @param callable $formatResponse
     */
    public function setFormatResponse(callable $formatResponse): void
    {
        $this->formatResponse = $formatResponse;
    }

    /**
     * @return callable
     */
    public function getFormatError(): ?callable
    {
        return $this->formatError;
    }

    /**
     * @param callable $formatError
     */
    public function setFormatError(callable $formatError): void
    {
        $this->formatError = $formatError;
    }

    /**
     * @return callable
     */
    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    /**
     * @param callable $callback
     */
    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param Schema $schema
     */
    public function setSchema(Schema $schema): void
    {
        $this->schema = $schema;
    }
}
