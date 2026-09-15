<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Repositories\WebsiteSetup\AiHelperRepository;

class AiHelperController extends Controller
{
    private $repo;

    public function __construct(AiHelperRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $data['title'] = ___('settings.ai_helper');
        return view('website-setup.ai-helper.index', compact('data'));
    }

    public function update(Request $request)
    {
        $result = $this->repo->updateSettings($request);
        if ($result) {
            return redirect()->back()->with('success', ___('alert.updated_successfully'));
        }
        return redirect()->back()->with('danger', ___('alert.something_went_wrong_please_try_again'));
    }

    // Sends the admin to Google's own consent screen. access_type=offline +
    // prompt=consent are both required to actually get a refresh_token back
    // (without them Google only hands back a short-lived access token).
    public function driveConnect()
    {
        $clientId = trim((string) setting('ai_helper_drive_client_id'));
        if (!$clientId) {
            return redirect()->back()->with('danger', 'Add the Google Drive Client ID and Client Secret first, then save, before connecting.');
        }

        $params = http_build_query([
            'client_id'              => $clientId,
            'redirect_uri'           => route('ai-helper.drive-callback'),
            'response_type'          => 'code',
            'access_type'            => 'offline',
            'prompt'                 => 'consent',
            'scope'                  => 'https://www.googleapis.com/auth/drive',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function driveCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('ai-helper.index')->with('danger', 'Google Drive connection was not completed: ' . $request->input('error'));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('ai-helper.index')->with('danger', 'Google did not return an authorization code. Please try connecting again.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => trim((string) setting('ai_helper_drive_client_id')),
            'client_secret' => trim((string) setting('ai_helper_drive_client_secret')),
            'redirect_uri'  => route('ai-helper.drive-callback'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful() || !$response->json('refresh_token')) {
            return redirect()->route('ai-helper.index')->with('danger', 'Google Drive did not return a long-term access token. Try disconnecting any previous access at myaccount.google.com/permissions and connecting again.');
        }

        $this->repo->setSetting('ai_helper_drive_refresh_token', $response->json('refresh_token'));

        return redirect()->route('ai-helper.index')->with('success', 'Google Drive connected successfully.');
    }

    public function driveDisconnect()
    {
        $this->repo->setSetting('ai_helper_drive_refresh_token', null);
        return redirect()->route('ai-helper.index')->with('success', 'Google Drive disconnected.');
    }

    public function logs()
    {
        $data['title'] = 'AI Helper Usage Log';
        $data['logs']  = \App\Models\WebsiteSetup\AiHelperLog::latest()->paginate(30);
        return view('website-setup.ai-helper.logs', compact('data'));
    }

    public function bulkDeleteLogs(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $deleted = $this->repo->bulkDestroyLogs($ids);
        if ($deleted === 0) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }
}
