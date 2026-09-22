<?php

namespace App\Helpers;

use App\Models\MsCoil;
use App\Models\PartBatchCoil;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CoilNoHelper
{
    public static function get(?string $partId, ?string $batchNo): ?string
    {
        if ($batchNo === null || trim($batchNo) === '') {
            return null;
        }

        $rows = collect([[
            'part_id' => $partId,
            'batch_no' => $batchNo,
        ]]);

        $key = $partId !== null && trim($partId) !== ''
            ? self::key($partId, $batchNo)
            : self::batchKey($batchNo);

        return self::lookupByPartBatch($rows)->get($key);
    }

    public static function lookupByPartBatch(Collection $rows): Collection
    {
        $rows = $rows
            ->map(function ($row) {
                return [
                    'part_id' => data_get($row, 'part_id') ?? data_get($row, 'PartID'),
                    'batch_no' => data_get($row, 'batch_no') ?? data_get($row, 'BatchNo'),
                ];
            })
            ->filter(fn ($row) => $row['batch_no'] !== null && trim((string) $row['batch_no']) !== '');

        if ($rows->isEmpty()) {
            return collect();
        }

        $batchNos = $rows->pluck('batch_no')->unique()->values();
        $partBatchCoil = new PartBatchCoil();
        $partBatchCoilTable = $partBatchCoil->getTable();

        if (!Schema::connection($partBatchCoil->getConnectionName())->hasTable($partBatchCoilTable)) {
            return collect();
        }

        $partBatchCoilColumns = Schema::connection($partBatchCoil->getConnectionName())->getColumnListing($partBatchCoilTable);
        if (!in_array('BatchNo', $partBatchCoilColumns, true)) {
            return collect();
        }

        $query = PartBatchCoil::query()
            ->from($partBatchCoilTable . ' as pbc')
            ->whereIn('pbc.BatchNo', $batchNos->all());

        $partIds = $rows->pluck('part_id')->filter(fn ($partId) => $partId !== null && trim((string) $partId) !== '')->unique()->values();
        if ($partIds->isNotEmpty() && in_array('PartID', $partBatchCoilColumns, true)) {
            $query->whereIn('pbc.PartID', $partIds->all());
        }

        if (in_array('CoilNo', $partBatchCoilColumns, true)) {
            $query->select('pbc.BatchNo', 'pbc.CoilNo');
            if (in_array('PartID', $partBatchCoilColumns, true)) {
                $query->addSelect('pbc.PartID');
            }
        } elseif (in_array('CoilID', $partBatchCoilColumns, true)) {
            $coilNoColumn = 'pbc.CoilID';
            $msCoil = new MsCoil();
            $msCoilTable = $msCoil->getTable();

            if (Schema::connection($msCoil->getConnectionName())->hasTable($msCoilTable)) {
                $msCoilColumns = Schema::connection($msCoil->getConnectionName())->getColumnListing($msCoilTable);
                if (in_array('CoilID', $msCoilColumns, true)) {
                    $query->leftJoin($msCoilTable . ' as coil', 'coil.CoilID', '=', 'pbc.CoilID');
                    $coilNoColumn = in_array('CoilNo', $msCoilColumns, true) ? 'coil.CoilNo' : 'coil.CoilID';
                }
            }

            $query->select('pbc.BatchNo')->selectRaw("{$coilNoColumn} as CoilNo");
            if (in_array('PartID', $partBatchCoilColumns, true)) {
                $query->addSelect('pbc.PartID');
            }
        } else {
            return collect();
        }

        return $query->get()
            ->filter(fn ($row) => $row->CoilNo !== null && trim((string) $row->CoilNo) !== '')
            ->reduce(function (Collection $carry, $row) {
                $carry->put(self::batchKey($row->BatchNo), $row->CoilNo);

                if (isset($row->PartID) && $row->PartID !== null && trim((string) $row->PartID) !== '') {
                    $carry->put(self::key($row->PartID, $row->BatchNo), $row->CoilNo);
                }

                return $carry;
            }, collect());
    }

    public static function key(string $partId, string $batchNo): string
    {
        return self::normalize($partId) . '|' . self::batchKey($batchNo);
    }

    public static function batchKey(string $batchNo): string
    {
        return self::normalize($batchNo);
    }

    private static function normalize($value): string
    {
        return strtolower(trim((string) $value));
    }
}
