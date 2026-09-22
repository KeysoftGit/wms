<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    /**
     * Display activity log index page
     */
    public function index()
    {
        // Load modules untuk dropdown filter
        $modules = ActivityLog::distinct()->pluck('FrmName')->filter()->values();

        return view('activity_log.index', compact('modules'));
    }

    /**
     * Datatable data for activity log
     */
    public function datatable(Request $request)
    {
        $data = ActivityLog::query();

        // Date range filter
        if ($request->has('start_date') && !empty($request->start_date)) {
            $data->whereDate('EntryTime', '>=', $request->start_date);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $data->whereDate('EntryTime', '<=', $request->end_date);
        }

        // User filter
        if ($request->has('user_id') && !empty($request->user_id)) {
            $data->where('UserID', $request->user_id);
        }

        // Module filter
        if ($request->has('module') && !empty($request->module)) {
            $data->where('FrmName', $request->module);
        }

        // Action filter
        if ($request->has('action') && !empty($request->action)) {
            $data->where('Action', $request->action);
        }

        // Reference ID filter
        if ($request->has('reference') && !empty($request->reference)) {
            $data->where('ReffID', 'like', '%' . $request->reference . '%');
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('EntryTime', function ($row) {
                return $row->EntryTime ? Carbon::parse($row->EntryTime)->format('d M Y H:i:s') : '-';
            })
            ->editColumn('ReffDate', function ($row) {
                return $row->ReffDate ? Carbon::parse($row->ReffDate)->format('d M Y') : '-';
            })
            ->editColumn('ResponseStatus', function ($row) {
                $status = $row->ResponseStatus ?? 0;
                $badge = 'secondary';

                if ($status >= 200 && $status < 300) {
                    $badge = 'success';
                } elseif ($status >= 300 && $status < 400) {
                    $badge = 'info';
                } elseif ($status >= 400 && $status < 500) {
                    $badge = 'warning';
                } elseif ($status >= 500) {
                    $badge = 'danger';
                }

                return '<span class="badge bg-' . $badge . '">' . $status . '</span>';
            })
            ->addColumn('method_badge', function ($row) {
                $method = $row->Method ?? 'UNKNOWN';
                $color = [
                    'GET' => 'info',
                    'POST' => 'success',
                    'PUT' => 'warning',
                    'PATCH' => 'warning',
                    'DELETE' => 'danger',
                ];

                $badgeColor = $color[$method] ?? 'secondary';
                return '<span class="badge bg-' . $badgeColor . '">' . $method . '</span>';
            })
            ->addColumn('payload_preview', function ($row) {
                if (!empty($row->Payload)) {
                    // Coba decode JSON untuk preview yang lebih baik
                    $decoded = json_decode($row->Payload, true);

                    if (is_array($decoded) && !empty($decoded)) {
                        // Ambil beberapa field pertama untuk preview
                        $previewData = [];
                        $count = 0;

                        foreach ($decoded as $key => $value) {
                            if ($count < 2) { // Ambil 2 field pertama saja
                                if (is_array($value)) {
                                    $previewData[] = $key . ': [...]';
                                } elseif (is_string($value) && strlen($value) > 20) {
                                    $previewData[] = $key . ': ' . substr($value, 0, 20) . '...';
                                } else {
                                    $previewData[] = $key . ': ' . (string)$value;
                                }
                                $count++;
                            } else {
                                break;
                            }
                        }

                        $preview = implode(', ', $previewData);
                        if (count($decoded) > 2) {
                            $preview .= '... (+' . (count($decoded) - 2) . ' more)';
                        }

                        return '<span class="text-muted small" title="Click to view full payload">' .
                            htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') .
                            '</span>';
                    } else {
                        // Jika bukan JSON, ambil 60 karakter pertama
                        $preview = strlen($row->Payload) > 60 ?
                            substr($row->Payload, 0, 60) . '...' :
                            $row->Payload;

                        return '<span class="text-muted small" title="Click to view full payload">' .
                            htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') .
                            '</span>';
                    }
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="d-flex justify-content-center gap-1">';

                // View detail button
                    // Encode payload sebagai base64
                    $payloadEncoded = !empty($row->Payload) ?
                        base64_encode($row->Payload) :
                        '';

                    $btn .= '
            <button
                class="btn btn-sm btn-info view-detail"
                data-bs-toggle="tooltip"
                title="View Details"
                data-payload-encoded="' . $payloadEncoded . '"
                data-user="' . htmlspecialchars($row->UserID, ENT_QUOTES, 'UTF-8') . '"
                data-module="' . htmlspecialchars($row->FrmName, ENT_QUOTES, 'UTF-8') . '"
                data-action="' . htmlspecialchars($row->Action, ENT_QUOTES, 'UTF-8') . '"
                data-reference="' . htmlspecialchars($row->ReffID, ENT_QUOTES, 'UTF-8') . '"
                data-route="' . htmlspecialchars($row->RoutePath, ENT_QUOTES, 'UTF-8') . '"
                data-method="' . htmlspecialchars($row->Method, ENT_QUOTES, 'UTF-8') . '"
                data-status="' . ($row->ResponseStatus ?? 0) . '"
                data-time="' . ($row->EntryTime ? Carbon::parse($row->EntryTime)->format('d M Y H:i:s') : '-') . '"
            >
                <i class="fa fa-fw fa-eye"></i>
            </button>
        ';

                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['action', 'ResponseStatus', 'method_badge', 'payload_preview'])
            ->make(true);
    }
}
