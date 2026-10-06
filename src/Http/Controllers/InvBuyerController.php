<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\SflInventory\Http\Requests\InvBuyerRequest;
use ME\SflInventory\Models\InvBuyer;
use ME\SflInventory\Services\MerchandisingLink;

class InvBuyerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('inv_buyer.list');

        $link = app(MerchandisingLink::class);
        if ($link->available()) {
            return $this->merchandisingIndex($request, $link);
        }

        $buyers = InvBuyer::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $trashedBuyers = InvBuyer::onlyTrashed()->latest('deleted_at')->get()
            ->map(fn ($buyer) => ['id' => $buyer->id, 'title' => $buyer->name, 'subtitle' => $buyer->code, 'deleted_at' => $buyer->deleted_at]);

        return view('sfl-inventory::admin.buyers.index', compact('buyers', 'trashedBuyers'));
    }

    /**
     * Buyers (and their styles) live in Merchandising: Inventory only shows
     * them. Inventory buyers with no Merchandising match (older records) are
     * listed read-only.
     */
    private function merchandisingIndex(Request $request, MerchandisingLink $link): View
    {
        $search = trim((string) $request->input('search'));
        $buyers = \ME\MerchandisingSfl\Models\Buyer::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $styles = \ME\MerchandisingSfl\Models\Style::query()->whereIn('buyer_id', $buyers->pluck('id'))
            ->orderBy('style_no')->get(['id', 'buyer_id', 'style_no', 'name', 'is_active'])->groupBy('buyer_id');
        $legacyStyles = $link->legacyStyles()->groupBy('buyer_id');

        $merNames = \ME\MerchandisingSfl\Models\Buyer::query()->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();
        $inventoryOnly = InvBuyer::query()->orderBy('name')->get()
            ->reject(fn ($b) => in_array(mb_strtolower(trim($b->name)), $merNames, true))->values();

        return view('sfl-inventory::admin.buyers.merchandising', compact('buyers', 'styles', 'legacyStyles', 'inventoryOnly'));
    }

    /** With Merchandising installed, buyers are added / changed / removed there only. */
    private function refuseWhenMerchandising(): ?RedirectResponse
    {
        return app(MerchandisingLink::class)->available()
            ? back()->with('error', 'Buyers are managed in Merchandising → Master Data → Buyers — Inventory only shows them.')
            : null;
    }

    public function store(InvBuyerRequest $request): RedirectResponse
    {
        if ($refused = $this->refuseWhenMerchandising()) {
            return $refused;
        }
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        InvBuyer::create($data);

        return back()->with('success', 'Buyer created successfully.');
    }

    public function update(InvBuyerRequest $request, InvBuyer $buyer): RedirectResponse
    {
        if ($refused = $this->refuseWhenMerchandising()) {
            return $refused;
        }
        $buyer->update($request->validated());

        return back()->with('success', 'Buyer updated successfully.');
    }

    public function destroy(InvBuyer $buyer): RedirectResponse
    {
        $this->authorize('inv_buyer.delete');
        if ($refused = $this->refuseWhenMerchandising()) {
            return $refused;
        }

        if ($buyer->isReferenced()) {
            return back()->with('error', 'This buyer has related documents and cannot be deleted.');
        }

        $buyer->delete();

        return back()->with('success', 'Buyer deleted successfully.');
    }

    public function restore(InvBuyer $buyer): RedirectResponse
    {
        $this->authorize('inv_buyer.delete');
        if ($refused = $this->refuseWhenMerchandising()) {
            return $refused;
        }

        $buyer->restore();

        return back()->with('success', 'Buyer restored successfully.');
    }

    public function forceDestroy(InvBuyer $buyer): RedirectResponse
    {
        $this->authorize('inv_buyer.force_delete');
        if ($refused = $this->refuseWhenMerchandising()) {
            return $refused;
        }

        $buyer->forceDelete();

        return back()->with('success', 'Buyer permanently deleted.');
    }
}
