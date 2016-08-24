<?php

namespace Sstalle\php7cc\NodeVisitor;

use PhpParser\Node;
use Sstalle\php7cc\CompatibilityViolation\Message;

class ContinueBreakOutsideLoopVisitor extends AbstractNestedLoopVisitor
{
    const LEVEL = Message::LEVEL_ERROR;

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node)
    {
        parent::enterNode($node);

        if ($node instanceof Node\Stmt\Break_ || $node instanceof Node\Stmt\Continue_) {
            $statement = $node instanceof Node\Stmt\Break_ ? 'break' : 'continue';
            $value = $node->num === null ? 0 : $node->num->value;
            if ($this->getCurrentLoopStack()->isEmpty()) {
                $this->addContextMessage(sprintf("%s not in the loop or switch context", $statement), $node);
            } elseif ($value > $this->getCurrentLoopStack()->count()) {
                $this->addContextMessage(sprintf("Cannot '%s' %d levels", $statement, $value), $node);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isTargetLoopNode(Node $node)
    {
        return $node instanceof Node\Stmt\While_ || $node instanceof Node\Stmt\Do_
            || $node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\For_
            || $node instanceof Node\Stmt\Switch_;
    }
}
