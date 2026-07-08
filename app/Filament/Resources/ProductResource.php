<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Setting Toko';
    protected static ?string $modelLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Produk';
 
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi produk')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama produk')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                            $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\TextInput::make('slug')->required(),
                        ]),
                    Forms\Components\TextInput::make('price')
                        ->label('Harga')
                        ->required()
                        ->numeric()
                        ->prefix('Rp'),
                    Forms\Components\Select::make('fabric_type')
                        ->label('Jenis kain')
                        ->options([
                            'tulis' => 'Batik Tulis',
                            'cap'   => 'Batik Cap',
                            'print' => 'Batik Print',
                        ]),
                    Forms\Components\TextInput::make('motif')->label('Motif'),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif (tampil di toko)')
                        ->default(true),
                ])->columns(2),
 
            Forms\Components\Section::make('Gambar produk')
                ->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label('Gambar')
                                ->image()
                                ->directory('products')
                                ->required(),
                            Forms\Components\Toggle::make('is_primary')
                                ->label('Gambar utama'),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah gambar'),
                ]),
 
            Forms\Components\Section::make('Ukuran & stok')
                ->description('Atur stok untuk tiap ukuran. Rentang tinggi/berat dipakai oleh chatbot untuk rekomendasi ukuran.')
                ->schema([
                    Forms\Components\Repeater::make('sizes')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('size')
                                ->label('Ukuran')
                                ->placeholder('S / M / L / XL')
                                ->required(),
                            Forms\Components\TextInput::make('stock')
                                ->label('Stok')
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Forms\Components\TextInput::make('min_height')->label('Tinggi min (cm)')->numeric(),
                            Forms\Components\TextInput::make('max_height')->label('Tinggi max (cm)')->numeric(),
                            Forms\Components\TextInput::make('min_weight')->label('Berat min (kg)')->numeric(),
                            Forms\Components\TextInput::make('max_weight')->label('Berat max (kg)')->numeric(),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah ukuran'),
                ]),
        ]);
    }
 
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('primaryImage.path')->label('Gambar'),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->sortable(),
                Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('sizes_stock')
                    ->label('Total stok')
                    ->getStateUsing(fn (Product $record) => $record->sizes->sum('stock')),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Status aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
 
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
