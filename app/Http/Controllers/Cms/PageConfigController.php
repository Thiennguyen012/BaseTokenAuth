<?php

namespace App\Http\Controllers\Cms;

use App\Models\PageConfigs\PageConfig;
use Illuminate\View\View;

class PageConfigController extends ModuleController
{
    protected string $module = 'page-configs';

    public function index(): View
    {
        $config = PageConfig::query()->firstOrCreate([], ['company_name' => '']);

        return view('cms.page-configs.index', $this->viewData([
            'recordId' => $config->id,
            'breadcrumbs' => $this->breadcrumbs(),
        ]));
    }
}
