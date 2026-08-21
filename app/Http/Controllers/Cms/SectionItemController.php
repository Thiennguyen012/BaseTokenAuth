<?php

namespace App\Http\Controllers\Cms;

use App\Models\PageSections\PageSection;
use App\Models\PageContents\PageContent;
use App\Models\SectionItems\SectionItem;
use Illuminate\View\View;

class SectionItemController extends ModuleController
{
    protected string $module = 'section-items';

    public function create(): View
    {
        $sectionId = request()->integer('page_section_id');
        $section = $sectionId ? PageSection::query()->with('page')->findOrFail($sectionId) : null;

        return view('cms.section-items.create', $this->viewData([
            'hideSectionRelation' => (bool) $section,
            'indexUrl' => $section
                ? route('cms.page-contents.sections.index', $section->page_content_id).'#section-'.$section->id
                : route('cms.section-items.index'),
            'breadcrumbs' => $section ? $this->itemBreadcrumbs($section, 'Thêm item') : $this->breadcrumbs('Thêm mới'),
        ]));
    }

    public function edit(string $id): View
    {
        $item = SectionItem::query()->with('section.page')->find($id);
        if (! $item) {
            return view('cms.section-items.edit', $this->viewData([
                'recordId' => $id,
                'breadcrumbs' => $this->breadcrumbs('Chỉnh sửa'),
            ]));
        }
        $section = $item->section;

        return view('cms.section-items.edit', $this->viewData([
            'recordId' => $id,
            'hideSectionRelation' => true,
            'indexUrl' => route('cms.page-contents.sections.index', $section->page_content_id).'#section-'.$section->id,
            'breadcrumbs' => $this->itemBreadcrumbs($section, 'Chỉnh sửa item'),
        ]));
    }

    public function createForSection(string $page, string $section): View
    {
        [$pageContent, $sectionModel] = $this->resolveContext($page, $section);

        return view('cms.section-items.create', $this->viewData([
            'hideSectionRelation' => true,
            'presetPageSectionId' => $sectionModel->id,
            'indexUrl' => route('cms.page-contents.sections.index', ['page' => $pageContent->slug]).'#section-'.$sectionModel->id,
            'breadcrumbs' => $this->itemBreadcrumbs($sectionModel, 'Thêm item'),
        ]));
    }

    public function editForSection(string $page, string $section, string $item): View
    {
        [$pageContent, $sectionModel] = $this->resolveContext($page, $section);
        $itemModel = SectionItem::query()->where('page_section_id', $sectionModel->id)->findOrFail($item);

        return view('cms.section-items.edit', $this->viewData([
            'recordId' => $itemModel->id,
            'hideSectionRelation' => true,
            'indexUrl' => route('cms.page-contents.sections.index', ['page' => $pageContent->slug]).'#section-'.$sectionModel->id,
            'breadcrumbs' => $this->itemBreadcrumbs($sectionModel, 'Chỉnh sửa item'),
        ]));
    }

    private function itemBreadcrumbs(PageSection $section, string $current): array
    {
        return [
            ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
            ['label' => 'Quản lý nội dung', 'url' => route('cms.page-contents.index')],
            ['label' => $section->page->title, 'url' => route('cms.page-contents.sections.index', ['page' => $section->page->slug])],
            ['label' => $section->title ?: 'Section #'.$section->id, 'url' => route('cms.page-contents.sections.index', ['page' => $section->page->slug]).'#section-'.$section->id],
            ['label' => $current, 'url' => null],
        ];
    }

    private function resolveContext(string $page, string $section): array
    {
        $pageContent = PageContent::query()
            ->where(fn ($query) => $query->where('slug', $page)->when(is_numeric($page), fn ($query) => $query->orWhereKey($page)))
            ->firstOrFail();
        $sectionModel = PageSection::query()
            ->with('page')
            ->where('page_content_id', $pageContent->id)
            ->findOrFail($section);

        return [$pageContent, $sectionModel];
    }
}
