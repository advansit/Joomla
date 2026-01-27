# J2Commerce Import/Export Component

Import and export J2Commerce products with all related Joomla data.

## Features

### Full Product Export/Import
Export and import complete products including:
- **Joomla Article** - Title, description, images, SEO metadata
- **Category** - With automatic creation if missing
- **J2Store Product** - Product type, tax profile, manufacturer
- **Variants** - SKU, price, weight, dimensions, stock
- **Tier Prices** - Quantity-based pricing, customer groups
- **Product Images** - Main, thumbnail, additional images
- **Options** - Product options with values and price modifiers
- **Filters** - Filter groups and values
- **Tags** - Article tags
- **Custom Fields** - Joomla custom field values
- **Menu Items** - Optional, with configurable access levels
- **Metafields** - J2Store metafield data

### Import Options
- Update existing products or create new only
- Create menu items automatically
- Configure menu type and access level
- Set default category for new products

### Supported Formats
- JSON (recommended for full export)
- CSV
- XML

## Requirements

- Joomla 4.x, 5.x or 6.x
- PHP 8.1 or higher
- J2Commerce/J2Store 4.x or higher

## Installation

1. Download `com_j2commerce_importexport_1.0.0.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Access via **Components → J2Commerce Import/Export**

## Usage

### Full Product Export
1. Select **Products (Complete)** as export type
2. Choose JSON format
3. Click **Export Data**

The export includes all product data in a single JSON file that can be re-imported.

### Full Product Import
1. Select **Products (Complete)** as import type
2. Upload the JSON file
3. Configure options:
   - **Update existing products** - Update if SKU matches
   - **Create menu items** - Auto-create menu entries
   - **Menu type** - Target menu for new items
   - **Access level** - Visibility of menu items
4. Click **Preview** to verify data
5. Click **Start Import**

### Simple Export/Import
For bulk updates of specific data:
- **Products (J2Store only)** - Basic product data
- **Variants** - SKU, prices, stock
- **Prices** - Tier pricing only
- **Categories** - Category structure

## JSON Export Structure

```json
[
  {
    "title": "Product Name",
    "alias": "product-name",
    "introtext": "<p>Short description</p>",
    "fulltext": "<p>Full description</p>",
    "article_state": 1,
    "catid": 8,
    "category_title": "Products",
    "category_path": "products",
    "access": 1,
    "language": "*",
    "metadesc": "SEO description",
    "product_type": "simple",
    "enabled": 1,
    "taxprofile_id": 1,
    "variants": [
      {
        "sku": "PROD-001",
        "price": 99.00,
        "quantity": 50,
        "weight": 1.5,
        "tier_prices": []
      }
    ],
    "j2store_images": {
      "main_image": "images/products/product.jpg",
      "main_image_alt": "Product Name"
    },
    "tags": [
      {"title": "New", "alias": "new"}
    ],
    "menu_item": {
      "title": "Product Name",
      "menutype": "mainmenu",
      "published": 1,
      "access": 1
    }
  }
]
```

## Configuration

**Components → J2Commerce Import/Export → Options**

| Setting | Default | Description |
|---------|---------|-------------|
| Batch Size | 100 | Records per batch (10-1000) |
| Default Format | JSON | Export format |
| CSV Delimiter | ; | Field separator for CSV |
| CSV Enclosure | " | Text qualifier for CSV |

## Development

### Structure
```
com_j2commerce_importexport/
├── administrator/
│   ├── components/com_j2commerce_importexport/
│   │   ├── services/provider.php
│   │   ├── src/
│   │   │   ├── Controller/
│   │   │   ├── Model/
│   │   │   │   ├── ExportModel.php
│   │   │   │   └── ImportModel.php
│   │   │   └── View/Dashboard/
│   │   └── tmpl/dashboard/
│   └── language/ (en-GB, de-CH, fr-FR)
├── tests/
├── com_j2commerce_importexport.xml
└── VERSION
```

### Running Tests

```bash
cd tests
docker compose up -d
sleep 60
./run-tests.sh all
docker compose down -v
```

## License

Proprietary. Copyright (C) 2025 Advans IT Solutions GmbH.

## Support

**Advans IT Solutions GmbH**  
https://advans.ch
