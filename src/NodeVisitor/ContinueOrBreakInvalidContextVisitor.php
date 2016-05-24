<?php

namespace Sstalle\php7cc\NodeVisitor;

use PhpParser\Node;
use Sstalle\php7cc\CompatibilityViolation\Message;
use Sstalle\php7cc\NodeAnalyzer\FunctionAnalyzer;

class ContinueOrBreakInvalidContextVisitor extends AbstractVisitor {
	const LEVEL = Message::LEVEL_ERROR;
	protected $validContextStack;

	public function __construct() {
		$this->validContextStack = new \SplStack;
	}

	private function isValidContext($node) {
		return $node instanceof Node\Stmt\Foreach_ ||
			$node instanceof Node\Stmt\While_ ||
			$node instanceof Node\Stmt\Switch_;
	}

	public function enterNode(Node $node) {
		if ($this->isValidContext($node)) {
			$this->validContextStack->push($node);
		} elseif ($node instanceof Node\Stmt\Break_ || $node instanceof Node\Stmt\Continue_) {
			$statement = $node instanceof Node\Stmt\Break_ ? 'break' : 'continue';


			if ($node->num !== null && !($node->num instanceof Node\Scalar\LNumber)) {
				// this is also invalid in PHP 5.x
				$this->addContextMessage(sprintf("'%s' operator with non-constant operand is not supported", $statement), $node);
			} else {
				$value = $node->num === null ? 0 : $node->num->value;
				if ($this->validContextStack->isEmpty()) {
					$this->addContextMessage(sprintf("'%s' statement not in the 'loop' or 'switch' context. It's a compile time error in PHP 7", $statement), $node);
				} elseif ($value > $this->validContextStack->count()) {
					$this->addContextMessage(sprintf("Cannot '%s' %d levels. It's a compile time error in PHP 7", $statement, $value), $node);
				}
			}
		}
	}

	public function leaveNode(Node $node) {
		if ($this->isValidContext($node)) {
			$this->validContextStack->pop();
		}
	}
}
