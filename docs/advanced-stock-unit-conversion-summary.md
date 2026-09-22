# Advanced Stock Unit Conversion Summary

## File Changed

- `resources/views/stock/card/advanced.blade.php`
- `app/Http/Controllers/Stock/StockCardController.php`

## Summary

Added a Unit selector to the Advanced Stock Monitoring filter area. The selector loads units based on the currently selected Part, then sends the selected Unit to the backend datatable request so stock movement quantities and summaries are converted server-side.

## Behavior

- The Unit selector is disabled until a Part is selected.
- Unit options are loaded from `misc.partunit2` using the selected `part_id`.
- When a Unit is selected, the datatable is refetched with `unit_id`.
- The backend fetches conversion from `MsPartUnit` using `part_id` and `unit_id`.
- Backend row `Qty` values are returned as:

```text
Qty / conversion
```

- Backend summary values are calculated from the converted row quantities:
  - Stock In
  - Stock Out
  - Final Stock
- If no Unit is selected, conversion defaults to `1` and backend values remain unchanged.
- Changing or clearing the Part also clears the selected Unit and resets conversion handling.

## Notes

- The frontend no longer converts only Final Stock.
- The datatable request now includes `unit_id`.
- The backend remains the source of truth for converted stock values.
