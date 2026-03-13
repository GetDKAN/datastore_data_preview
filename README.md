# Datastore Data Preview

Paginated, sortable HTML table previews of DKAN datastore resources. Provides a block plugin, render element, admin page, and programmatic builder service.

## Features

- **Dataset node integration** — automatically attaches preview tables to dataset node pages for all tabular distributions
- **Edit form links** — adds "Preview data" links next to each distribution's downloadURL in the dataset edit form
- **AJAX navigation** — sort, pager, and page-size interactions update via fetch without full page reloads; updates the browser URL via History API
- **Extra field** — registers `data_preview` as a display extra field on the `data` content type, configurable via Manage Display UI
- **Block plugin** — place and configure preview tables in any block region
- **Render element** — `#type => 'data_preview'` for use in custom render arrays
- **Pluggable data sources** — query datastore tables directly (database) or via DKAN's REST API (local or remote)

## Requirements

- Drupal `^10.2 || ^11`
- DKAN modules: `common`, `datastore`, `metastore`

## Installation

```bash
composer require getdkan/datastore_data_preview
drush en datastore_data_preview
```

## Usage

### Dataset Display

Data preview tables appear automatically on dataset node pages when the `data_preview` extra field is enabled. Only distributions with importable file types (CSV, TSV, etc.) are shown.

Control visibility and weight at **Admin > Structure > Content types > Data > Manage Display**. The extra field appears as "Data Preview" in the field list.

In the dataset edit form, each tabular distribution shows a "Preview data" link next to its downloadURL, linking to the admin preview page.

### Block

Place "Datastore Data Preview" via the block layout UI. Configure the resource ID, data source, columns, page size, and sort options in the block settings.

### Render Element

```php
$build['preview'] = [
  '#type' => 'data_preview',
  '#resource_id' => 'abc123__1',
  '#data_source' => 'database',
  '#columns' => ['name', 'date', 'amount'],
  '#default_page_size' => 25,
  '#default_sort' => 'date',
  '#default_sort_direction' => 'desc',
];
```

Properties: `#resource_id`, `#data_source` (`database`|`api`), `#data_source_instance` (optional `DataSourceInterface`), `#columns`, `#default_page_size`, `#default_sort`, `#default_sort_direction`, `#conditions`, `#api_base_url`.

### Admin Page

`/admin/dkan/data-preview/{resource_id}` — requires `administer dkan` permission.

### Programmatic (Builder Service)

```php
$builder = \Drupal::service('dkan.data_preview.builder');
$dataSource = \Drupal::service('dkan.data_preview.datasource.database');
$build = $builder->build($dataSource, 'abc123__1', [
  'columns' => ['name', 'date'],
  'default_page_size' => 50,
  'default_sort' => 'date',
]);
```

Builder options: `columns`, `page_sizes`, `default_page_size`, `default_sort`, `default_sort_direction`, `conditions`, `pager_element`, `query_prefix`.

## Architecture

### Data Source Pattern

All data sources implement `DataSourceInterface::fetchData()` and return a `DataSourceResult` (rows, totalCount, schema).

| Service ID | Class | Description |
|---|---|---|
| `dkan.data_preview.datasource.database` | `DatabaseDataSource` | Queries datastore DB tables via DKAN's `DatastoreService` |
| `dkan.data_preview.datasource.api` | `ApiDataSource` | HTTP POST to `/api/1/datastore/query/{id}` (supports external DKAN sites via `setBaseUrl()`) |
| — | `ArrayDataSource` | In-memory array data; instantiate directly, not a service |

### Services

| Service ID | Class | Purpose |
|---|---|---|
| `dkan.data_preview.builder` | `DataPreviewBuilder` | Builds render arrays with table, pager, page-size form |
| `dkan.data_preview.resource_resolver` | `ResourceIdResolver` | Converts distribution UUID/node → `{identifier}__{version}` |

### Resource ID Formats

- **Database source**: `{identifier}__{version}` (hash + version derived from the distribution's download URL)
- **API source**: distribution UUID
- `ResourceIdResolver` converts between formats

## Block Configuration

| Field | Type | Description |
|---|---|---|
| Resource ID | textfield | `identifier__version` (database) or distribution UUID (API) |
| Data Source | select | `database` (direct DB) or `api` (HTTP) |
| API Base URL | url | External DKAN site URL; empty = current site. Only for API source. |
| Columns | textfield | Comma-separated column machine names; empty = all |
| Page Size | select | 10, 25, 50, 100 (default: 25) |
| Sort Column | textfield | Column machine name for initial sort; empty = default order |
| Sort Direction | select | `asc` or `desc` (default: `asc`) |

## Development

### Testing

```bash
cd datastore_data_preview
composer install
vendor/bin/phpunit
```

### Linting

```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice src/
```
