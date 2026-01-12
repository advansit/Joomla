# Joomla Extensions Repository

**Status:** 🚧 Pre-Release / Development

Professional [Joomla](https://github.com/joomla/joomla-cms) and [J2Commerce](https://github.com/joomla-projects/j2commerce) extensions.

This repository contains extensions developed and maintained by Advans IT Solutions GmbH.

## 📁 Repository Structure

```
Joomla/
├── joomla/          # Joomla Core Extensions (coming soon)
├── j2commerce/      # J2Commerce Extensions
├── shared/          # Shared build and test scripts
└── .github/         # CI/CD workflows
```

## 📦 Available Extensions

### J2Commerce Extensions

See the **j2commerce/** folder for all available extensions and their documentation.

**Current Extensions:**
- Privacy Plugin - GDPR compliance for J2Commerce
- 2FA Plugin - Two-Factor Authentication
- AcyMailing Integration
- Product Compare
- Import/Export Component
- Cleanup Component

## Testing

Each extension has automated tests that run via GitHub Actions.

**Automatic:** Tests run automatically when files in the respective extension directory are modified.

**Manual:** Go to **Actions** → Select workflow → **Run workflow**

View test results: https://github.com/advansit/Joomla/actions

## Releases

Each extension has independent releases.

### Creating a Release

**Via Git Tag:**
```bash
# Example: Release AcyMailing v1.0.0
git tag -a acymailing-v1.0.0 -m "Initial release"
git push origin acymailing-v1.0.0
```

**Via GitHub UI:** Go to **Actions** → Select release workflow → **Run workflow**

### Tag Naming Convention

- `acymailing-v*` - J2Commerce AcyMailing Plugin
- `productcompare-v*` - J2Commerce Product Compare Plugin
- `importexport-v*` - J2Commerce Import/Export Component
- `cleanup-v*` - J2Store Cleanup Component
- `privacy-v*` - Privacy - J2Commerce Plugin
- `2fa-v*` - System - J2Commerce 2FA Plugin

### Release Workflows

**Location:** `.github/workflows/`

- `release-2fa.yml` - Creates releases for 2FA plugin
- `release-acymailing.yml` - Creates releases for AcyMailing plugin
- `release-cleanup.yml` - Creates releases for Cleanup component
- `release-importexport.yml` - Creates releases for Import/Export component
- `release-privacy.yml` - Creates releases for Privacy plugin
- `release-productcompare.yml` - Creates releases for Product Compare plugin

Each workflow builds the extension, generates changelog, and creates a GitHub release with the ZIP package.

View all releases: https://github.com/advansit/Joomla/releases

## Repository Configuration

This repository is configured with the following GitHub settings for security and collaboration.

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

