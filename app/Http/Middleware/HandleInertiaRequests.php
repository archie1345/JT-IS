<?php

namespace App\Http\Middleware;

use App\Support\AccessControl;
use App\Services\Ocr\OcrService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->getRoleNames()->values()->all() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name')->values()->all() : [],
            ],
            'navigation' => [
                'sidebarSections' => AccessControl::sidebarSections(),
                'footerItems' => AccessControl::footerItems(),
            ],
            'features' => [
                'ocr' => [
                    'configured' => app(OcrService::class)->configured(),
                    'unavailableMessage' => 'OCR belum aktif. Dokumen tetap bisa diunggah, lanjutkan input manual.',
                ],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
