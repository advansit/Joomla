# J2Commerce Import/Export Component

![Pre-Release](https://img.shields.io/badge/status-pre--release-orange)

Professional data import and export solution for [J2Commerce](https://github.com/joomla-projects/j2commerce) stores.

⚠️ **Pre-Release Notice:** This extension is currently in pre-release status. While fully functional and tested, it has not yet been deployed in production environments. Use in production at your own discretion.

## Product Description

The J2Commerce Import/Export Component streamlines bulk data management for your online store. Import thousands of products, update prices across your catalog, or export data for analysis - all with a user-friendly interface and reliable batch processing. Supports CSV, XML, and JSON formats with preview functionality to ensure data accuracy before import.

## Features

- Import/Export products, categories, prices, variants
- Multiple formats (CSV, XML, JSON)
- Batch processing
- Data validation
- Preview before import
- Configurable delimiters
- Error handling

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Commerce 3.x or higher
- Sufficient memory for large imports

## Installation

### For Users
1. Download `com_j2commerce_importexport.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Access via **Components → J2Commerce Import/Export**

### For Developers
```bash
cd dev/com_j2commerce_importexport
./build.sh
```

## Configuration

**Components → J2Commerce Import/Export → Options**

- **Batch Size:** Records per batch (Default: 100, Range: 10-1000)
- **Default Format:** CSV, XML, or JSON (Default: CSV)
- **CSV Delimiter:** Comma, Semicolon, or Tab (Default: Comma)
- **CSV Enclosure:** Double or single quote (Default: Double quote)

## Usage

### Exporting
1. **Components → J2Commerce Import/Export**
2. Select **Export** tab
3. Choose data type
4. Select format
5. Click **Export**
6. Download file

### Importing
1. Select **Import** tab
2. Choose data type
3. Upload file
4. Click **Preview**
5. Review data
6. Click **Process Import**

## File Formats

### CSV
```csv
product_id,sku,title,price,stock
1,PROD-001,"Product Name",29.99,100
```

### XML
```xml
<products>
  <product>
    <id>1</id>
    <sku>PROD-001</sku>
    <title>Product Name</title>
  </product>
</products>
```

### JSON
```json
{
  "products": [
    {"id": 1, "sku": "PROD-001", "title": "Product Name"}
  ]
}
```

## Automated Testing

This extension has comprehensive automated tests that run on every push via GitHub Actions.

### Test Suites

1. **Installation** - Package validation, component registration, file verification
2. **Import** - CSV/XML/JSON import functionality, data validation
3. **Export** - CSV/XML/JSON export functionality, data integrity
4. **Database** - Table operations, data consistency
5. **Uninstall** - Clean removal, database cleanup

### Running Tests Locally

```bash
cd tests
docker compose up -d
sleep 120  # Wait for Joomla initialization
./run-tests.sh all
docker compose down -v
```

Test results are saved in `tests/test-results/` and committed to `tests/logs/`.

## Development

### Structure
```
com_j2commerce_importexport/
├── README.md
├── VERSION
├── LICENSE.txt
├── j2commerce_importexport.xml
├── administrator/
│   ├── services/provider.php
│   ├── src/ (Controller, Model, View)
│   ├── tmpl/
│   └── language/ (en-CH, de-CH, fr-FR)
└── tests/
```

## Troubleshooting

### Import Fails with Memory Error
**Problem:** Large imports exceed PHP memory limit  
**Solution:** Reduce batch size in component options (try 50 or 25)

### CSV Import Shows Wrong Characters
**Problem:** Encoding issues with special characters  
**Solution:** Ensure CSV file is UTF-8 encoded. Use Excel's "CSV UTF-8" export option.

### Preview Shows No Data
**Problem:** File format not recognized  
**Solution:** Verify file format matches selected type. Check delimiter settings for CSV.

### Import Completes but No Products Visible
**Problem:** Products imported but not published  
**Solution:** Check product status in J2Commerce. Import preserves status from file.

### Export File is Empty
**Problem:** No data matches export criteria  
**Solution:** Verify data exists in J2Commerce. Check filter settings.

## Performance Considerations

### Large Imports
- Use batch processing (default: 100 records)
- Monitor server resources during import
- Consider splitting very large files (>10,000 records)
- Increase PHP max_execution_time if needed

### Memory Usage
- Each batch requires ~2MB memory per 100 records
- Adjust batch size based on available memory
- Monitor memory_limit in PHP configuration

### Recommended Settings
```ini
; php.ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
```

## Data Mapping

### Product Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| product_id | int | No | Existing product ID (for updates) |
| sku | string | Yes | Unique product identifier |
| title | string | Yes | Product name |
| price | decimal | Yes | Base price |
| stock | int | No | Stock quantity |
| status | int | No | 0=unpublished, 1=published |
| category_id | int | No | Category assignment |

### Category Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| category_id | int | No | Existing category ID |
| title | string | Yes | Category name |
| parent_id | int | No | Parent category ID |
| ordering | int | No | Display order |

### Variant Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| variant_id | int | No | Existing variant ID |
| product_id | int | Yes | Parent product ID |
| sku | string | Yes | Variant SKU |
| price_modifier | decimal | No | Price adjustment |
| stock | int | No | Variant stock |

## Multi-Language Support

This extension supports the following languages:
- **English (en-GB)** - Default
- **German (de-CH)** - Swiss German
- **French (fr-FR)** - French

Users can add additional language files by creating new language folders following Joomla's language structure:
```
administrator/language/{language-tag}/com_j2commerce_importexport.ini
administrator/language/{language-tag}/com_j2commerce_importexport.sys.ini
```

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
