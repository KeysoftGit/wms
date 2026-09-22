# Stock Cascading Filters

This note documents the advanced stock monitoring filter behavior used by
`resources/views/stock/card/advanced.blade.php`.

## Endpoints

- Warehouse Select2: `route('misc.warehouse2', ['stock_monitoring' => true])`
- Part Select2: `route('misc.part')`
- Cascading stock attributes: `route('helper.available_stock_details')`
- Datatable: `route('advanced_stock_monitoring.datatable')`

## Required Filters

`warehouse_id` and `part_id` are required before stock detail filters or the
datatable should load.

The frontend should:

- Keep detail filters disabled until both Warehouse and Part are selected.
- Defer the datatable initial load.
- Reload the datatable automatically after Warehouse, Part, or detail filters
  change.

## Cascading Detail Filters

The cascading filters are:

- `batch_no` maps to `BatchNo`
- `serial_no` maps to `SerialNo`
- `exp_date` maps to `ExpDate`
- `bin` maps to `BIN`
- `loc` maps to `LOC`

When fetching options for one target, send all currently selected detail filters
except the target itself. This allows users to pick filters in any order.

Example request:

```js
{
    part_id: filters.part_id,
    warehouse_id: filters.warehouse_id,
    target: 'batch_no',
    serial_no: filters.serial_no,
    exp_date: filters.exp_date,
    bin: filters.bin,
    loc: filters.loc,
    filter_qty: 0
}
```

## Null vs Not Selected

Detail filters need three states:

- Not selected: do not send the parameter, so all values are included.
- Normal selected value: send the selected value and filter with `where(...)`.
- Empty/null selected: send `__NULL__` and filter with `whereNull(...)`.

Use `__NULL__` as the frontend sentinel value for the `(Empty)` option.

Controller logic should be:

```php
if (!$request->has($input) || $request->input($input) === '') {
    return;
}

if ($request->input($input) === '__NULL__') {
    $query->whereNull($column);
    return;
}

$query->where($column, $request->input($input));
```

## Quantity Filtering

The helper endpoint supports `filter_qty`.

- `filter_qty=1`: apply `HAVING SUM(Qty) > 0`
- `filter_qty=0` or omitted: do not apply the qty having filter

Advanced stock monitoring should use `filter_qty=0`, because historical values
with a current summed qty of zero still need to be selectable for tracking.

## Part Serial Number Logic

`MiscController@getPart()` must return the serial flag:

```php
'WithSerialNo' => (int) $part->WithSerialNo,
```

The frontend should read the selected Part's Select2 metadata:

```js
const selectedPart = $('#part_id').select2('data')[0] || null;
selectedPartWithSerialNo = selectedPart && selectedPart.WithSerialNo !== undefined
    ? selectedPart.WithSerialNo
    : null;
```

Serial No behavior:

- `WithSerialNo = 1`: enable Serial No filter.
- `WithSerialNo = 0`: clear and disable Serial No filter.
- Disabled Serial No should not be sent to the datatable or helper endpoint.

The flag check should tolerate integer, string, and boolean values:

```js
function partUsesSerialNo() {
    return selectedPartWithSerialNo === true ||
        selectedPartWithSerialNo === 'true' ||
        Number(selectedPartWithSerialNo) === 1;
}
```

## Reset Behavior

Use two reset actions:

- Reset all: clears Warehouse, Part, all detail filters, and summary values.
- Clear details: clears only Batch, Serial, Exp Date, BIN, and LOC while keeping
  Warehouse and Part.
