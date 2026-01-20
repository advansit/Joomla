# Joomla Extensions Repository

Extensions for [Joomla](https://github.com/joomla/joomla-cms) and [J2Commerce](https://github.com/joomla-projects/j2commerce) developed and maintained by Advans IT Solutions GmbH.

## Repository Structure

```
Joomla/
├── j2commerce/               # J2Commerce Extensions
├── plg_ajax_joomlaajaxforms/ # Joomla AJAX Forms Plugin
├── shared/                   # Shared build and test scripts
└── .github/                  # CI/CD workflows
```

## Available Extensions

### Joomla Core Extensions

| Extension | Description | Joomla Version |
|-----------|-------------|----------------|
| [Joomla! AJAX Forms](plg_ajax_joomlaajaxforms/) | AJAX handling for Joomla core forms (password reset, username reminder) | 4.x, 5.x, 6.x |

### J2Commerce Extensions

See the `j2commerce/` folder for all available extensions and their documentation.

| Extension | Description |
|-----------|-------------|
| Privacy Plugin | GDPR compliance for J2Commerce |
| 2FA Plugin | Two-Factor Authentication |
| AcyMailing Integration | Newsletter integration |
| Product Compare | Compare products side-by-side |
| Import/Export Component | Bulk data management |
| Cleanup Component | Database maintenance |

## Testing

Each extension has automated tests that run via GitHub Actions.

Tests run automatically when files in the respective extension directory are modified. Manual execution is available via the Actions tab.

View test results: https://github.com/advansit/Joomla/actions

## Releases

Each extension has independent releases. View all releases: https://github.com/advansit/Joomla/releases

## Repository Configuration

This repository is configured with the following GitHub settings for security, automation, and collaboration.

### Security Settings

All security features are configured at: **Settings → Security → Code security and analysis**

#### Private Vulnerability Reporting
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/security-advisories/working-with-repository-security-advisories/configuring-private-vulnerability-reporting-for-a-repository

Allows security researchers to privately report potential security vulnerabilities directly to repository maintainers. Reports are submitted via GitHub's security advisory system and remain private until disclosed.

#### Dependency Graph
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/supply-chain-security/understanding-your-software-supply-chain/about-the-dependency-graph

Automatically detects and displays all dependencies (Composer packages, GitHub Actions) used in this repository. Provides visibility into the software supply chain.

#### Dependabot Alerts
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/dependabot/dependabot-alerts/about-dependabot-alerts

Automatically notifies maintainers when dependencies have known security vulnerabilities. Alerts appear in the Security tab and via notifications.

#### Dependabot Security Updates
**Status:** ✅ Enabled (with grouped updates)  
**Documentation:** https://docs.github.com/en/code-security/dependabot/dependabot-security-updates/about-dependabot-security-updates

Automatically creates pull requests to update dependencies with known security vulnerabilities. Grouped updates combine multiple security updates into a single pull request to reduce noise.

#### Dependabot Version Updates
**Status:** ✅ Enabled  
**Configuration:** `.github/dependabot.yml`  
**Documentation:** https://docs.github.com/en/code-security/dependabot/dependabot-version-updates/about-dependabot-version-updates

Automatically creates pull requests to keep dependencies up-to-date (not just security updates). Configured to check weekly for:
- Composer dependencies in plugin directories
- GitHub Actions workflows

#### Code Scanning (CodeQL)
**Status:** ✅ Enabled (default setup)  
**Documentation:** https://docs.github.com/en/code-security/code-scanning/introduction-to-code-scanning/about-code-scanning

Automatically analyzes code for security vulnerabilities and coding errors using GitHub's CodeQL engine. Runs on every push and pull request. Detects issues like SQL injection, XSS, and other common vulnerabilities.

#### Copilot Autofix
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/code-scanning/managing-code-scanning-alerts/about-autofix-for-codeql-code-scanning

Automatically suggests fixes for code scanning alerts using AI. Provides code suggestions to remediate security vulnerabilities detected by CodeQL.

#### Secret Scanning
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning

Scans the entire repository history for accidentally committed secrets (API keys, tokens, passwords). Alerts maintainers when secrets are detected.

#### Push Protection
**Status:** ✅ Enabled  
**Documentation:** https://docs.github.com/en/code-security/secret-scanning/push-protection-for-repositories-and-organizations

Prevents commits containing secrets from being pushed to the repository. Blocks the push and alerts the developer before secrets are committed.

### Access Control

#### Code Review Limits
**Status:** ✅ Enabled - Collaborators only  
**Location:** Settings → Moderation → Code review limits  
**Documentation:** https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/managing-repository-settings/managing-pull-request-reviews-in-your-repository

Only users with write access (collaborators) can approve or request changes on pull requests. All users can still comment and create pull requests.

#### Branch Protection
**Branch:** `main`  
**Location:** Settings → Branches  
**Documentation:** https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches

Protected branch rules prevent force pushes and ensure code quality standards.

### Repository Features

**Location:** Settings → General → Features

#### Enabled Features
- ✅ **Issues** - Bug reports and feature requests from the community
- ✅ **Discussions** - Community support and Q&A
- ✅ **Projects** - Public roadmap and project management

#### Disabled Features
- ❌ **Wikis** - Documentation is maintained in README files
- ❌ **Sponsorships** - Not accepting sponsorships

### Automation

#### GitHub Actions Workflows
**Location:** `.github/workflows/`

**Test Workflows (7):**
- `joomla-ajax-forms.yml` - Tests for Joomla AJAX Forms plugin
- `j2commerce-2fa.yml` - Tests for 2FA plugin
- `j2commerce-acymailing.yml` - Tests for AcyMailing plugin
- `j2commerce-import-export.yml` - Tests for Import/Export component
- `j2commerce-privacy.yml` - Tests for Privacy plugin
- `j2commerce-product-compare.yml` - Tests for Product Compare plugin
- `j2store-cleanup.yml` - Tests for Cleanup component

Each workflow runs automatically when files in the respective extension directory are modified.

**Release Workflows (7):**
- `release-joomla-ajax-forms.yml` - Creates releases for Joomla AJAX Forms plugin (tag: `ajaxforms-v*`)
- `release-2fa.yml` - Creates releases for 2FA plugin
- `release-acymailing.yml` - Creates releases for AcyMailing plugin
- `release-cleanup.yml` - Creates releases for Cleanup component
- `release-importexport.yml` - Creates releases for Import/Export component
- `release-privacy.yml` - Creates releases for Privacy plugin
- `release-productcompare.yml` - Creates releases for Product Compare plugin

Each workflow is triggered by git tags (e.g., `ajaxforms-v1.0.0`, `acymailing-v1.0.0`) or can be run manually via GitHub Actions UI.

### Security Policy

**File:** `SECURITY.md`  
**Documentation:** https://docs.github.com/en/code-security/getting-started/adding-a-security-policy-to-your-repository

Defines how security vulnerabilities should be reported. Directs users to GitHub's private vulnerability reporting system and provides contact information for urgent issues.

---

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.

