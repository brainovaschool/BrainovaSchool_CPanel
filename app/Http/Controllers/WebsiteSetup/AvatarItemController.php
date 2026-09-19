<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\LearningEngine\AvatarItem;
use App\Repositories\WebsiteSetup\AvatarItemRepository;

class AvatarItemController extends Controller
{
    private $repo;

    public function __construct(AvatarItemRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $category = array_key_exists($request->get('category'), AvatarItem::CATEGORIES)
            ? $request->get('category')
            : 'avatar';

        $data['category'] = $category;
        $data['items']    = $this->repo->getByCategory($category);
        $data['title']    = ___('settings.avatar_gallery');

        return view('website-setup.avatar-item.index', compact('data'));
    }

    public function create(Request $request)
    {
        $data['category'] = array_key_exists($request->get('category'), AvatarItem::CATEGORIES) ? $request->get('category') : 'avatar';
        $data['bodies']   = AvatarItem::active()->category('avatar')->orderBy('sort_order')->get();
        $data['title']    = ___('settings.add_avatar_item');
        return view('website-setup.avatar-item.create', compact('data'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category'    => 'required|in:' . implode(',', array_keys(AvatarItem::CATEGORIES)),
            'name'        => 'required|string|max:60',
            'price_coins' => 'nullable|integer|min:0',
            'image'       => 'required|image|max:2048',
            'pos_x'       => 'nullable|numeric',
            'pos_y'       => 'nullable|numeric',
            'scale'       => 'nullable|numeric',
            'rotation'    => 'nullable|integer',
        ]);

        $result = $this->repo->store($request);
        if ($result['status']) {
            return redirect()->route('avatar-item.index', ['category' => $request->category])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'category'    => 'required|in:' . implode(',', array_keys(AvatarItem::CATEGORIES)),
            'price_coins' => 'nullable|integer|min:0',
            'images'      => 'required|array',
            'images.*'    => 'image|max:2048',
        ]);

        $result = $this->repo->bulkStore($request);

        return redirect()->route('avatar-item.index', ['category' => $request->category])
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function edit($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('avatar-item.index')->with('danger', ___('alert.not_found'));
        }
        $data['bodies'] = AvatarItem::active()->category('avatar')->orderBy('sort_order')->get();
        $data['title']  = ___('settings.edit_avatar_item');
        return view('website-setup.avatar-item.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category'    => 'required|in:' . implode(',', array_keys(AvatarItem::CATEGORIES)),
            'name'        => 'required|string|max:60',
            'price_coins' => 'nullable|integer|min:0',
            'image'       => 'nullable|image|max:2048',
            'pos_x'       => 'nullable|numeric',
            'pos_y'       => 'nullable|numeric',
            'scale'       => 'nullable|numeric',
            'rotation'    => 'nullable|integer',
        ]);

        $result = $this->repo->update($request, $id);
        if ($result['status']) {
            return redirect()->route('avatar-item.index', ['category' => $request->category])->with('success', $result['message']);
        }
        return back()->withInput()->with('danger', $result['message']);
    }

    public function delete($id)
    {
        $result = $this->repo->destroy($id);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
