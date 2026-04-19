# OSMap J2Commerce Plugin

Adds all enabled J2Commerce products to the OSMap sitemap automatically.

## Description

OSMap does not index J2Commerce product pages out of the box. J2Commerce uses `published=-2` menu items for SEF URL generation — a status that OSMap's built-in Joomla plugin skips by design.

This plugin bridges the gap: it registers with OSMap for `com_j2store` menu items (the shop overview) and emits one sitemap node per enabled product, using the same routing mechanism as J2Commerce itself. The result is correct SEF URLs (`/de/shop/product-alias`) in the sitemap without any manual maintenance.

## Requirements

- Joomla 5.x or 6.x
- J2Commerce (formerly J2Store) 4.x or later
- [OSMap Free](https://extensions.joomla.org/extension/osmap/) 5.x or later

## Installation

1. Download `plg_osmap_j2commerce.zip` from the [latest release](https://github.com/advansit/Joomla/releases)
2. Install via Joomla Backend → Extensions → Install
3. Enable the plugin: Extensions → Plugins → search "OSMap J2Commerce" → enable
4. In OSMap (Components → OSMap → Sitemaps), ensure the menu containing your shop item is selected
5. Save the sitemap — products appear automatically

## Configuration

In the plugin parameters (Extensions → Plugins → OSMap J2Commerce):

| Parameter | Default | Description |
|---|---|---|
| Priority | `0.8` | Sitemap priority for product pages (overridden by OSMap sitemap settings) |
| Change Frequency | `weekly` | How often search engines should re-crawl product pages |

OSMap's per-menu priority and change frequency settings take precedence over the plugin defaults.

## How It Works

OSMap calls `getTree()` for every menu item belonging to `com_j2store`. The plugin queries all `published=-2` child menu items — these are the hidden routing entries J2Commerce creates for each product. For each product, a sitemap node is emitted with:

- `link`: `index.php?option=com_content&view=article&id=X&Itemid=Y` (product's own menu item as Itemid)
- Joomla's SEF router resolves this to `/de/shop/product-alias`

New products are picked up automatically on the next sitemap request — no manual updates needed.

## Changelog

### 1.0.0 (2026-04)
- Initial release
