# Roadmap & Phase 2 Summary

## Phase 2 Summary (Completed)

| Area | Improvement | Status |
|------|-------------|--------|
| Resilience | Retries with backoff (none/fixed/exponential + jitter) | ✅ |
| Execution Control | Per-job soft timeouts + global default | ✅ |
| Concurrency | Atomic file lock (flock + TTL) for runType=single | ✅ |
| Observability | `cronjob.*` event system with structured payloads | ✅ |
| Metrics | Pluggable exporter (+ InMemoryExporter) | ✅ |
| Logging | Configurable output truncation (`maxOutputLength`) | ✅ |
| Performance | Job name memoization (hash) | ✅ |
| Maintainability | `FrequenciesTrait` refactor (helper `applyParts`) | ✅ |
| Error Hierarchy | Unified under `CronJobException` / CI RuntimeException | ✅ |
| Documentation | New sections (config, events, metrics) | ✅ |
| Tests | Expanded coverage (81 tests) | ✅ |

### Key Benefits
- Safer executions (prevents unintended overlap for single-run jobs).
- Detailed diagnostics: per-attempt duration + events + structured metrics.
- Foundation ready for external exporters (Prometheus, etc.).
- Cleaner code, reduced duplication in frequency DSL.
- Public API unchanged; internal improvements transparent to existing users.

---

## Phase 3 (Proposals / Next Steps)

| Priority | Item | Goal | Notes |
|----------|------|------|-------|
| High | Native Prometheus exporter | Scrape-ready metrics | Conditional if client library installed (composer suggest). |
| (Done) | Graceful shutdown / signals | Ordered stop & lock release | Implemented: `cronjob.shutdown` event. |
| (Done) | Orphan lock reclaim | TTL + dead PID + initial heartbeat | Implemented in `StatusTrait`. |
| Medium | Configurable alerting | Failure streak thresholds | Listener + config options. |
| Medium | Duration percentiles | p95/p99 on flush | Lightweight calculation in exporter. |
| Medium | Metrics CLI command | `cronjob:metrics` snapshot | Uses InMemoryExporter or aggregator. |
| Low | Optional parallelization | Run independent jobs in pool | Evaluate cost/benefit; respect dependencies. |
| Low | Config caching | Fewer repeated reads | Micro optimization. |
| Low | Deprecation cleanup | Remove PHPUnit deprecation | Keep build clean. |
| Done | Lock inspection improvements | `--silent` and `--json` flags | Clear machine & human modes. |

### Technical Proposal Details

#### 1. Prometheus Exporter
Implement `PrometheusExporter` registering counters/histograms; optional HTTP endpoint returning `text/plain; version=0.0.4`. No hard dependency—skip if library absent.

#### 2. Graceful Shutdown (Done)
Signal handlers (SIGTERM/SIGINT/SIGQUIT when `ext-pcntl` + CLI) set stop flag; `cronjob.shutdown` fires after loop ends.

#### 3. Orphan Lock Reclaim (Done)
JSON metadata (`pid`, `time`, `heartbeat`, `stolen`) + expiration via TTL, dead PID, (future) heartbeat age. Future: periodic heartbeat refresh.

#### 4. Failure Streak Alerting
Maintain rolling counter per job; on threshold emit `cronjob.alert.failureStreak` event (configurable threshold).

#### 5. Percentiles
On flush compute p50, p90, p95, p99 from attempt durations (no external libs needed).

#### 6. Metrics CLI
`php spark cronjob:metrics` (planned). `cronjob:locks` already covers lock introspection.

#### 7. Optional Parallelization
Only for jobs without dependencies and not single-run. Introduce `maxParallel` in config. Consider `pcntl_fork` or process pool; evaluate Windows compatibility.

#### 8. Config Cache
Memoize config after first access inside `JobRunner` and reuse across traits/services.

#### 9. Deprecation Cleanup
Update dependencies or adapt test bootstrap to silence legacy deprecation notice.

---

## Adoption & Migration Guidance
No special steps required when upgrading from the immediately previous release: new features are opt-in (retries, timeouts, metrics exporter, events toggleable).

### Recommended to Enable
- Keep `enableEvents = true` (default) and add lightweight alert listeners.
- Configure a `defaultTimeout` to prevent hung jobs.
- Turn on truncation (`maxOutputLength`) if logs or DB rows grow large.

---

## Performance Considerations
- Events + exporter add marginal overhead (<1ms typical per attempt without heavy listeners).
- Truncation prevents unbounded growth of stored output.
- Atomic locking cost is minimal (open/write/close cycle).

---

## Additional Partial Phase 3 Additions
- Troubleshooting section added in `advanced.md` (locks, timeouts, retries).
- `cronjob:locks` command to list lock state (+ `--silent`, `--json` modes).
- Legacy method `getIsRunningInfo()` marked `@deprecated` (only used in `cronjob:finish`).

## Feedback
Open issues / PRs with suggestions: new backoff strategies, additional metrics, external integrations.

---

© MIT License
