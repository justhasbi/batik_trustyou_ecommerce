<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatSessionResource\Pages;
use App\Filament\Resources\ChatSessionResource\RelationManagers\MessagesRelationManager;
use App\Models\ChatSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChatSessionResource extends Resource
{
    protected static ?string $model = ChatSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Layanan';
    protected static ?string $modelLabel = 'Percakapan';
    protected static ?string $pluralModelLabel = 'Percakapan';

    // Tampilkan jumlah percakapan mode admin yang masih aktif sebagai badge menu
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('mode', 'admin')->where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('display_user')
                ->label('Pelanggan')
                ->formatStateUsing(fn ($record) => $record?->user?->name ?? 'Tamu')
                ->disabled(),
            Forms\Components\Select::make('mode')
                ->label('Mode')
                ->options(['bot' => 'Bot', 'admin' => 'Admin'])
                ->disabled(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['active' => 'Aktif', 'closed' => 'Selesai'])
                ->required(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->default('Tamu')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state) => $state === 'admin' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Pesan'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktivitas terakhir')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mode')
                    ->label('Mode')
                    ->options(['bot' => 'Bot', 'admin' => 'Admin']),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'closed' => 'Selesai']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Buka'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [MessagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatSessions::route('/'),
            'edit'  => Pages\EditChatSession::route('/{record}/edit'),
        ];
    }
}