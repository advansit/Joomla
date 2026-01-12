# J2Commerce Product Compare Plugin
Help customers make informed purchase decisions with side-by-side product comparison.

## Description

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
1. Download `plg_j2commerce_productcompare.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Enable via **System → Plugins**
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
├── language/ (en-GB, de-CH, fr-FR)
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

## Troubleshooting

### Compare Button Not Showing
**Problem:** Button missing on product pages  
**Solution:**
1. Verify plugin is enabled in **System → Plugins**
2. Check "Show in Product List" and "Show in Product Detail" settings
3. Verify J2Commerce template includes plugin positions
4. Clear Joomla cache

### Comparison Bar Not Appearing
**Problem:** Products added but bar not visible  
**Solution:**
1. Check browser console for JavaScript errors
2. Verify media files loaded (CSS/JS)
3. Check for CSS conflicts with template
4. Ensure session storage enabled in browser

### Modal Not Opening
**Problem:** Click "View Comparison" but nothing happens  
**Solution:**
1. Check browser console for errors
2. Verify jQuery/Bootstrap loaded
3. Test in different browser
4. Disable conflicting JavaScript plugins

### Products Not Persisting
**Problem:** Comparison list clears on page reload  
**Solution:**
1. Verify PHP sessions working
2. Check session timeout settings
3. Test with cookies enabled
4. Verify AJAX endpoints responding

### Maximum Products Not Enforced
**Problem:** Can add more than configured maximum  
**Solution:**
1. Clear browser cache
2. Verify plugin configuration saved
3. Check JavaScript console for errors
4. Re-save plugin settings

## Customization

### Styling the Compare Button
```css
/* Custom button styling */
.j2commerce-compare-btn {
    background: #007bff;
    color: white;
    border-radius: 4px;
    padding: 8px 16px;
}

.j2commerce-compare-btn:hover {
    background: #0056b3;
}
```

### Styling the Comparison Bar
```css
/* Comparison bar at bottom */
.j2commerce-compare-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #f8f9fa;
    border-top: 2px solid #dee2e6;
    padding: 15px;
    z-index: 1000;
}
```

### Styling the Modal
```css
/* Comparison modal */
.j2commerce-compare-modal {
    max-width: 90%;
    width: 1200px;
}

.j2commerce-compare-table {
    width: 100%;
    border-collapse: collapse;
}

.j2commerce-compare-table th,
.j2commerce-compare-table td {
    padding: 12px;
    border: 1px solid #dee2e6;
}
```

### Custom Button Text
Plugin settings allow custom button text per language:
- English: "Compare Products"
- German: "Produkte vergleichen"
- French: "Comparer les produits"

## Configuration Examples

### Minimal Comparison (2 Products)
```
Show in Product List: Yes
Show in Product Detail: Yes
Maximum Products: 2
Button Text: Compare
Button CSS Class: btn btn-sm btn-outline-primary
```

### Standard Comparison (4 Products)
```
Show in Product List: Yes
Show in Product Detail: Yes
Maximum Products: 4
Button Text: Compare
Button CSS Class: btn btn-secondary
```

### Extended Comparison (6 Products)
```
Show in Product List: Yes
Show in Product Detail: Yes
Maximum Products: 6
Button Text: Add to Compare
Button CSS Class: btn btn-primary
```

## Compared Attributes

The comparison table displays:
- Product image
- Product name
- SKU
- Price
- Stock status
- Short description
- Key specifications
- Add to cart button

Attributes are configurable via J2Commerce product settings.

## Browser Compatibility

### Tested Browsers
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android 10+)

### Required Features
- JavaScript enabled
- Session storage
- CSS3 support
- AJAX/Fetch API

## Performance Considerations

- **Session storage:** ~5KB per comparison list
- **AJAX calls:** 1 per add/remove action
- **Page load impact:** ~50KB (CSS + JS)
- **Database queries:** 0 additional (session-based)

## Multi-Language Support

This extension supports the following languages:
- **English (en-GB)** - Default
- **German (de-CH)** - Swiss German
- **French (fr-FR)** - French

Users can add additional language files by creating new language folders following Joomla's language structure:
```
language/{language-tag}/plg_j2commerce_productcompare.ini
language/{language-tag}/plg_j2commerce_productcompare.sys.ini
```

## Accessibility

- Keyboard navigation supported
- ARIA labels for screen readers
- Focus indicators on interactive elements
- Semantic HTML structure
- High contrast mode compatible

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
