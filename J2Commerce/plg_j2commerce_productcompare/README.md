# J2Commerce Product Compare Plugin

Help customers make informed purchase decisions with side-by-side product comparison.

## Product Description

The J2Commerce Product Compare Plugin adds a visual comparison feature to your store. Customers can select multiple products and view them side-by-side in an elegant modal interface. A persistent comparison bar keeps track of selected products across pages. Fully responsive design works on all devices, with configurable maximum products and customizable styling to match your store's theme.

## Features

- Side-by-side product comparison
- Comparison bar with thumbnails
- Modal comparison view
- Add from list and detail pages
- Configurable maximum products (default: 4)
- Session-based storage
- Responsive design
- Customizable button styling
- AJAX operations

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Commerce 3.x or higher

## Installation

### For Users
1. Download `plg_j2commerce_productcompare.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Enable via **System → Plugins**

### For Developers
```bash
cd dev/plg_j2commerce_productcompare
./build.sh
```

## Configuration

**System → Plugins → J2Commerce - Product Compare**

- **Show in Product List:** Display button in lists (Default: Yes)
- **Show in Product Detail:** Display on detail pages (Default: Yes)
- **Maximum Products:** Max products to compare (Default: 4, Range: 2-10)
- **Button Text:** Custom button text (Default: "Compare")
- **Button CSS Class:** CSS classes (Default: `btn btn-secondary`)

## Usage

1. Browse products
2. Click "Compare" button
3. Products added to comparison bar
4. Click "View Comparison" to see modal
5. Compare attributes side-by-side

## Testing

```bash
cd tests/integration
cp ../../plg_j2commerce_productcompare.zip test-package.zip
./run-tests.sh
```

Port: 8081

## Development

### Structure
```
plg_j2commerce_productcompare/
├── README.md
├── VERSION
├── LICENSE.txt
├── productcompare.xml
├── build.sh
├── services/provider.php
├── src/Extension/ProductCompare.php
├── language/ (en-CH, de-CH, fr-FR)
├── media/ (js, css)
└── tests/
```

### Building
```bash
./build.sh
../update-version.sh plg_j2commerce_productcompare 1.0.1
```

## Automated Testing

This plugin has automated tests that run on every push via GitHub Actions.

### Test Suites

1. **Installation** - Plugin registration, file verification
2. **Uninstall** - Clean removal from database

### Running Tests Locally

```bash
cd tests
docker compose up -d
sleep 120  # Wait for Joomla initialization
./run-tests.sh all
docker compose down -v
```

Test results are saved in `tests/test-results/`.

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
