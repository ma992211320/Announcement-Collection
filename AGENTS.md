# AGENTS.md

## Cursor Cloud specific instructions

### Overview
This is a PHP web scraper and data management tool (招标公告自动化采集系统) for collecting public tender announcements from the Hangzhou Lin'an District Public Resources Trading website. It has a web UI (Bootstrap 5 + Chart.js) and a CLI interface.

### Services
| Service | How to start | Port |
|---------|-------------|------|
| PHP dev server | `php -S 0.0.0.0:8080` (from repo root) | 8080 |
| MySQL | `sudo mysqld --user=mysql --datadir=/var/lib/mysql &` then `sudo chmod 755 /var/run/mysqld/` | 3306 |

### Key caveats
- **MySQL socket permissions**: After starting `mysqld` manually, the `/var/run/mysqld/` directory is created with `drwx------` (mysql-only). You must run `sudo chmod 755 /var/run/mysqld/` so PHP's PDO can connect via the Unix socket.
- **installed.lock**: `index.php` redirects to `install.php` unless `installed.lock` exists in the repo root. Create it with `touch installed.lock` after running `php init_db.php`.
- **Database credentials**: Defined as constants in `config.php`. The default user is `127_0_0_4` with password `ttreaTSGC3` and database `127_0_0_4`.
- **No lint/test framework**: The project has no formal linter or test runner. Validation is done via `php -l <file>` for syntax checks and running the `test_*.php` files manually (`php test_json.php`).
- **Crawler network dependency**: The crawler hits `https://www.hzlscgfw.cn` which may be geo-restricted or slow. The web UI's "查看示例数据" (Show Example Data) button works without network access.
- **vendor/ is committed**: Dependencies (Guzzle) are pre-committed in `vendor/`. Running `composer install` is still recommended to ensure autoload is correct.

### Standard commands
See `README.md` for CLI usage (`php cli.php --help`), web setup, and project structure.
