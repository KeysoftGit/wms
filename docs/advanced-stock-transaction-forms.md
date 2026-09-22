# Advanced Stock Transaction Forms

Use this guide when building an advanced transaction page that consumes or
registers stock with `Buku_Stock` attributes such as Batch No, Serial No,
Exp Date, BIN, and LOC.

Current references:

- Advanced GR: `resources/views/purchase/gr/advanced`
- Advanced DO: `resources/views/sales/do/advanced`
- Advanced Part Usage: `resources/views/stock/usage/advanced`
- Stock helper endpoint: `app/Http/Controllers/HelperController.php`
- Stock calculator: `app/Helpers/BukuStockHelper.php`
- Cascading filter details: `docs/stock-cascading-filters.md`

## Core Rule

Advanced flow must not change the basic flow. Keep advanced routes,
controllers, and views separated from the normal module unless the shared change
is explicitly required.

## Controller Rules

Use `BukuStockHelper::calculateCurrentStock(...)` for stock validation.

Validation must match the exact selected stock identity:

- `PartID`
- `WarehouseID`
- `BatchNo`
- `SerialNo`
- `ExpDate`
- `BIN`
- `LOC`

The helper supports nullable filtering. If a selected attribute is empty/null,
pass `__NULL__` or `null` intentionally so the helper applies `whereNull(...)`.
Do not skip the filter unless the business flow intentionally means "all values".

For stock-consuming documents, insert rows sequentially:

1. Validate one detail row.
2. Insert the transaction detail row.
3. Insert the `BukuStock` row.
4. Continue to the next row.

Do not bulk validate all rows first and insert `BukuStock` later. That can allow
two rows to consume the same available stock.

For update:

1. Load the current transaction.
2. Reverse parent document quantities if the module has them, such as SO
   `QtySent`.
3. Delete current transaction `BukuStock` rows.
4. Delete current detail rows.
5. Insert the new rows sequentially.
6. Recalculate parent outstanding quantities when needed.

This prevents the transaction from blocking itself during edit and avoids stale
parent quantities.

For delete:

1. Validate the header exists.
2. Reverse parent document quantities if applicable.
3. Delete related `BukuStock`.
4. Delete details and header.
5. Recalculate parent outstanding quantities when needed.

## Serial Number Rules

Always read `WithSerialNo` from `MsPart`.

- `WithSerialNo = 1`: Serial No is required and Qty should be locked to `1` in
  the frontend.
- `WithSerialNo = 0`: Serial No must be ignored/cleared and not used for stock
  validation.

Backend must enforce the same rule. Frontend checks are only UX.

## Unit Rules

Do not assume every module allows multi-unit selection.

- DO follows the Sales Order detail unit/qty flow.
- Part Usage uses the lowest unit only. Fetch the unit with `Conversion = 1`
  and store/validate using that unit. The backend must derive this from
  `MsPartUnit`; do not trust submitted `UnitID` or `Conversion`.

If a new module has special unit behavior, document it before copying another
module's modal.

## Frontend Modal Rules

The advanced consuming modal should follow the DO modal pattern unless the
business flow requires a difference.

Required behavior:

- Use Select2 inside the modal with the modal as `dropdownParent`.
- Use `modal-stock-field` class for stock attribute Select2 fields.
- Use clear buttons for Batch No, Serial No, Exp Date, BIN, and LOC.
- Provide a "Clear Details" button that clears only stock attributes.
- Use an icon-only datepicker button next to Exp Date.
- Store selected nullable stock attributes as `null` or `__NULL__`
  intentionally.
- Use AutoNumeric for quantity inputs when the module uses decimal quantities.
- Keep row fields readonly after adding them to the table; users edit via the
  modal.

For stock selection:

- Use `route('helper.available_stock_details')`.
- Send selected stock filters except the current target.
- Use `filter_qty=0` when the UI still needs to show zero-stock historical
  attribute values.
- Use `filter_qty=1` only when the option list must show positive-stock values.

For frontend stock validation:

- Add pages may validate stock before adding the row.
- Edit pages should avoid strict frontend stock validation when existing rows
  can affect available stock. Let backend validation handle the final truth
  after deleting old `BukuStock`.
- If frontend validation runs, show selected attributes in the error message,
  but do not show available stock quantity unless explicitly needed.

## Null Handling

There are three states:

- Not selected: parameter is not sent, meaning do not filter by that attribute.
- Selected normal value: send the value.
- Selected empty value: send `__NULL__`, meaning filter with `whereNull(...)`.

Do not collapse "not selected" and "selected empty" unless the feature does not
need to distinguish them. Stock validation usually needs exact identity, so
empty selected attributes should be passed as `__NULL__`.

## Error Messages

Stock errors should identify the selected stock identity:

- Part
- Warehouse
- Batch No
- Serial No
- Exp Date
- BIN
- LOC

Do not only show "stock not enough". The user needs to know which selected row
failed.

## Checklist Before Finishing

- Advanced route is separated from the basic route.
- Advanced controller is separated from the basic controller.
- Basic flow is untouched unless explicitly requested.
- Control panel guard is applied when the feature is gated.
- Backend stock validation uses `BukuStockHelper`.
- Update deletes current `BukuStock` before validating inserted replacement
  rows.
- Serial-controlled parts require Serial No.
- Non-serial parts ignore Serial No.
- Unit behavior matches the module.
- Frontend modal clears state when opened for a new row.
- Edit row opens modal with the existing row values.
- Rows are readonly after being added.
- Syntax checks pass for changed PHP and Blade files.
