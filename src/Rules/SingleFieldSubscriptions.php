<?php
namespace byTorsten\GraphQL\Subscriptions\Rules;

use GraphQL\Error\Error;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Validator\Rules\AbstractValidationRule;
use GraphQL\Validator\ValidationContext;

class SingleFieldSubscriptions extends AbstractValidationRule
{
    /**
     * @param null|string $name
     * @return string
     */
    protected function singleFieldOnlyMessage(?string $name): string {
        return ($name !== null ? 'Subscription "' . $name . '" ' : 'Anonymous Subscription ') . 'must select only one top level field.';
    }

    /**
     * Returns structure suitable for GraphQL\Language\Visitor
     *
     * @see \GraphQL\Language\Visitor
     * @param ValidationContext $context
     * @return array
     */
    public function getVisitor(ValidationContext $context)
    {
        return [
            NodeKind::OPERATION_DEFINITION => function (OperationDefinitionNode $node) use ($context) {
                if ($node->operation === 'subscription') {
                    if (count($node->selectionSet->selections) !== 1) {
                        $context->reportError(new Error(
                            $this->singleFieldOnlyMessage($node->name ? $node->name->value : null),
                            array_slice($node->selectionSet->selections, 1)
                            )
                        );
                    }
                }
            }
        ];
    }
}
