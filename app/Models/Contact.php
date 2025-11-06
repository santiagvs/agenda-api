<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','name','phone','email','photo'];

    protected $hidden = ['created_at','updated_at'];

    protected $appends = ['photo_url'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        return url(Storage::url($this->photo));
    }
}
