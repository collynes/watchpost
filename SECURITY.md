# Security policy

Watchpost is under active development. Until the first tagged release, the latest `main` branch is the only supported version.

## Reporting a vulnerability

Please report suspected vulnerabilities through GitHub's **Report a vulnerability** flow in the repository's Security tab. Do not include exploit details, credentials, or personal event data in a public issue. If private reporting is unavailable, contact the maintainer privately through their GitHub profile and ask for a secure reporting channel.

Include the affected version or commit, impact, and concise steps to reproduce. The maintainer will acknowledge the report and coordinate a fix and disclosure timeline with the reporter.

## Deployment

Keep production credentials in environment variables or a secret manager. Do not expose the private `watchpost` database schema through Supabase's Data API. Place the dashboard behind HTTPS, disable debug mode, restrict database roles, and limit access to ingestion credentials.
