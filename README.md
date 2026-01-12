# Joomla Extensions Repository

Extensions for [Joomla](https://github.com/joomla/joomla-cms) and [J2Commerce](https://github.com/joomla-projects/j2commerce) developed and maintained by Advans IT Solutions GmbH.

## Repository Structure

```
Joomla/
├── j2commerce/      # J2Commerce Extensions
├── shared/          # Shared build and test scripts
└── .github/         # CI/CD workflows
```

Joomla Core extensions will be added directly to the root when available.

## Available Extensions

### J2Commerce Extensions

See the `j2commerce/` folder for all available extensions and their documentation.

**Extensions:**
- Privacy Plugin - GDPR compliance for J2Commerce
- 2FA Plugin - Two-Factor Authentication
- AcyMailing Integration
- Product Compare
- Import/Export Component
- Cleanup Component

## Testing

Each extension has automated tests that run via GitHub Actions.

Tests run automatically when files in the respective extension directory are modified. Manual execution is available via the Actions tab.

View test results: https://github.com/advansit/Joomla/actions

## Releases

Each extension has independent releases. View all releases: https://github.com/advansit/Joomla/releases

## Security

Security vulnerabilities should be reported via GitHub's private vulnerability reporting system. See `SECURITY.md` for details.

---

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.

