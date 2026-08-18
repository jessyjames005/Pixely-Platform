# API Query Parameters

## Overview

Pixely Platform APIs use a common query parameter syntax for filtering,
sorting, pagination, and relationship loading.

The query parameters are interpreted by the API query layer before being
passed to the repository layer.

The main query parameters are:

- `filter`
- `sort`
- `page`
- `per_page`
- relationships

---

## Filtering

The `filter` parameter accepts one or more filter expressions.

### Basic syntax

```text
filter=<field>.<operator>.<value>
