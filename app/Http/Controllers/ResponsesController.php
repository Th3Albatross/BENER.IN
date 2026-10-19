<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponsesController
{
    public function show(Request $request, string $uuid)
    {
        $report = Report::where('identifier', $uuid)
            ->join('users', 'reports.users_id', '=', 'users.id')
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->select(
                'reports.*',
                'users.name as user_name',
                'facilities.name as facilities_name',
                'categories.name as categories_name'
            )
            ->firstOrFail();

        $user = $request->user();
        $isStaff = in_array(strtolower((string) $user->role), ['petugas', 'admin'], true);
        abort_unless((int) $report->users_id === (int) $user->id || $isStaff, 403);

        $responses = DB::table('responses')
            ->join('users', 'responses.users_id', '=', 'users.id')
            ->where('responses.reports_id', $report->id)
            ->select('responses.*', 'users.name as user_name', 'users.role as user_role')
            ->orderBy('responses.created_at')
            ->orderBy('responses.id')
            ->get();

        return view('discussion.show', [
            '_report' => $report,
            '_responses' => $responses,
        ]);
    }

    public function store(Request $request, string $uuid)
    {
        $report = Report::where('identifier', $uuid)->firstOrFail();
        $user = $request->user();
        $isStaff = in_array(strtolower((string) $user->role), ['petugas', 'admin'], true);
        abort_unless((int) $report->users_id === (int) $user->id || $isStaff, 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::table('responses')->insert([
            'users_id' => $user->id,
            'reports_id' => $report->id,
            'message' => $validated['message'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('responses.show', ['uuid' => $report->identifier])
            ->with('success', 'Pesan berhasil dikirim.');
    }

    public function update(Request $request, string $uuid, int $responseId)
    {
        $report = Report::where('identifier', $uuid)->firstOrFail();
        $user = $request->user();
        $isStaff = in_array(strtolower((string) $user->role), ['petugas', 'admin'], true);
        abort_unless((int) $report->users_id === (int) $user->id || $isStaff, 403);

        $response = DB::table('responses')
            ->where('id', $responseId)
            ->where('reports_id', $report->id)
            ->where('users_id', $user->id)
            ->first();

        abort_unless($response, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::table('responses')
            ->where('id', $responseId)
            ->where('reports_id', $report->id)
            ->where('users_id', $user->id)
            ->update([
                'message' => $validated['message'],
                'updated_at' => now(),
            ]);

        return redirect()->route('responses.show', ['uuid' => $report->identifier])
            ->with('success', 'Pesan berhasil diperbarui.');
    }
}
