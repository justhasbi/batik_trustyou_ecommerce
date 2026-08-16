<?php

namespace App\Filament\Resources\ChatSessionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    protected static ?string $title = 'Percakapan';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('message')
                ->label('Balasan admin')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender')
                    ->label('Pengirim')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'customer' => 'Pelanggan',
                        'admin'    => 'Admin',
                        'bot'      => 'Bot',
                        default    => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'customer' => 'info',
                        'admin'    => 'warning',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M H:i'),
            ])
            ->defaultSort('id', 'asc')
            ->headerActions([
                // Tombol "Balas": membuat pesan baru dengan sender=admin otomatis
                Tables\Actions\CreateAction::make()
                    ->label('Balas')
                    ->modalHeading('Kirim balasan')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sender'] = 'admin';
                        return $data;
                    })
                    ->after(function ($livewire) {
                        // Pastikan sesi berstatus aktif & mode admin agar muncul di antrean
                        $livewire->getOwnerRecord()->update(['mode' => 'admin', 'status' => 'active']);
                    }),
            ])
            ->actions([])
            ->paginated([25, 50]);
    }
}
