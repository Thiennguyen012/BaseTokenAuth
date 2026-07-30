<?php

namespace Tests\Feature;

use App\Models\PageContents\PageContent;
use App\Models\PageSections\PageSection;
use App\Models\SectionItems\SectionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageContentStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_sections_and_items_are_returned_in_sort_order(): void
    {
        $page = PageContent::query()->create(['slug' => 'home', 'title' => 'Trang chủ']);
        $secondSection = $page->sections()->create(['title' => 'Sản phẩm', 'sort_order' => 20]);
        $firstSection = $page->sections()->create(['title' => 'Banner', 'sort_order' => 10]);
        $secondItem = $firstSection->items()->create(['title' => 'Slide 2', 'sort_order' => 20]);
        $firstItem = $firstSection->items()->create(['title' => 'Slide 1', 'sort_order' => 10]);

        $page = $page->fresh()->load('sections.items');

        $this->assertSame([$firstSection->id, $secondSection->id], $page->sections->pluck('id')->all());
        $this->assertSame([$firstItem->id, $secondItem->id], $page->sections->first()->items->pluck('id')->all());
    }

    public function test_sections_and_items_support_morph_files_and_video_urls(): void
    {
        Storage::fake('public');
        $page = PageContent::query()->create(['slug' => 'home', 'title' => 'Trang chủ']);
        $section = $page->sections()->create([
            'title' => 'Video giới thiệu',
            'video_url' => 'https://example.com/video',
        ]);
        $item = $section->items()->create([
            'title' => 'Sản phẩm nổi bật',
            'video_url' => 'https://example.com/item-video',
        ]);
        $sectionFile = $section->files()->create([
            'title' => 'Banner',
            'disk' => 'public',
            'path' => 'pages/banner.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'type' => 'image',
        ]);
        $itemFile = $item->files()->create([
            'title' => 'Item image',
            'disk' => 'public',
            'path' => 'pages/item.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 512,
            'type' => 'image',
        ]);

        $this->assertInstanceOf(PageSection::class, $sectionFile->model);
        $this->assertInstanceOf(SectionItem::class, $itemFile->model);
        $this->assertSame($section->id, $sectionFile->model_id);
        $this->assertSame($item->id, $itemFile->model_id);
    }

    public function test_deleting_a_page_removes_sections_items_and_file_records(): void
    {
        Storage::fake('public');
        $page = PageContent::query()->create(['slug' => 'home', 'title' => 'Trang chủ']);
        $section = $page->sections()->create(['title' => 'Banner']);
        $item = $section->items()->create(['title' => 'Slide']);
        $file = $item->files()->create([
            'title' => 'Slide image',
            'disk' => 'public',
            'path' => 'pages/slide.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 512,
        ]);

        $page->delete();

        $this->assertDatabaseMissing('page_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('section_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}
