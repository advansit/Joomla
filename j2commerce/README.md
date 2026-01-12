# J2Commerce Extensions

Professional extensions for [J2Commerce](https://github.com/joomla-projects/j2commerce) e-commerce platform.

## 📦 Available Extensions

### Plugins

#### Privacy - J2Commerce (`plg_privacy_j2commerce`)
**Type:** Privacy Plugin  
**Description:** GDPR/DSGVO compliance solution with automated data retention management and lifetime license handling.

[📖 Documentation](plg_privacy_j2commerce/README.md)

---

#### System - J2Commerce 2FA (`plg_system_j2commerce_2fa`)
**Type:** System Plugin  
**Description:** Two-Factor Authentication integration for J2Commerce.

---

#### J2Commerce - AcyMailing (`plg_j2commerce_acymailing`)
**Type:** J2Commerce Plugin  
**Description:** Integration between J2Commerce and AcyMailing newsletter system.

---

#### J2Commerce - Product Compare (`plg_j2commerce_productcompare`)
**Type:** J2Commerce Plugin  
**Description:** Product comparison functionality for J2Commerce stores.

---

### Components

#### J2Commerce Import/Export (`com_j2commerce_importexport`)
**Type:** Component  
**Description:** Import and export functionality for J2Commerce products and orders.

---

#### J2Store Cleanup (`com_j2store_cleanup`)
**Type:** Component  
**Description:** Database cleanup and maintenance tools for J2Store.

---

## 🧪 Testing

All extensions have automated test suites that run via GitHub Actions.

**Test Infrastructure:**
- Shared test scripts in `../shared/tests/`
- Docker-based test environments
- Automated CI/CD pipelines

**View Test Results:** https://github.com/advansit/Joomla/actions

---

## 🔧 Development

### Building Extensions

Each extension uses shared build scripts:

```bash
cd plg_privacy_j2commerce
./build.sh
```

### Running Tests

```bash
cd plg_privacy_j2commerce/tests
docker compose up -d
./run-tests.sh all
docker compose down
```

---

**Advans IT Solutions GmbH**  
https://advans.ch

Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
