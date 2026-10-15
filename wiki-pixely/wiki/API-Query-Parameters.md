> _This page is generated from [`docs/api/query-parameters.md`](https://github.com/jessyjames005/Pixely-Platform/blob/develop/docs/api/query-parameters.md). Edit it in the repository, not in the wiki: wiki edits are overwritten._

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
