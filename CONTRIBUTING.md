# Contributing

Thanks for helping improve Watchpost. For a substantial change, open an issue first to agree on the behavior and scope. Keep pull requests focused and explain the user-visible change, security impact, and any configuration changes.

## Development

1. Follow the local setup in [README.md](README.md).
2. Keep credentials, real security events, mailbox exports, and production data out of commits. Use synthetic values such as `victim@example.com` and documentation IP addresses.
3. Add or update focused feature tests for behavior changes.
4. Before opening a pull request, run:

   ```sh
   php artisan test --compact
   vendor/bin/pint --format agent
   composer validate --strict
   ```

5. Describe the change, verification performed, and any known limitations in the pull request.

Please do not include secrets or personal event data in issue reports. Use the private reporting process in [SECURITY.md](SECURITY.md) for vulnerabilities.
