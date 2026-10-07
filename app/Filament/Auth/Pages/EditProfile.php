<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    protected function getAvatarFormComponent(): Component
    {
        return FileUpload::make('avatar')
            ->label('Foto profil')
            ->image()
            ->disk('public')
            ->directory('avatars')
            ->visibility('public')
            ->maxSize(2048)
            ->imageEditor();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getAvatarFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        $oldAvatar = $record->getOriginal('avatar');

        $record = parent::handleRecordUpdate($record, $data);

        if ($oldAvatar && $oldAvatar !== $record->getAttribute('avatar')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $record;
    }
}
