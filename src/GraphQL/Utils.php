<?php
namespace byTorsten\GraphQL\Subscriptions\GraphQL;

use GraphQL\Executor\ExecutionContext;
use GraphQL\Executor\Executor;
use GraphQL\Executor\Promise\Adapter\ReactPromiseAdapter;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;

/**
 * This is a group of helper methods until the graphql-php implementation opens up the executor class
 */
class Utils
{
    /**
     * Some crazy stuff to work around a private constructor
     *
     * @param ExecutionContext $exeContext
     * @return Executor
     */
    protected static function construct(ExecutionContext $exeContext = null): Executor
    {
        $executorReflection = new \ReflectionClass(Executor::class);
        /** @var Executor $executor */
        $executor = $executorReflection->newInstanceWithoutConstructor();

        if ($exeContext !== null) {
            $constructor = $executorReflection->getConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($executor, $exeContext);
        }

        return $executor;
    }

    /**
     * @param Schema $schema
     * @param DocumentNode $document
     * @param $rootValue
     * @param $contextValue
     * @param $variableValues
     * @param null $operationName
     * @param callable|null $fieldResolver
     * @return ExecutionContext
     */
    public static function buildExecutionContext(
        Schema $schema,
        DocumentNode $document,
        $rootValue,
        $contextValue,
        $variableValues,
        $operationName = null,
        callable $fieldResolver = null
    ): ExecutionContext {
        $buildExecutionContext = new \ReflectionMethod(Executor::class, 'buildExecutionContext');
        $buildExecutionContext->setAccessible(true);

        /** @var ExecutionContext $exeContext */
        $exeContext = $buildExecutionContext->invoke(null, $schema, $document, $rootValue, $contextValue, $variableValues, $operationName, $fieldResolver, new ReactPromiseAdapter());

        return $exeContext;
    }

    /**
     * @param Schema $schema
     * @param OperationDefinitionNode $operation
     * @return ObjectType
     */
    public static function getOperationRootType(Schema $schema, OperationDefinitionNode $operation): ObjectType
    {
        $instance = static::construct();
        $getOperationRootType = new \ReflectionMethod(Executor::class, 'getOperationRootType');
        $getOperationRootType->setAccessible(true);

        return $getOperationRootType->invoke($instance, $schema, $operation);
    }

    /**
     * @param ExecutionContext $exeContext
     * @param ObjectType $type
     * @param SelectionSetNode $selectionSet
     * @param array $fields
     * @param array $visitedFragmentNames
     * @return array
     */
    public static function collectFields(ExecutionContext $exeContext, ObjectType $type, SelectionSetNode $selectionSet, array $fields = [], array $visitedFragmentNames =[]): array
    {
        $instance = static::construct($exeContext);
        $collectFields = new \ReflectionMethod(Executor::class, 'collectFields');
        $collectFields->setAccessible(true);

        return $collectFields->invoke($instance, $type, $selectionSet, $fields, $visitedFragmentNames);
    }

    /**
     * @param Schema $schema
     * @param ObjectType $parentType
     * @param string $fieldName
     * @return FieldDefinition
     */
    public static function getFieldDef(Schema $schema, ObjectType $parentType, string $fieldName): ?FieldDefinition
    {
        $instance = static::construct();
        $getFieldDef = new \ReflectionMethod(Executor::class, 'getFieldDef');
        $getFieldDef->setAccessible(true);

        return $getFieldDef->invoke($instance, $schema, $parentType, $fieldName);
    }

    /**
     * @param $prev
     * @param $key
     * @return array
     */
    public static function addPath($prev, $key)
    {
        return ['prev' => $prev, 'key' => $key];
    }

    /**
     * @param ExecutionContext $exeContext
     * @param FieldDefinition $fieldDef
     * @param array|\ArrayObject $fieldNodes
     * @param ObjectType $parentType
     * @param array $path
     * @return ResolveInfo
     */
    public static function buildResolveInfo(ExecutionContext $exeContext, FieldDefinition $fieldDef, $fieldNodes, ObjectType $parentType, array $path): ResolveInfo
    {
        return new ResolveInfo([
            'fieldName' => $fieldDef->name,
            'fieldNodes' => $fieldNodes,
            'returnType' => $fieldDef->getType(),
            'parentType' => $parentType,
            'path' => $path,
            'schema' => $exeContext->schema,
            'fragments' => $exeContext->fragments,
            'rootValue' => $exeContext->rootValue,
            'operation' => $exeContext->operation,
            'variableValues' => $exeContext->variableValues,
        ]);
    }

    /**
     * @param ExecutionContext $exeContext
     * @param FieldDefinition $fieldDef
     * @param FieldNode $fieldNode
     * @param callable $resolveFn
     * @param $source
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function resolveFieldValueOrError(ExecutionContext $exeContext, FieldDefinition $fieldDef, FieldNode $fieldNode, callable $resolveFn, $source, ResolveInfo $info)
    {
        $instance = static::construct($exeContext);
        $resolveOrError = new \ReflectionMethod(Executor::class, 'resolveOrError');
        $resolveOrError->setAccessible(true);

        return $resolveOrError->invoke($instance, $fieldDef, $fieldNode, $resolveFn, $source, $exeContext->contextValue, $info);
    }
}
