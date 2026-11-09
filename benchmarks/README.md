# Global binding benchmark

Install dependencies with `composer install`, then run:

```sh
for n in 1000 2000 4000 8000 16000; do
    php benchmarks/global-bindings.php "$n"
done
```

The script invokes the rule's flat fact-processing pass with N separate global declarations, N unrelated local mutations, and one mutation of a global. It checks the diagnostic's message, identifier, and line. It excludes collection, normal assignment alias facts, nested scopes, and PHPStan traversal. The peak memory includes the generated facts and the PHP process. The input sizes demonstrate scaling, not typical function sizes.

In a flat function with G names, K declaration statements, and M mutations, the prior declaration copies and membership scans took O(GK + GM) time in the worst case. The sets use expected O(G + M) time and O(G) binding space for those operations, assuming bounded name length. Event collection, sorting, nested scopes, alias slots, and diagnostics have separate costs.

## Example comparison

On PHP 8.5.4 CLI NTS with CLI opcache disabled, PHPStan 2.3.4, Linux x86-64, and four virtual CPUs on an Intel Core Processor (Haswell, no TSX), five alternating runs at N=16000 produced these seconds:

| Base | Set implementation |
| --- | --- |
| 2.601783 | 0.102998 |
| 2.642019 | 0.094587 |
| 2.333790 | 0.098373 |
| 2.388889 | 0.103343 |
| 2.441483 | 0.107454 |

The median was 2.441483 seconds before and 0.102998 seconds after. Each run returned the expected diagnostic and reported a 30 MiB peak. A separate instrumented baseline attributed 0.915 seconds to declaration copies and 1.563 seconds to membership scans out of 2.606 seconds. Instrumentation changes timing and was not used in the comparison. Hardware profiling was unavailable because `perf_event_paranoid` was set to 4.

A separate end-to-end PHPStan check on the 80-declaration regression fixture returned the same 82 access diagnostics and two modification diagnostics before and after. Five alternating `--debug` runs took 1.30 to 1.61 seconds before and 1.34 to 1.48 seconds after. The fixture includes two additional duplicate declarations. This small synthetic fixture does not establish a real-project speedup.

To compare a checkout of the prior rule using the same dependencies, set `BENCH_RULE` to its `src/Rules/NeverModifyGloballyDeclaredVariablesRule.php` path. The benchmark loads that rule before invoking the pass.
