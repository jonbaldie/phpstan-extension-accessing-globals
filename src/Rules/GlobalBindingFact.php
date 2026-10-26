<?php

declare(strict_types=1);

namespace AccessingGlobals\Rules;

/**
 * Serializable collector contract. Positions order facts within a source; they
 * never determine scope membership. IDs include source and using-class context.
 *
 * @phpstan-type AnonymousScope array{position: int, type: 'closure'|'arrow', params: list<string>, uses: list<array{name: string, byRef: bool}>}
 * @phpstan-type FunctionScope array{id: string, parent: string|null, type: 'function'|'method'|'closure'|'arrow', params: list<string>, uses: list<array{name: string, byRef: bool}>}
 * @phpstan-type Event (array{kind: 'global', scopeId: string, pos: int, line: int, file: string, description: string, names: list<string|null>}|array{kind: 'alias_assign', scopeId: string, pos: int, line: int, file: string, description: string, lhs: string, slots: array<int|string, string>}|array{kind: 'mutation', scopeId: string, pos: int, line: int, file: string, description: string, targetName: string|null, throughObjectHandle: bool, outermostSlotKeys: list<int|string>})
 * @phpstan-type Fact (array{kind: 'scope', scope: FunctionScope}|Event)
 */
final class GlobalBindingFact
{
}
