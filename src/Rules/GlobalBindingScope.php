<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\MethodReflection;

/**
 * Associates anonymous syntax with PHPStan's lexical scopes. Scope exposes the
 * parent scope but not the anonymous function's AST node. Annotating descendants
 * structurally supplies that node's stable source position, without inferring
 * ancestry from byte ranges or persisting reflection/object identities.
 *
 * @phpstan-import-type AnonymousScope from GlobalBindingFact
 * @phpstan-import-type FunctionScope from GlobalBindingFact
 */
final class GlobalBindingScope
{
    private const ANCESTORS = 'accessingGlobals.anonymousScopes';

    public static function annotate(Node $node): void
    {
        if (get_class($node) === FileNode::class) {
            foreach ($node->getNodes() as $statement) {
                self::annotateAnonymous($statement, []);
            }
            return;
        }
        if (get_class($node) === InClassMethodNode::class || get_class($node) === InFunctionNode::class) {
            self::annotateAnonymous($node->getOriginalNode(), []);
            return;
        }
    }

    /** @param list<AnonymousScope> $ancestors */
    private static function annotateAnonymous(Node $node, array $ancestors): void
    {
        if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
            $params = [];
            foreach ($node->params as $param) {
                if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                    $params[] = $param->var->name;
                }
            }
            $uses = [];
            if ($node instanceof Node\Expr\Closure) {
                foreach ($node->uses as $use) {
                    if (is_string($use->var->name)) {
                        $uses[] = ['name' => $use->var->name, 'byRef' => $use->byRef];
                    }
                }
            }
            $ancestors[] = [
                'position' => $node->getStartFilePos(),
                'type' => $node instanceof Node\Expr\Closure ? 'closure' : 'arrow',
                'params' => $params,
                'uses' => $uses,
            ];
        }

        foreach ($node->getSubNodeNames() as $name) {
            $children = $node->$name;
            foreach (is_array($children) ? $children : [$children] as $child) {
                if (!$child instanceof Node) {
                    continue;
                }
                // Named functions and methods do not inherit lexical bindings.
                if ($child instanceof Node\Stmt\Function_ || $child instanceof Node\Stmt\ClassLike) {
                    continue;
                }
                $child->setAttribute(self::ANCESTORS, $ancestors);
                self::annotateAnonymous($child, $ancestors);
            }
        }
    }

    /** @return list<FunctionScope> From lexical root to innermost scope. */
    public static function describe(Node $node, Scope $scope): array
    {
        /** @var list<AnonymousScope> $ancestors */
        $ancestors = $node->getAttribute(self::ANCESTORS, []);
        return self::describeScope($scope, $ancestors);
    }

    /**
     * @param list<AnonymousScope> $ancestors
     * @return list<FunctionScope>
     */
    private static function describeScope(Scope $scope, array $ancestors): array
    {
        if ($scope->isInAnonymousFunction() && $ancestors !== []) {
            $anonymous = array_pop($ancestors);
            // Arrow scopes retain the enclosing scope's parentScope, rather
            // than linking to the enclosing scope itself. Their structural
            // ancestor supplies that missing level; named reflection/context
            // and the enclosing closure's parentScope are unchanged.
            $parentScope = $anonymous['type'] === 'arrow' ? $scope : $scope->getParentScope();
            $parents = $parentScope === null ? [] : self::describeScope($parentScope, $ancestors);
            $parent = $parents === [] ? null : $parents[array_key_last($parents)]['id'];
            $parents[] = [
                'id' => self::key($scope, [$parent, $anonymous['type'], $anonymous['position']]),
                'parent' => $parent,
                'type' => $anonymous['type'],
                'params' => $anonymous['params'],
                'uses' => $anonymous['uses'],
            ];
            return $parents;
        }

        $function = $scope->getFunction();
        if ($function === null) {
            return [];
        }
        return [[
            'id' => self::key($scope, [
                $function instanceof MethodReflection ? $function->getDeclaringClass()->getName() : null,
                $function->getName(),
            ]),
            'parent' => null,
            'type' => $function instanceof MethodReflection ? 'method' : 'function',
            'params' => [],
            'uses' => [],
        ]];
    }

    /** @param array<mixed> $identity */
    private static function key(Scope $scope, array $identity): string
    {
        return hash('sha256', json_encode([
            $scope->getFile(),
            $scope->getFileDescription(),
            $scope->getClassReflection()?->getName(),
            $identity,
        ], JSON_THROW_ON_ERROR));
    }
}
