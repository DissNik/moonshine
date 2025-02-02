<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Tests\Fixtures\Models\Item;
use MoonShine\Tests\Fixtures\Resources\TestItemResource;
use MoonShine\Tests\Fixtures\Resources\TestResource;
use MoonShine\Tests\Fixtures\Resources\TestResourceBuilder;
use MoonShine\UI\Fields\File;

uses()->group('fields');
uses()->group('file-field');

beforeEach(function () {
    $this->item = createItem(countComments: 0);
    $this->field = File::make('Files')
        ->multiple()
        ->dir('items');
});

it('show field on pages', function () {
    $resource = multipleFileResource()->setTestFields([
        $this->field,
    ]);

    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: IndexPage::class, resource: $resource)
    )
        ->assertOk()
        ->assertSee('Files')
    ;

    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: DetailPage::class, resource: $resource, params: ['resourceItem' => $this->item->getKey()])
    )
        ->assertOk()
        ->assertSee('Files')
    ;

    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: FormPage::class, resource: $resource, params: ['resourceItem' => $this->item->getKey()])
    )
        ->assertOk()
        ->assertSee('Files')
    ;
});

it('apply as base', function () {
    $resource = multipleFileResource()->setTestFields([
        $this->field,
    ]);
    ;

    $files = saveMultipleFiles($resource, $this->item);


    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: IndexPage::class, resource: $resource)
    )
        ->assertOk()
        ->assertSee('File')
        ->assertSee('items/' . $files[0]->hashName())
        ->assertSee('items/' . $files[1]->hashName())
    ;

    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: DetailPage::class, resource: $resource, params: ['resourceItem' => $this->item->getKey()])
    )
        ->assertOk()
        ->assertSee('items/' . $files[0]->hashName())
        ->assertSee('items/' . $files[1]->hashName())
    ;

    asAdmin()->get(
        $this->moonshineCore->getRouter()->getEndpoints()->toPage(page: FormPage::class, resource: $resource, params: ['resourceItem' => $this->item->getKey()])
    )
        ->assertOk()
        ->assertSee('items/' . $files[0]->hashName())
        ->assertSee('items/' . $files[1]->hashName())
    ;

});

it('before apply', function () {
    $resource = multipleFileResource();

    $files = saveMultipleFiles($resource, $this->item);

    $file3 = UploadedFile::fake()->create('test3.csv');

    $data = ['files' => [$file3], 'hidden_files' => ['items/' . $files[0]->hashName(), 'items/' . $files[1]->hashName()]];

    asAdmin()->put(
        $resource->getRoute('crud.update', $this->item->getKey()),
        $data
    )
        ->assertRedirect();

    $this->item->refresh();

    expect($this->item->files->toArray())
        ->toBe([
            'items/' . $files[0]->hashName(),
            'items/' . $files[1]->hashName(),
            'items/' . $file3->hashName(),
        ])
    ;

    Storage::disk('public')->assertExists('items/' . $files[0]->hashName());

    Storage::disk('public')->assertExists('items/' . $files[1]->hashName());

    Storage::disk('public')->assertExists('items/' . $file3->hashName());
});

it('after destroy', function () {
    $resource = multipleFileResource();

    $files = saveMultipleFiles($resource, $this->item);

    asAdmin()->delete(
        $resource->getRoute('crud.destroy', $this->item->getKey()),
    )
        ->assertRedirect();

    Storage::disk('public')->assertMissing('items/' . $files[0]->hashName());

    Storage::disk('public')->assertMissing('items/' . $files[1]->hashName());
});

it('after destroy disableDeleteFiles', function () {
    $resource = TestResourceBuilder::new(Item::class)
        ->setTestFields([
            ...app(TestItemResource::class)->getFormFields()->toArray(),
            File::make('Files')
                ->multiple()
                ->disableDeleteFiles()
                ->dir('items'),
        ]);

    $files = saveMultipleFiles($resource, $this->item);

    asAdmin()->delete(
        $resource->getRoute('crud.destroy', $this->item->getKey()),
    )
        ->assertRedirect();

    Storage::disk('public')->assertExists('items/' . $files[0]->hashName());

    Storage::disk('public')->assertExists('items/' . $files[1]->hashName());
});

function multipleFileResource(): TestResource
{
    return addFieldsToTestResource(
        File::make('Files')
            ->multiple()
            ->dir('items')
    );
}

function saveMultipleFiles(ModelResource $resource, Model $item): array
{
    $file1 = UploadedFile::fake()->create('test1.csv');
    $file2 = UploadedFile::fake()->create('test2.csv');

    $data = ['files' => [$file1, $file2]];

    asAdmin()->put(
        $resource->getRoute('crud.update', $item->getKey()),
        $data
    )
        ->assertRedirect();

    $item->refresh();

    expect($item->files->toArray())
        ->toBe(['items/' . $file1->hashName(), 'items/' . $file2->hashName()])
    ;

    Storage::disk('public')->assertExists('items/' . $file1->hashName());
    Storage::disk('public')->assertExists('items/' . $file2->hashName());

    return [$file1, $file2];
}
