<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

abstract class ModuleController extends Controller
{
    protected string $module;

    public function index(): View
    {
        return view("cms.{$this->module}.index", $this->viewData(['breadcrumbs' => $this->breadcrumbs()]));
    }

    public function create(): View
    {
        return view("cms.{$this->module}.create", $this->viewData(['breadcrumbs' => $this->breadcrumbs('Thêm mới')]));
    }

    public function edit(string $id): View
    {
        return view("cms.{$this->module}.edit", $this->viewData([
            'recordId' => $id,
            'breadcrumbs' => $this->breadcrumbs('Chỉnh sửa'),
        ]));
    }

    protected function viewData(array $extra = []): array
    {
        return array_merge([
            'module' => $this->module,
            'config' => config("cms.modules.{$this->module}"),
        ], $extra);
    }

    protected function breadcrumbs(?string $current = null): array
    {
        $items = [
            ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
            [
                'label' => config("cms.modules.{$this->module}.title"),
                'url' => $current ? route("cms.{$this->module}.index") : null,
            ],
        ];

        if ($current) {
            $items[] = ['label' => $current, 'url' => null];
        }

        return $items;
    }
}
