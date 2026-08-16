<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Penjualan';
    protected static ?string $modelLabel = 'Pesanan';
    protected static ?string $pluralModelLabel = 'Pesanan';

    // Status pengiriman yang bisa diatur admin
    public const SHIPPING_STATUSES = [
        'not_shipped' => 'Belum dikirim',
        'packed'      => 'Dikemas',
        'shipped'     => 'Dikirim',
        'in_transit'  => 'Dalam perjalanan',
        'delivered'   => 'Diterima',
    ];

    public const ORDER_STATUSES = [
        'pending'    => 'Menunggu pembayaran',
        'paid'       => 'Dibayar',
        'processing' => 'Diproses',
        'completed'  => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi pesanan')
                ->schema([
                    Forms\Components\TextInput::make('order_number')->label('No. pesanan')->disabled(),
                    Forms\Components\TextInput::make('total')->label('Total')->prefix('Rp')->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('Status pesanan')
                        ->options(self::ORDER_STATUSES)
                        ->required(),
                ])->columns(3),

            Forms\Components\Section::make('Pembayaran')
                ->schema([
                    Forms\Components\TextInput::make('payment_method')->label('Metode')->disabled(),
                    Forms\Components\TextInput::make('payment_channel')->label('Channel')->disabled(),
                    Forms\Components\TextInput::make('transaction_code')->label('Kode transaksi')->disabled(),
                    Forms\Components\TextInput::make('va_number')->label('Virtual Account')->disabled(),
                    Forms\Components\DateTimePicker::make('paid_at')->label('Dibayar pada')->disabled(),
                ])->columns(3),

            Forms\Components\Section::make('Pengiriman')
                ->description('Bagian yang diatur admin — status ini yang bisa ditanyakan pelanggan lewat chatbot.')
                ->schema([
                    Forms\Components\Select::make('shipping_status')
                        ->label('Status pengiriman')
                        ->options(self::SHIPPING_STATUSES)
                        ->required(),
                    Forms\Components\TextInput::make('courier')->label('Kurir')->placeholder('JNE, J&T, SiCepat...'),
                    Forms\Components\TextInput::make('shipping_method')->label('Layanan')->disabled(),
                    Forms\Components\TextInput::make('tracking_number')->label('No. resi'),
                ])->columns(3),

            Forms\Components\Section::make('Penerima')
                ->schema([
                    Forms\Components\TextInput::make('recipient_name')->label('Nama penerima')->disabled(),
                    Forms\Components\TextInput::make('recipient_phone')->label('Telepon')->disabled(),
                    Forms\Components\Textarea::make('shipping_address')->label('Alamat')->disabled()->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Item pesanan')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('product_name')->label('Produk')->disabled(),
                            Forms\Components\TextInput::make('size')->label('Ukuran')->disabled(),
                            Forms\Components\TextInput::make('quantity')->label('Qty')->disabled(),
                            Forms\Components\TextInput::make('subtotal')->label('Subtotal')->prefix('Rp')->disabled(),
                        ])
                        ->columns(4)
                        ->deletable(false)
                        ->addable(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('No. pesanan')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Pelanggan')->searchable(),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::ORDER_STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'paid', 'processing' => 'info',
                        default => 'gray',
                    }),
                // Status pengiriman bisa diubah langsung dari daftar tanpa buka detail
                Tables\Columns\SelectColumn::make('shipping_status')
                    ->label('Pengiriman')
                    ->options(self::SHIPPING_STATUSES),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipping_status')
                    ->label('Status pengiriman')
                    ->options(self::SHIPPING_STATUSES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status pesanan')
                    ->options(self::ORDER_STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Detail'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit'  => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}