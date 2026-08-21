<?php

namespace App\Http\Controllers\Cms;

use App\Models\PageContents\PageContent;
use App\Models\PageSections\PageSection;
use Illuminate\View\View;

class PageSectionController extends ModuleController
{
    protected string $module = 'page-sections';

    public function forPage(string $page): View
    {
        $pageContent = $this->findPage($page);
        $config = config('cms.modules.page-sections');
        $config['title'] = 'Section của '.$pageContent->title;
        $config['description'] = 'Quản lý các section thuộc trang này.';

        return view('cms.page-sections.index', [
            'module' => $this->module,
            'config' => $config,
            'pageContent' => $pageContent,
            'fixedParams' => ['page_content_id' => $pageContent->id],
            'createUrl' => route('cms.page-contents.sections.create', ['page' => $pageContent->slug]),
            'backUrl' => route('cms.page-contents.index'),
            'backLabel' => 'Quay lại quản lý nội dung',
            'breadcrumbs' => $this->pageBreadcrumbs($pageContent),
        ]);
    }

    public function redirectLegacy(string $page)
    {
        $pageContent = $this->findPage($page);

        return redirect()->route('cms.page-contents.sections.index', ['page' => $pageContent->slug]);
    }

    public function create(): View
    {
        $pageId = request()->integer('page_content_id');
        $page = $pageId ? PageContent::query()->findOrFail($pageId) : null;

        return view('cms.page-sections.create', $this->viewData([
            'hidePageRelation' => (bool) $page,
            'indexUrl' => $page ? route('cms.page-contents.sections.index', $page) : route('cms.page-sections.index'),
            'breadcrumbs' => $page ? $this->pageBreadcrumbs($page, 'Thêm mới') : $this->breadcrumbs('Thêm mới'),
        ]));
    }

    public function createForPage(string $page): View
    {
        return $this->sectionForm($this->findPage($page));
    }

    public function edit(string $id): View
    {
        $section = PageSection::query()->with('page')->findOrFail($id);

        return view('cms.page-sections.edit', $this->viewData([
            'recordId' => $id,
            'hidePageRelation' => true,
            'indexUrl' => route('cms.page-contents.sections.index', $section->page_content_id),
            'breadcrumbs' => $this->pageBreadcrumbs($section->page, 'Chỉnh sửa'),
        ]));
    }

    public function editForPage(string $page, string $section): View
    {
        $pageContent = $this->findPage($page);
        $sectionModel = PageSection::query()
            ->where('page_content_id', $pageContent->id)
            ->findOrFail($section);

        return view('cms.page-sections.edit', $this->viewData([
            'recordId' => $sectionModel->id,
            'hidePageRelation' => true,
            'indexUrl' => route('cms.page-contents.sections.index', ['page' => $pageContent->slug]),
            'breadcrumbs' => $this->pageBreadcrumbs($pageContent, 'Chỉnh sửa'),
        ]));
    }

    private function pageBreadcrumbs(PageContent $page, ?string $current = null): array
    {
        $items = [
            ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
            ['label' => 'Quản lý nội dung', 'url' => route('cms.page-contents.index')],
            ['label' => $page->title, 'url' => $current ? route('cms.page-contents.sections.index', ['page' => $page->slug]) : null],
        ];

        if ($current) $items[] = ['label' => $current, 'url' => null];

        return $items;
    }

    private function sectionForm(PageContent $page): View
    {
        return view('cms.page-sections.create', $this->viewData([
            'hidePageRelation' => true,
            'presetPageContentId' => $page->id,
            'indexUrl' => route('cms.page-contents.sections.index', ['page' => $page->slug]),
            'breadcrumbs' => $this->pageBreadcrumbs($page, 'Thêm mới'),
        ]));
    }

    private function findPage(string $page): PageContent
    {
        return PageContent::query()
            ->where(fn ($query) => $query->where('slug', $page)->when(is_numeric($page), fn ($query) => $query->orWhereKey($page)))
            ->firstOrFail();
    }
}
