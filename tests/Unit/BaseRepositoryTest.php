<?php

namespace Tests\Unit;

use App\Repositories\Base\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        DB::purge('sqlite');

        Schema::create('test_models', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');

        parent::tearDown();
    }

    public function test_query_builds_shared_query_with_conditions_and_selects(): void
    {
        TestModel::create(['name' => 'Alice']);
        TestModel::create(['name' => 'Bob']);

        $repository = new TestRepository();
        $query = $repository->query([
            'name' => ['name', 'like', 'Ali'],
        ], [], ['id', 'name']);

        $this->assertSame(1, $query->count());
        $this->assertSame('Alice', $query->first()->name);
    }
}

class TestModel extends Model
{
    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = true;
}

class TestRepository extends BaseRepository
{
    public function model(): string
    {
        return TestModel::class;
    }
}
