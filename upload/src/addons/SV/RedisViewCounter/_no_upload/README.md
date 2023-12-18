# RedisViewCounter
Moves some view counters to use Redis-based increment counters rather than scratch tables in MySQL.

Redis provides atomic get & del when pushing view counts totals into the database.

Supports:
- Threads view counters
- Attachment view counters
- Page view counters
- Article Management System's articles view counters
- XenForo Resource Manager's resources view counters
